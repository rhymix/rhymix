<?php 

	class DefaultValue {
		var $column_name;
		var $value;
		
		function DefaultValue($column_name, $value){
			$this->column_name = $column_name;
			$this->value = $value;
		}
		
		function isString(){
			$str_pos = strpos($this->value, '(');
	        if($str_pos===false) return true;
	        return false;			
		}
		
		function toString(){
			if(!isset($this->value)) return;
			
			// If value contains comma separated values and does not contain paranthesis
			//  -> default value is an array
			if(strpos($this->value, ',') !== false && strpos($this->value, '(') === false) {
				return sprintf('array(%s)', $this->value);
			}
			
	        $str_pos = strpos($this->value, '(');
	        // // TODO Replace this with parseExpression
	        if($str_pos===false) return '\''.$this->value.'\'';
	        //if($str_pos===false) return $this->value;
	
	        $func_name = substr($this->value, 0, $str_pos);
	        $args = substr($this->value, $str_pos+1, strlen($value)-1);
	
			switch($func_name) {
				case 'ipaddress' :
						$val = '$_SERVER[\'REMOTE_ADDR\']';
					break;
				case 'unixtime' :
						$val = 'time()';
					break;
				case 'curdate' :
						$val = 'date("YmdHis")';
					break;
				case 'sequence' :
						$val = '$this->getNextSequence()';
					break;
				case 'plus' :
						$args = abs($args);
						// TODO Make sure column name is escaped
						$val = sprintf('"%s+%d"', $this->column_name, $args);
					break;
				case 'minus' :
						$args = abs($args);
						$val = sprintf('"%s-%d"', $this->column_name, $args);
						break;
				case 'multiply' :
						$args = intval($args);
						$val = sprintf('"%s*%d"', $this->column_name, $args);
					break;
				default :
						$val = '\'' . $this->value . '\'';
						//$val = $this->value;
			}
	
			return $val;		
		}
	}

?>