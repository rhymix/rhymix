<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * NavigationTag class
 *
 * @author Arnia Software
 * @package /classes/xml/xmlquery/tags/navigation
 * @version 0.1
 */
class NavigationTag
{

	/**
	 * Order
	 * @var array
	 */
	var $order;

	/**
	 * List count
	 * @var int
	 */
	var $list_count;

	/**
	 * Page count
	 * @var int
	 */
	var $page_count;

	/**
	 * Page
	 * @var int
	 */
	var $page;

	/**
	 * Limit
	 * @var LimitTag object
	 */
	var $limit;

	/**
	 * constructor
	 * @param object $xml_navigation
	 * @return void
	 */
	function NavigationTag($xml_navigation)
	{
		$this->order = array();
		if($xml_navigation)
		{
			$order = $xml_navigation->index;
			if($order)
			{
				if(!is_array($order))
				{
					$order = array($order);
				}
				foreach($order as $order_info)
				{
					$this->order[] = new IndexTag($order_info);
				}

				if($xml_navigation->page && $xml_navigation->page->attrs || $xml_navigation->list_count && $xml_navigation->list_count->attrs)
				{
					$this->limit = new LimitTag($xml_navigation);
				}

				if($xml_navigation->list_count)
				{
					$this->list_count = $xml_navigation->list_count->attrs;
				}

				if($xml_navigation->page_count)
				{
					$this->page_count = $xml_navigation->page_count->attrs;
				}

				if($xml_navigation->page)
				{
					$this->page = $xml_navigation->page->attrs;
				}
			}
		}
	}

	/**
	 * NavigationTag object to string
	 * @return string
	 */
	function getOrderByString()
	{
		$output = 'array(' . PHP_EOL;
		foreach($this->order as $order)
		{
			$output .= $order->toString() . PHP_EOL . ',';
		}
		$output = substr($output, 0, -1);
		$output .= ')';
		return $output;
	}

	/**
	 * LimitTag object to string
	 * @return string
	 */
	function getLimitString()
	{
		if($this->limit)
		{
			return $this->limit->toString();
		}
		else
		{
			return "";
		}
	}

	function getArguments()
	{
		$arguments = array();
		foreach($this->order as $order)
		{
			$arguments = array_merge($order->getArguments(), $arguments);
		}
		if($this->limit)
		{
			$arguments = array_merge($this->limit->getArguments(), $arguments);
		}
		return $arguments;
	}

}
/* End of file NavigationTag.class.php */
/* Location: ./classes/xml/xmlquery/tags/navigation/NavigationTag.class.php */
