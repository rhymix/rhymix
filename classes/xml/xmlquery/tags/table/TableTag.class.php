<?php 
	
	/**
	 * @class TableTag
	 * @author Arnia Sowftare
	 * @brief Models the <table> tag inside an XML Query file
	 *
         * @abstract
         *   Example
         *      <table name="modules" />
         *      <table name="documents" alias="doc" />
         *   Attributes
         *      name - name of the table - table prefix will be automatically added
         *      alias - table alias. If no value is specified, the table name will be set as default alias
         *      join_type - in case the table is part of a join clause, this specifies the type of join: left, right etc.
         *                - permitted values: 'left join','left outer join','right join','right outer join'
         *   Children
         *      Can have children of type <conditions>
	 */

	class TableTag {
		var $unescaped_name;
		var $name;
		var $alias;
		var $join_type;
		var $conditions;
		var $conditionsTag;
                /**
                 * @brief Initialises Table Tag properties
                 * @param XML <table> tag $table
                 */
		function TableTag($table){
			$dbParser = DB::getParser();
                        
                        $this->unescaped_name = $table->attrs->name;
			$this->name = $dbParser->parseTableName($table->attrs->name);
			
                        $this->alias = $table->attrs->alias;
			if(!$this->alias) $this->alias = $table->attrs->name; 
			
			$this->join_type = $table->attrs->type;
			
                        $this->conditions = $table->conditions;			
                        
                        if($this->isJoinTable())
                            $this->conditionsTag = new JoinConditionsTag($this->conditions);
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
		
                /**
                 * @brief Returns string for printing in PHP query cache file
                 * The string contains code for instantiation of either 
                 * a Table or a JoinTable object
                 * @return string 
                 */
		function getTableString(){
			$dbParser = DB::getParser();
			if($this->isJoinTable()){
				return sprintf('new JoinTable(\'%s\', \'%s\', "%s", %s)'
								, $dbParser->escape($this->name)
								, $dbParser->escape($this->alias)
								, $this->join_type, $this->conditionsTag->toString());
			}
			return sprintf('new Table(\'%s\'%s)'
								, $dbParser->escape($this->name)
								, $this->alias ? ', \'' . $dbParser->escape($this->alias) .'\'' : '');			
		}
                
                function getArguments(){
                    if(!isset($this->conditionsTag)) return array();
                    return $this->conditionsTag->getArguments();
                }
	}

?>