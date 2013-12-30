<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilter
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the spamfilter module
 */
class spamfilter extends ModuleObject
{
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');
		// 2007.12.7 The triggers which try to perform spam filtering when new posts/comments/trackbacks are registered
		$oModuleController->insertTrigger('document.insertDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before');
		$oModuleController->insertTrigger('comment.insertComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before');
		$oModuleController->insertTrigger('trackback.insertTrackback', 'spamfilter', 'controller', 'triggerInsertTrackback', 'before');
		// 2008-12-17 Add a spamfilter for post modification actions
		$oModuleController->insertTrigger('comment.updateComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before');
		$oModuleController->insertTrigger('document.updateDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before');
		// 2013-11-14 The trigger which try to perform spam filtering when new message are registered
		$oModuleController->insertTrigger('communication.sendMessage', 'spamfilter', 'controller', 'triggerSendMessage', 'before');

		return new Object();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		// 2007.12.7 The triggers which try to perform spam filtering when new posts/comments/trackbacks are registered
		if(!$oModuleModel->getTrigger('document.insertDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before')) return true;
		if(!$oModuleModel->getTrigger('comment.insertComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before')) return true;
		if(!$oModuleModel->getTrigger('trackback.insertTrackback', 'spamfilter', 'controller', 'triggerInsertTrackback', 'before')) return true;
		// 2008-12-17 Add a spamfilter for post modification actions
		if(!$oModuleModel->getTrigger('comment.updateComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before')) return true;
		// 2013-11-14 The trigger which try to perform spam filtering when new message are registered
		if(!$oModuleModel->getTrigger('communication.sendMessage', 'spamfilter', 'controller', 'triggerSendMessage', 'before')) return true;

		/**
		 * Add the hit count field (hit)
		 */
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit')) return true;

		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description')) return true;

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// 2007.12.7 The triggers which try to perform spam filtering when new posts/comments/trackbacks are registered
		if(!$oModuleModel->getTrigger('document.insertDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before'))
			$oModuleController->insertTrigger('document.insertDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before');
		if(!$oModuleModel->getTrigger('comment.insertComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before'))
			$oModuleController->insertTrigger('comment.insertComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before');
		if(!$oModuleModel->getTrigger('trackback.insertTrackback', 'spamfilter', 'controller', 'triggerInsertTrackback', 'before'))
			$oModuleController->insertTrigger('trackback.insertTrackback', 'spamfilter', 'controller', 'triggerInsertTrackback', 'before');
		// 2008-12-17 Add a spamfilter for post modification actions
		if(!$oModuleModel->getTrigger('comment.updateComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before'))
		{
			$oModuleController->insertTrigger('comment.updateComment', 'spamfilter', 'controller', 'triggerInsertComment', 'before');
		}
		// 2008-12-17 Add a spamfilter for post modification actions
		if(!$oModuleModel->getTrigger('document.updateDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before'))
		{
			$oModuleController->insertTrigger('document.updateDocument', 'spamfilter', 'controller', 'triggerInsertDocument', 'before');
		}
		// 2013-11-14 The trigger which try to perform spam filtering when new message are registered
		if(!$oModuleModel->getTrigger('communication.sendMessage', 'spamfilter', 'controller', 'triggerSendMessage', 'before'))
		{
			$oModuleController->insertTrigger('communication.sendMessage', 'spamfilter', 'controller', 'triggerSendMessage', 'before');
		}

		/**
		 * Add the hit count field (hit)
		 */
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_word','hit','number',12,0,true);
			$oDB->addIndex('spamfilter_denied_word','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_word','latest_hit','date');
			$oDB->addIndex('spamfilter_denied_word','idx_latest_hit', 'latest_hit');
		}

		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description'))
		{
			$oDB->addColumn('spamfilter_denied_ip','description','varchar', 250);
		}

		return new Object(0,'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file spamfilter.class.php */
/* Location: ./modules/spamfilter/spamfilter.class.controller.php */
