<?php

class Sociallogin extends ModuleObject
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

	private $triggers = array(
		array('moduleObject.proc', 'sociallogin', 'controller', 'triggerModuleObjectAfter', 'after'),
		array('document.insertDocument', 'sociallogin', 'controller', 'triggerInsertDocumentAfter', 'after'),
		array('member.procMemberInsert', 'sociallogin', 'controller', 'triggerProcInsertMemberBefore', 'before'),
		array('member.insertMember', 'sociallogin', 'controller', 'triggerInsertMemberAfter', 'after'),
		array('member.getMemberMenu', 'sociallogin', 'controller', 'triggerMemberMenu', 'after'),
		array('member.deleteMember', 'sociallogin', 'controller', 'triggerDeleteMember', 'after'),
	);

	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new BaseObject(0, 'success_updated');
	}

	function moduleUninstall()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger)
		{
			if ($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new BaseObject();
	}

	function recompileCache()
	{
	}

	public static function getConfig()
	{
		if(self::$config === null)
		{
			$config = getModel('module')->getModuleConfig('sociallogin') ?: new stdClass();
			
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
	function getDriver(string $driver_name): \Rhymix\Modules\Sociallogin\Drivers\Base
	{
		$class_name = '\\Rhymix\\Modules\\Sociallogin\\Drivers\\' . ucfirst($driver_name);
		return $class_name::getInstance();
	}
}
