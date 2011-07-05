<?php 

	class ConditionArgument extends Argument {
		var $operation;
		
                
		function ConditionArgument($name, $value, $operation){
			parent::Argument($name, $value);
			$this->operation = $operation;	
                        
			if($this->type !== 'date'){
				$dbParser = XmlQueryParser::getDBParser();
				$this->value = $dbParser->escapeStringValue($this->value);
			}	                        
		}
			
		function createConditionValue(){
			if(!isset($this->value)) return;
			
	    	$name = $this->column_name;
	    	$operation = $this->operation;
	    	$value = $this->value;			
	    	
	    	switch($operation) {
                case 'like_prefix' :
                        $this->value =  $value.'%';
                    break;
                case 'like_tail' :
                        $this-> value = '%'.$value;
                    break;
                case 'like' :
                        $this->value = '%'.$value.'%';
                    break;
                case 'in' :
						if(is_array($value))
						{
							//$value = $this->addQuotesArray($value);
							//if($type=='number') return join(',',$value);
							//else 
							//$this->value =  "['". join("','",$value)."']";
						}
						else
						{
							$this->value = $value;
						}
                    break;                    
	    	}
			/*
	    	//if(!in_array($operation,array('in','notin','between')) && is_array($value)){
	    	//	$value = join(',', $value);
	    	//}
	    	// Daca operatia nu este in, notin, between si coloana e de tip numeric
	    		// daca valoarea e array -> concatenare
	    		// daca valoarea nu e array si nici nu contine paranteze (nu e functie) -> return (int)
	    		// altfel return valoare
	    		
//            if(!in_array($operation,array('in','notin','between')) && $type == 'number') {
//				if(is_array($value)){
//					$value = join(',',$value);
//				}
//                if(strpos($value, ',') === false && strpos($value, '(') === false) return (int)$value;
//                return $value;
//            }
//			
//            if(!is_array($value) && strpos($name, '.') !== false && strpos($value, '.') !== false) {
//                list($table_name, $column_name) = explode('.', $value);
//                if($column_type[$column_name]) return $value;
//            }

            switch($operation) {
                case 'like_prefix' :
						if(!is_array($value)) $value = preg_replace('/(^\'|\'$){1}/', '', $value);
                        $value = $value.'%';
                    break;
                case 'like_tail' :
						if(!is_array($value)) $value = preg_replace('/(^\'|\'$){1}/', '', $value);
                        $value = '%'.$value;
                    break;
                case 'like' :
						if(!is_array($value)) $value = preg_replace('/(^\'|\'$){1}/', '', $value);
                        $value = '%'.$value.'%';
                    break;
//                case 'notin' :
//						if(is_array($value))
//						{
//							$value = $this->addQuotesArray($value);
//							if($type=='number') return join(',',$value);
//							else return "'". join("','",$value)."'";
//						}
//						else
//						{
//							return $value;
//						}
//                    break;
//                case 'in' :
//						if(is_array($value))
//						{
//							$value = $this->addQuotesArray($value);
//							if($type=='number') return join(',',$value);
//							else return "'". join("','",$value)."'";
//						}
//						else
//						{
//							return $value;
//						}
//                    break;
//                case 'between' :
//						if(!is_array($value)) $value = array($value);
//			            $value = $this->addQuotesArray($value);
//						if($type!='number')
//						{
//							foreach($value as $k=>$v)
//							{
//								$value[$k] = "'".$v."'";
//							}
//						}

						//return $value;
                    break;
				default:
					if(!is_array($value)) $value = preg_replace('/(^\'|\'$){1}/', '', $value);
            }
			$this->value = $value;
            //return "'".$this->addQuotes($value)."'";			
			*/
	    	
		}
	
                function getType(){
			return $this->type;
		}
                
                function setColumnType($column_type){
			if(!isset($this->value)) return;
			if($column_type === '') return;
			
			$this->type = $column_type;
			
			//if($column_type === '') $column_type = 'varchar';

		}

		
                
        }

?>