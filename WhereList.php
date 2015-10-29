<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;
use ygg\Db\Query;

class WhereList implements Expr {

	private $_operator;
	private $_wheres;

	protected function __construct($operator = Query::OP_AND){
	
		$this->_operator = $operator;
		$this->_wheres = array();
	
	}

	public function sqlizeString(Driver $driver){

		return false;	

	}

	public function sqlizeForColumn(Driver $driver, $table){

		return false;

	}

	public function sqlizeForSet(Driver $driver, $column){

		return false;

	}

	public function sqlizeForWhere(Driver $driver, array $where){

		if(!count($this->_wheres))
			return '';

		return $driver->quoteList(
			$driver->sqlizeWhere($this->_wheres)
		);

	}

	public function add($column, $value, $operator = Query::EQUAL){

		$this->_wheres[] = Query::formatWhere($this->_operator, $column, $value, $operator);
		return $this;
	}

}
