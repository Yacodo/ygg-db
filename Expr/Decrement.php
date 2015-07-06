<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;

class Decrement implements Expr {

	public function sqlizeString(Driver $driver){

		return false;

	}

	public function sqlizeForColumn(Driver $driver, $table){

		return false;

	}

	public function sqlizeForSet(Driver $driver, $column){

		$column = $driver->quoteColumn($column);

		return $driver->setRepr(
			$column,
			$column . ' - 1'
		);

	}

	public function sqlizeForWhere(Driver $driver, array $where){

		return false;

	}

}
