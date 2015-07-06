<?php

namespace ygg\Db\Exception;

class NoData extends Exception {

	public $type;

	public function __construct($type){

		parent::__construct('No data for request (' . $type . ')', 500);

		$this->type = $type;

	}

}
