<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  member
 * @author NAVER (developers@xpressengine.com)
 * high class of the member module
 */
class Member extends ModuleObject
{
	/**
	 * Constants
	 */
	public const ADMIN_EXTRA_VARS = ['refused_reason', 'limited_reason'];
	public const NOUSE_EXTRA_VARS = ['error_return_url', 'success_return_url', '_rx_ajax_compat', '_rx_ajax_form', '_rx_csrf_token', 'ruleset', 'captchaType', 'use_editor', 'use_html'];
	public const STATUS_LIST = ['APPROVED', 'DENIED', 'UNAUTHED', 'SUSPENDED', 'DELETED'];

	/**
	 * Implement if additional tasks are necessary when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		$oModuleController = getController('module');
		$config = ModuleModel::getModuleConfig('member');

		// Set default config
		if(!$config)
		{
			$config = MemberModel::getMemberConfig();
			$config->mid = 'member';
			$config->force_mid = true;
			$config->password_reset_method = 2;
			$this->createMid($config->mid);
			$oModuleController->insertModuleConfig('member', $config);
		}

		$oMemberModel = getModel('member');
		$oMemberController = getController('member');
		$oMemberAdminController = getAdminController('member');
		$groups = $oMemberModel->getGroups();
		if(!count($groups))
		{
			// Set an administrator, regular member(group1), and associate member(group2)
			$group_args = new stdClass;
			$group_args->title = lang('admin_group');
			$group_args->is_default = 'N';
			$group_args->is_admin = 'Y';
			$output = $oMemberAdminController->insertGroup($group_args);

			$group_args = new stdClass;
			$group_args->title = lang('default_group_1');
			$group_args->is_default = 'Y';
			$group_args->is_admin = 'N';
			$output = $oMemberAdminController->insertGroup($group_args);

			$group_args = new stdClass;
			$group_args->title = lang('default_group_2');
			$group_args->is_default = 'N';
			$group_args->is_admin = 'N';
			$oMemberAdminController->insertGroup($group_args);
		}

		// Configure administrator information
		$admin_args = new stdClass;
		$admin_args->is_admin = 'Y';
		$output = executeQuery('member.getMemberList', $admin_args);
		if(!$output->data)
		{
			$admin_info = Context::gets('password','nick_name','email_address', 'user_id');
			if($admin_info->email_address)
			{
				$admin_info->user_name = 'admin';
				// Insert admin information
				$oMemberAdminController->insertAdmin($admin_info);
				// Log-in Processing
				$output = $oMemberController->doLogin($admin_info->email_address);
			}
		}
		// Register denied ID(default + module name)
		$oModuleModel = getModel('module');
		$module_list = ModuleModel::getModuleList();
		foreach($module_list as $key => $val)
		{
			$oMemberAdminController->insertDeniedID($val->module,'');
		}
		$oMemberAdminController->insertDeniedID('www','');
		$oMemberAdminController->insertDeniedID('root','');
		$oMemberAdminController->insertDeniedID('administrator','');
		$oMemberAdminController->insertDeniedID('telnet','');
		$oMemberAdminController->insertDeniedID('ftp','');
		$oMemberAdminController->insertDeniedID('http','');
		// Create cache directory to use in the member module
		FileHandler::makeDir('./files/member_extra_info/image_name');
		FileHandler::makeDir('./files/member_extra_info/image_mark');
		FileHandler::makeDir('./files/member_extra_info/profile_image');
		FileHandler::makeDir('./files/member_extra_info/signature');
	}

	/**
	 * a method to check if successfully installed
	 *
	 * @return boolean
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();

		// check member directory (11/08/2007 added)
		if(!is_dir("./files/member_extra_info")) return true;
		// check member directory (22/10/2007 added)
		if(!is_dir("./files/member_extra_info/profile_image")) return true;

		// Check length of password column
		if($oDB->getColumnInfo('member', 'password')->size < 250)
		{
			return true;
		}
		if($oDB->getColumnInfo('member_auth_mail', 'new_password')->size < 250)
		{
			return true;
		}

		// Add columns for phone number
		if(!$oDB->isColumnExists("member", "phone_number")) return true;
		if(!$oDB->isIndexExists("member","idx_phone_number")) return true;
		if(!$oDB->isColumnExists("member", "phone_country")) return true;
		if(!$oDB->isIndexExists("member","idx_phone_country")) return true;
		if(!$oDB->isColumnExists("member", "phone_type")) return true;
		if(!$oDB->isIndexExists("member","idx_phone_type")) return true;

		// Add columns for IP address
		if(!$oDB->isColumnExists("member", "ipaddress")) return true;
		if(!$oDB->isIndexExists("member","idx_ipaddress")) return true;
		if(!$oDB->isColumnExists("member", "last_login_ipaddress")) return true;
		if(!$oDB->isIndexExists("member","idx_last_login_ipaddress")) return true;

		// Add column for status
		if(!$oDB->isColumnExists("member", "status")) return true;
		if(!$oDB->isIndexExists("member", "idx_status")) return true;

		// Add column for list order
		if(!$oDB->isColumnExists("member", "list_order")) return true;
		if(!$oDB->isIndexExists("member","idx_list_order")) return true;

		// Check autologin table
		if(!$oDB->isColumnExists("member_autologin", "security_key")) return true;
		if(!$oDB->isColumnExists("member_autologin", "previous_key")) return true;

		// Check scrap folder table
		if(!$oDB->isColumnExists("member_scrap", "folder_srl")) return true;

		if(!$oDB->isIndexExists('member_nickname_log', 'idx_before_nick_name')) return true;
		if(!$oDB->isIndexExists('member_nickname_log', 'idx_after_nick_name')) return true;
		if(!$oDB->isIndexExists('member_nickname_log', 'idx_user_id')) return true;

		// Check individual indexes for member_group_member table
		if(!$oDB->isIndexExists('member_group_member', 'idx_member_srl')) return true;

		// Add device token type and last active date 2020.10.28
		if(!$oDB->isColumnExists('member_devices', 'device_token_type')) return true;
		if(!$oDB->isColumnExists('member_devices', 'last_active_date')) return true;

		// Check member_auth_mail table
		if(!$oDB->isColumnExists('member_auth_mail', 'auth_type')) return true;
		if(!$oDB->isIndexExists('member_auth_mail', 'unique_auth_key')) return true;
		if(!$oDB->isIndexExists('member_auth_mail', 'idx_member_srl')) return true;
		if($oDB->isIndexExists('member_auth_mail', 'unique_key')) return true;

		// Update status column
		$output = executeQuery('member.getDeniedAndStatus');
		if ($output->data->count)
		{
			return true;
		}

		// Check mid
		$config = ModuleModel::getModuleConfig('member');
		if (empty($config->mid) || $this->checkMid($config->mid) !== 1)
		{
			return true;
		}

		// Check members with phone country in old format
		if ($config->phone_number_default_country && !preg_match('/^[A-Z]{3}$/', $config->phone_number_default_country))
		{
			return true;
		}
		$output = executeQuery('member.getMemberCountByPhoneCountry', (object)['phone_country' => '82']);
		if ($output->data->count)
		{
			return true;
		}

		// Check signup form
		if(!$config->signupForm || !is_array($config->signupForm)) return true;
		$phone_found = false;
		foreach($config->signupForm as $signupItem)
		{
			if($signupItem->name === 'find_account_question')
			{
				return true;
			}
			if($signupItem->name === 'email_address' && $signupItem->isPublic !== 'N')
			{
				return true;
			}
			if($signupItem->name === 'phone_number')
			{
				$phone_found = true;
			}
		}
		if(!$phone_found)
		{
			return true;
		}

		// Check agreements
		if(!$config->agreements)
		{
			return true;
		}

		// supprot multilanguage agreement.
		if(FileHandler::exists('./files/member_extra_info/agreement.txt')) return true;
		if(FileHandler::exists('./files/ruleset/insertMember.xml')) return true;
		if(FileHandler::exists('./files/ruleset/login.xml')) return true;

		// Allow duplicate nickname
		if($config->allow_duplicate_nickname == 'Y')
		{
			if($oDB->isIndexExists('member', 'unique_nick_name') || !$oDB->isIndexExists('member', 'idx_nick_name'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleController = getController('module');

		// Check member directory
		FileHandler::makeDir('./files/member_extra_info/image_name');
		FileHandler::makeDir('./files/member_extra_info/image_mark');
		FileHandler::makeDir('./files/member_extra_info/signature');
		FileHandler::makeDir('./files/member_extra_info/profile_image');

		// Check length of password column
		if($oDB->getColumnInfo('member', 'password')->size < 250)
		{
			$oDB->modifyColumn('member', 'password', 'varchar', 250, null, true);
		}
		if($oDB->getColumnInfo('member_auth_mail', 'new_password')->size < 250)
		{
			$oDB->modifyColumn('member_auth_mail', 'new_password', 'varchar', 250, null, true);
		}

		// Add columns for phone number
		if(!$oDB->isColumnExists("member", "phone_number"))
		{
			$oDB->addColumn("member", "phone_number", "varchar", 80, null, false, 'email_host');
		}
		if(!$oDB->isColumnExists("member", "phone_country"))
		{
			$oDB->addColumn("member", "phone_country", "varchar", 10, null, false, 'phone_number');
		}
		if(!$oDB->isColumnExists("member", "phone_type"))
		{
			$oDB->addColumn("member", "phone_type", "varchar", 10, null, false, 'phone_country');
		}
		if(!$oDB->isIndexExists("member","idx_phone_number"))
		{
			$oDB->addIndex("member","idx_phone_number", array("phone_number"));
		}
		if(!$oDB->isIndexExists("member","idx_phone_country"))
		{
			$oDB->addIndex("member","idx_phone_country", array("phone_country"));
		}
		if(!$oDB->isIndexExists("member","idx_phone_type"))
		{
			$oDB->addIndex("member","idx_phone_type", array("phone_type"));
		}

		// Add columns for IP address
		if(!$oDB->isColumnExists("member", "ipaddress"))
		{
			$oDB->addColumn("member", "ipaddress", "varchar", 120, null, false, 'regdate');
		}
		if(!$oDB->isColumnExists("member", "last_login_ipaddress"))
		{
			$oDB->addColumn("member", "last_login_ipaddress", "varchar", 120, null, false, 'last_login');
		}
		if(!$oDB->isIndexExists("member","idx_ipaddress"))
		{
			$oDB->addIndex("member","idx_ipaddress", array("ipaddress"));
		}
		if(!$oDB->isIndexExists("member","idx_last_login_ipaddress"))
		{
			$oDB->addIndex("member","idx_last_login_ipaddress", array("last_login_ipaddress"));
		}

		// Add column for status
		if(!$oDB->isColumnExists("member", "status"))
		{
			$oDB->addColumn("member", "status", "varchar", 20, 'APPROVED', true, 'denied');
		}
		if(!$oDB->isIndexExists("member", "idx_status"))
		{
			$oDB->addIndex("member", "idx_status", array("status"));
		}

		// Add column for list order
		if(!$oDB->isColumnExists("member", "list_order"))
		{
			$oDB->addColumn("member", "list_order", "number", 11);
			@set_time_limit(0);
			$args = new stdClass();
			$args->list_order = 'member_srl';
			executeQuery('member.updateMemberListOrderAll',$args);
			executeQuery('member.updateMemberListOrderAll');
		}
		if(!$oDB->isIndexExists("member","idx_list_order"))
		{
			$oDB->addIndex("member","idx_list_order", array("list_order"));
		}

		// Check autologin table
		if(!$oDB->isColumnExists("member_autologin", "security_key"))
		{
			$oDB->dropTable('member_autologin');
			$oDB->createTable($this->module_path . '/schemas/member_autologin.xml');
		}
		if(!$oDB->isColumnExists("member_autologin", "previous_key"))
		{
			$oDB->addColumn("member_autologin", "previous_key", "varchar", 80, null, false, "security_key");
		}

		// Check scrap folder table
		if(!$oDB->isColumnExists("member_scrap", "folder_srl"))
		{
			$oDB->addColumn("member_scrap", "folder_srl", "number", 11);
			$oDB->addIndex("member_scrap","idx_folder_srl", array("folder_srl"));
		}

		// Add to index in member nickname log table. 2020. 07 .20 @BJRambo
		if(!$oDB->isIndexExists('member_nickname_log', 'idx_before_nick_name'))
		{
			$oDB->addIndex('member_nickname_log', 'idx_before_nick_name', array('before_nick_name'));
			$oDB->addIndex('member_nickname_log', 'idx_after_nick_name', array('after_nick_name'));
			$oDB->addIndex('member_nickname_log', 'idx_user_id', array('user_id'));
		}

		// Check index for member_group_member table
		if(!$oDB->isIndexExists('member_group_member', 'idx_member_srl'))
		{
			$oDB->addIndex('member_group_member', 'idx_member_srl', array('member_srl'));
		}

		// Add device token type and last active date 2020.10.28
		if(!$oDB->isColumnExists('member_devices', 'device_token_type'))
		{
			$oDB->addColumn('member_devices', 'device_token_type', 'varchar', '20', '', true, 'device_token');
			$oDB->addIndex('member_devices', 'idx_device_token_type', array('device_token_type'));
			$oDB->query("UPDATE member_devices SET device_token_type = 'fcm' WHERE device_type = 'android' OR LENGTH(device_token) > 64");
			$oDB->query("UPDATE member_devices SET device_token_type = 'apns' WHERE device_type = 'ios' AND LENGTH(device_token) = 64");
		}
		if(!$oDB->isColumnExists('member_devices', 'last_active_date'))
		{
			$oDB->addColumn('member_devices', 'last_active_date', 'date', '', '', true, 'regdate');
			$oDB->addIndex('member_devices', 'idx_last_active_date', array('last_active_date'));
			$oDB->query("UPDATE member_devices SET last_active_date = regdate WHERE last_active_date = ''");
		}

		// Check member_auth_mail table
		if(!$oDB->isColumnExists('member_auth_mail', 'auth_type'))
		{
			$oDB->addColumn('member_auth_mail', 'auth_type', 'varchar', '20', 'password_v1', true, 'new_password');
			$oDB->query("UPDATE member_auth_mail SET auth_type = 'signup' WHERE is_register = 'Y'");
		}
		if(!$oDB->isIndexExists('member_auth_mail', 'unique_auth_key'))
		{
			$oDB->addIndex('member_auth_mail', 'unique_auth_key', ['auth_key'], true);
		}
		if(!$oDB->isIndexExists('member_auth_mail', 'idx_member_srl'))
		{
			$oDB->addIndex('member_auth_mail', 'idx_member_srl', ['member_srl']);
		}
		if($oDB->isIndexExists('member_auth_mail', 'unique_key'))
		{
			$oDB->dropIndex('member_auth_mail', 'unique_key');
		}

		// Update status column
		$output = executeQuery('member.getDeniedAndStatus');
		if ($output->data->count)
		{
			$oDB->begin();
			$result = $oDB->query("UPDATE `member` SET `status` = 'DENIED' WHERE `denied` = 'Y'");
			if ($result)
			{
				$result = $oDB->query("UPDATE `member` AS `m` " .
					"JOIN `member_auth_mail` AS `a` ON `m`.`member_srl` = `a`.`member_srl` " .
					"SET `m`.`status` = 'UNAUTHED' WHERE `m`.`status` = 'DENIED' " .
					"AND `a`.`is_register` = 'Y'");
				if ($result)
				{
					$oDB->commit();
				}
				else
				{
					$oDB->rollback();
				}
			}
			else
			{
				$oDB->rollback();
			}
		}

		// Get module config
		$config = ModuleModel::getModuleConfig('member') ?: new stdClass;
		$changed = false;

		// Check mid
		if (empty($config->mid) || $this->checkMid($config->mid) !== 1)
		{
			$config->mid = 'member';
			$output = $this->createMid($config->mid, $config->skin ?: 'default', $config->mskin ?: 'default');
			if (!$output->toBool())
			{
				return $output;
			}
			$changed = true;
		}

		// Check members with phone country in old format
		if ($config->phone_number_default_country && !preg_match('/^[A-Z]{3}$/', $config->phone_number_default_country))
		{
			$config->phone_number_default_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($config->phone_number_default_country);
			$changed = true;
		}
		$output = executeQuery('member.getMemberCountByPhoneCountry', (object)['phone_country' => '82']);
		if ($output->data->count)
		{
			executeQuery('member.updateMemberPhoneCountry', (object)array(
				'old_phone_country' => '82',
				'new_phone_country' => 'KOR',
			));
		}

		// Check signup form
		$oModuleController = getController('module');
		if(empty($config->identifier))
		{
			$config->identifier = 'user_id';
			$changed = true;
		}
		if(empty($config->identifiers))
		{
			$config->identifiers = array('user_id', 'email_address');
			$changed = true;
		}
		if(empty($config->signupForm) || !is_array($config->signupForm))
		{
			$config->signupForm = MemberAdminController::createSignupForm($config);
			$changed = true;
		}
		if($changed)
		{
			$oModuleController->updateModuleConfig('member', $config);
		}

		$phone_found = false;
		foreach($config->signupForm as $no => $signupItem)
		{
			if($signupItem->name === 'find_account_question')
			{
				unset($config->signupForm[$no]);
				$config->signupForm = array_values($config->signupForm);
				$changed = true;
				continue;
			}
			if($signupItem->name === 'email_address' && $signupItem->isPublic !== 'N')
			{
				$signupItem->isPublic = 'N';
				$changed = true;
				continue;
			}
			if($signupItem->name === 'phone_number')
			{
				$phone_found = true;
				continue;
			}
		}
		// Insert phone number after email address
		if(!$phone_found)
		{
			$newForm = array();
			foreach($config->signupForm as $signupItem)
			{
				$newForm[] = $signupItem;
				if($signupItem->name === 'email_address')
				{
					$newItem = new stdClass;
					$newItem->isDefaultForm = true;
					$newItem->name = $newItem->title = 'phone_number';
					$newItem->mustRequired = false;
					$newItem->imageType = false;
					$newItem->required = false;
					$newItem->isUse = false;
					$newItem->isPublic = 'N';
					$newForm[] = $newItem;
				}
			}
			$config->signupForm = $newForm;
			$changed = true;
		}

		// Check agreements
		if(!$config->agreements)
		{
			$agreement = new stdClass;
			$agreement->title = lang('agreement');
			$agreement->content = $config->agreement;
			$agreement->use_editor = 'Y';
			$agreement->type = 'required';
			$config->agreements[] = $agreement;
			$config->agreement = null;
			$changed = true;
		}

		// Save updated config
		if($changed)
		{
			$oModuleController->updateModuleConfig('member', $config);
		}

		if(file_exists('./files/member_extra_info/agreement.txt'))
		{
			$source_file = RX_BASEDIR.'files/member_extra_info/agreement.txt';
			$target_file = RX_BASEDIR.'files/member_extra_info/agreement_' . Context::get('lang_type') . '.txt';

			FileHandler::rename($source_file, $target_file);
		}

		if(FileHandler::exists('./files/ruleset/insertMember.xml'))
		{
			FileHandler::removeFile('./files/ruleset/insertMember.xml');
		}
		if(FileHandler::exists('./files/ruleset/login.xml'))
		{
			FileHandler::removeFile('./files/ruleset/login.xml');
		}

		// Allow duplicate nickname
		if($config->allow_duplicate_nickname == 'Y')
		{
			if($oDB->isIndexExists('member', 'unique_nick_name'))
			{
				$oDB->dropIndex('member', 'unique_nick_name', true);
			}
			if(!$oDB->isIndexExists('member', 'idx_nick_name'))
			{
				$oDB->addIndex('member', 'idx_nick_name', array('nick_name'));
			}
		}
	}

	/**
	 * Check mid
	 *
	 * This method returns 0 if the mid doesn't exist,
	 * 1 if the mid exists and belongs to this module,
	 * and -1 if the mid exists but belongs to a different module.
	 *
	 * @param string $mid
	 * @return int
	 */
	public function checkMid($mid = 'member')
	{
		$module_info = \ModuleModel::getModuleInfoByMid($mid);
		if (!$module_info)
		{
			return 0;
		}
		elseif ($module_info->module === $this->module)
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Create mid
	 *
	 * @param string $mid
	 * @param string $skin
	 * @param string $mskin
	 * @return BaseObject
	 */
	public function createMid($mid = 'member', $skin = 'default', $mskin = 'default')
	{
		$check = $this->checkMid($mid);
		if ($check == 1)
		{
			return new BaseObject();
		}
		if ($check == -1)
		{
			return new BaseObject(-1, sprintf(lang('msg_exists_member_mid'), $mid));
		}

		return ModuleController::getInstance()->insertModule((object)array(
			'mid' => $mid,
			'module' => $this->module,
			'browser_title' => lang('member'),
			'description' => '',
			'layout_srl' => -1,
			'mlayout_srl' => -1,
			'skin' => $skin,
			'mskin' => $mskin,
			'use_mobile' => 'Y',
			'isMenuCreate' => false,
		));
	}

	/**
	 * @brief Record login error and return the error, about IPaddress.
	 */
	function recordLoginError($error = 0, $message = 'success')
	{
		if($error == 0) return new BaseObject($error, $message);

		// Create a member model object
		$config = MemberModel::getMemberConfig();

		// Check if there is recoding table.
		$oDB = DB::getInstance();
		if(!$oDB->isTableExists('member_login_count') || $config->enable_login_fail_report == 'N') return new BaseObject($error, $message);

		$args = new stdClass();
		$args->ipaddress = \RX_CLIENT_IP;

		$output = executeQuery('member.getLoginCountByIp', $args);
		if($output->data && $output->data->count)
		{
			$last_update = strtotime($output->data->last_update);
			$term = intval($_SERVER['REQUEST_TIME']-$last_update);
			//update, if IP address access in a short time, update count. If not, make count 1.
			if($term < $config->max_error_count_time)
			{
				$args->count = $output->data->count + 1;
			}
			else
			{
				$args->count = 1;
			}
			$output = executeQuery('member.updateLoginCountByIp', $args);
		}
		else
		{
			//insert
			$args->count = 1;
			$output = executeQuery('member.insertLoginCountByIp', $args);
		}
		return new BaseObject($error, $message);
	}

	/**
	 * @brief Record login error and return the error, about MemberSrl.
	 */
	function recordMemberLoginError($error = 0, $message = 'success', $args = NULL)
	{
		if($error == 0 || !$args->member_srl) return new BaseObject($error, $message);

		// Create a member model object
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();

		// Check if there is recoding table.
		$oDB = DB::getInstance();
		if(!$oDB->isTableExists('member_count_history') || $config->enable_login_fail_report == 'N') return new BaseObject($error, $message);

		$output = executeQuery('member.getLoginCountHistoryByMemberSrl', $args);
		if($output->data && $output->data->content)
		{
			//update
			$content = unserialize($output->data->content);
			if (is_array($content) && count($content) >= 250)
			{
				$content = array_slice($content, -200);
			}
			$content[] = array(\RX_CLIENT_IP, lang($message), \RX_TIME);
			$args->content = serialize($content);
			$output = executeQuery('member.updateLoginCountHistoryByMemberSrl', $args);
		}
		else
		{
			//insert
			$content[0] = array(\RX_CLIENT_IP, lang($message), \RX_TIME);
			$args->content = serialize($content);
			$output = executeQuery('member.insertLoginCountHistoryByMemberSrl', $args);
		}
		return $this->recordLoginError($error, $message);
	}
}
/* End of file member.class.php */
/* Location: ./modules/member/member.class.php */
