<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  board
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module high class
 **/

class Board extends ModuleObject
{
	/**
	 * Default search columns
	 */
	public $search_option = [
		'title_content',
		'title',
		'content',
		'comment',
		'user_name',
		'nick_name',
		'user_id',
		'tag',
	];

	/**
	 * Default sort columns
	 */
	public $order_target = [
		'list_order',
		'update_order',
		'regdate',
		'voted_count',
		'blamed_count',
		'readed_count',
		'comment_count',
		'title',
		'nick_name',
		'user_name',
		'user_id',
	];

	/**
	 * Default values
	 */
	public $skin = 'default';
	public $list_count = 20;
	public $page_count = 10;
	public $category_list;

	/**
	 * Callback functions for autoinstall
	 */
	function moduleInstall()
	{

	}

	function checkUpdate()
	{

	}

	function moduleUpdate()
	{

	}

	function moduleUninstall()
	{

	}
}
