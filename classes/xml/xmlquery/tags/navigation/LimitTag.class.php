<?php 

	class LimitTag {	
		var $arguments;
		var $page;
		var $page_count;
		var $list_count;

		function LimitTag($index){
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
			
			if($index->page->attrs && $index->page_count->attrs){
				$this->page = $index->page->attrs;
				$this->page_count = $index->page_count->attrs;
				$this->arguments[] = new QueryArgument($index->page);
				$this->arguments[] = new QueryArgument($index->page_count);
			}

			$this->list_count = $index->list_count->attrs;
			$this->arguments[] = new QueryArgument($index->list_count);
		}

		function toString(){
			if ($this->page)return sprintf("new Limit(\$%s_argument, \$%s_argument, \$%s_argument)",$this->list_count->var, $this->page->var,  $this->page_count->var);
			else return sprintf("new Limit(\$%s_argument)", $this->list_count->var);
		}

		function getArguments(){
			return $this->arguments;
		}				
	}
?>