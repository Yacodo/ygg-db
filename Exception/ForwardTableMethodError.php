<?php

namespace ygg\Db\Exception;

class ForwardTableMethodError extends \Exception {

	public $table;
	public $method;

	public function __construct($table, $method){

		parent::__construct('Cannot forward method "' . $method . '" to table "' . $table . '" from \ygg\Db\TableRow.');

	}

}
