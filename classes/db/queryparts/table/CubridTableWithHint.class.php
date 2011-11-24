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

                    // Retrieve table prefix, to add it to index name
                    $db_info = Context::getDBInfo();
                    $prefix = $db_info->master_db["db_table_prefix"];

                    foreach($this->index_hints_list as $index_hint){
                        $index_hint_type = $index_hint->getIndexHintType();
                        if($index_hint_type !== 'IGNORE'){
                            $result .= $this->alias . '.'
                                        . '"' . $prefix . substr($index_hint->getIndexName(), 1)
                                        . ($index_hint_type == 'FORCE' ? '(+)' : '')
                                        . ', ';
                        }

                    }
                    $result = substr($result, 0, -2);
                    return $result;
		}
	}

?>