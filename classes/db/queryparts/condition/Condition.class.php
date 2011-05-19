<?php 

	class Condition {
		var $column_name;
		var $value;
		var $operation;
		var $pipe;
		
		function Condition($column_name, $value, $operation, $pipe = ""){
			$this->column_name = $column_name;
			$this->value = $value;
			$this->operation = $operation;
			$this->pipe = $pipe;
		}
		
		function toString(){
			return $this->pipe . ' ' . $this->getConditionPart($this->column_name, $this->value, $this->operation);
		}
		
	    function getConditionPart($name, $value, $operation) {
            switch($operation) {
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
                        if(!isset($value)) return;
                        if($value === '') return;
                        if(!in_array(gettype($value), array('string', 'integer'))) return;
				break;
                case 'between' :
					if(!is_array($value)) return;
					if(count($value)!=2) return;

            }

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
                        return $name.' in ('.$value.')';
                    break;
                case 'notin' :
                        return $name.' not in ('.$value.')';
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