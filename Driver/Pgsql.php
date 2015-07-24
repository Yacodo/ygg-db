<?php

namespace ygg\Db\Driver;
use ygg\Db;
use ygg\Db\PDO;
use ygg\Db\Expr;
use ygg\Db\Query;
use ygg\Db\Insert;
use ygg\Db\Update;
use ygg\Db\Delete;

class Pgsql extends PDO {

	static private $_joins = array(
		Query::JOIN => 'JOIN',
		Query::LJOIN => 'LEFT JOIN',
		Query::RJOIN => 'RIGHT JOIN'
	);

	static private $_operators = array(
		Query::EQUAL => '=',
		Query::NOTEQUAL => '!=',
		Query::LT => '<',
		Query::GT => '>',
		Query::LTE => '<=',
		Query::GTE => '>=',
		Query::LIKE => '~',
		Query::REGEXP => '~'
	);

	static private $_orders = array(
		Query::ASC => 'ASC',
		Query::DESC => 'DESC'
	);

	/**
	 * Connection to database
	 *
	 * @return Pgsql
	**/
	public function connect(){

		//Stop here if already connected
		if(!empty($this->_link))
			return $this;

		$options = array();

		$dsn = 'pgsql:';

		//Split host:port
		$server = explode(':', $this->_server, 2);

		$dsn.= 'host=' . $server[0];

		//Seach for specified port
		if(isset($server[1])){

			$dsn.= ';port=' . (int) $server[1];

		}

		//dbname
		if($this->_dbname){

			$dsn.= ';dbname=' . $this->_dbname;

		}

		if($this->_login){
		
			$dsn.= ';user=' . $this->_login;

		}

		if($this->_pwd){
			
			$dsn.= ';password=' . $this->_pwd;

		}


		$this->_link = $this->pdoConnect($dsn, $options);

		return $this;

	}

	/**
	 * Quote a column name for Pgsql
	 * quoteColumn('column') = `column`
	 * quoteColumn('column', 'table') = `table`.`column`
	 * quoteColumn('table.column') = `table`.`column`
	 *
	 * @param string $column Column name
	 * @param string|null $table Table name [Optional]
	 * @return string
	**/
	public function quoteColumn($column, $table = null){

		//$column = table.column case
		if(strrchr($column, '.') !== false){

			$tmp = explode('.', $column);

			$table = $tmp[0];
			$column = $tmp[1];

		}

		//Quote table
		$result = ($table AND !\is_numeric($table))
			? ('"' . $table . '".')
			: null;

		$result.= ($column == Query::ALL)
			? '*'
			: ('"' . $column . '"');

		return $result;

	}

	/**
	 * Alias for Pgsql
	 *
	 * @param string $name Data to alias
	 * @param string $alias Alias
	 * @retun string|null Return SQL Alias if needed
	**/
	public function alias($name, $alias){

		return (\is_string($alias) AND $name != $alias)
			? ' AS ' . $alias
			: null;

	}

	public function convertValue($value){
		if(is_bool($value))
			$value = (int) $value;

		return $value;
	}

	/**
	 * Get an operator for Pgsql
	 *
	 * @param string $operator Operator from Query list
	 * @return string|bool False if not supported operator
	**/
	public function getOperator($operator){

		return (isset(self::$_operators[$operator]))
			? self::$_operators[$operator]
			: false;

	}

	/**
	 * Set representation in Pgsql
	 *
	 * @param string $column Column already filtered (quoteColumn, etc)
	 * @param string $value Value already filtered (quoteValue, parameter, etc)
	 * @return string
	**/
	public function setRepr($column, $value){

		return $column . ' = ' . $value;
	
	}

	/**
	 * Where representation in Pgsql
	 *
	 * @param string $x X
	 * @param string $operator Operator
	 * @param string $y Y
	 * @return string
	**/
	public function whereRepr($x, $operator, $y){

		return $x . ' ' . $operator . ' ' . $y;

	}

