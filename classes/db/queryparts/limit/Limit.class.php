<?php 
	class Limit {
		var $start;
		var $list_count;
		var $page_count;
		var $page;

		function Limit($page, $list_count, $page_count){
			$this->start = ($page-1)*$list_count;
			$this->list_count = $list_count;
			$this->page_count = $page_count;
			$this->page = $page;
		}
		
		function isPageHandler(){//in case you choose to use query limit in other cases than page select
			return true;
		}
		
		function toString(){
			return $this->start . ' , ' . $this->list_count;
		}
	}

?>