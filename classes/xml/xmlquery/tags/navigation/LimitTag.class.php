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

			$this->list_count = new QueryArgument($index->list_count);
			$this->arguments[] = $this->list_count;
		}

		function toString(){
                        $name = $this->list_count->getArgumentName();
			if ($this->page)return sprintf("new Limit(\$%s_argument, \$%s_argument, \$%s_argument)",$name, $name,  $name);
			else return sprintf("new Limit(\$%s_argument)", $name);
		}

		function getArguments(){
			return $this->arguments;
		}
	}
?>