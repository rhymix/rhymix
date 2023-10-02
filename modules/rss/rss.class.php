<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * High class of rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Rss extends ModuleObject
{
	// Add forwards
	protected static $add_forwards = array(
		array('rss', 'view', 'rss'),
		array('rss', 'view', 'atom'),
	);

	/**
	 * Install
	 */
	public function moduleInstall()
	{
		$this->moduleUpdate();
	}

	/**
	 * Check update
	 */
	public function checkUpdate()
	{
		// Check forwards for add
		foreach(self::$add_forwards as $forward)
		{
			if(!ModuleModel::getActionForward($forward[2]))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Update
	 */
	public function moduleUpdate()
	{
		// Add forwards
		foreach(self::$add_forwards as $forward)
		{
			if(!ModuleModel::getActionForward($forward[2]))
			{
				ModuleController::getInstance()->insertActionForward($forward[0], $forward[1], $forward[2]);
			}
		}
	}
}
/* End of file rss.class.php */
/* Location: ./modules/rss/rss.class.php */
