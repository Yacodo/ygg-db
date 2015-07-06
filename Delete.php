<?php

namespace ygg\Db;

class Delete extends Query {

	public function __construct($table){
	
		$this->_wheres = array();
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

			$this->_query = $driver->sqlizeDelete(
				array($this->getRefTableName()),
				$this->_wheres
			);

		}

		return $this->_query;

	}

}
