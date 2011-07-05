<?php 

	class IndexTag {	
		var $argument_name;
		var $argument;
		var $default;
		var $sort_order;
		var $sort_order_argument;
		
		function IndexTag($index){
			$this->argument_name = $index->attrs->var;
			
                        // Sort index - column by which to sort
			//$dbParser = XmlQueryParser::getDBParser();
			//$index->attrs->default = $dbParser->parseExpression($index->attrs->default);
			$this->default = $index->attrs->default;
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
			$this->argument = new QueryArgument($index);			
                        
                        // Sort order - asc / desc
			$this->sort_order = $index->attrs->order;
			if(!in_array($this->sort_order, array("asc", "desc"))){
				$arg->var = $this->sort_order;
				$arg->default = '"asc"';
				$this->sort_order_argument = new QueryArgument($arg);
				$this->sort_order = "\$args->".$this->sort_order;
			}
			else $this->sort_order = '"'.$this->sort_order.'"';
		}
		
		function toString(){
			return sprintf("new OrderByColumn(\$%s_argument, %s)", $this->argument_name, $this->sort_order);
		}

		function getArguments(){
			$arguments = array();
			$arguments[] = $this->argument;
			if($this->sort_order_argument)
				$arguments[] = $this->sort_order_argument;
			return $arguments;
		}				
	}

?>