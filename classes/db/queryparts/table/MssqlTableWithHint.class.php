<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class MssqlTableWithHint extends Table
{

	/**
	 * table name
	 * @var string
	 */
	var $name;

	/**
	 * table alias
	 * @var string
	 */
	var $alias;

	/**
	 * index hint type, ex) IGNORE, FORCE, USE...
	 * @var array
	 */
	var $index_hints_list;

	/**
	 * constructor
	 * @param string $name
	 * @param string $alias
	 * @param string $index_hints_list
	 * @return void
	 */
	function MssqlTableWithHint($name, $alias = NULL, $index_hints_list)
	{
		parent::Table($name, $alias);
		$this->index_hints_list = $index_hints_list;
	}

	function toString()
	{
		$result = parent::toString();

		$index_hint_string = '';
		$indexTypeList = array('USE' => 1, 'FORCE' => 1);
		foreach($this->index_hints_list as $index_hint)
		{
			$index_hint_type = $index_hint->getIndexHintType();
			if(isset($indexTypeList[$index_hint_type]))
			{
				$index_hint_string .= 'INDEX(' . $index_hint->getIndexName() . '), ';
			}
		}
		if($index_hint_string != '')
		{
			$result .= ' WITH(' . substr($index_hint_string, 0, -2) . ') ';
		}
		return $result;
	}

}
/* End of file MssqlTableWithHint.class.php */
/* Location: ./classes/db/queryparts/table/MssqlTableWithHint.class.php */
