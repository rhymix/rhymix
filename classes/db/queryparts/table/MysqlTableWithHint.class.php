<?php

	class MysqlTableWithHint extends Table {
		var $name;
		var $alias;
                var $index_hints_list;

		function MysqlTableWithHint($name, $alias = NULL, $index_hints_list){
                    parent::Table($name, $alias);
                    $this->index_hints_list = $index_hints_list;
		}

                function toString(){
                    $result = parent::toString();

                    $use_index_hint = ''; $force_index_hint = ''; $ignore_index_hint = '';
                    foreach($this->index_hints_list as $index_hint){
                        $index_hint_type = $index_hint->getIndexHintType();
                        if($index_hint_type == 'USE') $use_index_hint .= $index_hint->getIndexName() . ', ';
                        else if($index_hint_type == 'FORCE') $force_index_hint .= $index_hint->getIndexName() . ', ';
                        else if($index_hint_type == 'IGNORE') $ignore_index_hint .= $index_hint->getIndexName() . ', ';
                    }
                    if($use_index_hint != ''){
                        $result .= ' USE INDEX (' . substr($use_index_hint, 0, -2) . ') ';
                    }
                    if($force_index_hint != ''){
                        $result .= ' FORCE INDEX (' . substr($force_index_hint, 0, -2) . ') ';
                    }
                    if($ignore_index_hint != ''){
                        $result .= ' IGNORE INDEX (' . substr($ignore_index_hint, 0, -2) . ') ';
                    }
                    return $result;
                }
	}

?>