<?php 

	class Condition {
		var $column_name;
		var $argument;
		var $operation;
		var $pipe;
		
		var $_value;
		
		function Condition($column_name, $argument, $operation, $pipe = ""){
			$this->column_name = $column_name;
			$this->argument = $argument;
			$this->operation = $operation;
			$this->pipe = $pipe;
			if($this->hasArgument())
				$this->_value = $argument->getValue();
                        else if(is_a($this->argument, 'Subquery'))
                                $this->_value = $argument->toString();
			else 
				$this->_value = $argument;
		}
		
		function hasArgument(){
			return is_a($this->argument, 'Argument');
		}
		
		function getArgument(){
			if($this->hasArgument()) return $this->argument;
			return null;
		}
		
		function toString($withValue = true){
                        if(!$this->show()) return '';
			if($withValue)
				return $this->toStringWithValue();
			return $this->toStringWithoutValue();
		}
		
		function toStringWithoutValue(){
			if($this->hasArgument())
				return $this->pipe . ' ' . $this->getConditionPart("?");
			else return $this->toString();
		}
		
		function toStringWithValue(){
			return $this->pipe . ' ' . $this->getConditionPart($this->_value);
		}
		
		function setPipe($pipe){
			$this->pipe = $pipe;
		}
		
		function show(){
                    if($this->hasArgument() && !$this->argument->isValid()) return false;
		    switch($this->operation) {
                        case 'equal' :
                        case 'more' :
                        case 'excess' :
                        case 'less' :
                        case 'below' :
                        case 'like_tail' :
                        case 'like_prefix' :
                        case 'like' :
                        case 'in' :
                        case 'notin' :
                        case 'notequal' :
                                // if variable is not set or is not string or number, return
                                if(!isset($this->_value)) return false;
                                if($this->_value === '') return false;
                                if(!in_array(gettype($this->_value), array('string', 'integer'))) return false;
                                        break;
                        case 'between' :
                                                if(!is_array($this->_value)) return false;
                                                if(count($this->_value)!=2) return false;

                    }			
			return true;
		}
	
		function getConditionPart($value) {
	    	$name = $this->column_name;
	    	$operation = $this->operation;    	
	    	
                    switch($operation) {
                        case 'equal' :
                                return $name.' = '.$value;
                            break;
                        case 'more' :
                                return $name.' >= '.$value;
                            break;
                        case 'excess' :
                                return $name.' > '.$value;
                            break;
                        case 'less' :
                                return $name.' <= '.$value;
                            break;
                        case 'below' :
                                return $name.' < '.$value;
                            break;
                        case 'like_tail' :
                        case 'like_prefix' :
                        case 'like' :
                                return $name.' like '.$value;
                            break;
                        case 'in' :
                                return $name.' in '.$value;
                            break;
                        case 'notin' :
                                return $name.' not in '.$value;
                            break;
                        case 'notequal' :
                                return $name.' <> '.$value;
                            break;
                        case 'notnull' :
                                return $name.' is not null';
                            break;
                        case 'null' :
                                return $name.' is null';
                            break;
                                        case 'between' :
                        return $name.' between ' . $value[0] . ' and ' . $value[1];
					break;
            }
        }		
	}

?>