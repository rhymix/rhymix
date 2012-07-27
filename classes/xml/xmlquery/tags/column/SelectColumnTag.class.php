<?php
	/**
	 * SelectColumnTag
	 * Models the <column> tag inside an XML Query file whose action is 'select'
	 *
	 * @author Arnia Software
	 * @package /classes/xml/xmlquery/tags/column
	 * @version 0.1
	 */
	class SelectColumnTag extends ColumnTag{
		/**
		 * alias
		 * @var string
		 */
		var $alias;
		/**
		 * click count status
		 * @var bool
		 */
		var $click_count;
		
		/**
		 * constructor
		 * @param string|object $column
		 * @return void
		 */
		function SelectColumnTag($column){
			if ($column == "*" || $column->attrs->name == '*')
			{
				parent::ColumnTag(NULL);
				$this->name = "*";
			}
			else
			{
				parent::ColumnTag($column->attrs->name);
				$dbParser = new DB(); $dbParser = &$dbParser->getParser();
				$this->name = $dbParser->parseExpression($this->name);
				
				$this->alias = $column->attrs->alias;
				$this->click_count = $column->attrs->click_count;
			}
		}
		
		function getExpressionString(){
			if($this->name == '*') return "new StarExpression()";
			if($this->click_count)
				return sprintf('new ClickCountExpression(%s, %s, $args->%s)', $this->name, $this->alias,$this->click_count);
			if(strpos($this->name, '$') === 0)
					return sprintf('new SelectExpression($args->%s)', substr($this->name, 1));
			$dbParser = DB::getParser();
			return sprintf('new SelectExpression(\'%s\'%s)', $this->name, $this->alias ? ', \''.$dbParser->escape($this->alias) .'\'': '');	
		}
	}
