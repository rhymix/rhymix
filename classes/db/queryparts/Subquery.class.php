<?php

	class Subquery extends Query {
		var $alias;
		var $join_type;

		function Subquery($alias, $columns, $tables, $conditions, $groups, $orderby, $limit, $join_type = null){
			$this->alias = $alias;

			$this->queryID = null;
			$this->action = "select";

			$this->columns = $columns;
			$this->tables = $tables;
			$this->conditions = $conditions;
			$this->groups = $groups;
			$this->orderby = $orderby;
			$this->limit = $limit;
                        $this->join_type = $join_type;
		}

		function getAlias(){
			return $this->alias;
		}

                function isJoinTable(){
                    if($this->join_type) return true;
                    return false;
                }

                function toString($with_values = true){
                    $oDB = &DB::getInstance();
                    return '(' .$oDB->getSelectSql($this, $with_values) . ')';

                }

                function isSubquery(){
                    return true;
                }
	}

?>