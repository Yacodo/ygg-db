<?php

namespace ygg\Db;

class TableRow extends Row {

	protected $_table;

	protected $_datasClean;
	protected $_datas;

	/**
	 * Constructor
	 *
	 * @param Table $table Table for Row
	**/
	public function __construct(Table $table, array $datas = array()){

		$this->_table = $table;

		$this->_datasClean = $datas;
		$this->_datas = array();

	}

	/**
	 * Magic setter
	 *
	 * @param string $name Column name
	 * @param mixed $value Column value
	**/
	public function __set($name, $value){

		$this->_datas[$name] = $value;

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

			}else{

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

	/**
	 * Save row
	 * Insert when datasClean is null ($this->clean();)
	 * Update when datasClean is array ($this->setResult($result)->fetch();)
	 *
	 * @return Row
	**/
	public function save(){

		$hasDatas = count($this->_datas) > 0; 

		//Update
		if(count($this->_datasClean) > 0){ 

			//Nothing to update
			if($hasDatas){

				//By identifier
				if(($id = $this->_table->getTableIdentifier()) AND isset($this->_datasClean[$id])){

					$conditions = array(
						$id => $this->_datasClean[$id]
					);

				}else{ //By current row datas

					$conditions = $this->_datasClean;

				}

				return $this->_table->update($this->_datas, $conditions);

			}

		}elseif($hasDatas){ //Insert

			return $this->_table->insert($this->_datas);

		}

		//Nothing for save(), no datas ($_datas) for update or insert
		return false;

	}

	/**
	 * Clean Row
	 *
	 * @return Row
	**/
	public function clean(){

		$this->_datasClean = array();
		$this->_datas = array();

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

		return (!$default AND is_array($this->_datas))
			? array_merge($this->_datasClean, $this->_datas)
			: $this->_datasClean;
	
	}

	/**
	 * \ArrayAccess::offsetExists impl
	**/
	public function offsetExists($offset){

		return (isset($this->_datasClean[$offset]) || isset($this->_datas[$offset]));

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

}
