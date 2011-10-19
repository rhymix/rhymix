<?php

	class DefaultValue {
		var $column_name;
		var $value;
		var $is_sequence = false;
                var $is_operation = false;
                var $operation = '';

                var $_is_string = false;

		function DefaultValue($column_name, $value){
                        $dbParser = &DB::getParser();
			$this->column_name = $dbParser->parseColumnName($column_name);
			$this->value = $value;
                        $this->value = $this->_setValue();
		}

		function isString(){
                    return $this->_is_string;
                    $str_pos = strpos($this->value, '(');
                    if($str_pos===false) return true;
                    return false;
		}

                function isSequence(){
                    return $this->is_sequence;
                }

                function isOperation(){
                    return $this->is_operation;
                }

                function getOperation(){
                    return $this->operation;
                }

                function _setValue(){
			if(!isset($this->value)) return;

			// If value contains comma separated values and does not contain paranthesis
			//  -> default value is an array
			if(strpos($this->value, ',') !== false && strpos($this->value, '(') === false) {
				return sprintf('array(%s)', $this->value);
			}

                        $str_pos = strpos($this->value, '(');
                        // // TODO Replace this with parseExpression
                        if($str_pos===false) {
                            $this->_is_string = true;
                            return '\''.$this->value.'\'';
                        }
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
                                                $this->is_sequence = true;
						$val = '$sequence';
					break;
				case 'plus' :
						$args = abs($args);
                                                $this->is_operation = true;
                                                $this->operation = '+';
						$val = sprintf('%d', $args);
					break;
				case 'minus' :
						$args = abs($args);
                                                $this->is_operation = true;
                                                $this->operation = '-';
						$val = sprintf('%d', $args);
					break;
				case 'multiply' :
						$args = intval($args);
                                                $this->is_operation = true;
                                                $this->operation = '*';
						$val = sprintf('%d', $args);
					break;
				default :
						$val = '\'' . $this->value . '\'';
						//$val = $this->value;
			}

			return $val;
                }

		function toString(){
                        return $this->value;
		}
	}

?>