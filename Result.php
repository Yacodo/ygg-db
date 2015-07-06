<?php

namespace ygg\Db;

abstract class Result implements \Countable, \Iterator {

	private $_n;
	private $_row;

	protected $_query;
	protected $_driver;
	protected $_result;

	abstract public function fetch($rowNumber = 0);
	abstract public function hasResult();
	abstract public function rowCount();

	/**
	 * Constructor
	 *
	 * @param Query $query Request Query
	 * @param Driver $driver Request Driver
	 * @param mixed $result Request result
	**/
	final public function __construct(Query $query, Driver $driver, $result){

		$this->_query = $query;
		$this->_driver = $driver;
		$this->_result = $result;

	}

	/**
	 * Get Request Query
	 *
	 * @return Query
	**/
	public function getQuery(){

		return $this->_query;

	}

	/**
	 * Get Request Driver
	 *
	 * @return Driver
	**/
	public function getDriver(){

		return $this->_driver;

	}

	/**
	 * Get Request result
	 *
	 * @return mixed
	**/
	public function getResult(){

		return $this->_result;
	
	}

	/**
	 * \Iterator::rewind() impl
	**/
	public function rewind(){

		if($this->hasResult()){

			$this->_row = $this->fetch(1);

		}

		$this->_n = 1;

	}

	/**
	 * \Iterator::valid() impl
	**/
	public function valid(){

		return ($this->_row);

	}

	/**
	 * \Iterator::current() impl
	**/
	public function current(){

		return $this->_row;

	}

	/**
	 * \Iterator::key() impl
	**/
	public function key(){

		return $this->_n;

	}

	/**
	 * \Iterator::next impl
	**/
	public function next(){

		$this->_row = $this->fetch();
		++$this->_n;

	}

	/**
	 * \Countable::count() impl
	**/
	public function count(){

		//No result, 0
		if(!$this->hasResult())
			return 0;

		return $this->rowCount();

	}

	/**
	 * Fetch all row (to an array of associative array)
	 *
	 * @return array
	**/
	public function fetchAll(){

		if(!($this->_query instanceof Select))
			return false;

		return $this->getDriver()->fetchAll($this->_query->getPreparedQuery());

	}

}
