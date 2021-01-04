<?php

class Sociallogin extends ModuleObject
{
	public $config;

	public $default_services = array(
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
		array('member.procMemberInsert', 'sociallogin', 'controller', 'triggerInsertMember', 'before'),
		array('member.getMemberMenu', 'sociallogin', 'controller', 'triggerMemberMenu', 'after'),
		array('member.deleteMember', 'sociallogin', 'controller', 'triggerDeleteMember', 'after'),
	);

	/**
	 * @brief Constructor
	 */
	function __construct()
	{
		$this->config = $this->getConfig();

		if (!Context::isExistsSSLAction('procSocialloginCallback') && Context::getSslStatus() == 'optional')
		{
			Context::addSSLActions(array(
				'dispSocialloginConfirmMail',
				'procSocialloginConfirmMail',
				'procSocialloginCallback',
				'dispSocialloginConnectSns',
			));
		}
	}

	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

		// Ʈ���� ��ġ
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

	/**
	 * @brief ��� ����
	 */
	function moduleUninstall()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		// Ʈ���� ����
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

	function getConfig()
	{
		$config = getModel('module')->getModuleConfig('sociallogin');

		if (!$config)
		{
			$config = new stdClass();
		}

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
			$config->sns_services = $this->default_services;
		}

		return $config;
	}

	function getLibrary($library_name)
	{
		require_once _XE_PATH_ . '/modules/sociallogin/sociallogin.library.php';

		if (!isset($this->library[$library_name]))
		{
			if (($library_file = sprintf(_XE_PATH_ . '/modules/sociallogin/libs/%s.lib.php', $library_name)) && !file_exists($library_file))
			{
				return;
			}

			require_once($library_file);

			if (($instance_name = sprintf('library%s', ucwords($library_name))) && !class_exists($instance_name, false))
			{
				return;
			}

			$this->library[$library_name] = new $instance_name($library_name);
		}

		return $this->library[$library_name];
	}
}
