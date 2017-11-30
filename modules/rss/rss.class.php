<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * High class of rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rss extends ModuleObject
{
	// Add forwards
	protected static $add_forwards = array(
		array('rss', 'view', 'rss'),
		array('rss', 'view', 'atom'),
	);
	
	// Add triggers
	protected static $add_triggers = array(
		array('moduleHandler.proc', 'rss', 'controller', 'triggerRssUrlInsert', 'after'),
		array('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before'),
		array('module.procModuleAdminCopyModule', 'rss', 'controller', 'triggerCopyModule', 'after'),
	);
	
	// Remove triggers
	protected static $remove_triggers = array(
		array('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before'),
	);
	
	/**
	 * Install
	 */
	function moduleInstall()
	{
		$this->moduleUpdate();
	}
	
	/**
	 * Check update
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		
		// Check forwards for add
		foreach(self::$add_forwards as $forward)
		{
			if(!$oModuleModel->getActionForward($forward[2]))
			{
				return true;
			}
		}
		
		// Check triggers for add
		foreach(self::$add_triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}
		
		// Check triggers for remove
		foreach(self::$remove_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		
		// Add forwards
		foreach(self::$add_forwards as $forward)
		{
			if(!$oModuleModel->getActionForward($forward[2]))
			{
				$oModuleController->insertActionForward($forward[0], $forward[1], $forward[2]);
			}
		}
		
		// Add triggers
		foreach(self::$add_triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}
		
		// Remove triggers
		foreach(self::$remove_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}
	}
	
	function recompileCache()
	{
		
	}
}
/* End of file rss.class.php */
/* Location: ./modules/rss/rss.class.php */
