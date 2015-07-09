<?php

namespace ygg\Db\Exception;

class NoDriver extends \ygg\Db\Exception {

	public function __construct(){

		parent::__construct('No driver defined for ygg\Db\Query', 500);

	}

}
