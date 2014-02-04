<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * LimitTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/navigation
 * @version 0.1
 */
class LimitTag
{

	/**
	 * Value is relate to limit query
	 * @var array
	 */
	var $arguments;

	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $page;

	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $page_count;

	/**
	 * QueryArgument object
	 * @var QueryArgument
	 */
	var $list_count;

	/**
	 * constructor
	 * @param object $index
	 * @return void
	 */
	function LimitTag($index)
	{
		if($index->page && $index->page->attrs && $index->page_count && $index->page_count->attrs)
		{
			if(!isset($index->page->attrs->default))
				$index->page->attrs->default = 1;
			if(!isset($index->page_count->attrs->default))
				$index->page_count->attrs->default = 10;
			$this->page = new QueryArgument($index->page);
			$this->page_count = new QueryArgument($index->page_count);
			$this->arguments[] = $this->page;
			$this->arguments[] = $this->page_count;
		}

		if(!isset($index->list_count->attrs->default))
			$index->list_count->attrs->default = 0;
		$this->list_count = new QueryArgument($index->list_count);
		$this->arguments[] = $this->list_count;
	}

	function toString()
	{
		if($this->page)
		{
			return sprintf('new Limit(${\'%s_argument\'}, ${\'%s_argument\'}, ${\'%s_argument\'})', $this->list_count->getArgumentName(), $this->page->getArgumentName(), $this->page_count->getArgumentName());
		}
		else
		{
			return sprintf('new Limit(${\'%s_argument\'})', $this->list_count->getArgumentName());
		}
	}

	function getArguments()
	{
		return $this->arguments;
	}

}
/* End of file LimitTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/navigation/LimitTag.class.php */
