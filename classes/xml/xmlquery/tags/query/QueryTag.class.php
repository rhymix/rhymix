<?php

class QueryTag {
	var $action;
	var $query_id;
	var $priority;
	var $column_type;
	var $query;

	//xml tags
	var $columns;
	var $tables;
	var $conditions;
	var $groups;
	var $navigation;
	var $arguments;
	var $preBuff;
	var $buff;
	var $isSubQuery;

        var $join_type;
	var $alias;

	function QueryTag($query, $isSubQuery = false){
		$this->action = $query->attrs->action;
		$this->query_id = $query->attrs->id;
		$this->priority = $query->attrs->priority;
		$this->query = $query;
		$this->isSubQuery = $isSubQuery;
		if($this->isSubQuery) $this->action = 'select';
                if($query->attrs->alias){
                    $dbParser = DB::getParser();
                    $this->alias = $dbParser->escape($query->attrs->alias);
                }
                $this->join_type = $query->attrs->join_type;

		$this->getColumns();
		$tables = $this->getTables();
		$this->setTableColumnTypes($tables);
		$this->getConditions();
		$this->getGroups();
		$this->getNavigation();
		$this->getPrebuff();
		$this->getBuff();
	}

	function show(){
		return true;
	}

	function getQueryId(){
		return $this->query->attrs->query_id ? $this->query->attrs->query_id : $this->query->attrs->id;
	}

	function getPriority(){
		return $this->query->attrs->priority;
	}

	function getAction(){
		return $this->query->attrs->action;
	}

	function setTableColumnTypes($tables){
		$query_id = $this->getQueryId();
		if(!isset($this->column_type[$query_id])){
			$table_tags = $tables->getTables();
			$column_type = array();
			foreach($table_tags as $table_tag){
                            if(is_a($table_tag, 'TableTag')){
				$tag_column_type = QueryParser::getTableInfo($query_id, $table_tag->getTableName());
				$column_type = array_merge($column_type, $tag_column_type);
                            }
			}
			$this->column_type[$query_id] = $column_type;
		}
	}

	function getColumns(){
		if($this->action == 'select'){
			return $this->columns =  new SelectColumnsTag($this->query->columns);
		}else if($this->action == 'insert'){
			return $this->columns =  new InsertColumnsTag($this->query->columns->column);
		}else if($this->action == 'update') {
			return $this->columns =  new UpdateColumnsTag($this->query->columns->column);
		}else if($this->action == 'delete') {
			return $this->columns =  null;
		}
	}

	function getPrebuff(){
		// TODO Check if this work with arguments in join clause
		$arguments = $this->getArguments();

		$prebuff = '';
		foreach($arguments as $argument){
                    if(isset($argument)){
                        $arg_name = $argument->getArgumentName();
                        if($arg_name){
                            $prebuff .= $argument->toString();
                            $column_type = $this->column_type[$this->getQueryId()][$argument->getColumnName()];
                            if(isset($column_type))
                                $prebuff .= sprintf('if(${\'%s_argument\'} !== null) ${\'%s_argument\'}->setColumnType(\'%s\');' . "\n"
                                        , $arg_name
                                        , $arg_name
                                        , $column_type );
                        }
                    }
		}
		$prebuff .= "\n";

		return $this->preBuff = $prebuff;
	}

	function getBuff(){
		$buff = '';
		if($this->isSubQuery){
			$buff = 'new Subquery(';
			$buff .= "'" . $this->alias . '\', ';
			$buff .=  ($this->columns ? $this->columns->toString() : 'null' ). ', '.PHP_EOL;
                        $buff .=  $this->tables->toString() .','.PHP_EOL;
                        $buff .=  $this->conditions->toString() .',' .PHP_EOL;
                        $buff .=  $this->groups->toString() . ',' .PHP_EOL;
                        $buff .=  $this->navigation->getOrderByString() .','.PHP_EOL;
                        $limit =  $this->navigation->getLimitString() ;
			$buff .=  $limit ? $limit : 'null' . PHP_EOL;
                        $buff .=  $this->join_type ? "'" . $this->join_type . "'" : '';
			$buff .= ')';

			$this->buff = $buff;
			return $this->buff;
		}

		$buff .= '$query = new Query();'.PHP_EOL;
		$buff .= sprintf('$query->setQueryId("%s");%s', $this->query_id, "\n");
		$buff .= sprintf('$query->setAction("%s");%s', $this->action, "\n");
		$buff .= sprintf('$query->setPriority("%s");%s', $this->priority, "\n");
		$buff .= $this->preBuff;
		if($this->columns)
			$buff .= '$query->setColumns(' . $this->columns->toString() . ');'.PHP_EOL;

        $buff .= '$query->setTables(' . $this->tables->toString() .');'.PHP_EOL;
        $buff .= '$query->setConditions('.$this->conditions->toString() .');'.PHP_EOL;
       	$buff .= '$query->setGroups(' . $this->groups->toString() . ');'.PHP_EOL;
       	$buff .= '$query->setOrder(' . $this->navigation->getOrderByString() .');'.PHP_EOL;
		$buff .= '$query->setLimit(' . $this->navigation->getLimitString() .');'.PHP_EOL;

		$this->buff = $buff;
		return $this->buff;
	}

	function getTables(){
                if($this->query->index_hint->attrs->for == 'ALL' || Context::getDBType() == strtolower($this->query->index_hint->attrs->for))
                    return $this->tables = new TablesTag($this->query->tables, $this->query->index_hint);
                else
                    return $this->tables = new TablesTag($this->query->tables);
	}

	function getConditions(){
		return $this->conditions = new ConditionsTag($this->query->conditions);
	}

	function getGroups(){
		return $this->groups = new GroupsTag($this->query->groups->group);
	}

	function getNavigation(){
		return $this->navigation = new NavigationTag($this->query->navigation);
	}

	function toString(){
		return $this->buff;
	}

	function getTableString(){
		return $this->buff;
	}

	function getConditionString(){
		return $this->buff;
	}

	function getExpressionString(){
		return $this->buff;
	}


	function getArguments(){
		$arguments = array();
		if($this->columns)
			$arguments = array_merge($arguments, $this->columns->getArguments());
                $arguments = array_merge($arguments, $this->tables->getArguments());
		$arguments = array_merge($arguments, $this->conditions->getArguments());
		$arguments = array_merge($arguments, $this->navigation->getArguments());
		return $arguments;
	}

}
?>
