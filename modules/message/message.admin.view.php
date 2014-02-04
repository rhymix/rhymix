<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  messageAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief admin view class of the message module
 */
class messageAdminView extends message
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Configuration
	 */
	function dispMessageAdminConfig()
	{
		// Get a list of skins(themes)
		$oModuleModel = getModel('module');

		$skin_list = $oModuleModel->getskins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// Get configurations (using module model object)
		$config = $oModuleModel->getModuleConfig('message');
		Context::set('config',$config);

		// Set a template file
		$this->setTemplatePath($this->module_path.'tpl');

		//Security
		$security = new Security();
		$security->encodeHTML('skin_list..title', 'mskin_list..title');

		$this->setTemplateFile('config');
	}
}
/* End of file message.admin.view.php */
/* Location: ./modules/message/message.admin.view.php */
