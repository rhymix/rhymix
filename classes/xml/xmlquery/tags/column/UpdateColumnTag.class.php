<?php 

    /**
     * @class UpdateColumnTag
     * @author Arnia Software
     * @brief Models the <column> tag inside an XML Query file whose action is 'update'
     *
     **/



	class UpdateColumnTag extends ColumnTag {
		var $argument;
		
		function UpdateColumnTag($column) {
			parent::ColumnTag($column->attrs->name);
			$dbParser = XmlQueryParser::getDBParser();
			$this->name = $dbParser->parseColumnName($this->name);
			require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
			$this->argument = new QueryArgument($column);
		}
		
		function getExpressionString(){
			return sprintf('new UpdateExpression(\'%s\', $%s_argument)'
						, $this->name
						, $this->argument->argument_name);	
		}	
		
		function getArgument(){
			return $this->argument;
		}	
	}

?>