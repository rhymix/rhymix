<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilter
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the spamfilter module
 */
class spamfilter extends ModuleObject
{
	protected static $_insert_triggers = array(
		array('document.insertDocument', 'before', 'controller', 'triggerInsertDocument'),
		array('document.updateDocument', 'before', 'controller', 'triggerInsertDocument'),
		array('document.manage', 'before', 'controller', 'triggerManageDocument'),
		array('comment.insertComment', 'before', 'controller', 'triggerInsertComment'),
		array('comment.updateComment', 'before', 'controller', 'triggerInsertComment'),
		array('trackback.insertTrackback', 'before', 'controller', 'triggerInsertTrackback'),
		array('communication.sendMessage', 'before', 'controller', 'triggerSendMessage'),
	);
	
	protected static $_delete_triggers = array();
	
	/**
	 * Register all triggers.
	 * 
	 * @return object
	 */
	public function registerTriggers()
	{
		$oModuleController = getController('module');
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->insertTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->deleteTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		return new BaseObject(0, 'success_updated');
	}
	
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	public function moduleInstall()
	{
		return $this->registerTriggers();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	public function checkUpdate()
	{
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description')) return true;
		return false;
	}

	/**
	 * @brief Execute update
	 */
	public function moduleUpdate()
	{
		$output = $this->registerTriggers();
		if (!$output->toBool())
		{
			return $output;
		}
		
		$oDB = &DB::getInstance();
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
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip','hit','number',12,0,true);
			$oDB->addIndex('spamfilter_denied_ip','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip','latest_hit','date');
			$oDB->addIndex('spamfilter_denied_ip','idx_latest_hit', 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description'))
		{
			$oDB->addColumn('spamfilter_denied_ip','description','varchar', 250);
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	public function recompileCache()
	{
		
	}
}
/* End of file spamfilter.class.php */
/* Location: ./modules/spamfilter/spamfilter.class.controller.php */
