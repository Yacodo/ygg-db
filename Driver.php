<?php

namespace ygg\Db;

interface Driver {

	public function connect();
	public function disconnect();
	public function giveMeControl();

	public function quoteColumn($column, $table = null);
	public function quoteValue($value);
	public function alias($name, $alias);

	public function getOperator($operator);

	public function setRepr($column, $value);
	public function whereRepr($x, $operator, $y);

	public function sqlizeColumns(array $columns);
	public function sqlizeFrom(array $froms);
	public function sqlizeSet(array $sets);
	public function sqlizeJoin(array $joins);
	public function sqlizeWhere(array $wheres);
	public function sqlizeOrder(array $orders);
	public function sqlizeLimit(array $limits);

	public function sqlizeSelect(
		array $columns,
		array $froms,
		array $joins,
		array $wheres,
		array $orders,
		array $limits
	);  
	public function sqlizeInsert(array $froms, array $sets);
	public function sqlizeUpdate(array $froms, array $sets, array $wheres);
	public function sqlizeDelete(array $froms, array $wheres);

	public function countFunction($column);
	public function nowFunction();
	public function randFunction();
	public function dateFormatFunction($column, $format);

	public function query($query);
	public function exec($query);
	public function prepare($query);
	public function execute($prepared, array $params = array());

	public function fetch($results, $offset = 0);
	public function fetchAll($results);

	public function select($table);
	public function insert($table, array $datas);
	public function update($table, array $datas, array $wheres = null);
	public function delete($table, array $wheres);

	public function getTableSequence(Table $table);
	public function lastInsertId($name = null);

}
