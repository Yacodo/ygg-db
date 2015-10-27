<?php

namespace ygg\Db;

abstract class Query {

	//Operators
	const EQUAL = '=';
	const NOTEQUAL = '!=';
	const LT = '<';
	const GT = '>';
	const LTE = '<=';
	const GTE = '>=';
	const LIKE = 'LIKE';
	const REGEXP = 'REGEXP';

	//Joins types
	const JOIN = 'join';
	const LJOIN = 'ljoin';
	const RJOIN = 'rjoin';

	//Ordering types
	const ASC = 'ASC';
	const DESC = 'DESC';

	//Etc...
	const ALL = '*';
	const OP_AND = 'AND';
	const OP_OR = 'OR';

	protected $_refTable;
	protected $_driver;

	protected $_columns = null;
	protected $_froms = null;
	protected $_sets = null;
	protected $_joins = null;
	protected $_wheres = null;
	protected $_orders = null;
	protected $_limits = null;

	protected $_mounts = null;

	protected $_params;

	protected $_query;
	protected $_prepared;

	abstract public function getQuery();

	public function __construct($table){

		$this->_refTable = $table;

		if($table instanceof Table){
		
			$this->_driver = $table->getDriver();
			$name = $this->getRefTableName();
			$alias = $this->getRefTableAlias();

		}else{

			$name = $table;
			$alias = null;

		}

		$this->from(
			$name,
			$alias,
			self::ALL
		);
	
	}

	/**
	 * Get reference Table
	 *
	 * @return Table|string
	**/
	public function getRefTable(){

		return $this->_refTable;

	}

	/**
	 * Get Reference Table Name
	 *
	 * @return string
	**/
	public function getRefTableName(){

		return ($this->_refTable instanceof Table)
			? $this->_refTable->getTableName()
			: $this->_refTable;

	}

	/**
	 * Get Reference Table Alias
	 *
	 * @return string
	**/
	public function getRefTableAlias(){

		return ($this->_refTable instanceof Table)
			? $this->_refTable->getTableAlias()
			: $this->_refTable;

	}

	/**
	 * Set Driver for query
	 *
	 * @param Driver $driver Driver
	 * @return Query
	**/
	public function setDriver(Driver $driver){

		$this->_driver = $driver;
		return $this;

	}

	/**
	 * Get Driver
	 *
	 * @return Driver Throw an exception if missing
	**/
	public function getDriver(){

		if(empty($this->_driver)){

			throw new Exception\NoDriver();

		}

		return $this->_driver;

	}

	/**
	 * Add a parameter
	 * addParam('name', 'value') For :name => value
	 * addParam(':name', 'value') Same
	 * addParam('value') ? => value
	 *
	 * Note : For the last call, you must now what you're doing.
	 * (Parameter position in generation, for example)
	 *
	 * @param mixed $name Name for parameter, or value
	 * @param mixed $value Value for parameter
	 * @return Query
	**/
	public function addParam($name, $value = null){

		if($this->_params !== null){

			//if($value !== null AND $name !== null){
			if($name !== null){

				if(\strlen($name) > 1 AND $name[0] != ':'){

					$name = ':' . $name;

				}

				$this->_params[$name] = $this->getDriver()->convertValue($value);

			}

		}

		return $this;
	
	}

	/**
	 * Get parameters list
	 *
	 * @return array
	**/
	public function getParams(){
	
		return $this->_params;
	
	}

	/**
	 * Clean parameters list
	 *
	 * @return Query
	**/
	public function cleanParams(){
	
		$this->_params = array();
		return $this;
	
	}

	/**
	 * Add columns to Query
	 * (Note: replace all columns for the table,
	 * if you want to limit the ref table columns (* by default),
	 * call columns() without second argument)
	 *
	 * @param array|string|null $columns Columns list (or string for one column)
	 * @param string|null $table Table alias [or name] for columns [optionnal], if null ref table
	**/
	public function columns($columns, $table = null){
		
		if($this->_columns !== null){

			$table = ($table != null) ? $table : $this->getRefTableAlias();

			$this->_columns[$table] = (\is_array($columns))
				? $columns
				: (
					($columns != null)
						? array($columns)
						: array()
				);

		}

		return $this;

	}

