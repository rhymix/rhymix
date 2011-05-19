<?php 
	class OrderByColumn {
		var $column_name;
		var $sort_order;
		
		function OrderByColumn($column_name, $sort_order){
			$this->column_name = $column_name;
			$this->sort_order = $sort_order;
		}
		
		function toString(){
			return $this->column_name . ' ' . $this->sort_order;
		}
	}

?>