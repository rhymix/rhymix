<?php 

	class Table {
		var $name;
		var $alias;
		
		function Table($name, $alias = NULL){
			$this->name = $name;
			$this->alias = $alias;
		}
		
		function toString(){
			return sprintf("%s%s", $this->name, $this->alias ? ' as ' . $this->alias : '');
		}
		
		function getName(){
			return $this->name;
		}
		
		function getAlias(){
			return $this->alias;
		}
		
		function isJoinTable(){
			if(in_array($tableName,array('left join','left outer join','right join','right outer join'))) return true;
			return false;
		}
	}

?>