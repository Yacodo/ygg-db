<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Driver;
use ygg\Db\Query;

class DateFormat implements Expr {

	private $_column;
	private $_format;

	public function __construct($column, $format){

		$this->_column = $column;
		$this->_format = $format;
	
	}

	public function sqlizeString(Driver $driver){

		return $driver->dateFormatFunction($this->_column, $this->_format);

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
