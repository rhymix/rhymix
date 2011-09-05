<?php

	class LimitTag {
		var $arguments;
		var $page;
		var $page_count;
		var $list_count;

		function LimitTag($index){
			if($index->page->attrs && $index->page_count->attrs){
				$this->page = new QueryArgument($index->page);
				$this->page_count = new QueryArgument($index->page_count);
				$this->arguments[] = $this->page;
				$this->arguments[] = $this->page_count;
			}

			$this->list_count = new QueryArgument($index->list_count);
			$this->arguments[] = $this->list_count;
		}

		function toString(){
			if ($this->page)return sprintf("new Limit(\$%s_argument, \$%s_argument, \$%s_argument)", $this->list_count->getArgumentName(), $this->page->getArgumentName(),  $this->page_count->getArgumentName());
			else return sprintf("new Limit(\$%s_argument)", $this->list_count->getArgumentName());
		}

		function getArguments(){
			return $this->arguments;
		}
	}
?>