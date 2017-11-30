<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  poll
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the poll module
 */
class poll extends ModuleObject
{
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Register in the action forward (to use in administrator mode)
		$oModuleController = getController('module');
		// Set the default skin
		$oModuleController = getController('module');

		$config = new stdClass;
		$config->skin = 'default';
		$config->colorset = 'normal';
		$oModuleController->insertModuleConfig('poll', $config);
		// 2007.10.17 When deleting posts/comments delete the poll as well
		$oModuleController->insertTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after');
		$oModuleController->insertTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after');
		$oModuleController->insertTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after');
		$oModuleController->insertTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after');
		$oModuleController->insertTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after');
		$oModuleController->insertTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after');
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		// 2007.10.17 When deleting posts/comments delete the poll as well
		if(!$oModuleModel->getTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after')) return true;

		if(!$oDB->isColumnExists('poll', 'poll_type'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('poll_log','poll_item'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('poll_item','add_user_srl'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();

		// 2007.10.17 When deleting posts/comments delete the poll as well
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after'))
			$oModuleController->insertTrigger('document.deleteDocument', 'poll', 'controller', 'triggerDeleteDocumentPoll', 'after');
		if(!$oModuleModel->getTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after'))
			$oModuleController->insertTrigger('comment.deleteComment', 'poll', 'controller', 'triggerDeleteCommentPoll', 'after');
		// 2008.04.22 A poll connection to add posts/comments
		if(!$oModuleModel->getTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after')) 
			$oModuleController->insertTrigger('document.insertDocument', 'poll', 'controller', 'triggerInsertDocumentPoll', 'after');
		if(!$oModuleModel->getTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after')) 
			$oModuleController->insertTrigger('comment.insertComment', 'poll', 'controller', 'triggerInsertCommentPoll', 'after');
		if(!$oModuleModel->getTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after')) 
			$oModuleController->insertTrigger('document.updateDocument', 'poll', 'controller', 'triggerUpdateDocumentPoll', 'after');
		if(!$oModuleModel->getTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after')) 
			$oModuleController->insertTrigger('comment.updateComment', 'poll', 'controller', 'triggerUpdateCommentPoll', 'after');

		if(!$oDB->isColumnExists('poll','poll_type'))
		{
			$oDB->addColumn('poll', 'poll_type', 'number', 11, 0);
		}

		if(!$oDB->isColumnExists('poll_log','poll_item'))
		{
			$oDB->addColumn('poll_log', 'poll_item', 'varchar', 250, 0);
		}

		if(!$oDB->isColumnExists('poll_item','add_user_srl'))
		{
			$oDB->addColumn('poll_item', 'add_user_srl', 'number', 11, 0);
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}

	/**
	 * @brief Check if this poll could display member information
	 */
	function checkMemberInfo($type)
	{
		return ($type==1 || $type==3);
	}

	/**
	 * @brief Check if the items of this poll could be added by members.
	 */
	function isAbletoAddItem($type)
	{
		return ($type==2 || $type==3);
	}
}
/* End of file poll.class.php */
/* Location: ./modules/poll/poll.class.php */
