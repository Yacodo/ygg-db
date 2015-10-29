<?php

namespace ygg\Db;

class Table {

	protected $_driver;

	protected $_name;
	protected $_alias;

	protected $_id;

	protected $_lazy_load = true;

	protected $_mounts = array();
	protected $_not_nulls = array();
	protected $_filters_datas = array();

	/**
	 * Get table name
	 *
	 * @return string
	**/
	public function getTableName(){

		return $this->_name;

	}

	/**
	 * Get table alias
	 *
	 * @return string
	**/
	public function getTableAlias(){

		return (!empty($this->_alias))
			? $this->_alias
			: $this->_name;

	}

	/**
	 * Get table identifier (column name)
	 *
	 * @return string
	**/
	public function getTableIdentifier(){

		return $this->_id;

	}

	/**
	 * Get the sequence name for current table ID
	 *
	 * @return mixed Sequence name or null
	**/
	public function getTableSequence(){
		return $this->_driver->getTableSequence($this);
	}

	/**
	 * Verify if table can be lazy loaded (happen when TableRow::__get($var) fail to find something and only datas in _datasClean is the identifier)
	**/
	public function isTableLazyLoad(){
		return $this->_lazy_load;
	}

	/**
	 * List virtual mounts points
	**/
	public function getTableMounts(){

		return $this->_mounts;

	}

	/**
	 * Add a mount point
	 *
	 * @param string $prefix Prefix to mount
	 * @param Table $table Table to use (Table::createRow()) for mounting point
	**/
	public function addTableMount($prefix, Table $table){

		$this->_mounts[$prefix] = $table;

		return $this;

	}

	/**
	 * Non nullable columns (if a column got a NULL value = it's supported to not change anything)
	 *
	 * @result array List of non nullable columns
	**/ 
	public function getTableNotNulls(){
		return $this->_not_nulls;
	}

	/**
	 * Add a not null column
	 *
	 * @param string|array $column Column name (array values : list of columns name)
	**/
	public function addTableNotNull($column){

		if(is_array($column)){

			foreach($column AS $c)
				$this->addTableNotNull($c);

		}elseif(is_string($column)){

			$this->_not_nulls[] = $column;

		}else{

			//TODO Exception
			return false;

		}

		return $this;

	}

	/**
	 * Add a filtering function for a column on TableRow::setData
	 *
	 * @param string $column Column name
	 * @param \Closure $filter Filtering function
	**/
	public function onSetData($column, \Closure $filter){
		if($filter instanceof \Closure)
			$this->_filters_datas[$column] = $filter;
		else
			//TODO Exception
			return false;

		return $this;
	}

	/**
	 * Filter a data value
	 *
	 * @param string $column Column name
	 * @param mixed $value Column value
	 * @param boolean $cancel_assign
	**/
	public function filterData($column, $value, TableRow $row,  &$cancel_assign = false){
		if(isset($this->_filters_datas[$column])){
			$value = $this->_filters_datas[$column]($value, $row, $cancel_assign);
		}

		return $value;
	}

	/**
	 * Set driver
	 *
	 * @param Driver $driver Driver
	 * @return Table
	**/
	public function setDriver(Driver $driver){

		$this->_driver = $driver;
		return $this;

	}

	/**
	 * Get driver
	 * 
	 * @return Driver
	**/
	public function getDriver(){

		//Get driver (string AS connection name OR default connection)
		if(!($this->_driver instanceof Driver)){

			$this->_driver = (\is_string($this->_driver))
				? Manager::get($this->_driver) //By it's name
				: Manager::getDefault(); //Default connection.

		}

		return $this->_driver;

	}

	/**
	 * Create a Select query
	 *
	 * @return Select
	**/
	public function select(){

		return new Select($this);

	}

	/**
	 * Perform an SQL COUNT()
	 *
	 * @param array $wheres Count condition
	 * @return int
	**/
	public function count(array $wheres = array()){

		$select = $this->select()
			->columns(array('count' => new Expr\Count()));

		//Add where
		//TODO handle OR/etc...
		foreach($wheres AS $column => $value){

			$select->where($column, $value);

		}

		//Fetch first row and get count
		return $select->execute(true)->count;

	}

	/**
	 * Create a table row 
	 * (like a row, but with a reference table... yeah that simple)
	 *
	 * @param array|mixed $datas Row datas
	**/
	public function createRow($datas = array(), TableRow $mounted_by = null){

		return new TableRow($this, $datas, $mounted_by);	

	}

	/**
	 * Remove undesired columns form datas array (used by TableRow::save())
	 *
	 * @param &array $datas Array of datas
	 * @return array
	**/
	public function clearDatasColumns(array &$datas, $remove_id = true){
		//Remove ID if present inside
		if($remove_id AND !empty($this->_id)){
			unset($datas[$this->_id]);
		}

		foreach($this->_not_nulls AS $column){
			if(isset($datas[$column]) AND $datas[$column] === null)
				unset($datas[$column]);
		}

		return $datas;
	}

	/**
	 * Insert query
	 *
	 * @param array $datas Datas for insert
	 * @param mixed &$insert_id new insert id helper
	 * @return Insert|bool Insert or inserting results
	**/
	public function insert(array $datas = null, &$insert_id = null){

		//No datas ? new Insert
		if($datas == null)
			return new Insert($this);

		$driver = $this->getDriver();
	   	$res = $driver->insert($this, $datas);

		if(!empty($this->_id)){

			//$insert_id = (isset($datas[$this->_id]))
			//	? $datas[$this->_id]
			//	: $driver->lastInsertId($this);
			$insert_id = $driver->lastInsertId($this);

		}

		return $res;

	}

	/**
	 * Update query
	 *
	 * @param array $datas Datas for update
	 * @param array $wheres Condition for update
	 * @return Update|int Update or number of updated rows
	**/
	public function update(array $datas = null, array $wheres = null){

		//No datas ? new Update
		if($datas == null)
			return new Update($this);

		return $this->getDriver()->update($this, $datas, $wheres);

	}

	/**
	 * Delete query
	 *
	 * @param array $wheres Condition for delete
	 * @return Delete|int Delete or number of deleted rows
	**/
	public function delete(array $wheres = null){

		if($wheres == null)
			return new Delete($this);

		return $this->getDriver()->delete($this, $wheres);

	}

	/**
	 * Verify if an ID exists in the table
	 *
	 * @param int $id Row id
	 * @return bool
	**/
	public function existsId($id){

		if(empty($this->_id))
			return false;

		return (bool) $this->count(array($this->_id => $id));

	}

	/**
	 * Select by ID
	 *
	 * @param mixed $id Identifier
	 * @return TableRow
	**/
	public function getById($id){

		if(empty($this->_id))
			return false;

		$select = $this->select();
		$select->where($this->_id, $id);

		return $select->execute(true);

	}

	/**
	 * Update by ID
	 *
	 * @param mixed $id Identifier
	 * @param array $datas Datas
	 * @return int
	**/
	public function updateById($id, array $datas){

		if(empty($this->_id))
			return 0;

		return $this->update($datas, array($this->_id => $id));

	}

	/**
	 * Delete by ID
	 *
	 * @param mixed $id Identifier
	 * @return int
	**/
	public function deleteById($id){

		if(empty($this->_id))
			return 0;

		return $this->delete(array($this->_id => $id));

	}

	public function __toString(){

		return $this->_name;

	}

}
