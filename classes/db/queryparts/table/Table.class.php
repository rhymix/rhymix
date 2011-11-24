<?php 

	class Table {
		var $name;
		var $alias;
		
		function Table($name, $alias = NULL){
			$this->name = $name;
			$this->alias = $alias;
		}
		
		function toString(){
                    //return $this->name;
                    return sprintf("%s%s", $this->name, $this->alias ? ' as ' . $this->alias : '');
		}
		
		function getName(){
			return $this->name;
		}
		
		function getAlias(){
			return $this->alias;
		}
		
		function isJoinTable(){
                    return false;
		}
	}

?>