<?php 
	class Limit {
		var $start;
		var $list_count;
		var $page_count;
		var $page;

		function Limit($list_count, $page= NULL, $page_count= NULL){
			$this->list_count = $list_count;
			if ($page){
				$list_count_value = $list_count->getValue();
				$page_value = $page->getValue();
				$this->start = ($page_value - 1) * $list_count_value;
				$this->page_count = $page_count;
				$this->page = $page;
			}			
		}
		
		function isPageHandler(){//in case you choose to use query limit in other cases than page select
			if ($this->page)return true;
			else return false;
		}
		
		function getOffset(){
			return $this->start;
		}
		
		function getLimit(){
			return $this->list_count->getValue();
		}
		
		function toString(){
			if ($this->page) return $this->start . ' , ' . $this->list_count->getValue();
			else return $this->list_count->getValue();
		}
	}
?>