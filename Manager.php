<?php

namespace ygg;

class Manager {

	static private $_connections;
	static private $_default;

	private function __construct(){}

	/**
	 * Verify if a connection exists
	 *
	 * @param string $name Connection name
	 * @return bool
	**/
	static public function exists($name){

		return isset(self::$_connections[$name]);

	}

	/**
	 * Set default driver
	 *
	 * @param string $name Connection name
	**/
	static public function setDefault($name){

		if(self::exists($name))
			self::$_default = $name;

	}

	/**
	 * Add a connection
	 *
	 * @param string $name Connection name
	 * @param Db\Driver $driver Connection driver
	 * @param bool $default Used as a default connection on true
	**/
	static public function add($name, Db\Driver $driver, $default = false){

		self::$_connections[$name] = $driver;

		//Add default connection if defined or first connection
		if($default === true OR empty(self::$_default))
			self::setDefault($name);

	}

	/**
	 * Get a connection
	 *
	 * @param string $name Connection name
	 * @return Db\Driver
	**/
	static public function get($name){

		//Get connection
		if(self::exists($name))
			return self::$_connections[$name];

		//Still here ? GTFO
		throw new Db\Exception\MissingConnection($name);

	}

	/**
	 * Get default connection
	 *
	 * @return Db\Driver
	**/
	static public function getDefault(){

		//Return default connection
		if(!empty(self::$_default))
			return self::get(self::$_default);

		//Still here ? GTFO.
		throw new Db\Exception\NoConnection();

	}

}
