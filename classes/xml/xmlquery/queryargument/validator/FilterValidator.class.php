<?php 
	class FilterValidator extends Validator {
		var $argument_name;
		var $filter;
		
		function FilterValidator($argument_name, $filter) {
			$this->argument_name = $argument_name;
			$this->filter = $filter;
		}
		
		function toString(){		
			return sprintf('if(isset($args->%s)) { unset($_output); $_output = $this->checkFilter("%s",$args->%s,"%s"); if(!$_output->toBool()) return $_output; } %s',$this->argument_name, $this->argument_name,$this->argument_name,$this->filter,"\n");
		}
	}

?>