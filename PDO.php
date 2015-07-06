<?php

namespace ygg\Db;

abstract class PDO implements Driver {

	protected $_server;
	protected $_dbname;
	protected $_login;
	protected $_pwd;
	protected $_charset;

	protected $_link;

	/**
	 * Constructor
	 *
	 * @param string $server Server or UNIX Socket
	 * @param string $login Login
	 * @param string $pwd Password
	 * @param string $dbname Database name
	 * @param string $charset Charset
	**/
	public function __construct($server, $login = null, $pwd = null, $dbname = null, $charset = null){

		$this->_server = $server;
		$this->_login = $login;
		$this->_pwd = $pwd;
		$this->_dbname = $dbname;
		$this->_charset = $charset;		

		$this->_link = null;

	}

	/**
	 * Instanciate PDO
	 *
	 * @param string $dsn DSN
	 * @param array $options PDO Options
	 * @return \PDO
	**/
	public function pdoConnect($dsn, array $options = null){

		try{

			$pdo = new \PDO($dsn, $this->_login, $this->_pwd, $options);
			$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

		}catch(\PDOException $e){

			throw new Exception\ConnectionError(
				$e->getMessage(),
				get_class($this),
				$this->_server,
				$this->_dbname,
				$this->_login,
				$this->_pwd,
				$this->_charset
			);

		}

		return $pdo;

	}

	/**
	 * Disconnect
	 *
	 * @return PDO
	**/
	public function disconnect(){

		$this->_link = null;
		return $this;

	}

	/**
	 * Get database link
	 * (Because winners don't do (fake-)ORM.)
	 *
	 * @return \PDO|null
	**/
	public function giveMeControl(){

		return $this->_link;

	}

	/**
	 * Quote value
	 *
	 * @return string
	**/
	public function quoteValue($value){

		$this->connect();
		return $this->_link->quote($value);

	}

	/**
	 * Try to execute a request, throw a RequestError Exception if problem encountered
	 *
	 * @param \Closure $closure Request
	 * @return mixed
	**/
	private function tryRequest(\Closure $closure, $query, array $params = null){

		$this->connect();

		try{

			return $closure();

		}catch(\PDOException $e){

			if($query instanceof \PDOStatement){

				$infos = $query->errorInfo();
				$query = $query->queryString;
				$code = $infos[0];

			}else{

				$infos = $this->_link->errorInfo();
				$code = $this->_link->errorCode();

			}

			$message = $infos[2];

			throw new Exception\RequestError($query, $params, $code, $message);

		}

	}

	/**
	 * Query
	 *
	 * @param string $query Query string
	 * @return \PDOStatement
	**/
	public function query($query){

		return $this->tryRequest(function() use($query){
			return $this->_link->query($query);
		}, $query);

	}

	/**
	 * Exec
	 *
	 * @param string $query Query string
	 * @return \PDOStatement
	**/
	public function exec($query){

		return $this->tryRequest(function() use($query){
			return $this->_link->exec($query);
		}, $query);

	}

	/**
	 * Prepare a request
	 *
	 * @param string $query Query string
	 * @return \PDOStatement
	**/
	public function prepare($query){

		$this->connect();
		return $this->_link->prepare($query);

	}

	/**
	 * Execute a query
	 *
	 * @param \PDOStatement|Query $prepared PDOStatement or Query
	 * @param array $params Query parameters
	 * @return \PDOStatement
	**/
	public function execute($prepared, array $params = array()){

		//Instance of ygg\Db\Query ?
		if($prepared instanceof Query){

			$query = $prepared;
			$prepared = $query->getPreparedQuery();
			$params = $query->getParams();

		}

		//Replace Expr
		foreach($params AS $param => $value){

			if($value instanceof Expr){

				$params[$param] = $value->sqlizeString($this);

			}

		}

		//Try request
		$result = $this->tryRequest(
			function() use($prepared, $params){
				return $prepared->execute($params);
			},
			$prepared,
			$params
		);

		//Internal handler result
		if(isset($query)){

			$result = new Result\PDO($query, $this, $result);

		}

		return $result;

	}

	/**
	 * Create a Select query
	 * (using current connection)
	 *
	 * @param Table|string $table
	 * @return ygg\Db\Select
	**/
	public function select($table){

		$select = new Select($table);
		$select->setDriver($this);

		return $select;

	}

	/**
	 * Create an Insert Query
	 *
	 * @param Table|string $table Table or table name
	 * @param array $datas Date for insert
	 * @return bool
	**/
	public function insert($table, array $datas = array()){

		//Create an insert query
		$insert = new Insert($table);
		$insert->setDriver($this);

		//No data ? Return Insert
		if(empty($datas))
			return $insert;

		//Add values
		foreach($datas AS $key => $value){

			$insert->set($key, $value);

		}

		return $insert->execute();

	}

	/**
	 * Create an Update Query
	 *
	 * @param Table|table $table Table or table name
	 * @param array $datas New datas
	 * @param array $wheres Condition for update
	 * @return int number of affected rows
	**/
	public function update($table, array $datas = array(), array $wheres = null){

		//Create an update query
		$update = new Update($table);
		$update->setDriver($this);

		//No data ? Return Update
		if(empty($datas))
			return $update;

		//Set value(s)
		foreach($datas AS $key => $value){

			$update->set($key, $value);

		}

		//Add condition(s)
		if($wheres != null){

			// TODO More elegant condition system 
			foreach($wheres AS $key => $value){

				if(is_array($value)){

					$operator = $value['operator'];
					$value = $value['value'];

				}else{

					$operator = $update::EQUAL;

				}

				$update->where($key, $value, $operator);

			}


		}

		return $update->execute();

	}

	/**
	 * Create a Delete Query
	 *
	 * @param Table|string $table Table or table name
	 * @param array $wheres Condition for delete
	 * @return int Affected rows
	**/
	public function delete($table, array $wheres = array()){

		//Create a delete query
		$delete = new Delete($table);
		$delete->setDriver($this);

		//No condition ? Hum... return in case of error
		if(empty($wheres))
			return $delete;

		//Add condition(s)
		foreach($wheres AS $key => $value){

			if(is_array($value)){

				$operator = $value['operator'];
				$value = $value['value'];

			}else{

				$operator = $delete::EQUAL;

			}

			$delete->where($key, $value, $operator);

		}

		return $delete->execute();
	
	}

	/**
	 * Fetch a row
	 *
	 * @param \PDOStatement $results Results
	 * @param int $offset Row number
	 * @return mixed
	**/
	public function fetch($results, $offset = 0){

		$this->connect();

		$offset = (int) $offset;

		$orientation = ($offset > 0)
			? \PDO::FETCH_ORI_ABS
			: \PDO::FETCH_ORI_NEXT;

		return $results->fetch(\PDO::FETCH_ASSOC, $orientation, $offset);

	}

	/**
	 * Fetch all row
	 *
	 * @param \PDOStatement $results Result
	 * @return array
	**/
	public function fetchAll($results){

		$this->connect();

		return $results->fetchAll(\PDO::FETCH_ASSOC);

	}

	/**
	 * Get last inserted id
	 *
	 * @return mixed
	**/
	public function lastInsertId(){

		$this->connect();
		return $this->_link->lastInsertId();

	}

}
