<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;

class Rand implements Expr {

	public function sqlizeString(Driver $driver){

		return $driver->randFunction();

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
