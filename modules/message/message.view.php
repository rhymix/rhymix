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
	function dispMessage($detail = null, $location = null)
	{
		// Get skin configuration
		$config = ModuleModel::getModuleConfig('message') ?: new stdClass;
		if(!$config->skin)
		{
			$config->skin = 'xedition';
		}
		if(!$config->mskin)
		{
			$config->mskin = 'default';
		}
		
		// Set the template path
		if (contains('mobile', get_class($this), false))
		{
			if($config->mskin === '/USE_RESPONSIVE/')
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
				if(!is_dir($template_path))
				{
					$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
				}
			}
			else
			{
				$template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
				if(!is_dir($template_path))
				{
					$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
				}
			}
		}
		else
		{
			$template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
			if(!is_dir($template_path))
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
			}
		}
		$this->setTemplatePath($template_path);

		// Get the member configuration
		$member_config = ModuleModel::getModuleConfig('member');
		Context::set('member_config', $member_config);
		
		// Set SSL mode (for backward compatibility only)
		$ssl_mode = false;
		if($member_config->enable_ssl == 'Y')
		{
			if(strncasecmp('https://', Context::getRequestUri(), 8) === 0) $ssl_mode = true;
		}
		
		// Disable location if debug not available
		if (!Rhymix\Framework\Debug::isEnabledForCurrentUser())
		{
			$location = null;
		}
		
		// Remove basedir from location (if any)
		if ($location && starts_with(\RX_BASEDIR, $location))
		{
			$location = substr($location, strlen(\RX_BASEDIR));
		}

		Context::set('ssl_mode', $ssl_mode);
		Context::set('system_message', nl2br($this->getMessage()));
		Context::set('system_message_detail', nl2br($detail));
		Context::set('system_message_location', escape($location));

		$this->setTemplateFile('system_message');
		
		// Default 403 Error
		if($this->getHttpStatusCode() === 200)
		{
			$this->setHttpStatusCode(403);
		}
	}
}
/* End of file message.view.php */
/* Location: ./modules/message/message.view.php */
