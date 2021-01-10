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
	);

	private $library = array();

	private $triggers = array(
		array('moduleHandler.init', 'sociallogin', 'controller', 'triggerModuleHandler', 'after'),
		array('moduleObject.proc', 'sociallogin', 'controller', 'triggerModuleObjectBefore', 'before'),
		array('moduleObject.proc', 'sociallogin', 'controller', 'triggerModuleObjectAfter', 'after'),
		array('display', 'sociallogin', 'controller', 'triggerDisplay', 'before'),
		array('document.insertDocument', 'sociallogin', 'controller', 'triggerInsertDocumentAfter', 'after'),
		array('member.procMemberInsert', 'sociallogin', 'controller', 'triggerInsertMemberAction', 'before'),
		array('member.getMemberMenu', 'sociallogin', 'controller', 'triggerMemberMenu', 'after'),
		array('member.deleteMember', 'sociallogin', 'controller', 'triggerDeleteMember', 'after'),
	);

	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		if (!$oDB->isColumnExists('sociallogin', 'socialnumber'))
		{
			return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		if (!$oDB->isColumnExists('sociallogin', 'socialnumber'))
		{
			$oDB->addColumn('sociallogin', 'socialnumber', 'number', 11, null, false);
		}

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
	 * @param $library_name
	 * @return mixed|bool
	 */
	function getLibrary($library_name)
	{
		require_once RX_BASEDIR . '/modules/sociallogin/sociallogin.library.php';

		if (!isset($this->library[$library_name]))
		{
			
			$library_file = RX_BASEDIR . "/modules/sociallogin/libs/{$library_name}.lib.php";
			if ($library_file && !FileHandler::exists($library_file))
			{
				return false;
			}

			require_once($library_file);
			$ucword_name = ucwords($library_name);
			$instance_name = "library{$ucword_name}";
			if ($instance_name && !class_exists($instance_name, false))
			{
				return false;
			}

			$this->library[$library_name] = new $instance_name($library_name);
		}

		return $this->library[$library_name];
	}
}
