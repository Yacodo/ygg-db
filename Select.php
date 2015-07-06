<?php

namespace ygg\Db;

class Select extends Query {

	public function __construct($table){

		$this->_columns = array();
		$this->_froms = array();
		$this->_joins = array();
		$this->_wheres = array();
		$this->_orders = array();
		$this->_limits = array('offset' => null, 'limit' => null);

		$this->_params = array();

		parent::__construct($table);
	
	}

	/**
	 * Get query
	 *
	 * @return mixed
	**/
	public function getQuery(){

		if(empty($this->_query)){

			$driver = $this->getDriver();

			$this->_query = $driver->sqlizeSelect(
				$this->_columns,
				$this->_froms,
				$this->_joins,
				$this->_wheres,
				$this->_orders,
				$this->_limits
			);

		}

		return $this->_query;

	}

	/**
	 * @see Query::execute()
	 * @param bool $fetchFirst Fetch first row if possible
	 * @return Result|Row
	**/
	public function execute($fetchFirst = false){

		$res = parent::execute();

		//Fetch first or return Result
		return ($fetchFirst)
			? $res->fetch()
			: $res;
	
	}

}
