<?php

namespace Rhymix\Modules\Sociallogin;

class Base extends \ModuleObject
{
	public static $config = null;

	public static $default_services = array(
		'twitter',
		'facebook',
		'google',
		'naver',
		'kakao',
		'discord',
		'github',
		'apple',
	);
	
	public static function getConfig()
	{
		if(self::$config === null)
		{
			$config = getModel('module')->getModuleConfig('sociallogin') ?: new \stdClass();
			
			if (!$config->delete_auto_log_record)
			{
				$config->delete_auto_log_record = 0;
			}

			if (!$config->skin)
			{
				$config->skin = 'default';
			}

			if (!$config->mskin)
			{
				$config->mskin = 'default';
			}

			if (!$config->sns_follower_count)
			{
				$config->sns_follower_count = 0;
			}

			if (!$config->mail_auth_valid_hour)
			{
				$config->mail_auth_valid_hour = 0;
			}

			if (!$config->sns_services)
			{
				$config->sns_services = [];
			}

			if (!$config->sns_input_add_info)
			{
				$config->sns_input_add_info = [];
			}
			
			self::$config = $config;
		}

		return self::$config;
	}

	/**
	 * Get Library for sns 
	 * @param $driver_name
	 * @return \Rhymix\Modules\Sociallogin\Drivers\Base
	 */
	public static function getDriver(string $driver_name): \Rhymix\Modules\Sociallogin\Drivers\Base
	{
		$class_name = '\\Rhymix\\Modules\\Sociallogin\\Drivers\\' . ucfirst($driver_name);
		return $class_name::getInstance();
	}
}
