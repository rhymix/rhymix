<?php

	class MssqlTableWithHint extends Table {
		var $name;
		var $alias;
                var $index_hints_list;

		function MssqlTableWithHint($name, $alias = NULL, $index_hints_list){
                    parent::Table($name, $alias);
                    $this->index_hints_list = $index_hints_list;
		}

                function toString(){
                    $result = parent::toString();

                    $index_hint_string = '';
                    foreach($this->index_hints_list as $index_hint){
                        $index_hint_type = $index_hint->getIndexHintType();
                        if(in_array($index_hint_type, array('USE', 'FORCE')))
                                $index_hint_string .= 'INDEX(' . $index_hint->getIndexName() . '), ';
                    }
                    if($index_hint_string != ''){
                        $result .= ' WITH(' . substr($index_hint_string, 0, -2) . ') ';
                    }
                    return $result;
                }
	}

?>