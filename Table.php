<?php

namespace ygg\Db;

class Table {

	protected $_driver;

	protected $_name;
	protected $_alias;

	protected $_id;

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
	public function createRow($datas = array()){

		return new TableRow($this, $datas);	

	}

	/**
	 * Insert query
	 *
	 * @param array $datas Datas for insert
	 * @param mixed &$insertId new insert id helper
	 * @return Insert|bool Insert or inserting results
	**/
	public function insert(array $datas = null, &$insertId = null){

		//No datas ? new Insert
		if($datas == null)
			return new Insert($this);

		$driver = $this->getDriver();
	   	$res = $driver->insert($this, $datas);

		if(!empty($this->_id)){

			$insertId = (isset($datas[$this->_id]))
				? $datas[$this->_id]
				: $driver->lastInsertId();

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
