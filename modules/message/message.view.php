<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  messageView
 * @author NAVER (developers@xpressengine.com)
 * @brief view class of the message module
 */
class MessageView extends Message
{
	/**
	 * @brief Display messages
	 */
	public function dispMessage($detail = null, $location = null)
	{
		// Get skin configuration
		$config = ModuleModel::getModuleConfig('message') ?: new stdClass;
		if(empty($config->skin))
		{
			$config->skin = 'xedition';
		}
		if(empty($config->mskin))
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

		Context::set('ssl_mode', \RX_SSL);
		Context::set('system_message', nl2br($this->getMessage()));
		Context::set('system_message_detail', nl2br($detail));
		Context::set('system_message_help', self::getErrorHelp(strval($detail)));
		Context::set('system_message_location', escape($location));

		if ($this->getError())
		{
			if ($detail)
			{
				$this->add('errorDetail', $detail);
			}
			if ($location)
			{
				$this->add('errorLocation', $location);
			}
		}

		$this->setTemplateFile('system_message');

		// Default 403 Error
		if($this->getHttpStatusCode() === 200)
		{
			$this->setHttpStatusCode(403);
		}
	}

	/**
	 * Get friendly help message for common types of errors.
	 *
	 * @param string $error_message
	 * @return string
	 */
	public static function getErrorHelp(string $error_message): string
	{
		$regexp_list = [
			'/Class [\'"]Object[\'"] not found/' => 'baseobject',
			'/Undefined constant [\'"][^\'"]+?[\'"]/' => 'undef_constant',
			'/Attempt to assign property [\'"][^\'"]+?[\'"] on null/' => 'undef_object',
			'/Argument #\d+ \(\$\w+\) must be of type (Countable\|)?array, \w+ given ?/' => 'not_array',
			'/Syntax error, unexpected end of file\b/i' => 'unexpected_eof',
		];

		foreach ($regexp_list as $regexp => $key)
		{
			if (preg_match($regexp, $error_message))
			{
				return lang('message.error_help.' . $key);
			}
		}

		return '';
	}
}
/* End of file message.view.php */
/* Location: ./modules/message/message.view.php */
