<?php
	/**
	 * DBParser class
	 * @author NHN (developers@xpressengine.com)
	 * @package /classes/xml/xmlquery
	 * @version 0.1
	 */
	class DBParser {
		/**
		 * Character for escape target value on the left
		 * @var string
		 */
		var $escape_char_left;
		/**
		 * Character for escape target value on the right
		 * @var string
		 */
		var $escape_char_right;
		/**
		 * Table prefix string
		 * @var string
		 */
		var $table_prefix;
		
		/**
		 * constructor
		 * @param string $escape_char_left
		 * @param string $escape_char_right
		 * @param string $table_prefix
		 * @return void
		 */
		function DBParser($escape_char_left,  $escape_char_right = "", $table_prefix = "xe_"){
			$this->escape_char_left = $escape_char_left;
			if ($escape_char_right !== "")$this->escape_char_right = $escape_char_right;
			else $this->escape_char_right = $escape_char_left;
			$this->table_prefix = $table_prefix;
		}
		
		/**
		 * Get escape character
		 * @param string $leftOrRight left or right
		 * @return string
		 */
		function getEscapeChar($leftOrRight){
			if ($leftOrRight === 'left')return $this->escape_char_left;
			else return $this->escape_char_right;
		}
		
		/**
		 * escape the value
		 * @param mixed $name
		 * @return string
		 */
		function escape($name){
			return $this->escape_char_left . $name . $this->escape_char_right;
		}		
		
		/**
		 * escape the string value
		 * @param string $name
		 * @return string
		 */
		function escapeString($name){
			return "'".$this->escapeStringValue($name)."'";
		}
		
		/**
		 * escape the string value
		 * @param string $value
		 * @return string
		 */
		function escapeStringValue($value){
			if($value == "*")	return $value;
			if (is_string($value))	return $value = str_replace("'","''",$value);
			return $value;
		}
		
		/**
		 * Return table full name
		 * @param string $name table name without table prefix
		 * @return string table full name with table prefix
		 */
		function parseTableName($name){
			return $this->table_prefix . $name;
		}
		
		/**
		 * Return colmun name after escape
		 * @param string $name column name before escape
		 * @return string column name after escape
		 */
		function parseColumnName($name){
			return $this->escapeColumn($name);
		}
		
		/**
		 * Escape column
		 * @param string $column_name
		 * @return string column name with db name
		 */
		function escapeColumn($column_name){
			if($this->isUnqualifiedColumnName($column_name))
				return $this->escape($column_name);
			if($this->isQualifiedColumnName($column_name)){
				list($table, $column) = explode('.', $column_name);
				// $table can also be an alias, so the prefix should not be added
				return $this->escape($table).'.'.$this->escape($column);
				//return $this->escape($this->parseTableName($table)).'.'.$this->escape($column);
			}
		}

		/**
		 * Column name is suitable for use in checking
		 * @param string $column_name
		 * @return bool
		 */
		function isUnqualifiedColumnName($column_name){
			if(strpos($column_name,'.')===false && strpos($column_name,'(')===false) return true;
			return false;	
		}
		
		/**
		 * Column name is suitable for use in checking
		 * @param string $column_name
		 * @return bool
		 */
		function isQualifiedColumnName($column_name){
			if(strpos($column_name,'.')!==false && strpos($column_name,'(')===false) return true;
			return false;		
		}

		function parseExpression($column_name){
			$functions = preg_split('/([\+\-\*\/\ ])/', $column_name, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
			foreach($functions as $k => $v){
                $function = &$functions[$k];
				if(strlen($function)==1) continue; // skip delimiters
				$pos = strrpos("(", $function);
				$matches = preg_split('/([a-zA-Z0-9_*]+)/', $function, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$total_brackets = substr_count($function, "(");
				$brackets = 0;
				foreach($matches as $i => $j){
					$match = &$matches[$i];
					if($match == '(') {$brackets++; continue;}
					if(strpos($match,')') !== false) continue;
					if(in_array($match, array(',', '.'))) continue;
					if($brackets == $total_brackets){
						if(!is_numeric($match)) {
							$match = $this->escapeColumnExpression($match);	
						}
					}
				}
				$function = implode('', $matches);
			}	
			return implode('', $functions);				
		}
		
		/*
		 * Checks argument is asterisk
		 * @param string $column_name
		 * @return bool
		 */
		function isStar($column_name){
			if(substr($column_name,-1) == '*') return true;
			return false;
		}
		
		/*
		 * Checks to see if expression is an aggregate star function
		 * like count(*)
		 * @param string $column_name
		 * @return bool
		 */
		function isStarFunction($column_name){
			if(strpos($column_name, "(*)")!==false) return true;
			return false;
		}
		
		/*
		 * Return column name after escape
		 * @param string $column_name
		 * @return string
		 */
		function escapeColumnExpression($column_name){
			if($this->isStar($column_name)) return $column_name;
			if($this->isStarFunction($column_name)){
				return $column_name;
			}
			if(strpos(strtolower($column_name), 'distinct') !== false) return $column_name;
			return $this->escapeColumn($column_name);			
		}				
	}
	
	
