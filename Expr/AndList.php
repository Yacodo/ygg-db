<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Query;
use ygg\Db\Driver;

class AndList implements WhereList {

	public function __construct(){
	
		parent::__construct(Query::OP_AND);
	
	}

	public function orWhere(){

		$list = new OrList();
		$this->add('', $list);

		return $list;
	
	}

}
