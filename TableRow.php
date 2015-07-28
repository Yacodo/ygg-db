<?php

namespace ygg\Db;

class TableRow extends Row {

	protected $_table;

	protected $_datasClean;
	protected $_datas;

	protected $_mounted;

	/**
	 * Constructor
	 *
	 * @param Table $table Table for Row
	 **/
	public function __construct(Table $table, array $datas = array()){

		$this->_table = $table;

		$this->mountDatas($datas);

	}

	/**
	 * Return current TableRow table
	 *
	 * @return Table
	**/
	public function getTable(){
		
		return $this->_table;

	}

	private function mountDatas(array $datas){

		$this->_datas = array();
		$this->_mounted = array();

		if(!empty($this->_table->getTableMounts())){

			$prefix_datas = array();

			$save_identifiers = array();

			$datas = array_filter(
				$datas,
				function($value, $key) use(&$prefix_datas, &$save_identifier){

					foreach($this->_table->getTableMounts() AS $prefix => $table){
						$position = strpos($key, '_');

						if(
							(\is_array($value) AND $key == $prefix)
							OR
							($position AND mb_strlen($key) >= $position + 1 AND substr($key, 0, $position) == $prefix)
						){

							if(!isset($prefix_datas[$prefix]))
								$prefix_datas[$prefix] = array();



							//For one by one value ($prefix_$key = $value)
							if($position){
								$key = substr($key, $position + 1);
								$prefix_datas[$prefix][$key] = $value;

								//Save identifier
								if($key == $table->getTableIdentifier()){
									return true;
								}


							//For values container ($prefix => [$values...])
							}else{
								$prefix_datas[$prefix] = $value;

								//Save identifier for current row object ($prefix_$identifier)
								if(isset($prefix_datas[$prefix][$table->getTableIdentifier()])){
									$save_identifiers[$prefix . '_' . $table->getTableIdentifier()] = $prefix_datas[$prefix][$table->getTableIdentifier()];	
								}

							}

							return false;

						}

					}

					return true;

				},
				ARRAY_FILTER_USE_BOTH
			);

			//Restore FKs identifier 
			$datas = array_merge($datas, $save_identifiers);

			foreach($prefix_datas AS $prefix => $values){
				$this->_mounted[$prefix] = $this->_table->getTableMounts()[$prefix]
					->createRow($values);
			}

		}

		$this->_datasClean = $datas;

	}

	protected function getMountedPrefix($prefix){

		if(isset($this->_table->getTableMounts()[$prefix])){

			//Checking if mounted prefix exists
			if(!isset($this->_mounted[$prefix])){
				$this->_mounted[$prefix] = $this->_table->getTableMounts()[$prefix]->createRow();
			}

			return $this->_mounted[$prefix];

		}

		return null;

	}

	/**
	 * Add data
	 *
	 * @param string $name Data name
	 * @param mixed $value Data value
	**/
	public function setData($name, $value){
		$value = $this->_table->filterData($name, $value);

		$this->_datas[$name] = $value;
	}

	/**
	 * Magic setter
	 *
	 * @param string $name Column name
	 * @param mixed $value Column value
	 **/
	public function __set($name, $value){

		$position = strcspn($name, '._');

		//Checking if position is not the last char
		if(
			($position AND mb_strlen($name) >= $position + 1)
			OR
			\is_array($value)
		){

			//Get the prefix
			$prefix = substr($name, 0, $position);

			$mounted = $this->getMountedPrefix($prefix);

			if($mounted){
				$t_id = $mounted->_table->getTableIdentifier();

				if(\is_array($value)){
					$mounted->fromArray($value);
					
				}else{
					$key = substr($name, $position + 1);
					$mounted->__set($key, $value);
				}

				$this->reloadIDMounted($mounted, $prefix);

				return;
			}

		}

		$this->setData($name, $value);

	}

	/**
	 * Magic getter
	 *
	 * @param string $name Column name
	 * @return mixed False if column not found
	 **/
	public function __get($name){

		if(isset($this->_datas[$name])){

			$value = $this->_datas[$name];

		}else{

			if(isset($this->_datasClean[$name])){

				$value = $this->_datasClean[$name];

			}elseif(isset($this->_mounted[$name])){

				$value = $this->_mounted[$name];

			}else{

				if($this->reloadable()){
					$this->reload();
					return $this->__get($name);
				}
				
				return null;

			}

		}

		return $value;

	}

	/**
	 * @alias __set()
	 **/
	public function set($name, $value){

		$this->__set($name, $value);

	}

	/**
	 * @alias __get()
	 **/
	public function get($name){

		return $this->__get($name);

	}

	protected function reloadIDMounted(TableRow $mounted, $prefix){
		$t_id = $mounted->_table->getTableIdentifier();
		$id = $mounted->__get($t_id);

		$this->setData($prefix . '_' . $t_id, $id);
	}

