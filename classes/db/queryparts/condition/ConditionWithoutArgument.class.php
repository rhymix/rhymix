<?php
	/**
	 * @author NHN (developers@xpressengine.com)
	 * @package /classes/db/queryparts/condition
	 * @version 0.1
	 */
	class ConditionWithoutArgument extends Condition {
		/**
		 * constructor
		 * @param string $column_name
		 * @param mixed $argument
		 * @param string $operation
		 * @param string $pipe
		 * @return void
		 */
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
