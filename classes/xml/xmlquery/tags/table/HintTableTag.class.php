<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * HintTableTag
 * Models the <table> tag inside an XML Query file and the corresponding <index_hint> tag
 *
 * @author Arnia Sowftare
 * @package /classes/xml/xmlquery/tags/table
 * @version 0.1
 */
class HintTableTag extends TableTag
{

	/**
	 * Action for example, 'select', 'insert', 'delete'...
	 * @var array
	 */
	var $index;

	/**
	 * constructor
	 * Initialises Table Tag properties
	 * @param object $table XML <table> tag
	 * @param array $index
	 * @return void
	 */
	function HintTableTag($table, $index)
	{
		parent::TableTag($table);
		$this->index = $index;
	}

	function getTableString()
	{
		$dbParser = DB::getParser();
		$dbType = ucfirst(Context::getDBType());

		$result = sprintf('new %sTableWithHint(\'%s\'%s, array('
				, $dbType == 'Mysqli' ? 'Mysql' : $dbType
				, $dbParser->escape($this->name)
				, $this->alias ? ', \'' . $dbParser->escape($this->alias) . '\'' : ', null'
				//, ', \'' . $dbParser->escape($this->index->name) .'\', \'' . $this->index->type .'\''
		);
		foreach($this->index as $indx)
		{
			$result .= "new IndexHint(";
			$result .= '\'' . $dbParser->escape($indx->name) . '\', \'' . $indx->type . '\'' . ') , ';
		}
		$result = substr($result, 0, -2);
		$result .= '))';
		return $result;
	}

	function getArguments()
	{
		if(!isset($this->conditionsTag))
		{
			return array();
		}
		return $this->conditionsTag->getArguments();
	}

}
/* End of file HintTableTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/table/HintTableTag.class.php */
