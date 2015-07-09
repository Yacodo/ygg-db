<?php

namespace ygg\Db\Exception;

class NoConnection extends \ygg\Db\Exception {

	public function __construct(){

		parent::__construct('No default connection', 500);

	}

}
