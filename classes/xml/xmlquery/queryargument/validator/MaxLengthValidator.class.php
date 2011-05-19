<?php 
	
	class MaxLengthValidator extends Validator {
		var $argument_name;
		var $value;
		
		function MaxLengthValidator($argument_name, $value) {
			$this->argument_name = $argument_name;
			$this->value = $value;
		}
		
		function toString(){		
			return 'if($args->'
						.$this->argument_name
						.'&&strlen($args->'.$this->argument_name.')>'.$this->value
						.') return new Object(-1, sprintf($lang->filter->outofrange, $lang->'
						.$this->argument_name.'?$lang->'
						.$this->argument_name.':\''.$this->argument_name.'\'));'."\n";	
		}
	}

?>