<?php

namespace ygg\Db;

class Insert extends Query {

	public function __construct($table){
	
		$this->_sets = array();
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

			$this->_query = $driver->sqlizeInsert(
				array($this->getRefTableName()),
				$this->_sets
			);

		}

		return $this->_query;

	}

}
