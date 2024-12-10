<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  board
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module high class
 **/

class Board extends ModuleObject
{
	var $search_option = array('title_content','title','content','comment','user_name','nick_name','user_id','tag'); ///< 검색 옵션

	var $order_target = array('list_order', 'update_order', 'regdate', 'voted_count', 'blamed_count', 'readed_count', 'comment_count', 'title', 'nick_name', 'user_name', 'user_id'); // 정렬 옵션

	var $skin = "default"; ///< skin name
	var $list_count = 20; ///< the number of documents displayed in a page
	var $page_count = 10; ///< page number
	var $category_list = NULL; ///< category list

	/**
	 * constructor
	 *
	 * @return void
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * @brief install the module
	 **/
	function moduleInstall()
	{

	}

	/**
	 * @brief chgeck module method
	 **/
	function checkUpdate()
	{

	}

	/**
	 * @brief update module
	 **/
	function moduleUpdate()
	{

	}

	function moduleUninstall()
	{

	}
}