	/**
	 * Sqlize columns list for Pgsql
	 * $columns = array(
	 *		'table' => array(
	 *			'columnName',
	 *			'count' => new odo\Db\Expr\Count(),
	 *			'alias' => 'name'
	 *		)
	 * );
	 *
	 * table.columnName,
	 * COUNT(*) AS count,
	 * table.name AS alias
	 *
	 * @param array $columns Columns list
	 * @return string
	**/
	public function sqlizeColumns(array $columns){

		$result = '';

		foreach($columns AS $table => $list){

			if(!empty($list)){

				foreach($list AS $alias => $name){

					//Get column for SQL
					$tmp = ($name instanceof Expr)
						? $name->sqlizeForColumn($this, $table)
						: $this->quoteColumn($name, $table);

					//New column, new line
					if($result)
						$result.= ",\n";

					//Add it in SQL and alias if needed
					$result.= "\t" . $tmp . $this->alias($name, $alias);
				
				}

			}

		}

		return $result . "\n";
	
	}

	/**
	 * Sqlize a FROM for Pgsql
	 * $froms = array(
	 *		'table',
	 *		'alias' => 'Pgsql'
	 * );
	 *
	 * `table`,
	 * `Pgsql` AS alias
	 *
	 * @param array $froms Tables list
	 * @return string
	**/
	public function sqlizeFrom(array $froms){

		$result = '';

		foreach($froms AS $alias => $table){

			//New table, new line
			if($result)
				$result.= ",\n";

			//Add column, alias if needed
			$result.= "\t" . $this->quoteColumn($table);
			$result.= $this->alias($table, $alias);
		
		}

		return $result . "\n";
	
	}

	/**
	 * Sqlize SET for Pgsql
	 * $sets = array(
	 *		'column' => 'value',
	 *		'columnTwo' => new odo\Db\Expr\Param(':param')
	 * );
	 *
	 * `column` = 'value',
	 * `columnTwo` = :param
	 *
	 *
	 * @param array $sets Sets list
	 * @return string
	**/
	public function sqlizeSet(array $sets){

		$result = '';

		foreach($sets AS $column => $value){

			//New set, new line
			if($result)
				$result.= ",\n";

			//Add set
			$result.= "\t";
			$result.= ($value instanceof Expr)
				? $value->sqlizeForSet($this, $column)
				: $this->setRepr($this->quoteColumn($column), $this->quoteValue($value));
		
		}

		return $result . "\n";
	
	}

	/**
	 * Sqlize VALUES keys part for Pgsql
	 * $sets = array(
	 *		'column' => 'value',
	 *		'columnTwo' => new odo\Db\Expr\Param(':param')
	 * );
	 *
	 * ("column", "columnTwo")
	 *
	 *
	 * @param array $sets Sets list
	 * @return string
	**/
	public function sqlizeValuesKeys(array $sets){

		$result = '';

        if(count($sets)){

            foreach($sets AS $column => $value){

                if($result){
                    $result.= ', ';
                }

                
                $result.= $this->quoteColumn($column);

            }

            $result = '(' . $result . ')';

        }

		return $result . "\n";
	
	}

    /**
     * Sqlize VALUES datas part for Pgsql
     * $sets = array(
     *		'column' => 'value',
     *		'columnTwo' => new odo\Db\Expr\Param(':param')
     * );
     *
     * ('value', :param)
     *
     *
     * @param array $sets Sets list
     * @return string
    **/
    public function sqlizeValuesDatas(array $sets){

        $result = '';

		if(count($sets)){

			foreach($sets AS $value){

				if($result){
					$result.= ', ';
				}

				$result.= ($value instanceof Expr)
					? $value->sqlizeString($this)
					: $this->quoteValue($value);

			
			}

			$result = '(' . $result . ')';

		}

		return $result . "\n";

    }

