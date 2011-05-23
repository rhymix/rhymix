<?php

require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/ColumnTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/SelectColumnsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/InsertColumnsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/UpdateColumnsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/column/DeleteColumnsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/table/TablesTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/condition/JoinConditionsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/group/GroupsTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/NavigationTag.class.php');
require_once(_XE_PATH_.'classes/xml/xmlquery/tags/navigation/IndexTag.class.php');

class QueryParser {
	var $query;
	var $action;
	var $query_id;
	
	var $column_type;
	
	function QueryParser($query){
		$this->query = $query;
		$this->action = $this->query->attrs->action;
		$this->query_id = $this->query->attrs->id;
	}	
	
	function getQueryId(){
		return $this->query->attrs->query_id ? $this->query->attrs->query_id : $this->query->attrs->id;
	}
	
	function getAction(){
		return $this->query->attrs->action;
	}

	function getTableInfo($query_id, $table_name){
		$column_type = array();
		
	    $id_args = explode('.', $query_id);
        if(count($id_args)==2) {
       	 	$target = 'modules';
         	$module = $id_args[0];
            $id = $id_args[1];
        } elseif(count($id_args)==3) {
       		$target = $id_args[0];
            if(!in_array($target, array('modules','addons','widgets'))) return;
            	$module = $id_args[1];
            	$id = $id_args[2];
        }		
		
		// get column properties from the table
        $table_file = sprintf('%s%s/%s/schemas/%s.xml', _XE_PATH_, 'modules', $module, $table_name);
        if(!file_exists($table_file)) {
        	$searched_list = FileHandler::readDir(_XE_PATH_.'modules');
            $searched_count = count($searched_list);
            for($i=0;$i<$searched_count;$i++) {
            	$table_file = sprintf('%s%s/%s/schemas/%s.xml', _XE_PATH_, 'modules', $searched_list[$i], $table_name);
                if(file_exists($table_file)) break;
            }
		}

        if(file_exists($table_file)) {
        	$table_xml = FileHandler::readFile($table_file);
        	$xml_parser = new XmlParser();
            $table_obj = $xml_parser->parse($table_xml);
            if($table_obj->table) {
            	if(isset($table_obj->table->column) && !is_array($table_obj->table->column))
                {
                	$table_obj->table->column = array($table_obj->table->column);
                }

               	foreach($table_obj->table->column as $k => $v) {
               		$column_type[$v->attrs->name] = $v->attrs->type;
                }
            }
        }		
        
        return $column_type;
	}
	
	function setTableColumnTypes($tables){
		$query_id = $this->getQueryId();
		if(!isset($this->column_type[$query_id])){
			$table_tags = $tables->getTables();
			$column_type = array();
			foreach($table_tags as $table_tag){
				$tag_column_type = $this->getTableInfo($query_id, $table_tag->getTableName());
				$column_type = array_merge($column_type, $tag_column_type);
			}
			$this->column_type[$query_id] = $column_type;
		}
	}
	
	function toString(){
		if($this->action == 'select'){			
			$columns =  new SelectColumnsTag($this->query->columns->column);
		}else if($this->action == 'insert'){
			$columns =  new InsertColumnsTag($this->query->columns->column);
		}else if($this->action == 'update') {			
			$columns =  new UpdateColumnsTag($this->query->columns->column);
		}else if($this->action == 'delete') {			
			$columns =  new DeleteColumnsTag($this->query->columns->column);
		}
		
		
		$tables = new TablesTag($this->query->tables->table);	
		$conditions = new ConditionsTag($this->query->conditions);		
		$groups = new GroupsTag($this->query->groups->group);
		$navigation = new NavigationTag($this->query->navigation);
		
		$this->setTableColumnTypes($tables);
		
		$arguments = array();
		$arguments = array_merge($arguments, $columns->getArguments());
		$arguments = array_merge($arguments, $conditions->getArguments());
		$arguments = array_merge($arguments, $navigation->getArguments());
		
		$prebuff = '';
		foreach($arguments as $argument){
			if(isset($argument) && $argument->getArgumentName()){
			$prebuff .= $argument->toString();
			$prebuff .= sprintf("$%s_argument->escapeValue('%s');\n"
				, $argument->getArgumentName()
				, $this->column_type[$this->getQueryId()][$argument->getColumnName()] );
			}
		}
		$prebuff .= "\n";
		
		$buff = '';
                  
		$buff .= '$output->columns = ' . $columns->toString() . ';'.PHP_EOL;
        $buff .= '$output->tables = ' . $tables->toString() .';'.PHP_EOL;
        $buff .= '$output->conditions = '.$conditions->toString() .';'.PHP_EOL;
        $buff .= '$output->groups = ' . $groups->toString() . ';'; 	
		$buff .= '$output->orderby = ' . $navigation->getOrderByString() .';';
				
		return "<?php if(!defined('__ZBXE__')) exit();\n"
                  . sprintf('$output->query_id = "%s";%s', $this->query_id, "\n")
                  . sprintf('$output->action = "%s";%s', $this->action, "\n")
                  . $prebuff
                  . $buff
                  . 'return $output; ?>';		
		

	}
}

?>