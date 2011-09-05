<?php

	class ConditionWithoutArgument extends Condition {
		function ConditionWithoutArgument($column_name, $argument, $operation, $pipe = ""){
                    parent::Condition($column_name, $argument, $operation, $pipe);
                    if(in_array($operation, array('in', 'not in')))
                            $this->_value = '('. $argument .')';
                    else
                        $this->_value = $argument;

		}
	}

?>
