<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db/queryparts/table
 * @version 0.1
 */
class IndexHint
{

	/**
	 * index name
	 * @var string
	 */
	var $index_name;

	/**
	 * index hint type, ex) IGNORE, FORCE, USE...
	 * @var string
	 */
	var $index_hint_type;

	/**
	 * constructor
	 * @param string $index_name
	 * @param string $index_hint_type
	 * @return void
	 */
	function IndexHint($index_name, $index_hint_type)
	{
		$this->index_name = $index_name;
		$this->index_hint_type = $index_hint_type;
	}

	function getIndexName()
	{
		return $this->index_name;
	}

	function getIndexHintType()
	{
		return $this->index_hint_type;
	}

}
/* End of file IndexHint.class.php */
/* Location: ./classes/db/queryparts/table/IndexHint.class.php */
