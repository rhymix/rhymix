<?php

	class ConditionWithoutArgument extends Condition {
		function ConditionWithoutArgument($column_name, $argument, $operation, $pipe = ""){
                    parent::Condition($column_name, $argument, $operation, $pipe);
                    if(in_array($operation, array('in', 'notin'))){
                        if(is_array($argument)) $argument = implode($argument, ',');
                        $this->_value = '('. $argument .')';
                    }
                    else
                        $this->_value = $argument;

		}
	}

?>
