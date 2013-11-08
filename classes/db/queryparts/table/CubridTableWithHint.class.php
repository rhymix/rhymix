<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class CubridTableWithHint extends Table
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
	 * index hint list
	 * @var array
	 */
	var $index_hints_list;

	/**
	 * constructor
	 * @param string $name
	 * @param string $alias
	 * @param array $index_hints_list
	 * @return void
	 */
	function CubridTableWithHint($name, $alias = NULL, $index_hints_list)
	{
		parent::Table($name, $alias);
		$this->index_hints_list = $index_hints_list;
	}

	/**
	 * Return index hint string
	 * @return string
	 */
	function getIndexHintString()
	{
		$result = '';

		// Retrieve table prefix, to add it to index name
		$db_info = Context::getDBInfo();
		$prefix = $db_info->master_db["db_table_prefix"];

		foreach($this->index_hints_list as $index_hint)
		{
			$index_hint_type = $index_hint->getIndexHintType();
			if($index_hint_type !== 'IGNORE')
			{
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
/* End of file CubridTableWithHint.class.php */
/* Location: ./classes/db/queryparts/table/CubridTableWithHint.class.php */
