<?php 

	class LimitTag {	
		var $arguments;
		var $page;
		var $page_count;
		var $list_count;

		function LimitTag($index){
			$this->page = $index->page->attrs;
			$this->page_count = $index->page_count->attrs;
			$this->list_count = $index->list_count->attrs;

			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
			$this->arguments[] = new QueryArgument($index->page);
			$this->arguments[] = new QueryArgument($index->list_count);
		}

		function toString(){
			return sprintf("new Limit(\$%s_argument->getValue(), \$%s_argument->getValue())", $this->page->var, $this->list_count->var);
		}

		function getArguments(){
			return $this->arguments;
		}				
	}
?>