<?php

namespace ygg\Db;

class Row implements \ArrayAccess {

	protected $_datas;

	/**
	 * Constructor
	 *
	 * @param array $datas Datas
	**/
	public function __construct(array $datas = array()){

		$this->_datas = $datas;

	}

	/**
	 * Magic getter
	 *
	 * @param string $name Column name
	 * @return mixed False if column not found
	**/
	public function __get($name){

		return (isset($this->_datas[$name]))
			? $this->_datas[$name]
			: false;

	}

	/**
	 * @alias __get()
	**/
	public function get($name){

		return $this->__get($name);

	}

	/**
	 * Get current row datas
	**/
	public function toArray(){

		return (is_array($this->_datas))
			? $this->_datas
			: array();

	}

	/**
	 * \ArrayAccess::offsetExists impl
	**/
	public function offsetExists($offset){

		return isset($this->_datas[$offset]);

	}

	/**
	 * \ArrayAccess::offsetGet impl
	**/
	public function offsetGet($offset){

		return ($this->offsetExists($offset))
			? $this->_datas[$offset]
			: null;

	}

	/**
	 * \ArrayAccess::offsetSet impl
	 **/
	public function offsetSet($offset, $value){

		//Do nothing, read only

	}

	/**
	 * \ArrayAccess::offsetUnset impl
	**/
	public function offsetUnset($offset){

		//NOPE.

	}

}
