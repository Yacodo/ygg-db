<?php

namespace ygg\Db;

interface Expr {

	public function sqlizeString(Driver $driver);
	public function sqlizeForColumn(Driver $driver, $table);
	public function sqlizeForSet(Driver $driver, $column);
	public function sqlizeForWhere(Driver $driver, array $where);

}
