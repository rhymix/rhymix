<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widget
 * @author NAVER (developers@xpressengine.com)
 * @brief widget module's high class
 */
class widget extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// Create cache directory used by widget
		FileHandler::makeDir('./files/cache/widget');
		FileHandler::makeDir('./files/cache/widget_cache');
		// Add this widget compile the trigger for the display.after
		$oModuleController = getController('module');
		$oModuleController->insertTrigger('display', 'widget', 'controller', 'triggerWidgetCompile', 'before');

		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		// widget compile display.after trigger for further (04/14/2009)
		if(!$oModuleModel->getTrigger('display', 'widget', 'controller', 'triggerWidgetCompile', 'before')) return true;

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// widget compile display.after trigger for further (04/14/2009)
		if(!$oModuleModel->getTrigger('display', 'widget', 'controller', 'triggerWidgetCompile', 'before'))
		{
			$oModuleController->insertTrigger('display', 'widget', 'controller', 'triggerWidgetCompile', 'before');
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file widget.class.php */
/* Location: ./modules/widget/widget.class.php */
