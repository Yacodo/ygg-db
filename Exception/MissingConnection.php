<?php

namespace ygg\Db\Exception;

class MissingConnection extends Exception {

	public $name;

	public function __construct($name){

		parent::__construct('Missing connection "' . $name . '"', 500);

		$this->name = $name;

	}

}
