<?php

namespace ygg\Db\Exception;

class MissingDriver extends \Exception {

	public $driver;

	public function __construct($driver){

		parent::__construct('Missing driver "' . $driver . '"', 500);

		$this->driver = $driver;

	}

}
