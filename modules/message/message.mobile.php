<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class messageMobile extends messageView
{
	/**
	 * @brief Initialization
	 **/
	function init()
	{
	}

	/**
	 * @brief Message output
	 **/
	function dispMessage($detail = null)
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('message');
		if(!is_object($config)) $config = new stdClass;
		if(!$config->mskin) $config->mskin = 'default';
		
		// Set the template path
		if($config->mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
			if(!is_dir($template_path) || !$config->skin)
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
			if(!is_dir($template_path) || !$config->mskin)
			{
				$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
			}
		}

		// Get the member configuration
		$oModuleModel = getModel('module');
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
		Context::set('system_message_detail', nl2br($detail));

		Context::set('act', 'procMemberLogin');
		Context::set('mid', '');

		$this->setTemplatePath($template_path);
		$this->setTemplateFile('system_message');
		
		// Default 403 Error
		if($this->getHttpStatusCode() === 200)
		{
			$this->setHttpStatusCode(403);
		}
	}
}
/* End of file message.mobile.php */
/* Location: ./modules/message/message.mobile.php */
