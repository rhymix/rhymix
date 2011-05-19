<?php 
	// TODO This is temporary for when column types will be
	// used to prepare input
	
	class EscapeCheck {
		var $argument_name;
				
		function EscapeCheck($argument_name){
			$this->argument_name = $argument_name;
		}
			
		function toString(){
			return sprintf("if(is_string(\$args->%s) && !is_numeric(\$args->%s)) \$args->%s = \$dbParser->escapeString(\$args->%s);\n"
					, $this->argument_name
					, $this->argument_name
					, $this->argument_name
					, $this->argument_name); 	
		}		
	}

?>