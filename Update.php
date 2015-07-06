<?php

namespace ygg\Db;

class Update extends Query {

	public function __construct($table){

		$this->_sets = array();
		$this->_wheres = array();
		$this->_params = array();

		parent::__construct($table);

	}

	/**
	 * Get query
	 *
	 * @return bool
	**/
	public function getQuery(){

		if(empty($this->_query)){

			$driver = $this->getDriver();

			$this->_query = $driver->sqlizeUpdate(
				array($this->getRefTableName()),
				$this->_sets,
				$this->_wheres
			);

		}

		return $this->_query;

	}

}
