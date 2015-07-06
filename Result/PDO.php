<?php

namespace ygg\Db\Result;
use ygg\Db\Result;
use ygg\Db\Select;
use ygg\Db\Row;
use ygg\Db\Table;

class Pdo extends Result {

	/**
	 * Fetch a row (next by default)
	 *
	 * @param int $rowNumber 0 mean next, other for offset
	 * @return mixed
	**/
	public function fetch($rowNumber = 0){

		if(!($this->_query instanceof Select))
			return false;

		$row = $this->getDriver()->fetch($this->_query->getPreparedQuery(), $rowNumber);

		//No result
		if(!$row)
			return $row;

		$table = $this->_query->getRefTable();

		return ($table instanceof Table)
			? $table->createRow($row)
			: new Row($row);
	
	}

	/**
	 * Get numbers of rows affected or returned
	 *
	 * @return int
	**/
	public function rowCount(){

		return $this->_query->getPreparedQuery()->rowCount();

	}

	/**
	 * Verify results
	 *
	 * @return bool
	**/
	public function hasResult(){

		return ($this->_result AND $this->rowCount());

	}

}
