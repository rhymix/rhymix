<?php

	/**
	 * @class TablesTag
	 * @author Arnia Sowftare
	 * @brief Models the <tables> tag inside an XML Query file
	 *
         * @abstract
         *   Example
         *      <tables>
         *          <table name="documents" alias="doc" />
         *      </tables>
         *   Attributes
         *      None.
         *   Children
         *      Can have children of type <table> or <query>
	 */

	class TablesTag {
		var $tables;

		function TablesTag($xml_tables_tag, $xml_index_hints_tag = NULL){
                    $this->tables = array();

                    $xml_tables = $xml_tables_tag->table;
                    if(!is_array($xml_tables)) $xml_tables = array($xml_tables);

                    if($xml_index_hints_tag){
                        $index_nodes = $xml_index_hints_tag->index;
                        if(!is_array($index_nodes)) $index_nodes = array($index_nodes);
                        foreach($index_nodes as $index_node) {
                            if(!$indexes[$index_node->attrs->table]) $indexes[$index_node->attrs->table] = array();
                            $count = count($indexes[$index_node->attrs->table]);
                            $indexes[$index_node->attrs->table][$count]->name = $index_node->attrs->name;
                            $indexes[$index_node->attrs->table][$count]->type = $index_node->attrs->type;
                        }
                    }

                    foreach($xml_tables as $tag){
                        if($tag->attrs->query == 'true'){
                            $this->tables[] = new QueryTag($tag, true);
                        }
                        else {
                            if($indexes && $indexes[$tag->attrs->name])
                                $this->tables[] = new HintTableTag($tag, $indexes[$tag->attrs->name]);
                            else
                                $this->tables[] = new TableTag($tag);
                        }
                    }
		}

		function getTables(){
			return $this->tables;
		}

		function toString(){
			$output_tables = 'array(' . PHP_EOL;
			foreach($this->tables as $table){
				if(is_a($table, 'QueryTag'))
					$output_tables .= $table->toString() . PHP_EOL . ',';
				else
					$output_tables .= $table->getTableString() . PHP_EOL . ',';
			}
			$output_tables = substr($output_tables, 0, -1);
			$output_tables .= ')';
			return $output_tables;
		}

                function getArguments(){
                    $arguments = array();
                    foreach($this->tables as $table)
                           $arguments = array_merge($arguments, $table->getArguments());
                    return $arguments;
                }
	}
?>