	/**
	 * Set a value
	 * set('column', null); // Set column with null value
	 * set('gigawatts', 2.1); // Set gigawatts to 2.1
	 * set(array('column' => null, 'gigawatts' => 2.1)); // Same for both columns
	 *
	 * @param array|string $column Column name
	 * @param string|null Column value
	**/
	public function set($column, $value = null){
	
		if($this->_sets !== null){

			if(\is_array($column)){

				foreach($column AS $cName => $cValue){

					$this->set($cName, $cValue);

				}

			}else{

				if(!($value instanceof Expr)){

					$keyParam = ':set_' . $column ;
					$this->addParam($keyParam, $value);
					$value = new Expr\Param($keyParam);

				}

				$this->_sets[$column] = $value;
			}

		}

		return $this;
	
	}

	/**
	 * Add a table to the Query
	 *
	 * @param string $name Table name
	 * @param string|null $alias Table alias (Null = no alias)
	 * @param array|string|null $columns
	 * @return Query
	**/
	public function from($name, $alias = null, $columns = null){

		if($this->_froms !== null){

			$alias = ($alias != null) ? $alias : $name;

			$this->_froms[$alias] = $name;

			if($columns){

				$this->columns($columns, $alias);

			}

		}

		return $this;
	
	}

	/**
	 * Add a Query join
	 *
	 * @param string $type Jointure type
	 * @param array|string $table Table informations
	 * @param string $condition Jointure condition
	 * @param array|string|null $columns Jointures columns (null == no column)
	**/
	protected function addJoin($type, $table, $condition, $columns){

		if($this->_joins !== null){

			if(!\in_array($type, array(self::JOIN, self::LJOIN, self::RJOIN))){

				$type = self::JOIN;

			}

			$table = (array) $table;

			$name = \current($table);

			$alias = (!\is_numeric($key = \key($table)))
				? $key
				: $name;

			$this->_joins[$alias] = array(
				'type' => $type,
				'table' => $name,
				'condition' => $condition
			);

			if($columns){

				$this->columns($columns, $alias);

			}

		}
	
	}

	/**
	 * Add a Query join (Type = JOIN)
	 *
	 * @see join()
	 * @return Query
	**/
	public function join($table, $condition, $columns = self::ALL){

		$this->addJoin(self::JOIN, $table, $condition, $columns);
		return $this;
	
	}

	/**
	 * Add a Query join (Type = LEFT JOIN)
	 *
	 * @see addJoin()
	 * @return Query
	**/
	public function leftJoin($table, $condition, $columns = self::ALL){

		$this->addJoin(self::LJOIN, $table, $condition, $columns);
		return $this;
	
	}

	/**
	 * Add a Query join (Type = RIGHT JOIN)
	 *
	 * @see addJoin()
	 * @return Query
	**/
	public function rightJoin($table, $condition, $columns = self::ALL){

		$this->addJoin(self::RJOIN, $table, $condition, $columns);
		return $this;
	
	}

	/**
	 * Prepare "virtual mounting point" for Select
	 * 
	 * @param string $prefix Prefix for mounting
	 * @param Table $table Table to mount for datas
	**/
	public function addMount($prefix, Table $table){

		if($this->mount !== null){

			$this->mount[$prefix] = $table;

		}

		return $this;

	}

	public function getMounts(){
		return $this->mounts;
	}

	/**
	 * Add a Query Condition
	 *
	 * @param string $type Where type
	 * @param string $column Column name
	 * @param string $value Column value
	 * @param mixed $operator Operator
	 * @param bool Avoid "parameterization" of value
	**/
	/**protected function addWhere($type, $column, $value, $operator, $param = true){

		if($this->_wheres !== null){

			$param = true;

			if(\is_array($value)){

				if(!\array_key_exists('value', $value) OR !\array_key_exists('operator', $value)){

					throw new Exception\MissingInformations($value, array('operator', 'value'));

				}

				$operator = $value['operator'];

				$value = $value['value'];

			}
			
			if(
				$operator === false
				OR
				!in_array(
					$operator,
					array(
						self::EQUAL,
						self::NOTEQUAL,
						self::LT,
						self::GT,
						self::LTE,
						self::GTE,
						self::LIKE,
						self::REGEXP
					)
				)
			){

				if($operator === false){

					$param = false;

				}

				$operator = self::EQUAL;

			}

			if($param AND $value != null){

				if(!($value instanceof Expr)){

					$keyParam = ':where_' . \str_replace('.', '_', $column);
					$this->addParam($keyParam, $value);
					$value = new Expr\Param($keyParam);

				}

			}

			$this->_wheres[] = array(
				'type' => $type,
				'column' => $column,
				'value' => $value,
				'operator' => $operator
			);

		}

	}**/

	public function createParam($column, $value){

		if(!($value instanceof Expr)){

			$keyParam = ':param_' . \str_replace('.', '_', $column);
			$this->addParam($keyParam, $value);
			$value = new Expr\Param($keyParam);

		}

		return $value;

	}

