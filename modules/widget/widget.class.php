<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widget
 * @author NAVER (developers@xpressengine.com)
 * @brief widget module's high class
 */
class Widget extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	public function moduleInstall()
	{
		// Create cache directory used by widget
		FileHandler::makeDir('./files/cache/widget');
		FileHandler::makeDir('./files/cache/widget_cache');
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	public function checkUpdate()
	{
		return false;
	}

	/**
	 * @brief Execute update
	 */
	public function moduleUpdate()
	{

	}
}
/* End of file widget.class.php */
/* Location: ./modules/widget/widget.class.php */
