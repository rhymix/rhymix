<?php 
	class ConditionQueryArgumentValidator extends QueryArgumentValidator {
		
		function ConditionQueryArgumentValidator($tag){
			parent::QueryArgumentValidator($tag);
		}
		
		function toString(){
			if(!$this->argument_name) return '';
			if(!isset($this->validator_string)){
				$validator = parent::toString();
				require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/validator/EscapeCheck.class.php');
				$v = new EscapeCheck($this->argument_name);
				$validator .= $v->toString();
				$this->validator_string = $validator;
			}
			return $this->validator_string;
		}
	}
?>