	/**
	 * Sqlize JOIN for Pgsql
	 * $joins = array(
	 *		'alias' => array(
	 *			'type' => odo\Db\Query::JOIN,
	 *			'table' => 'tableName',
	 *			'condition' => 'alias.u_id = user.id'
	 *		),
	 *		array(
	 *			'type' => odo\Db\Query::LJOIN,
	 *			'table' => 'secondTable',
	 *			'condition' => 'columnOne, columnTwo'
	 *		)
	 * );
	 * 
	 * JOIN
	 * 		`tableName` AS alias
	 * 		ON
	 * 			alias.u_id = user.id
	 * LEFT JOIN
	 * 		`secondTable`
	 * 		USING
	 * 			(columnOne, columnTwo)
	 *
	 * Warning : No "security filter" in this function.
	 * Generally you don't need user input for join...
	 * elsewhere check by yourself (and I'm pretty sure
	 * you're doing it wrong.)
	 *
	 * @param array $joins Joins list
	 * @return string
	**/
	public function sqlizeJoin(array $joins){
	
		$result = '';

		foreach($joins AS $alias => $infos){

			//Fallback on Query::JOIN if join type is missing
			if(!isset(self::$_joins[$infos['type']])){

				$infos['type'] = Query::JOIN;

			}

			//Join type
			$result.= self::$_joins[$infos['type']] . "\n";

			//Table and alias
			$result.= "\t" . $this->quoteColumn($infos['table']);
			$result.= $this->alias($infos['table'], $alias) . "\n";

			//Join condition
			$result.= (\preg_match('/[<>=\!]/', $infos['condition']))
				? "\tON\n"
				: "\tUSING\n";

			$result.= "\t\t" . $infos['condition'] . "\n";

		}

		return $result;
	
	}

	/**
	 * Sqlize WHERE for Pgsql
	 * $wheres = array(
	 *		array(
	 *			'type' => 'AND',
	 *			'column' => 'column',
	 *			'operator' => '!=',
	 *			'value' => new ygg\Db\Expr\Param(':value')
	 *		),
	 *		array(
	 *			'type' => 'OR',
	 *			'column' => 'test',
	 *			'operator' => '=',
	 *			'value' => 'foobar'
	 *		)
	 * );
	 * `column` != :value
	 * OR
	 * `test` = 'foobar'
	 *
	 * @param array $wheres Wheres list
	 * @return string
	**/
	public function sqlizeWhere(array $wheres){
	
		$result = '';	

		foreach($wheres AS $where){

			//Fallback on Query::EQUAL if operator is missing
			if(!isset(self::$_operators[$where['operator']])){

				$where['operator'] = Query::EQUAL;

			}

			//New condition, new line
			if($result)
				$result.= "\n\t" . $where['type'] . "\n";

			$result.= "\t";
			$result.= ($where['value'] instanceof Expr)
				? $where['value']->sqlizeForWhere($this, $where) //Expr
				: $this->whereRepr( //"normal" where
					$this->quoteColumn($where['column']),
					self::$_operators[$where['operator']],
					$this->quoteValue($where['value'])
				);

		}

		return $result . "\n";
	
	}

	/**
	 * Sqlize ORDER for Pgsql
	 * $orders = array(
	 *		array('type' => ygg\Db\Query::ASC, 'column' => 'column'),
	 *		array('type' => ygg\Db\Query::DESC, 'counterpick' => 'DESC')
	 * );
	 *
	 * `column` ASC,
	 * `counterpick` DESC
	 *
	 * @param array $orders Ordering list
	 * @return string
	**/
	public function sqlizeOrder(array $orders){
	
		$result = '';

		foreach($orders AS $order){

			//Fallback on Query::ASC if order type is missing
			if(!isset(self::$_orders[$order['type']])){

				$order['type'] = Query::ASC;
			
			}

			//New order, new line
			if($result)
				$result.= ",\n";

			//Quote and add column
			if($order['column'] instanceof Db\Expr){

				$result.= "\t" . $order['column']->sqlizeString($this);

			}else{

				$result.= "\t" . $this->quoteColumn($order['column']);
				$result.= ' ' . self::$_orders[$order['type']];

			}
		
		}

		return $result . "\n";
	
	}

	/**
	 * Sqlize a LIMIT for Pgsql
	 * $limits = array(
	 *		'offset' => 20,
	 *		'limit' => 10
	 * );
	 * 
	 * 20, 10
	 *
	 * @param array $limits Limit
	 * @return string
	**/
	public function sqlizeLimit(array $limits){
	
		$result = '';

		//Offset ?
		$offset = (isset($limits['offset']) AND $limits['offset'] != null)
			? $limits['offset']
			: null;

		//Limit ?
		$limit = (isset($limits['limit']) AND $limits['limit'] != null)
			? $limits['limit']
			: null;

		//Limited at least
		if($limit != null){

			$result.= "LIMIT\n\t";

			//If offset
			if($offset != null)
				$result.= $offset . ', ';

			$result.= $limit;

		}

		return $result;
	
	}

