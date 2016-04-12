<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * High class of rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rss extends ModuleObject
{
	public $gzhandler_enable = false;

	/**
	 * Additional tasks required to accomplish during the installation
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		// Register in action forward
		$oModuleController = getController('module');

		$oModuleController->insertActionForward('rss', 'view', 'rss');
		$oModuleController->insertActionForward('rss', 'view', 'atom');
		// 2007.10.18 Add a trigger for participating additional configurations of the service module
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before');
		// 2007. 10. 19 Call the trigger to set RSS URL before outputing
		$oModuleController->insertTrigger('moduleHandler.proc', 'rss', 'controller', 'triggerRssUrlInsert', 'after');

		return new Object();
	}

	/**
	 * A method to check if the installation has been successful
	 * @return bool
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		// Add the Action forward for atom
		if(!$oModuleModel->getActionForward('atom')) return true;
		// 2007. 10. Add a trigger for participating additional configurations of the service module
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before')) return true;
		// 2007. 10. 19 Call the trigger to set RSS URL before outputing
		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'rss', 'controller', 'triggerRssUrlInsert', 'after')) return true;

		if($oModuleModel->getTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before')) return true;

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'rss', 'controller', 'triggerCopyModule', 'after')) return true;

		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// Add atom act
		if(!$oModuleModel->getActionForward('atom'))
			$oModuleController->insertActionForward('rss', 'view', 'atom');
		// 2007. 10. An additional set of 18 to participate in a service module, add a trigger
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before')) 
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before');
		// 2007. 10. 19 outputs the trigger before you call to set up rss url
		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'rss', 'controller', 'triggerRssUrlInsert', 'after')) 
			$oModuleController->insertTrigger('moduleHandler.proc', 'rss', 'controller', 'triggerRssUrlInsert', 'after');
		if($oModuleModel->getTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before'))
			$oModuleController->deleteTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before');

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'rss', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'rss', 'controller', 'triggerCopyModule', 'after');
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * Re-generate the cache file
	 *
	 * @return void
	 */
	function recompileCache()
	{
	}
}
/* End of file rss.class.php */
/* Location: ./modules/rss/rss.class.php */
