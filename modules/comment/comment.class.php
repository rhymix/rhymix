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
class comment extends ModuleObject
{

	/**
	 * Implemented if additional tasks are required when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		$oDB = DB::getInstance();

		// register the action forward (for using on the admin mode)
		$oModuleController = getController('module');

		$oDB->addIndex(
				"comments", "idx_module_list_order", array("module_srl", "list_order"), TRUE
		);

		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		$oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		$oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
		// 2008. 02. 22 add comment setting when a new module added
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');
	}

	/**
	 * Method to check if installation is succeeded
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();

		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		if(!ModuleModel::getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after'))
		{
			return TRUE;
		}
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		if(!ModuleModel::getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after'))
		{
			return TRUE;
		}
		// 2008. 02. 22 add comment setting when a new module added
		if(!ModuleModel::getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before'))
		{
			return TRUE;
		}
		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
		{
			return TRUE;
		}

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!ModuleModel::getTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after'))
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
		
		// 2018.01.24 Improve mass file deletion
		if(!ModuleModel::getTrigger('document.moveDocumentModule', 'comment', 'controller', 'triggerMoveDocument', 'after'))
		{
			return true;
		}
		if(!ModuleModel::getTrigger('document.copyDocumentModule', 'comment', 'controller', 'triggerAddCopyDocument', 'add'))
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
		$oModuleController = getController('module');
		
		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		if(!ModuleModel::getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after'))
		{
			$oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
		}
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		if(!ModuleModel::getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after'))
		{
			$oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
		}
		// 2008. 02. 22 add comment setting when a new module added
		if(!ModuleModel::getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before'))
		{
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');
		}
		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
		{
			$oDB->addIndex("comments", "idx_module_list_order", array("module_srl", "list_order"), TRUE);
		}

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!ModuleModel::getTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after');
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
		
		// 2018.01.24 Improve mass file deletion
		if(!ModuleModel::getTrigger('document.moveDocumentModule', 'comment', 'controller', 'triggerMoveDocument', 'after'))
		{
			$oModuleController->insertTrigger('document.moveDocumentModule', 'comment', 'controller', 'triggerMoveDocument', 'after');
		}
		if(!ModuleModel::getTrigger('document.copyDocumentModule', 'comment', 'controller', 'triggerAddCopyDocument', 'add'))
		{
			$oModuleController->insertTrigger('document.copyDocumentModule', 'comment', 'controller', 'triggerAddCopyDocument', 'add');
		}
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{
		
	}

}
/* End of file comment.class.php */
/* Location: ./modules/comment/comment.class.php */
