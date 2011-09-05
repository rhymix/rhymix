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

		function TablesTag($xml_tables_tag){
                    $this->tables = array();

                    $xml_tables = $xml_tables_tag->table;
                    if(!is_array($xml_tables)) $xml_tables = array($xml_tables);

                    foreach($xml_tables as $tag){
                        if($tag->attrs->query == 'true'){
                            $this->tables[] = new QueryTag($tag, true);
                        }
                        else {
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