<?php 
	
	/**
	 * 
	 * @class TableTag
	 * @author Arnia Sowftare
	 * @brief Models the <table> tag inside an XML Query file
	 *
	 */

	class TableTag {
		var $unescaped_name;
		var $name;
		var $alias;
		var $join_type;
		var $conditions;
		
		var $dbParser;
		
		function TableTag($table, $dbParser){
			$this->dbParser = $dbParser;
			
			$this->unescaped_name = $table->attrs->name;
			$this->name = $this->dbParser->parseTableName($table->attrs->name);
			$this->alias = $table->attrs->alias;
			//if(!$this->alias) $this->alias = $alias;
			
			$this->join_type = $table->attrs->type;
			$this->conditions = $table->conditions;			
		}
		
		function isJoinTable(){
			if(in_array($this->join_type,array('left join','left outer join','right join','right outer join')) 
				&& count($this->conditions)) return true;
			return false;
		}
		
		function getTableAlias(){
			return $this->alias;
		}
		
		function getTableName(){
			return $this->unescaped_name;
		}
		
		function getTableString(){
			if($this->isJoinTable()){
				$conditionsTag = new JoinConditionsTag($this->conditions, $this->dbParser);
				return sprintf('new JoinTable(\'%s\', \'%s\', "%s", %s)'
								, $this->dbParser->escape($this->name)
								, $this->dbParser->escape($this->alias)
								, $this->join_type, $conditionsTag->toString());
			}
			return sprintf('new Table(\'%s\'%s)'
								, $this->dbParser->escape($this->name)
								, $this->alias ? ', \'' . $this->dbParser->escape($this->alias) .'\'' : '');			
		}
	}

?>