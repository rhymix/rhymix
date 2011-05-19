<?php

    /**
     * @class SelectColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'select'
     *
     **/

	class SelectColumnTag extends ColumnTag{
		var $alias;
		var $click_count;
		
		function SelectColumnTag($column, $dbParser){
			parent::ColumnTag($column->attrs->name, $dbParser);
			if(!$this->name) $this->name = "*";			
			if($this->name != "*") 
				$this->name = $this->dbParser->parseExpression($this->name);
				
			$this->alias = $column->attrs->alias;
			$this->click_count = $column->attrs->click_count;
		}
		
		function getExpressionString(){
			if($this->name == '*') return "new StarExpression()";
			if($this->click_count)
				return sprintf('new ClickCountExpression(%s, %s, $args->%s)', $this->name, $this->alias,$this->click_count);
			return sprintf('new SelectExpression(\'%s\'%s)', $this->name, $this->alias ? ', \''.$this->dbParser->escape($this->alias) .'\'': '');	
		}
	}
?>