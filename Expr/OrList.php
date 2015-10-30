<?php

namespace ygg\Db\Expr;
use ygg\Db\Expr;
use ygg\Db\Query;
use ygg\Db\Driver;

class OrList extends WhereList {

	public function __construct(){
	
		parent::__construct(Query::OP_OR);
	
	}
	
	public function where(){

		$list = new AndList();
		$this->add('', $list);

		return $list;
	
	}

}
