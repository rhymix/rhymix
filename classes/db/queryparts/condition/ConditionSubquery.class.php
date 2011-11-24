<?php

	class ConditionSubquery extends Condition {

            function ConditionSubquery($column_name, $argument, $operation, $pipe = ""){
                parent::Condition($column_name, $argument, $operation, $pipe);
                $this->_value = $this->argument->toString();
            }
	}

?>
