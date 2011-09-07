<?php

	class ConditionArgument extends Argument {
		var $operation;


		function ConditionArgument($name, $value, $operation){
                        if(isset($value) && in_array($operation, array('in', 'notin', 'between')) && !is_array($value)){
                            $value = str_replace(' ', '', $value);
                            $value = str_replace('\'', '', $value);
                            $value = explode(',', $value);
                        }
			parent::Argument($name, $value);
			$this->operation = $operation;
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
                                    $this->value = '%'.$value;
                                break;
                            case 'like' :
                                    $this->value = '%'.$value.'%';
                                break;
                            case 'in':
                                    if(!is_array($value)) $this->value = array($value);
                                break;
                            case 'notin':
                                    if(!is_array($value)) $this->value = array($value);
                                break;
                        }
		}

                function getType(){
			return $this->type;
		}

                function setColumnType($column_type){
			if(!isset($this->value)) return;
			if($column_type === '') return;

			$this->type = $column_type;
		}

        }

?>