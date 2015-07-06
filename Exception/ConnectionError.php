<?php

namespace ygg\Db\Exception;

class ConnectionError extends Exception {

	public $infos;
	public $driver;
	public $server;
	public $dbname;
	public $login;
	public $pwd;
	public $charset;

	public function __construct($infos, $driver, $server, $dbname, $login, $pwd, $charset){

		parent::__construct('Connection error for "' . $login . '@' . $server . '"', 500);

		$this->infos = $infos;
		$this->driver = $driver;
		$this->server = $server;
		$this->dbname = $dbname;
		$this->login = $login;
		$this->pwd = $pwd;
		$this->charset = $charset;

	}

}
