<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;

class Param implements Expr {

	private $_param;

	public function __construct($param){
	
		$this->_param = $param;
	
	}

	public function sqlizeString(Driver $driver){

		return $this->_param;

	}

	public function sqlizeForColumn(Driver $driver, $table){

		return false;

	}

	public function sqlizeForSet(Driver $driver, $column){

		return $driver->setRepr(
			$driver->quoteColumn($column),
			$this->_param
		);
	
	}

	public function sqlizeForWhere(Driver $driver, array $where){

		return $driver->whereRepr(
			$driver->quoteColumn($where['column']),
			$driver->getOperator($where['operator']),
			$this->_param
		);

	}

}