	/**
	 * Sqlize a SELECT query
	 *
	 * @param array $columns Columns list
	 * @param array $froms Tables list
	 * @param array $joins Jointures list
	 * @param array $wheres Wheres list
	 * @param array $orders Ordering list
	 * @param array $limits Limits list
	 * @return string
	**/
	public function sqlizeSelect(array $columns, array $froms, array $joins, array $wheres, array $orders, array $limits){

		//Select colum(s)
		$sql = "SELECT\n";
		$sql.= $this->sqlizeColumns($columns);

		//From table(s)
		$sql.= "FROM\n";
		$sql.= $this->sqlizeFrom($froms);

		//Add join(s)
		if(count($joins))
			$sql.= $this->sqlizeJoin($joins);

		//Add condition(s)
		if(count($wheres)){

			$sql.= "WHERE\n";
			$sql.= $this->sqlizeWhere($wheres);

		}

		//Add ordering
		if(count($orders)){

			$sql.= "ORDER BY\n";
			$sql.= $this->sqlizeOrder($orders);

		}

		//Add limit
		$sql.= $this->sqlizeLimit($limits);

		return $sql;

	}

	/**
	 * Sqlize an INSERT query
	 *
	 * @param array $froms Tables list
	 * @param array $sets Values list
	 * @return string
	**/
	public function sqlizeInsert(array $froms, array $sets){

		if(empty($sets))
			throw new Db\Exception\NoData('insert');

		//Insert into table
		$sql = "INSERT INTO\n";
		$sql.= $this->sqlizeFrom($froms);

		$sql.= $this->sqlizeValuesKeys($sets);
		$sql.= "VALUES\n";
		$sql.= $this->sqlizeValuesDatas($sets);

		return $sql;
	
	}

	/**
	 * Sqlize an UPDATE query
	 *
	 * @param array $froms Tables list
	 * @param array $sets Values list
	 * @param array $wheres Wheres list
	 * @return string
	**/
	public function sqlizeUpdate(array $froms, array $sets, array $wheres){

		if(empty($sets))
			throw new Db\Exception\NoData('update');

		//Update table
		$sql = "UPDATE\n";
		$sql.= $this->sqlizeFrom($froms);

		//Set value(s)
		$sql.= "SET\n";
		$sql.= $this->sqlizeSet($sets);

		//Add condition(s)
		if(count($wheres)){

			$sql.= "WHERE\n";
			$sql.= $this->sqlizeWhere($wheres);

		}

		return $sql;
	
	}

	/**
	 * Sqlize a DELETE query
	 *
	 * @param array $froms Tables informations
	 * @param array $wheres Wheres informations
	 * @return string
	**/
	public function sqlizeDelete(array $froms, array $wheres){
	
		$sql = "DELETE FROM\n";
		$sql.= $this->sqlizeFrom($froms);

		if(\count($wheres)){

			$sql.= "WHERE\n";
			$sql.= $this->sqlizeWhere($wheres);

		}

		return $sql;

	}

	/**
	 * Sqlize the COUNT function for Pgsql
	 *
	 * @param string $column
	 * @return string
	**/
	public function countFunction($column){

		return 'COUNT(' . $this->quoteColumn($column) . ')';
	
	}

	/**
	 * Sqlize the NOW() function for Pgsql
	 *
	 * @return string
	**/
	public function nowFunction(){

		return 'NOW()';

	}

	/**
	 * Sqlize the RAND() function for Pgsql
	 *
	 * @return string
	 **/
	public function randFunction(){

		return 'RAND()';

	}

	/**
	 * Return a Pgsql DATE_FORMAT function
	 *
	 * @param string $column Column with date
	 * @param string $format Date format
	 * @return string
	 **/
	public function dateFormatFunction($column, $format){

		return 'DATE_FORMAT(' . $column . ', "' . $format . '")';

	}

	/**
	 * Return a "defaul" ($table_$col_seq) Pgsql sequence name for table identifier
	 *
	 * @param Table $table Table
	 * @return string|null
	**/
	public function getTableSequence(\ygg\Db\Table $table){

		$name = null;

		if($table && $table->getTableIdentifier()){
			$name = $table->getTableName() . "_" . $table->getTableIdentifier() . "_seq";
		}

		return $name;

	}
}
