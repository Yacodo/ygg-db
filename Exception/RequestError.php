<?php

namespace ygg\Db\Exception;

class RequestError extends Exception {

	public $query;
	public $params;
	public $errorCode;
	public $message;

	public function __construct($query, $params, $errorCode, $message){

		parent::__construct('SQL Error [' . $errorCode . ']', 500);

		$this->query = $query;
		$this->params = $params;
		$this->errorCode = $errorCode;
		$this->message = $message;

	}

}
