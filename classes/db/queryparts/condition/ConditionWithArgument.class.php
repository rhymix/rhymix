<?php

	class ConditionWithArgument extends Condition {

            function ConditionWithArgument($column_name, $argument, $operation, $pipe = ""){
                        parent::Condition($column_name, $argument, $operation, $pipe);
			$this->_value = $argument->getValue();
		}

		function getArgument(){
                        return $this->argument;
		}

		function toStringWithoutValue(){
                        $value = $this->argument->getUnescapedValue();

                        if(is_array($value)){
                            $q = '';
                            foreach ($value as $v) $q .= '?,';
                            if($q !== '') $q = substr($q, 0, -1);
                            $q = '(' . $q . ')';
                        }
                        else $q = '?';
                        return $this->pipe . ' ' . $this->getConditionPart($q);
		}

		function show(){
                    if(!$this->argument->isValid()) return false;
                    if($this->_value === '\'\'') return false;
                    return parent::show();
		}
	}

?>
