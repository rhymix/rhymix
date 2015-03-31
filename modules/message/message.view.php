<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  messageView
 * @author NAVER (developers@xpressengine.com)
 * @brief view class of the message module
 */
class messageView extends message
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Display messages
	 */
	function dispMessage()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$this->module_config = $config = $oModuleModel->getModuleConfig('message', $this->module_info->site_srl);

		if(!$config)
		{
			$config = new stdClass();
		}

		if(!$config->skin)
		{
			$config->skin = 'xedition';
			$template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
		}
		else
		{
			//check theme
			$config_parse = explode('|@|', $config->skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/message/', $config_parse[0]);
			}
			else
			{
				$template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
			}
		}
		// Template path
		$this->setTemplatePath($template_path);

		// Get the member configuration
		$member_config = $oModuleModel->getModuleConfig('member');
		Context::set('member_config', $member_config);
		// Set a flag to check if the https connection is made when using SSL and create https url
		$ssl_mode = false;
		if($member_config->enable_ssl == 'Y')
		{
			if(strncasecmp('https://', Context::getRequestUri(), 8) === 0) $ssl_mode = true;
		}
		Context::set('ssl_mode',$ssl_mode);

		Context::set('system_message', nl2br($this->getMessage()));

		$this->setTemplateFile('system_message');
	}
}
/* End of file message.view.php */
/* Location: ./modules/message/message.view.php */
