<?php

	class CubridTableWithHint extends Table {
		var $name;
		var $alias;
                var $index_hints_list;

		function CubridTableWithHint($name, $alias = NULL, $index_hints_list){
                    parent::Table($name, $alias);
                    $this->index_hints_list = $index_hints_list;
		}

		function getIndexHintString(){
                    $result = '';
                    foreach($this->index_hints_list as $index_hint){
                        $result .= $this->alias . '.' . $index_hint->getIndexName()
                                . ($index_hint->getIndexHintType() == 'FORCE' ? '(+)' : '') . ', ';

                    }
                    $result = substr($result, 0, -2);
                    return $result;
		}
	}

?>