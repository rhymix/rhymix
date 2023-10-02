<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * comment
 * comment module's high class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class Comment extends ModuleObject
{

	/**
	 * Implemented if additional tasks are required when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		$oDB = DB::getInstance();
		$oDB->addIndex(
				"comments", "idx_module_list_order", array("module_srl", "list_order"), TRUE
		);
	}

	/**
	 * Method to check if installation is succeeded
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
		{
			return TRUE;
		}

		// 2016. 1. 29: Add a column(declare_message) for report
		if(!$oDB->isColumnExists("comment_declared_log","declare_message"))
		{
			return true;
		}

		if(!$oDB->isIndexExists("comments", "idx_parent_srl"))
		{
			return true;
		}

		// 2017.12.21 Add an index for nick_name
		if(!$oDB->isIndexExists('comments', 'idx_nick_name'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
		{
			$oDB->addIndex("comments", "idx_module_list_order", array("module_srl", "list_order"), TRUE);
		}

		// 2016. 1. 29: Add a column(declare_message) for report
		if(!$oDB->isColumnExists("comment_declared_log","declare_message"))
		{
			$oDB->addColumn('comment_declared_log',"declare_message","text");
		}

		if(!$oDB->isIndexExists("comments", "idx_parent_srl"))
		{
			$oDB->addIndex('comments', 'idx_parent_srl', array('parent_srl'));
		}

		// 2017.12.21 Add an index for nick_name
		if(!$oDB->isIndexExists('comments', 'idx_nick_name'))
		{
			$oDB->addIndex('comments', 'idx_nick_name', array('nick_name'));
		}
	}
}
/* End of file comment.class.php */
/* Location: ./modules/comment/comment.class.php */
