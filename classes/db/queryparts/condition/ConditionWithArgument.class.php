<?php

	class ConditionWithArgument extends Condition {

            function ConditionWithArgument($column_name, $argument, $operation, $pipe = ""){
                    if($argument === null) { $this->_show = false; return; }
                        parent::Condition($column_name, $argument, $operation, $pipe);
			$this->_value = $argument->getValue();
		}

		function getArgument(){
                    if(!$this->show()) return;
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
                    if(!isset($this->_show)){
                        if(!$this->argument->isValid()) $this->_show = false;
                        if($this->_value === '\'\'') $this->_show = false;
                        if(!isset($this->_show)){
                            return parent::show();
                        }
                    }
                    return $this->_show;
		}
	}

?>
