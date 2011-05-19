<?php
	class DBParser {
		var $escape_char;
		var $table_prefix;
		
		function DBParser($escape_char, $table_prefix = "xe_"){
			$this->escape_char = $escape_char;
			$this->table_prefix = $table_prefix;
		}
		
		function getEscapeChar(){
			return $this->escape_char;
		}
		
		function escape($name){
			return $this->escape_char . $name . $this->escape_char;
		}		
		
		function escapeString($name){
			return "'".$name."'";
		}
		
		function parseTableName($name){
			return $this->table_prefix . $name;
		}
		
		function parseColumnName($name){
			return $this->escapeColumn($name);
		}
		
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

		function isUnqualifiedColumnName($column_name){
			if(strpos($column_name,'.')===false && strpos($column_name,'(')===false) return true;
			return false;	
		}
		
		function isQualifiedColumnName($column_name){
			if(strpos($column_name,'.')!==false && strpos($column_name,'(')===false) return true;
			return false;		
		}

		function parseExpression($column_name){
			$functions = preg_split('/([\+\-\*\/\ ])/', $column_name, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
			foreach($functions as &$function){
				if(strlen($function)==1) continue; // skip delimiters
				$pos = strrpos("(", $function);
				$matches = preg_split('/([a-zA-Z0-9_*]+)/', $function, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$total_brackets = substr_count($function, "(");
				$brackets = 0;
				foreach($matches as &$match){
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
		
		function isStar($column_name){
			if(substr($column_name,-1) == '*') return true;
			return false;
		}
		
		/*
		 * Checks to see if expression is an aggregate star function
		 * like count(*)
		 */
		function isStarFunction($column_name){
			if(strpos($column_name, "(*)")!==false) return true;
			return false;
		}
		
		function escapeColumnExpression($column_name){
			if($this->isStar($column_name)) return $column_name;
			if($this->isStarFunction($column_name)){
				return $column_name;
			}
			return $this->escapeColumn($column_name);			
		}				
	}
	
	