<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;
use ygg\Db\Query;

class Count implements Expr {

	private $_column;

	public function __construct($column = Query::ALL){

		$this->_column = $column;
	
	}

	public function sqlizeString(Driver $driver){

		return $driver->countFunction($this->_column);

	}

	public function sqlizeForColumn(Driver $driver, $table){

		return $this->sqlizeString($driver);

	}

	public function sqlizeForSet(Driver $driver, $column){

		return $driver->setRepr(
			$driver->quoteColumn($column),
			$this->sqlizeString($driver)
		);

	}

	public function sqlizeForWhere(Driver $driver, array $where){

		return $driver->whereRepr(
			$driver->quoteColumn($where['column']),
			$driver->getOperator($where['operator']),
			$this->sqlizeString($driver)
		);

	}

}
