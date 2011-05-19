<?php 

	class DefaultCheck extends Validator {
		var $argument_name;
		var $value;
		
		function DefaultCheck($argument_name, $value) {
			$this->argument_name = $argument_name;
			$this->value = $value;
		}
		
		function toString(){		
			if(!isset($this->argument_name)) return '';
			
			$value = $this->value->toString();
			
	        if($this->value->isString()) {
	        	$value = "'".$value."'";
	        }
			
			return 'if(!isset($args->'.$this->argument_name.')) $args->'.$this->argument_name.' = '.$value.';'."\n";
		}
	}
	
?>