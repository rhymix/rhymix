<?php 
	class Limit {
		var $start;
		var $end;
		
		function Limit($page, $list_count){
			$this->start = ($page-1)*$list_count;
			$this->end = $list_count;
		}
		
		function toString(){
			return $this->start . ' , ' . $this->end;
		}
	}

?>