<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  sessionAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin view class of the session module
 */
class sessionAdminView extends session
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Configure
	 */
	function dispSessionAdminIndex()
	{
		// Set the template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('index');
	}
}
/* End of file session.admin.view.php */
/* Location: ./modules/session/session.admin.view.php */