	/**
	 * Verify if current row can be saved
	**/ 
	public function saveable(array &$datas = null){

		if(!$datas)
			$datas = $this->_datas;
		
		return count($this->_table->clearDatasColumns($datas)) > 0;


	}
	
	/**
	 * Save row
	 * Insert when datasClean is null
	 * Update when datasClean is array
	 *
	 * @return Row
	 **/
	public function save($force_id = 0){

		foreach($this->_mounted AS $prefix => $mounted){
			$mounted->save();
			$this->reloadIDMounted($mounted, $prefix);
		}

		$datas = $this->_datas;

		//Check if new datas added
		$saveable = $this->saveable($datas); 

		//check for Update
		if(count($this->_datasClean)){ 

			if($saveable){

				//By identifier
				if(($id = $this->_table->getTableIdentifier())){

					if($force_id > 0 OR isset($this->_datasClean[$id])){

						$conditions = array(
							$id => ($force_id > 0)
								? $force_id
								: $this->_datasClean[$id]
						);

					}

				}

				if(!isset($conditions)){ //By current row datas (if none assigned)
					$conditions = $this->_datasClean;
				}

				return $this->_table->update(
					$datas, 
					$conditions
				);

			}

		}elseif($saveable){ //check for Insert

			$this->_table->insert(
				$datas,
				$id
			);
			
			$t_id = $this->_table->getTableIdentifier();

			//Assign ID after create
			if($t_id){
				unset($this->_datas[$t_id]);
				$this->_datasClean[$t_id] = $id;
			}

			return $id;

		}

		//Nothing for save(), no datas ($_datas) for update or insert
		return false;

	}

	public function reloadable(){
		if($this->_table->isTableLazyLoad() AND !empty($this->_table->getTableIdentifier())){

			return (
				count($this->_datasClean) == 1
				AND
				isset($this->_datasClean[$this->_table->getTableIdentifier()])
			);

		}

		return false;

	}

	/**
	 * Reload using table id
	 *
	 * @param boolean $mounted True to reload mounted in the process (happen after reloading current item
	**/
	public function reload($mounted = false){

		$table_id = $this->_table->getTableIdentifier();

		if($table_id){
			
			$id = $this->__get($table_id);

			if($id){

				$result = $this->_table->getById($id);

				if($result){

					$this->clean();

					//HARD COPY... TODO Find an elegant way ??
					$this->_datasClean = $result->_datasClean;
					$this->_datas = $result->_datas;
					$this->_mounted = $result->_mounted;

					unset($result);
				
				}

			}

		}

		if($mounted)
			$this->reloadMounted();

		return $this;

	}

	/**
	 * Reload mounted
	**/
	public function reloadMounted(){
		foreach($this->_mounted AS $mounted)
			$mounted->reload(true);

		return $this;
	}

	/**
	 * Clean Row
	 *
	 * @return Row
	**/
	public function clean(){

		$this->_datasClean = array();
		$this->_datas = array();
		$this->_mounted = array();

		return $this;

	}

	/**
	 * Set values from an array
	 * 
	 * @param array $values Values
	 * @return Row
	**/
	public function fromArray(array $values){

		foreach($values AS $key => $value){

			$this->__set($key, $value);

		}

		return $this;
	
	}

	/**
	 * Convert current datas to an Array
	 *
	 * @param bool $default Return only original row datas on true
	 * @return array
	**/
	public function toArray($default = false){

		if(!is_array($this->_datasClean))
			return array();

		$datas = (!$default AND is_array($this->_datas))
			? array_merge($this->_datasClean, $this->_datas)
			: $this->_datasClean;

		foreach($this->_mounted AS $prefix => $mounted){
			$datas[$prefix] = $mounted->toArray($default);
		}

		return $datas;
	
	}

	/**
	 * \ArrayAccess::offsetExists impl
	**/
	public function offsetExists($offset){

		//Avoiding shitty checking before accessing value
		return true; //(isset($this->_datasClean[$offset]) || isset($this->_datas[$offset]));

	}

	/**
	 * \ArrayAccess::offsetGet impl
	**/
	public function offsetGet($offset){

		return $this->__get($offset);

	}

	/**
	 * \ArrayAccess::offsetSet impl
	 **/
	public function offsetSet($offset, $value){

		$this->__set($offset, $value);

	}

	/**
	 * Autocall method from Table
	 * ex: 
	 * Table::getName($tablerow){ return $tablerow->name; }
	 * can be called from TableRow instance using $this->getName();
	**/ 
	public function __call($name, array $arguments){

		if(method_exists($this->_table, $name)){

			$args = [$this];

			if($arguments)
				$args[] = [$arguments];

			return call_user_func_array([$this->_table, $name], $args);
		}else{
			throw new Exception\ForwardTableMethodError(get_class($this->_table), $name);
		}

	}

}