	protected function addWhere($type, $column, $value, $operator){

		if($this->_wheres !== null){

			$this->_wheres[] = self::formatWhere(
				$type,
				$column,
				$this->createParam($column, $value),
				$operator
			);

			/**$param = true;

			if(\is_array($value)){

				if(!\array_key_exists('value', $value) OR !\array_key_exists('operator', $value)){

					throw new Exception\MissingInformations($value, array('operator', 'value'));

				}

				$operator = $value['operator'];

				$value = $value['value'];

			}
			
			if(
				$operator === false
				OR
				!in_array(
					$operator,
					array(
						self::EQUAL,
						self::NOTEQUAL,
						self::LT,
						self::GT,
						self::LTE,
						self::GTE,
						self::LIKE,
						self::REGEXP
					)
				)
			){

				if($operator === false){

					$param = false;

				}

				$operator = self::EQUAL;

			}

			if($param AND $value != null){

				if(!($value instanceof Expr)){

					$keyParam = ':where_' . \str_replace('.', '_', $column);
					$this->addParam($keyParam, $value);
					$value = new Expr\Param($keyParam);

				}

			}

			$this->_wheres[] = array(
				'type' => $type,
				'column' => $column,
				'value' => $value,
				'operator' => $operator
			);**/

		}

	}

	public static function formatWhere($type, $column, $value, $operator){

		if(\is_array($value)){

			if(!\array_key_exists('value', $value) OR !\array_key_exists('operator', $value)){

				throw new Exception\MissingInformations($value, array('operator', 'value'));

			}

			$operator = $value['operator'];

			$value = $value['value'];

		}
		
		if(
			!in_array(
				$operator,
				array(
					self::EQUAL,
					self::NOTEQUAL,
					self::LT,
					self::GT,
					self::LTE,
					self::GTE,
					self::LIKE,
					self::REGEXP
				)
			)
		){

			$operator = self::EQUAL;

		}

		return array(
			'type' => $type,
			'column' => $column,
			'value' => $value,
			'operator' => $operator
		);

	}

	/**
	 * Add a Query condition (Type = AND)
	 * where('column', 'value', '=') // operator is optional
	 * where('column', array('value' => 'value', 'operator' => '=')) // Same as first
	 *
	 * @see addWhere()
	**/
	public function where($column, $value, $operator = self::EQUAL){

		$this->addWhere('AND', $column, $value, $operator);
		return $this;
	
	}

	/**
	 * Add a Query condition (Type = OR)
	 *
	 * @see where() 
	**/
	public function orWhere($column, $value, $operator = self::EQUAL){

		$this->addWhere('OR', $column, $value, $operator);
		return $this;

	}

	/**
	 * Query order
	 * 
	 * @param string|array $column Column name
	 * @param string $type Order
	 * @return Query
	**/
	public function order($column, $type = self::ASC){

		if(!$this->_orders !== null){

			if(\strtoupper($type) != self::DESC){

				$type = self::ASC;

			}

			if(is_string($column) OR $column instanceof Expr){

				$this->_orders[] = array('type' => $type, 'column' => $column);

			}else{

				foreach($column AS $sub){

					$this->order($sub, $type);

				}

			}

		}

		return $this;
	
	}

	/**
	 * Query limit
	 * limit(10, 50) // 50 results from offset 10
	 * limit(50) // 50 results from start
	 * 
	 * @param int $offset Beginning number row
	 * @param int|null $limit Limit result to $limit number
	 * @return Query
	**/
	public function limit($offset, $limit = null){
	
		if($this->_limits !== null){

			$offset = ($offset !== null) ? (int) $offset : null;
			$limit = ($limit !== null) ? (int) $limit : null;

			$this->_limits = ($offset != null AND $limit != null)
				? array('offset' => $offset, 'limit' => $limit)
				: array('offset' => null, 'limit' => $offset);

		}

		return $this;
	
	}

	/**
	 * Get the prepared Query
	 *
	 * @return mixed
	**/
	public function getPreparedQuery(){

		if(empty($this->_prepared)){

			$this->_prepared = $this->getDriver()->prepare($this->getQuery());
		
		}

		return $this->_prepared;
	
	}

	/**
	 * Execute Request
	 *
	 * @return mixed
	**/
	public function execute(){

		$driver = $this->getDriver();

		$res = $driver->execute($this);

		return ($this instanceof Select)
			? $res
			: $res->rowCount();

	}

}
