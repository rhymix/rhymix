<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberController
 * @author NAVER (developers@xpressengine.com)
 * Controller class of member module
 */
class memberController extends member
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Log-in by checking user_id and password
	 *
	 * @param string $user_id
	 * @param string $password
	 * @param string $keep_signed
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberLogin($user_id = null, $password = null, $keep_signed = null)
	{
		if(!$user_id && !$password && Context::getRequestMethod() == 'GET')
		{
			$this->setRedirectUrl(getNotEncodedUrl(''));
			throw new Rhymix\Framework\Exception('null_user_id');
		}

		// Variables
		if(!$user_id) $user_id = Context::get('user_id');
		$user_id = trim($user_id);

		if(!$password) $password = Context::get('password');
		$password = trim($password);

		if(!$keep_signed) $keep_signed = Context::get('keep_signed');
		// Return an error when id and password doesn't exist
		if(!$user_id) throw new Rhymix\Framework\Exception('null_user_id');
		if(!$password) throw new Rhymix\Framework\Exception('null_password');

		$output = $this->doLogin($user_id, $password, $keep_signed=='Y'?true:false);
		if (!$output->toBool()) return $output;

		$config = ModuleModel::getModuleConfig('member');
		$member_info = Context::get('logged_info');

		// Check change_password_date
		$limit_date = $config->change_password_date;

		// Check if change_password_date is set
		if($limit_date > 0)
		{
			if($member_info->change_password_date < date ('YmdHis', strtotime ('-' . $limit_date . ' day')))
			{
				$msg = sprintf(lang('msg_change_password_date'), $limit_date);
				return $this->setRedirectUrl(getNotEncodedUrl('','vid',Context::get('vid'),'mid',Context::get('mid'),'act','dispMemberModifyPassword'), new BaseObject(-1, $msg));
			}
		}

		// Delete all previous authmail if login is successful
		$args = new stdClass();
		$args->member_srl = $member_info->member_srl;
		executeQuery('member.deleteAuthMail', $args);
		
		// If a device token is supplied, attempt to register it.
		$device_token = Context::get('device_token');
		if ($device_token)
		{
			$output = executeQuery('member.getMemberDevice', ['device_token' => $device_token]);
			if (!$output->data || $output->data->member_srl != $member_info->member_srl)
			{
				$output = $this->procMemberRegisterDevice($member_info->member_srl);
				if ($output instanceof BaseObject && !$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				executeQuery('member.updateMemberDeviceLastActiveDate', ['device_token' => $device_token]);
			}
		}
		
		if(!$config->after_login_url)
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
		}
		else
		{
			$returnUrl = $config->after_login_url;
		}
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Register device
	 */
	function procMemberRegisterDevice($member_srl = null)
	{
		Context::setResponseMethod('JSON');

		// Check user_id, password, device_token
		$allow_guest_device = config('push.allow_guest_device');
		$user_id = Context::get('user_id');
		$password = Context::get('password');
		$device_token = Context::get('device_token');
		$device_model = escape(Context::get('device_model'));

		// Return an error when id and password doesn't exist
		if(!$member_srl && !$user_id && !$allow_guest_device) return new BaseObject(-1, 'NULL_USER_ID');
		if(!$member_srl && !$password && !$allow_guest_device) return new BaseObject(-1, 'NULL_PASSWORD');
		if(!$device_token) return new BaseObject(-1, 'NULL_DEVICE_TOKEN');

		// Get device information
		$browserInfo = Rhymix\Framework\UA::getBrowserInfo();
		$device_type = escape(strtolower($browserInfo->os));
		$device_version = $browserInfo->os_version;
		if(!$device_model)
		{
			$device_model = escape($browserInfo->device);
		}

		// Detect device token type
		if (preg_match('/^[0-9a-z]{64}$/', $device_token))
		{
			$device_token_type = 'apns';
		}
		elseif (preg_match('/^[0-9a-zA-Z:_-]+$/', $device_token) && strlen($device_token) > 64)
		{
			$device_token_type = 'fcm';
		}
		else
		{
			return new BaseObject(-1, 'INVALID_DEVICE_TOKEN');
		}
		
		if ($member_srl)
		{
			$member_srl = intval($member_srl);
		}
		elseif ($user_id && $password)
		{
			$output = $this->procMemberLogin($user_id, $password);
			if(!$output->toBool())
			{
				return new BaseObject(-1, 'LOGIN_FAILED');
			}
			$logged_info = Context::get('logged_info');
			$member_srl = intval($logged_info->member_srl);
		}
		else
		{
			$logged_info = null;
			$member_srl = 0;
		}

		// Generate keys
		$random_key = Rhymix\Framework\Security::getRandom();
		$device_key = hash_hmac('sha256', $random_key, $member_srl . ':' . config('crypto.authentication_key'));

		// Prepare query arguments
		$args = new stdClass;
		$args->device_srl = getNextSequence();
		$args->member_srl = $member_srl;
		$args->device_token = $device_token;
		$args->device_token_type = $device_token_type;
		$args->device_key = $device_key;
		$args->device_type = $device_type;
		$args->device_version = $device_version;
		$args->device_model = $device_model;
		
		// Call trigger (before)
		$trigger_output = ModuleHandler::triggerCall('member.insertMemberDevice', 'before', $args);
		if(!$trigger_output->toBool()) return $trigger_output;

		// Start transaction
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// Remove duplicated token key
		executeQuery('member.deleteMemberDevice', ['device_token' => $device_token]);
		
		// Create member_device
		$output = executeQuery('member.insertMemberDevice', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// Call trigger (after)
		ModuleHandler::triggerCall('member.insertMemberDevice', 'after', $args);
		
		$oDB->commit();

		// Set parameters
		$this->add('member_srl', $member_srl);
		$this->add('user_id', $logged_info ? $logged_info->user_id : null);
		$this->add('user_name', $logged_info ? $logged_info->user_name : null);
		$this->add('nick_name', $logged_info ? $logged_info->nick_name : null);
		$this->add('device_key', $random_key);
	}

	/**
	 * Automatically log-in to registered device
	 */
	function procMemberLoginWithDevice()
	{
		Context::setResponseMethod('JSON');

		// Check member_srl, device_token, device_key
		$allow_guest_device = config('push.allow_guest_device');
		$member_srl = intval(Context::get('member_srl'));
		$device_token = Context::get('device_token');
		$random_key = Context::get('device_key');

		// Return an error when id, password and device_key doesn't exist
		if(!$member_srl && !$allow_guest_device) return new BaseObject(-1, 'NULL_MEMBER_SRL');
		if(!$device_token) return new BaseObject(-1, 'NULL_DEVICE_TOKEN');
		if(!$random_key) return new BaseObject(-1, 'NULL_DEVICE_KEY');

		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->device_token = $device_token;
		$args->device_key = hash_hmac('sha256', $random_key, $member_srl . ':' . config('crypto.authentication_key'));
		$output = executeQueryArray('member.getMemberDevice', $args);
		if(!$output->toBool())
		{
			return new BaseObject(-1, 'DEVICE_RETRIEVE_FAILED');
		}

		if(!$output->data)
		{
			return new BaseObject(-1, 'UNREGISTERED_DEVICE');
		}

		// Log-in
		if($member_srl)
		{
			$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl);
			$output = $this->doLogin($member_info->user_id);
			if(!$output->toBool())
			{
				return new BaseObject(-1, 'LOGIN_FAILED');
			}
		}
		else
		{
			$member_info = null;
		}
		
		// Update last active date
		executeQuery('member.updateMemberDeviceLastActiveDate', ['device_token' => $device_token]);
		
		$this->add('member_srl', $member_srl);
		$this->add('user_id', $member_info ? $member_info->user_id : null);
		$this->add('user_name', $member_info ? $member_info->user_name : null);
		$this->add('nick_name', $member_info ? $member_info->nick_name : null);
	}

	/**
	 * Log-out
	 *
	 * @return Object
	 */
	function procMemberLogout()
	{
		// Call a trigger before log-out (before)
		$logged_info = Context::get('logged_info');
		$trigger_output = ModuleHandler::triggerCall('member.doLogout', 'before', $logged_info);
		if(!$trigger_output->toBool()) return $trigger_output;
		
		// Destroy session information
		Rhymix\Framework\Session::logout();
		self::clearMemberCache($logged_info->member_srl);
		
		// Call a trigger after log-out (after)
		ModuleHandler::triggerCall('member.doLogout', 'after', $logged_info);

		$output = new BaseObject();

		$config = ModuleModel::getModuleConfig('member');
		if($config->after_logout_url)
		{
			$output->redirect_url = $config->after_logout_url;
		}

		return $output;
	}

	/**
	 * Scrap document
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberScrapDocument()
	{
		$document_srl = (int) (Context::get('document_srl') ?: Context::get('target_srl'));
		if(!$document_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$oDocument = DocumentModel::getDocument($document_srl);
		
		// Check document
		if(!$oDocument->isAccessible())
		{
			throw new Rhymix\Framework\Exception('msg_is_secret');
		}
		
		$module_info = ModuleModel::getModuleInfoByModuleSrl($oDocument->get('module_srl'));
		
		$logged_info = Context::get('logged_info');
		$grant = ModuleModel::getGrant($module_info, $logged_info);
		
		// Check access to module of the document
		if(!$grant->access)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// Check grant to module of the document
		if(isset($grant->list) && isset($grant->view) && (!$grant->list || !$grant->view))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// Check consultation option
		if(isset($grant->consultation_read) && $module_info->consultation == 'Y' && !$grant->consultation_read && !$oDocument->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// Find default scrap folder
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->name = '/DEFAULT/';
		$output = executeQuery('member.getScrapFolderList', $args);
		if($output->toBool() && is_object($output->data) && $output->data->folder_srl)
		{
			$default_folder_srl = $output->data->folder_srl;
		}
		else
		{
			$default_folder_srl = null;
		}
		
		// Variables
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $default_folder_srl;
		$args->user_id = $oDocument->get('user_id');
		$args->user_name = $oDocument->get('user_name');
		$args->nick_name = $oDocument->get('nick_name');
		$args->target_member_srl = $oDocument->get('member_srl');
		$args->title = $oDocument->get('title');
		
		// Check if already scrapped
		$output = executeQuery('member.getScrapDocument', $args);
		if($output->data->count)
		{
			throw new Rhymix\Framework\Exception('msg_alreay_scrapped');
		}
		
		// Call trigger (before)
		$trigger_output = ModuleHandler::triggerCall('member.procMemberScrapDocument', 'before', $args);
		if (!$trigger_output->toBool())
		{
			return $trigger_output;
		}
		
		// Insert
		$output = executeQuery('member.addScrapDocument', $args);
		if(!$output->toBool()) return $output;
		
		// Call trigger (after)
		ModuleHandler::triggerCall('member.procMemberScrapDocument', 'after', $args);
		
		$this->setError(-1);
		$this->setMessage('success_registed');
	}

	/**
	 * Delete a scrap
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberDeleteScrap()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		if(!$document_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		// Variables
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->document_srl = $document_srl;
		return executeQuery('member.deleteScrapDocument', $args);
	}

	/**
	 * Move a scrap to another folder
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberMoveScrapFolder()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		$folder_srl = (int)Context::get('folder_srl');
		if(!$document_srl || !$folder_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Check that the target folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Move
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->document_srl = $document_srl;
		$args->folder_srl = $folder_srl;
		return executeQuery('member.updateScrapDocumentFolder', $args);
	}

	/**
	 * Create a scrap folder
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertScrapFolder()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');
		
		// Get new folder name
		$folder_name = Context::get('name');
		$folder_name = escape(trim(utf8_normalize_spaces($folder_name)));
		if(!$folder_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Check existing folder with same name
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->name = $folder_name;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(count($output->data) || $folder_name === lang('default_folder'))
		{
			throw new Rhymix\Framework\Exception('msg_folder_alreay_exists');
		}
		
		// Create folder
		$args = new stdClass;
		$args->folder_srl = getNextSequence();
		$args->member_srl = $logged_info->member_srl;
		$args->name = $folder_name;
		$args->list_order = $args->folder_srl;
		$this->add('folder_srl', $args->folder_srl);
		return executeQuery('member.insertScrapFolder', $args);
	}

	/**
	 * Rename a scrap folder
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberRenameScrapFolder()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');
		
		// Get new folder name
		$folder_srl = intval(Context::get('folder_srl'));
		$folder_name = Context::get('name');
		$folder_name = escape(trim(utf8_normalize_spaces($folder_name)));
		if(!$folder_srl || !$folder_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Check that the original folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if(array_first($output->data)->name === '/DEFAULT/')
		{
			throw new Rhymix\Framework\Exception('msg_folder_is_default');
		}
		
		// Check existing folder with same name
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->not_folder_srl = $folder_srl;
		$args->name = $folder_name;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(count($output->data) || $folder_name === lang('default_folder'))
		{
			throw new Rhymix\Framework\Exception('msg_folder_alreay_exists');
		}
		
		// Rename folder
		$args = new stdClass;
		$args->folder_srl = $folder_srl;
		$args->name = $folder_name;
		return executeQuery('member.updateScrapFolder', $args);
	}

	/**
	 * Delete a scrap folder
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberDeleteScrapFolder()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');
		
		// Get folder_srl to delete
		$folder_srl = intval(Context::get('folder_srl'));
		if(!$folder_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Check that the folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if(array_first($output->data)->name === '/DEFAULT/')
		{
			throw new Rhymix\Framework\Exception('msg_folder_is_default');
		}
		
		// Check that the folder is empty
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapDocumentList', $args);
		if(count($output->data))
		{
			throw new Rhymix\Framework\Exception('msg_folder_not_empty');
		}
		
		// Delete folder
		$args = new stdClass;
		$args->folder_srl = $folder_srl;
		return executeQuery('member.deleteScrapFolder', $args);
	}

	/**
	 * Migrate a member's scrapped documents to the new folder system.
	 *
	 * @param int $member_srl
	 * @return void|Object (void : success, Object : fail)
	 */
	function migrateMemberScrappedDocuments($member_srl)
	{
		$args = new stdClass;
		$args->folder_srl = getNextSequence();
		$args->member_srl = $member_srl;
		$args->name = '/DEFAULT/';
		$args->list_order = $args->folder_srl;
		$output = executeQuery('member.insertScrapFolder', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$output = executeQuery('member.updateScrapFolderFromNull', $args);
		if(!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * Save posts
	 * @deprecated - instead Document Controller - procDocumentTempSave method use
	 * @return Object
	 */
	function procMemberSaveDocument()
	{
		return new BaseObject(0, 'Deprecated method');
	}

	/**
	 * Delete the post
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberDeleteSavedDocument()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		if(!$document_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
		
		$oDocument = DocumentModel::getDocument($document_srl);
		if ($oDocument->get('member_srl') != $logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$configStatusList = DocumentModel::getStatusList();
		if ($oDocument->get('status') != $configStatusList['temp'])
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Variables
		$oDocumentController = getController('document');
		$oDocumentController->deleteDocument($document_srl);
	}
	
	/**
	 * Delete an autologin
	 */
	function procMemberDeleteAutologin()
	{
		// Check login information
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		$logged_info = Context::get('logged_info');
		
		$autologin_id = intval(Context::get('autologin_id'));
		$autologin_key = Context::get('autologin_key');
		if (!$autologin_id || !$autologin_key)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$args = new stdClass;
		$args->autologin_id = $autologin_id;
		$args->autologin_key = $autologin_key;
		$output = executeQueryArray('member.getAutologin', $args);
		if ($output->toBool() && $output->data)
		{
			$autologin_info = array_first($output->data);
			if ($autologin_info->member_srl == $logged_info->member_srl)
			{
				$output = executeQuery('member.deleteAutologin', $args);
				if ($output->toBool())
				{
					$this->add('deleted', 'Y');
				}
				else
				{
					$this->add('deleted', 'N');
				}
			}
			else
			{
				$this->add('deleted', 'N');
			}
		}
		else
		{
			$this->add('deleted', 'N');
		}
	}

	/**
	 * Check values when member joining
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberCheckValue()
	{
		$name = Context::get('name');
		$value = Context::get('value');
		if(!$value) return;

		$config = MemberModel::getMemberConfig();

		// Check if logged-in
		$logged_info = Context::get('logged_info');

		switch($name)
		{
			case 'user_id' :
				// Check denied ID
				if(MemberModel::isDeniedID($value)) return new BaseObject(0,'denied_user_id');
				// Check if duplicated
				$member_srl = MemberModel::getMemberSrlByUserID($value);
				if($member_srl && $logged_info->member_srl != $member_srl ) return new BaseObject(0,'msg_exists_user_id');
				break;
			case 'nick_name' :
				// Check denied ID
				if(MemberModel::isDeniedNickName($value))
				{
					return new BaseObject(0,'denied_nick_name');
				}
				// Check if duplicated
				if($config->allow_duplicate_nickname !== 'Y')
				{
					$member_srl = MemberModel::getMemberSrlByNickName($value);
					if($member_srl && $logged_info->member_srl != $member_srl ) return new BaseObject(0,'msg_exists_nick_name');
				}
				break;
			case 'email_address' :
				// Check managed Email Host
				if(MemberModel::isDeniedEmailHost($value))
				{
					$emailhost_check = $config->emailhost_check;

					$managed_email_host = lang('managed_email_host');

					$email_hosts = MemberModel::getManagedEmailHosts();
					foreach ($email_hosts as $host)
					{
						$hosts[] = $host->email_host;
					}
					$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
					return new BaseObject(0,$message);
				}

				// Check if duplicated
				$member_srl = MemberModel::getMemberSrlByEmailAddress($value);
				if($member_srl && $logged_info->member_srl != $member_srl ) return new BaseObject(0,'msg_exists_email_address');
				break;
		}
	}

	/**
	 * Join Membership
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsert()
	{
		if (Context::getRequestMethod() == 'GET')
		{
			throw new Rhymix\Framework\Exceptions\SecurityViolation;
		}
		
		$config = MemberModel::getMemberConfig();

		// call a trigger (before)
		$trigger_output = ModuleHandler::triggerCall ('member.procMemberInsert', 'before', $config);
		if(!$trigger_output->toBool ()) return $trigger_output;
		// Check if an administrator allows a membership
		if($config->enable_join != 'Y') throw new Rhymix\Framework\Exceptions\FeatureDisabled('msg_signup_disabled');

		// Check if the user accept the license terms (only if terms exist)
		$accept_agreement = Context::get('accept_agreement');
		if(!is_array($accept_agreement))
		{
			$accept_agreement = array_fill(0, count($config->agreements), $accept_agreement);
		}
		$accept_agreement_rearranged = array();
		foreach($config->agreements as $i => $agreement)
		{
			if($agreement->type === 'disabled')
			{
				continue;
			}
			if($agreement->type === 'required' && $accept_agreement[$i] !== 'Y')
			{
				throw new Rhymix\Framework\Exception('msg_accept_agreement');
			}
			$accept_agreement_rearranged[$i] = $accept_agreement[$i] === 'Y' ? 'Y' : 'N';
		}

		// Extract the necessary information in advance
		$getVars = array();
		$use_phone = false;
		if($config->signupForm)
		{
			foreach($config->signupForm as $formInfo)
			{
				if($formInfo->name === 'phone_number' && $formInfo->isUse)
				{
					$use_phone = true;
				}
				if($formInfo->isDefaultForm && ($formInfo->isUse || $formInfo->required || $formInfo->mustRequired))
				{
					$getVars[] = $formInfo->name;
				}
			}
		}

		$args = new stdClass;
		foreach($getVars as $val)
		{
			$args->{$val} = Context::get($val);
			if ($val === 'birthday')
			{
				$args->birthday_ui = Context::get('birthday_ui');
			}
			if ($val === 'phone_number')
			{
				$args->phone_country = preg_replace('/[^A-Z]/', '', Context::get('phone_country'));
			}
		}
		
		// mobile input date format can be different
		if($args->birthday)
		{
			if($args->birthday !== intval($args->birthday))
			{
				$args->birthday = date('Ymd', strtotime($args->birthday));
			}
			else
			{
				$args->birthday = intval($args->birthday);
			}
		}
		
		if(!$args->birthday && $args->birthday_ui)
		{
			$args->birthday = intval(strtr($args->birthday_ui, array('-'=>'', '/'=>'', '.'=>'', ' '=>'')));
		}
		
		$args->allow_mailing = Context::get('allow_mailing');
		$args->allow_message = Context::get('allow_message');

		if($args->password1) $args->password = $args->password1;
		
		// Check phone number
		if ($config->phone_number_verify_by_sms === 'Y' && $use_phone)
		{
			if (!isset($_SESSION['verify_by_sms']) || !$_SESSION['verify_by_sms']['status'])
			{
				throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
			}
			if ($config->phone_number_default_country && (!$args->phone_country || $config->phone_number_hide_country === 'Y'))
			{
				$args->phone_country = $config->phone_number_default_country;
			}
			if ($args->phone_country && !preg_match('/^[A-Z]{3}$/', $args->phone_country))
			{
				$args->phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($args->phone_country);
			}
			if ($args->phone_country !== $_SESSION['verify_by_sms']['country'])
			{
				throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
			}
			if ($args->phone_number !== $_SESSION['verify_by_sms']['number'])
			{
				throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
			}
		}

		// check password strength
		if(!MemberModel::checkPasswordStrength($args->password, $config->password_strength))
		{
			$message = lang('about_password_strength');
			throw new Rhymix\Framework\Exception($message[$config->password_strength]);
		}

		// Remove some unnecessary variables from all the vars
		$all_args = Context::getRequestVars();
		unset($all_args->xe_validator_id);
		unset($all_args->module);
		unset($all_args->act);
		unset($all_args->is_admin);
		unset($all_args->member_srl);
		unset($all_args->description);
		unset($all_args->group_srl_list);
		unset($all_args->body);
		unset($all_args->accept_agreement);
		unset($all_args->signature);
		unset($all_args->password);
		unset($all_args->password2);
		unset($all_args->mid);
		unset($all_args->success_return_url);
		unset($all_args->error_return_url);
		unset($all_args->ruleset);
		unset($all_args->captchaType);
		unset($all_args->secret_text);
		unset($all_args->use_editor);
		unset($all_args->use_html);

		// Set the user state as "denied" when using mail authentication
		if($config->enable_confirm == 'Y') $args->denied = 'Y';
		// Add extra vars after excluding necessary information from all the requested arguments
		$extra_vars = delObjectVars($all_args, $args);
		$args->extra_vars = serialize($extra_vars);

		// remove whitespace
		$checkInfos = array('user_id', 'user_name', 'nick_name', 'email_address');
		foreach($checkInfos as $val)
		{
			if(isset($args->{$val}))
			{
				$args->{$val} = preg_replace('/[\pZ\pC]+/u', '', utf8_clean(html_entity_decode($args->{$val})));
			}
		}
		
		// Check symbols in nickname
		if($config->nickname_symbols === 'N')
		{
			if(preg_match('/[^\pL\d]/u', $args->nick_name, $matches))
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_invalid_symbol_in_nickname'), escape($matches[0])));
			}
		}
		elseif($config->nickname_symbols === 'LIST')
		{
			$list = preg_quote($config->nickname_symbols_allowed_list, '/');
			if(preg_match('/[^\pL\d' . $list . ']/u', $args->nick_name, $matches))
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_invalid_symbol_in_nickname'), escape($matches[0])));
			}
		}
		
		// Insert member info
		$output = $this->insertMember($args);
		if($output instanceof BaseObject && !$output->toBool())
		{
			return $output;
		}
		
		// Insert agreement info
		foreach($accept_agreement_rearranged as $agreement_sequence => $agreed)
		{
			$ag_args = new stdClass;
			$ag_args->member_srl = $args->member_srl;
			$ag_args->agreement_sequence = $agreement_sequence;
			$ag_args->agreed = $agreed;
			$output = executeQuery('member.insertAgreed', $ag_args);
			if($output instanceof BaseObject && !$output->toBool())
			{
				return $output;
			}
		}

		// insert ProfileImage, ImageName, ImageMark
		$profile_image = Context::get('profile_image');
		if(is_uploaded_file($profile_image['tmp_name']))
		{
			$this->insertProfileImage($args->member_srl, $profile_image['tmp_name']);
		}

		$image_mark = Context::get('image_mark');
		if(is_uploaded_file($image_mark['tmp_name']))
		{
			$this->insertImageMark($args->member_srl, $image_mark['tmp_name']);
		}

		$image_name = Context::get('image_name');
		if(is_uploaded_file($image_name['tmp_name']))
		{
			$this->insertImageName($args->member_srl, $image_name['tmp_name']);
		}

		// Save Signature
		$signature = Context::get('signature');
		$this->putSignature($args->member_srl, $signature);

		// Log-in
		if($config->enable_confirm != 'Y')
		{
			$output = $this->doLogin($args->{$config->identifier});
			if(!$output->toBool()) {
				if($output->error == -9)
					$output->error = -11;
				return $this->setRedirectUrl(getUrl('', 'act', 'dispMemberLoginForm'), $output);
			}
		}
		
		// Register device
		$device_token = Context::get('device_token');
		if ($device_token)
		{
			$output = executeQuery('member.getMemberDevice', ['device_token' => $device_token]);
			if (!$output->data || $output->data->member_srl != $args->member_srl)
			{
				$this->procMemberRegisterDevice($args->member_srl);
			}
		}

		// Results
		$this->add('member_srl', $args->member_srl);
		if($config->redirect_url) $this->add('redirect_url', $config->redirect_url);
		if($config->enable_confirm == 'Y')
		{
			$msg = sprintf(lang('msg_confirm_mail_sent'), $args->email_address);
			$this->setMessage($msg);
			return $this->setRedirectUrl(getUrl('', 'act', 'dispMemberLoginForm'), new BaseObject(-12, $msg));
		}
		else $this->setMessage('success_registed');
		
		// Call a trigger (after)
		ModuleHandler::triggerCall('member.procMemberInsert', 'after', $config);

		if($config->redirect_url)
		{
			$returnUrl = $config->redirect_url;
		}
		else
		{
			if(Context::get('success_return_url'))
			{
				$returnUrl = Context::get('success_return_url');
			}
			else if($_COOKIE['XE_REDIRECT_URL'])
			{
				$returnUrl = $_COOKIE['XE_REDIRECT_URL'];
				setcookie("XE_REDIRECT_URL", '', 1);
			}
		}

		self::clearMemberCache($args->member_srl, $site_module_info->site_srl);

		$this->setRedirectUrl($returnUrl);
	}

	function procMemberModifyInfoBefore()
	{
		if($_SESSION['rechecked_password_step'] != 'INPUT_PASSWORD')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$password = Context::get('password');

		if(!$password)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$columnList = array('member_srl', 'password');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		
		// Verify the current password
		if(!MemberModel::isValidPassword($member_info->password, $password))
		{
			throw new Rhymix\Framework\Exception('invalid_password');
		}

		$_SESSION['rechecked_password_step'] = 'VALIDATE_PASSWORD';

		if(Context::get('success_return_url'))
		{
			$redirectUrl = Context::get('success_return_url');
		}
		else
		{
			$redirectUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyInfo');
		}
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * Edit member profile
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberModifyInfo()
	{
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		if($_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		unset($_SESSION['rechecked_password_step']);
		
		// Get current module config and user info
		$config = MemberModel::getMemberConfig();
		$logged_info = Context::get('logged_info');

		// Extract the necessary information in advance
		$getVars = array('allow_mailing','allow_message');
		$use_phone = false;
		if($config->signupForm)
		{
			foreach($config->signupForm as $formInfo)
			{
				if($formInfo->name === 'phone_number' && $formInfo->isUse)
				{
					$use_phone = true;
				}
				if($formInfo->isDefaultForm && ($formInfo->isUse || $formInfo->required || $formInfo->mustRequired))
				{
					$getVars[] = $formInfo->name;
				}
			}
		}

		$args = new stdClass;
		foreach($getVars as $val)
		{
			$args->{$val} = Context::get($val);
			if($val === 'birthday')
			{
				$args->birthday_ui = Context::get('birthday_ui');
			}
			if ($val === 'phone_number')
			{
				$args->phone_country = preg_replace('/[^A-Z]/', '', Context::get('phone_country'));
			}
		}

		// mobile input date format can be different
		if($args->birthday)
		{
			if($args->birthday !== intval($args->birthday))
			{
				$args->birthday = date('Ymd', strtotime($args->birthday));
			}
			else
			{
				$args->birthday = intval($args->birthday);
			}
		}
		
		if(!$args->birthday && $args->birthday_ui)
		{
			$args->birthday = intval(strtr($args->birthday_ui, array('-'=>'', '/'=>'', '.'=>'', ' '=>'')));
		}
		
		// Check phone number
		if ($config->phone_number_verify_by_sms === 'Y' && $use_phone)
		{
			$phone_verify_needed = false;
			if ($config->phone_number_default_country && (!$args->phone_country || $config->phone_number_hide_country === 'Y'))
			{
				$args->phone_country = $config->phone_number_default_country;
			}
			if ($args->phone_country && !preg_match('/^[A-Z]{3}$/', $args->phone_country))
			{
				$args->phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($args->phone_country);
			}
			if ($args->phone_country !== $logged_info->phone_country)
			{
				$phone_verify_needed = true;
			}
			if (preg_replace('/[^0-9]/', '', $args->phone_number) !== $logged_info->phone_number)
			{
				$phone_verify_needed = true;
			}
			if ($phone_verify_needed)
			{
				if (!isset($_SESSION['verify_by_sms']) || !$_SESSION['verify_by_sms']['status'])
				{
					throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
				}
				if ($args->phone_country !== $_SESSION['verify_by_sms']['country'])
				{
					throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
				}
				if ($args->phone_number !== $_SESSION['verify_by_sms']['number'])
				{
					throw new Rhymix\Framework\Exception('verify_by_sms_incomplete');
				}
			}
		}

		$args->member_srl = $logged_info->member_srl;

		// Remove some unnecessary variables from all the vars
		$all_args = Context::getRequestVars();
		unset($all_args->xe_validator_id);
		unset($all_args->module);
		unset($all_args->act);
		unset($all_args->is_admin);
		unset($all_args->member_srl);
		unset($all_args->description);
		unset($all_args->group_srl_list);
		unset($all_args->body);
		unset($all_args->accept_agreement);
		unset($all_args->signature);
		unset($all_args->password);
		unset($all_args->password2);
		unset($all_args->mid);
		unset($all_args->success_return_url);
		unset($all_args->error_return_url);
		unset($all_args->ruleset);
		unset($all_args->captchaType);
		unset($all_args->secret_text);
		unset($all_args->use_editor);
		unset($all_args->use_html);
		unset($all_args->_filter);
		$extra_vars = delObjectVars($all_args, $args);
		$args->extra_vars = serialize($extra_vars);

		// remove whitespace
		$checkInfos = array('user_id', 'user_name', 'nick_name', 'email_address');
		foreach($checkInfos as $val)
		{
			if(isset($args->{$val}))
			{
				$args->{$val} = preg_replace('/[\pZ\pC]+/u', '', utf8_clean(html_entity_decode($args->{$val})));
			}
		}

		// Check symbols in nickname
		if($config->nickname_symbols === 'N')
		{
			if(preg_match('/[^\pL\d]/u', $args->nick_name, $matches))
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_invalid_symbol_in_nickname'), escape($matches[0])));
			}
		}
		elseif($config->nickname_symbols === 'LIST')
		{
			$list = preg_quote($config->nickname_symbols_allowed_list, '/');
			if(preg_match('/[^\pL\d' . $list . ']/u', $args->nick_name, $matches))
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_invalid_symbol_in_nickname'), escape($matches[0])));
			}
		}
		
		// Execute insert or update depending on the value of member_srl
		$output = $this->updateMember($args);
		if(!$output->toBool()) return $output;

		$profile_image = Context::get('profile_image');
		if(is_uploaded_file($profile_image['tmp_name']))
		{
			$this->insertProfileImage($args->member_srl, $profile_image['tmp_name']);
		}

		$image_mark = Context::get('image_mark');
		if(is_uploaded_file($image_mark['tmp_name']))
		{
			$this->insertImageMark($args->member_srl, $image_mark['tmp_name']);
		}

		$image_name = Context::get('image_name');
		if(is_uploaded_file($image_name['tmp_name']))
		{
			$this->insertImageName($args->member_srl, $image_name['tmp_name']);
		}

		// Save Signature
		$signature = Context::get('signature');
		$this->putSignature($args->member_srl, $signature);
		if($config->member_allow_fileupload === 'Y')
		{
			getController('file')->setFilesValid($args->member_srl, 'sig');
		}
		
		// Get user_id information
		$member_info = MemberModel::getMemberInfoByMemberSrl($args->member_srl);

		// Call a trigger after successfully modified (after)
		ModuleHandler::triggerCall('member.procMemberModifyInfo', 'after', $member_info);
		$this->setSessionInfo();
		
		// Return result
		$this->add('member_srl', $args->member_srl);
		$this->setMessage('success_updated');

		$site_module_info = Context::get('site_module_info');
		self::clearMemberCache($args->member_srl, $site_module_info->site_srl);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Change the user password
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberModifyPassword()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		// Extract the necessary information in advance
		$current_password = trim(Context::get('current_password'));
		$password = trim(Context::get('password1'));
		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		// Get information of member_srl
		$columnList = array('member_srl', 'password');

		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		// Verify the cuttent password
		if(!MemberModel::isValidPassword($member_info->password, $current_password, $member_srl)) throw new Rhymix\Framework\Exception('invalid_password');

		// Check if a new password is as same as the previous password
		if($current_password == $password) throw new Rhymix\Framework\Exception('invalid_new_password');

		// Execute insert or update depending on the value of member_srl
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->password = $password;
		$output = $this->updateMemberPassword($args);
		if(!$output->toBool()) return $output;
		
		// Log out all other sessions.
		$member_config = ModuleModel::getModuleConfig('member');
		if ($member_config->password_change_invalidate_other_sessions === 'Y')
		{
			Rhymix\Framework\Session::destroyOtherSessions($member_srl);
		}

		$this->add('member_srl', $args->member_srl);
		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Membership withdrawal
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberLeave()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;
		// Extract the necessary information in advance
		$password = trim(Context::get('password'));
		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		// Get information of member_srl
		$columnList = array('member_srl', 'password');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		// Verify the cuttent password
		if(!MemberModel::isValidPassword($member_info->password, $password)) throw new Rhymix\Framework\Exception('invalid_password');

		$output = $this->deleteMember($member_srl);
		if(!$output->toBool()) return $output;
		// Destroy all session information
		executeQuery('member.deleteAutologin', (object)array('member_srl' => $member_srl));
		Rhymix\Framework\Session::logout();
		// Return success message
		$this->setMessage('success_leaved');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Add a profile image
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertProfileImage()
	{
		// Check if the file is successfully uploaded
		$file = Context::get('profile_image');
		if(!is_uploaded_file($file['tmp_name'])) throw new Rhymix\Framework\Exception('msg_not_uploaded_profile_image');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_profile_image');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_profile_image');
		// Return if member module is set not to use an image name or the user is not an administrator ;
		$config = MemberModel::getMemberConfig();
		if($logged_info->is_admin != 'Y' && $config->profile_image != 'Y') throw new Rhymix\Framework\Exception('msg_not_uploaded_profile_image');

		$output = $this->insertProfileImage($member_srl, $file['tmp_name']);
		if(!$output->toBool()) return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Insert a profile image
	 *
	 * @param int $member_srl
	 * @param object $target_file
	 *
	 * @return void
	 */
	function insertProfileImage($member_srl, $target_file)
	{
		$config = MemberModel::getMemberConfig();
		
		// Get an image size
		$max_width = $config->profile_image_max_width;
		$max_height = $config->profile_image_max_height;
		$max_filesize = $config->profile_image_max_filesize;

		Context::loadLang(_XE_PATH_ . 'modules/file/lang');

		// Get file information
		FileHandler::clearStatCache($target_file);
		list($width, $height, $type) = @getimagesize($target_file);
		if(IMAGETYPE_PNG == $type) $ext = 'png';
		elseif(IMAGETYPE_JPEG == $type) $ext = 'jpg';
		elseif(IMAGETYPE_GIF == $type) $ext = 'gif';
		else
		{
			throw new Rhymix\Framework\Exception('msg_not_uploaded_profile_image');
		}

		$target_path = sprintf('files/member_extra_info/profile_image/%s', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		$target_filename = sprintf('%s%d.%s', $target_path, $member_srl, $ext);
		// Convert if the image size is larger than a given size
		if($width > $max_width || $height > $max_height)
		{
			$temp_filename = sprintf('files/cache/tmp/profile_image_%d.%s', $member_srl, $ext);
			FileHandler::createImageFile($target_file, $temp_filename, $max_width, $max_height, $ext);

			// 파일 용량 제한
			FileHandler::clearStatCache($temp_filename);
			$filesize = filesize($temp_filename);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				FileHandler::removeFile($temp_filename);
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_profile_image'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			FileHandler::moveFile($temp_filename, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}
		else
		{
			// 파일 용량 제한
			$filesize = filesize($target_file);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_profile_image'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			@copy($target_file, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}

		return new BaseObject(0, 'success');
	}

	/**
	 * Add an image name
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertImageName()
	{
		// Check if the file is successfully uploaded
		$file = Context::get('image_name');
		if(!is_uploaded_file($file['tmp_name'])) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_name');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_name');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_name');
		// Return if member module is set not to use an image name or the user is not an administrator ;
		$config = MemberModel::getMemberConfig();
		if($logged_info->is_admin != 'Y' && $config->image_name != 'Y') throw new Rhymix\Framework\Exception('msg_not_uploaded_image_name');

		$output = $this->insertImageName($member_srl, $file['tmp_name']);
		if(!$output->toBool()) return $output;

		// Page refresh
		//$this->setRefreshPage();

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Insert a image name
	 *
	 * @param int $member_srl
	 * @param object $target_file
	 *
	 * @return void
	 */
	function insertImageName($member_srl, $target_file)
	{
		$config = MemberModel::getMemberConfig();
		
		// Get an image size
		$max_width = $config->image_name_max_width;
		$max_height = $config->image_name_max_height;
		$max_filesize = $config->image_name_max_filesize;

		Context::loadLang(_XE_PATH_ . 'modules/file/lang');

		// Get a target path to save
		$target_path = sprintf('files/member_extra_info/image_name/%s/', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		$target_filename = sprintf('%s%d.gif', $target_path, $member_srl);
		// Get file information
		list($width, $height, $type) = @getimagesize($target_file);
		// Convert if the image size is larger than a given size or if the format is not a gif
		if($width > $max_width || $height > $max_height || $type!=1)
		{
			$temp_filename = sprintf('files/cache/tmp/image_name_%d.gif', $member_srl, $ext);
			FileHandler::createImageFile($target_file, $temp_filename, $max_width, $max_height, 'gif');

			// 파일 용량 제한
			FileHandler::clearStatCache($temp_filename);
			$filesize = filesize($temp_filename);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				FileHandler::removeFile($temp_filename);
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_image_name'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			FileHandler::moveFile($temp_filename, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}
		else
		{
			// 파일 용량 제한
			$filesize = filesize($target_file);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_image_name'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			@copy($target_file, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}

		return new BaseObject(0, 'success');
	}

	/**
	 * Delete profile image
	 *
	 * @return Object
	 */
	function procMemberDeleteProfileImage($_memberSrl = 0)
	{
		$member_srl = ($_memberSrl) ? $_memberSrl : Context::get('member_srl');
		if(!$member_srl)
		{
			return new BaseObject(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$profile_image = MemberModel::getProfileImage($member_srl);
			FileHandler::removeFile($profile_image->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($profile_image->file)), true);
			self::clearMemberCache($member_srl);
		}
		return new BaseObject(0,'success');
	}

	/**
	 * Delete Image name
	 *
	 * @return void
	 */
	function procMemberDeleteImageName($_memberSrl = 0)
	{
		$member_srl = ($_memberSrl) ? $_memberSrl : Context::get('member_srl');
		if(!$member_srl)
		{
			return new BaseObject(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$image_name = MemberModel::getImageName($member_srl);
			FileHandler::removeFile($image_name->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($image_name->file)), true);
		}
		return new BaseObject(0,'success');
	}

	/**
	 * Add an image to mark
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertImageMark()
	{
		// Check if the file is successfully uploaded
		$file = Context::get('image_mark');
		if(!is_uploaded_file($file['tmp_name'])) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_mark');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_mark');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) throw new Rhymix\Framework\Exception('msg_not_uploaded_image_mark');
		// Membership in the images mark the module using the ban was set by an administrator or return;
		$config = MemberModel::getMemberConfig();
		if($logged_info->is_admin != 'Y' && $config->image_mark != 'Y') throw new Rhymix\Framework\Exception('msg_not_uploaded_image_mark');

		$this->insertImageMark($member_srl, $file['tmp_name']);
		if(!$output->toBool()) return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Insert a image mark
	 *
	 * @param int $member_srl
	 * @param object $target_file
	 *
	 * @return void
	 */
	function insertImageMark($member_srl, $target_file)
	{
		$config = MemberModel::getMemberConfig();
		
		// Get an image size
		$max_width = $config->image_mark_max_width;
		$max_height = $config->image_mark_max_height;
		$max_filesize = $config->image_mark_max_filesize;

		Context::loadLang(_XE_PATH_ . 'modules/file/lang');

		$target_path = sprintf('files/member_extra_info/image_mark/%s/', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		$target_filename = sprintf('%s%d.gif', $target_path, $member_srl);
		// Get file information
		list($width, $height, $type, $attrs) = @getimagesize($target_file);

		if($width > $max_width || $height > $max_height || $type!=1)
		{
			$temp_filename = sprintf('files/cache/tmp/image_mark_%d.gif', $member_srl);
			FileHandler::createImageFile($target_file, $temp_filename, $max_width, $max_height, 'gif');

			// 파일 용량 제한
			FileHandler::clearStatCache($temp_filename);
			$filesize = filesize($temp_filename);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				FileHandler::removeFile($temp_filename);
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_group_image_mark'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			FileHandler::moveFile($temp_filename, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}
		else
		{
			$filesize = filesize($target_file);
			if($max_filesize && $filesize > ($max_filesize * 1024))
			{
				FileHandler::removeFile($target_file);
				throw new Rhymix\Framework\Exception(implode(' ' , array(
					Context::getLang('msg_not_uploaded_group_image_mark'),
					Context::getLang('msg_exceeds_limit_size')
				)));
			}

			FileHandler::removeFilesInDir($target_path);
			@copy($target_file, $target_filename);
			FileHandler::clearStatCache($target_filename);
		}

		return new BaseObject(0, 'success');
	}

	/**
	 * Delete Image Mark
	 *
	 * @return Object
	 */
	function procMemberDeleteImageMark($_memberSrl = 0)
	{
		$member_srl = ($_memberSrl) ? $_memberSrl : Context::get('member_srl');
		if(!$member_srl)
		{
			return new BaseObject(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$image_mark = MemberModel::getImageMark($member_srl);
			FileHandler::removeFile($image_mark->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($image_mark->file)), true);
		}
		return new BaseObject(0,'success');
	}

	/**
	 * Find ID/Password
	 *
	 * @return Object
	 */
	function procMemberFindAccount()
	{
		$email_address = Context::get('email_address');
		if(!$email_address) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		// Check if a member having the same email address exists
		$member_srl = MemberModel::getMemberSrlByEmailAddress($email_address);
		if(!$member_srl) throw new Rhymix\Framework\Exception('msg_email_not_exists');

		// Get information of the member
		$columnList = array('denied', 'member_srl', 'user_id', 'user_name', 'email_address', 'nick_name');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);

		// Check if possible to find member's ID and password
		if($member_info->denied == 'Y')
		{
			$chk_args = new stdClass;
			$chk_args->member_srl = $member_info->member_srl;
			$output = executeQuery('member.chkAuthMail', $chk_args);
			if($output->toBool() && $output->data->count != '0') throw new Rhymix\Framework\Exception('msg_user_not_confirmed');
		}

		// Insert data into the authentication DB
		$args = new stdClass();
		$args->user_id = $member_info->user_id;
		$args->member_srl = $member_info->member_srl;
		$args->new_password = Rhymix\Framework\Password::getRandomPassword(8);
		$args->auth_key = Rhymix\Framework\Security::getRandom(40, 'hex');
		$args->is_register = 'N';

		$output = executeQuery('member.insertAuthMail', $args);
		if(!$output->toBool()) return $output;
		// Get content of the email to send a member
		Context::set('auth_args', $args);

		$member_config = ModuleModel::getModuleConfig('member');
		$memberInfo = array();
		global $lang;
		if(is_array($member_config->signupForm))
		{
			$exceptForm=array('password', 'find_account_question');
			foreach($member_config->signupForm as $form)
			{
				if(!in_array($form->name, $exceptForm) && $form->isDefaultForm && ($form->required || $form->mustRequired))
				{
					$memberInfo[$lang->{$form->name}] = $member_info->{$form->name};
				}
			}
		}
		else
		{
			$memberInfo[$lang->user_id] = $args->user_id;
			$memberInfo[$lang->user_name] = $args->user_name;
			$memberInfo[$lang->nick_name] = $args->nick_name;
			$memberInfo[$lang->email_address] = $args->email_address;
		}
		Context::set('memberInfo', $memberInfo);

		if(!$member_config->skin) $member_config->skin = "default";
		if(!$member_config->colorset) $member_config->colorset = "white";

		Context::set('member_config', $member_config);

		$tpl_path = sprintf('%sskins/%s', $this->module_path, $member_config->skin);
		if(!is_dir($tpl_path)) $tpl_path = sprintf('%sskins/%s', $this->module_path, 'default');

		$find_url = getFullUrl ('', 'module', 'member', 'act', 'procMemberAuthAccount', 'member_srl', $member_info->member_srl, 'auth_key', $args->auth_key);
		Context::set('find_url', $find_url);

		$oTemplate = &TemplateHandler::getInstance();
		$content = $oTemplate->compile($tpl_path, 'find_member_account_mail');

		// Get information of the Webmaster
		$member_config = ModuleModel::getModuleConfig('member');

		// Send a mail
		$oMail = new \Rhymix\Framework\Mail();
		$oMail->setSubject(lang('msg_find_account_title'));
		$oMail->setBody($content);
		$oMail->addTo($member_info->email_address, $member_info->nick_name);
		$oMail->send();

		// Return message
		$msg = sprintf(lang('msg_auth_mail_sent'), $member_info->email_address);
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberFindAccount');
			$this->setRedirectUrl($returnUrl);
		}
		return new BaseObject(0,$msg);
	}

	/**
	 * Generate a temp password by answering to the pre-determined question
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberFindAccountByQuestion()
	{
		throw new Rhymix\Framework\Exception('msg_question_not_allowed');
	}

	/**
	 * Execute finding ID/Passoword
	 * When clicking the link in the verification email, a method is called to change the old password and to authenticate it
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberAuthAccount()
	{
		$config = MemberModel::getMemberConfig();
		
		// Test user_id and authkey
		$member_srl = Context::get('member_srl');
		$auth_key = Context::get('auth_key');

		if(!$member_srl || !$auth_key)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $member_srl;
		$trigger_obj->auth_key = $auth_key;
		$trigger_output = ModuleHandler::triggerCall('member.procMemberAuthAccount', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// Test logs for finding password by user_id and authkey
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->auth_key = $auth_key;
		$output = executeQuery('member.getAuthMail', $args);

		if(!$output->toBool() || $output->data->auth_key !== $auth_key)
		{
			executeQuery('member.deleteAuthMail', $args);
			throw new Rhymix\Framework\Exception('msg_invalid_auth_key');
		}

		$expires = (intval($config->authmail_expires) * intval($config->authmail_expires_unit)) ?: 86400;
		if(ztime($output->data->regdate) < time() - $expires)
		{
			executeQuery('member.deleteAuthMail', $args);
			throw new Rhymix\Framework\Exception('msg_expired_auth_key');
		}

		// Back up the value of $output->data->is_register
		$is_register = $output->data->is_register;

		// If credentials are correct, change the password to a new one
		if($is_register === 'Y')
		{
			$args->denied = 'N';
		}
		else
		{
			$args->password = MemberModel::hashPassword($output->data->new_password);
		}

		$output = executeQuery('member.updateMemberPassword', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		// 인증 정보를 여기서 삭제하지 않고 로그인 시점에 삭제되도록 함
		// https://github.com/rhymix/rhymix/issues/1232
		// executeQuery('member.deleteAuthMail', $args);

		self::clearMemberCache($args->member_srl);

		// Call a trigger (after)
		$trigger_obj->is_register = $is_register;
		$trigger_output = ModuleHandler::triggerCall('member.procMemberAuthAccount', 'after', $trigger_obj);

		// Notify the result
		$message = $is_register === 'Y' ? lang('msg_success_confirmed') : lang('msg_success_authed');
		Context::setValidatorMessage('modules/member/skins', $message);
		$this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberLoginForm'));
	}

	/**
	 * Request to re-send the authentication mail
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberResendAuthMail()
	{
		// Get an email_address
		$email_address = Context::get('email_address');
		if(!$email_address) throw new Rhymix\Framework\Exceptions\InvalidRequest;
		// Log test by using email_address
		$args = new stdClass;
		$args->email_address = $email_address;
		$member_srl = MemberModel::getMemberSrlByEmailAddress($email_address);
		if(!$member_srl)
		{
			throw new Rhymix\Framework\Exception('msg_not_exists_member');
		}

		$columnList = array('member_srl', 'user_id', 'user_name', 'nick_name', 'email_address');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		if(!$member_info || !$member_info->member_srl)
		{
			throw new Rhymix\Framework\Exception('msg_not_exists_member');
		}
		if($member_info->denied !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_activation_not_needed');
		}

		$member_config = ModuleModel::getModuleConfig('member');
		if(!$member_config->skin) $member_config->skin = "default";
		if(!$member_config->colorset) $member_config->colorset = "white";

		// Check if a authentication mail has been sent previously
		$chk_args = new stdClass;
		$chk_args->member_srl = $member_info->member_srl;
		$output = executeQuery('member.chkAuthMail', $chk_args);
		if($output->toBool() && $output->data->count == '0')
		{
			throw new Rhymix\Framework\Exception('msg_activation_key_not_found');
		}

		$auth_args = new stdClass;
		$auth_args->member_srl = $member_info->member_srl;
		$output = executeQueryArray('member.getAuthMailInfo', $auth_args);
		if(!$output->data || !$output->data[0]->auth_key)
		{
			throw new Rhymix\Framework\Exception('msg_activation_key_not_found');
		}
		
		$auth_info = $output->data[0];

		// Update the regdate of authmail entry
		$renewal_args = new stdClass;
		$renewal_args->member_srl = $member_info->member_srl;
		$renewal_args->auth_key = $auth_info->auth_key;
		$output = executeQuery('member.updateAuthMail', $renewal_args);		

		$memberInfo = array();
		global $lang;
		if(is_array($member_config->signupForm))
		{
			$exceptForm=array('password', 'find_account_question');
			foreach($member_config->signupForm as $form)
			{
				if(!in_array($form->name, $exceptForm) && $form->isDefaultForm && ($form->required || $form->mustRequired))
				{
					$memberInfo[$lang->{$form->name}] = $member_info->{$form->name};
				}
			}
		}
		else
		{
			$memberInfo[$lang->user_id] = $member_info->user_id;
			$memberInfo[$lang->user_name] = $member_info->user_name;
			$memberInfo[$lang->nick_name] = $member_info->nick_name;
			$memberInfo[$lang->email_address] = $member_info->email_address;
		}

		// Get content of the email to send a member
		Context::set('memberInfo', $memberInfo);
		Context::set('member_config', $member_config);

		$tpl_path = sprintf('%sskins/%s', $this->module_path, $member_config->skin);
		if(!is_dir($tpl_path)) $tpl_path = sprintf('%sskins/%s', $this->module_path, 'default');

		$auth_url = getFullUrl('','module','member','act','procMemberAuthAccount','member_srl',$member_info->member_srl, 'auth_key',$auth_info->auth_key);
		Context::set('auth_url', $auth_url);

		$oTemplate = &TemplateHandler::getInstance();
		$content = $oTemplate->compile($tpl_path, 'confirm_member_account_mail');

		// Send a mail
		$oMail = new \Rhymix\Framework\Mail();
		$oMail->setSubject(lang('msg_confirm_account_title'));
		$oMail->setBody($content);
		$oMail->addTo($member_info->email_address, $member_info->nick_name);
		$oMail->send();

		$msg = sprintf(lang('msg_confirm_mail_sent'), $args->email_address);
		$this->setMessage($msg);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
		$this->setRedirectUrl($returnUrl);
	}

	function _sendAuthMail($auth_args, $member_info)
	{
		$member_config = MemberModel::getMemberConfig();
		// Get content of the email to send a member
		Context::set('auth_args', $auth_args);

		$memberInfo = array();

		global $lang;
		if(is_array($member_config->signupForm))
		{
			$exceptForm=array('password', 'find_account_question');
			foreach($member_config->signupForm as $form)
			{
				if(!in_array($form->name, $exceptForm) && $form->isDefaultForm && ($form->required || $form->mustRequired))
				{
					$memberInfo[$lang->{$form->name}] = $member_info->{$form->name};
				}
			}
		}
		else
		{
			$memberInfo[$lang->user_id] = $member_info->user_id;
			$memberInfo[$lang->user_name] = $member_info->user_name;
			$memberInfo[$lang->nick_name] = $member_info->nick_name;
			$memberInfo[$lang->email_address] = $member_info->email_address;
		}
		Context::set('memberInfo', $memberInfo);

		if(!$member_config->skin) $member_config->skin = "default";
		if(!$member_config->colorset) $member_config->colorset = "white";

		Context::set('member_config', $member_config);

		$tpl_path = sprintf('%sskins/%s', $this->module_path, $member_config->skin);
		if(!is_dir($tpl_path)) $tpl_path = sprintf('%sskins/%s', $this->module_path, 'default');

		$auth_url = getFullUrl('','module','member','act','procMemberAuthAccount','member_srl',$member_info->member_srl, 'auth_key',$auth_args->auth_key);
		Context::set('auth_url', $auth_url);

		$oTemplate = &TemplateHandler::getInstance();
		$content = $oTemplate->compile($tpl_path, 'confirm_member_account_mail');
		
		// Send a mail
		$oMail = new \Rhymix\Framework\Mail();
		$oMail->setSubject(lang('msg_confirm_account_title'));
		$oMail->setBody($content);
		$oMail->addTo($member_info->email_address, $member_info->nick_name);
		$oMail->send();
	}

	/**
	 * Join a virtual site
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberSiteSignUp()
	{
		$site_module_info = Context::get('site_module_info');
		$logged_info = Context::get('logged_info');
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$columnList = array('site_srl', 'group_srl', 'title');
		$default_group = MemberModel::getDefaultGroup($site_module_info->site_srl, $columnList);
		$this->addMemberToGroup($logged_info->member_srl, $default_group->group_srl, $site_module_info->site_srl);
		$groups[$default_group->group_srl] = $default_group->title;
		$logged_info->group_list = $groups;
	}

	/**
	 * Leave the virtual site
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberSiteLeave()
	{
		$site_module_info = Context::get('site_module_info');
		$logged_info = Context::get('logged_info');
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$args = new stdClass;
		$args->site_srl= $site_module_info->site_srl;
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('member.deleteMembersGroup', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_deleted');
		self::clearMemberCache($args->member_srl, $site_module_info->site_srl);
	}

	/**
	 * Save the member configurations
	 *
	 * @param object $args
	 *
	 * @return void
	 */
	function setMemberConfig($args)
	{
		if(!$args->skin) $args->skin = "default";
		if(!$args->colorset) $args->colorset = "white";
		if(!$args->editor_skin) $args->editor_skin= "ckeditor";
		if(!$args->editor_colorset) $args->editor_colorset = "moono-lisa";
		if($args->enable_join!='Y') $args->enable_join = 'N';
		$args->enable_openid= 'N';
		if($args->profile_image !='Y') $args->profile_image = 'N';
		if($args->image_name!='Y') $args->image_name = 'N';
		if($args->image_mark!='Y') $args->image_mark = 'N';
		if($args->group_image_mark!='Y') $args->group_image_mark = 'N';
		if(!trim(strip_tags($args->agreement))) $args->agreement = null;
		$args->limit_day = (int)$args->limit_day;

		$agreement = trim($args->agreement);
		unset($args->agreement);

		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('member',$args);
		if(!$output->toBool()) return $output;

		$agreement_file = _XE_PATH_.'files/member_extra_info/agreement.txt';
		FileHandler::writeFile($agreement_file, $agreement);

		return new BaseObject();
	}

	/**
	 * Save the signature as a file
	 *
	 * @param int $member_srl
	 * @param string $signature
	 *
	 * @return void
	 */
	function putSignature($member_srl, $signature)
	{
		if((!$signature = utf8_trim(removeHackTag($signature))) || is_empty_html_content($signature))
		{
			getController('member')->delSignature($member_srl);
			return;
		}
		
		// Editor converter
		$obj = new stdClass;
		$config = MemberModel::getMemberConfig();
		if($config->signature_html == 'N')
		{
			$obj->converter = 'text';
		}
		$obj->content = $signature;
		$obj->editor_skin = $config->signature_editor_skin;
		$signature = getModel('editor')->converter($obj);
		
		$filename = sprintf('files/member_extra_info/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
		$buff = sprintf('<?php if(!defined("__XE__")) exit();?>%s', $signature);
		Rhymix\Framework\Storage::write($filename, $buff);
		
		return $signature;
	}

	/**
	 * Delete the signature file
	 *
	 * @param string $member_srl
	 *
	 * @return void
	 */
	function delSignature($member_srl)
	{
		$dirname = RX_BASEDIR . sprintf('files/member_extra_info/signature/%s', getNumberingPath($member_srl));
		Rhymix\Framework\Storage::deleteDirectory($dirname, false);
		Rhymix\Framework\Storage::deleteEmptyDirectory($dirname, true);
	}

	/**
	 * Add group_srl to member_srl
	 *
	 * @param int $member_srl
	 * @param int $group_srl
	 * @param int $site_srl
	 *
	 * @return Object
	 */
	function addMemberToGroup($member_srl, $group_srl, $site_srl=0)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->group_srl = $group_srl;
		if($site_srl) $args->site_srl = $site_srl;

		// Add
		$output = executeQuery('member.addMemberToGroup',$args);
		ModuleHandler::triggerCall('member.addMemberToGroup', 'after', $args);

		self::clearMemberCache($member_srl, $site_srl);

		return $output;
	}

	/**
	 * Change a group of certain members
	 * Available only when a member has a single group
	 *
	 * @param object $args
	 *
	 * @return Object
	 */
	function replaceMemberGroup($args)
	{
		$obj = new stdClass;
		$obj->site_srl = $args->site_srl;
		$obj->member_srl = implode(',',$args->member_srl);

		$output = executeQueryArray('member.getMembersGroup', $obj);
		if($output->data) foreach($output->data as $key => $val) $date[$val->member_srl] = $val->regdate;

		$output = executeQuery('member.deleteMembersGroup', $obj);
		if(!$output->toBool()) return $output;

		$inserted_members = array();
		foreach($args->member_srl as $key => $val)
		{
			if($inserted_members[$val]) continue;
			$inserted_members[$val] = true;

			unset($obj);
			$obj = new stdClass;
			$obj->member_srl = $val;
			$obj->group_srl = $args->group_srl;
			$obj->site_srl = $args->site_srl;
			$obj->regdate = $date[$obj->member_srl];
			$output = executeQuery('member.addMemberToGroup', $obj);
			if(!$output->toBool()) return $output;

			self::clearMemberCache($obj->member_srl, $args->site_srl);
		}

		return new BaseObject();
	}


	/**
	 * Auto-login
	 *
	 * @param string $autologin_key
	 * @return int|false
	 */
	function doAutologin($autologin_key = null)
	{
		// Validate the key.
		if (strlen($autologin_key) == 48)
		{
			$security_key = substr($autologin_key, 24, 24);
			$autologin_key = substr($autologin_key, 0, 24);
		}
		else
		{
			return false;
		}
		
		// Fetch autologin information from DB.
		$args = new stdClass;
		$args->autologin_key = $autologin_key;
		$output = executeQuery('member.getAutologin', $args);
		if (!$output->toBool() || !$output->data)
		{
			return false;
		}
		if (is_array($output->data))
		{
			$output->data = array_first($output->data);
		}
		
		// Hash the security key.
		$valid_security_keys = array(base64_encode(hash_hmac('sha256', $security_key, $autologin_key, true)));
		
		// Check the security key.
		if (!in_array($output->data->security_key, $valid_security_keys) || !$output->data->member_srl)
		{
			$args = new stdClass;
			$args->autologin_key = $autologin_key;
			executeQuery('member.deleteAutologin', $args);
			return false;
		}
		
		// Update the security key.
		$new_security_key = Rhymix\Framework\Security::getRandom(24, 'alnum');
		$args = new stdClass;
		$args->autologin_key = $autologin_key;
		$args->security_key = base64_encode(hash_hmac('sha256', $new_security_key, $autologin_key, true));
		$update_output = executeQuery('member.updateAutologin', $args);
		if ($update_output->toBool())
		{
			Rhymix\Framework\Session::setAutologinKeys($autologin_key, $new_security_key);
		}
		
		// Update the last login time.
		executeQuery('member.updateLastLogin', (object)['member_srl' => $output->data->member_srl]);
		self::clearMemberCache($output->data->member_srl);
		
		// Return the member_srl.
		return intval($output->data->member_srl);
	}

	/**
	 * Log-in
	 *
	 * @param string $user_id
	 * @param string $password
	 * @param boolean $keep_signed
	 *
	 * @return Object
	 */
	function doLogin($user_id, $password = '', $keep_signed = false)
	{
		$user_id = strtolower($user_id);
		if(!$user_id) return new BaseObject(-1, 'null_user_id');
		// Call a trigger before log-in (before)
		$trigger_obj = new stdClass();
		$trigger_obj->user_id = $user_id;
		$trigger_obj->password = $password;
		$trigger_output = ModuleHandler::triggerCall('member.doLogin', 'before', $trigger_obj);
		if(!$trigger_output->toBool()) return $trigger_output;

		// check IP access count.
		$config = MemberModel::getMemberConfig();
		$args = new stdClass();
		$args->ipaddress = \RX_CLIENT_IP;

		// check identifier
		if((!$config->identifiers || in_array('email_address', $config->identifiers)) && strpos($user_id, '@') !== false)
		{
			$member_info = MemberModel::getMemberInfoByEmailAddress($user_id);
			if(!$user_id || strtolower($member_info->email_address) !== strtolower($user_id))
			{
				return $this->recordLoginError(-1, 'invalid_email_address');
			}

		}
		elseif($config->identifiers && in_array('phone_number', $config->identifiers) && strpos($user_id, '@') === false)
		{
			if(preg_match('/^\+([0-9-]+)\.([0-9.-]+)$/', $user_id, $matches))
			{
				$user_id = $matches[2];
				$phone_country = $matches[1];
				if($config->phone_number_hide_country === 'Y')
				{
					$phone_country = $config->phone_number_default_country;
				}
			}
			elseif($config->phone_number_default_country)
			{
				$phone_country = $config->phone_number_default_country;
			}
			else
			{
				return $this->recordLoginError(-1, 'invalid_user_id');
			}
			
			if($phone_country && !preg_match('/^[A-Z]{3}$/', $phone_country))
			{
				$phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($phone_country);
			}
			
			$user_id = preg_replace('/[^0-9]/', '', $user_id);
			$member_info = MemberModel::getMemberInfoByPhoneNumber($user_id, $phone_country);
			if(!$user_id || strtolower($member_info->phone_number) !== $user_id)
			{
				return $this->recordLoginError(-1, 'invalid_user_id');
			}
		}
		elseif(!$config->identifiers || in_array('user_id', $config->identifiers))
		{
			$member_info = MemberModel::getMemberInfoByUserID($user_id);
			if(!$user_id || strtolower($member_info->user_id) !== strtolower($user_id))
			{
				return $this->recordLoginError(-1, 'invalid_user_id');
			}
		}
		else
		{
			return $this->recordLoginError(-1, 'invalid_user_id');
		}

		$output = executeQuery('member.getLoginCountByIp', $args);
		$errorCount = $output->data->count;
		if($errorCount >= $config->max_error_count)
		{
			$last_update = strtotime($output->data->last_update);
			$term = intval($_SERVER['REQUEST_TIME']-$last_update);
			if($term < $config->max_error_count_time)
			{
				$term = $config->max_error_count_time - $term;
				if($term < 60) $term = intval($term).lang('unit_sec');
				elseif(60 <= $term && $term < 3600) $term = intval($term/60).lang('unit_min');
				elseif(3600 <= $term && $term < 86400) $term = intval($term/3600).lang('unit_hour');
				else $term = intval($term/86400).lang('unit_day');

				return new BaseObject(-1, sprintf(lang('excess_ip_access_count'), $term));
			}
			else
			{
				$args->ipaddress = \RX_CLIENT_IP;
				$output = executeQuery('member.deleteLoginCountByIp', $args);
			}
		}

		// Password Check
		if($password && !MemberModel::isValidPassword($member_info->password, $password, $member_info->member_srl))
		{
			return $this->recordMemberLoginError(-1, 'invalid_password', $member_info);
		}

		// If denied == 'Y', notify
		if($member_info->denied == 'Y')
		{
			$args->member_srl = $member_info->member_srl;
			$output = executeQuery('member.chkAuthMail', $args);
			if ($output->toBool() && $output->data->count)
			{
				return new BaseObject(-1, sprintf(lang('msg_user_not_confirmed'), $member_info->email_address));
			}
			
			$refused_reason = $member_info->refused_reason ? ('<br>' . lang('refused_reason') . ': ' . $member_info->refused_reason) : '';
			return new BaseObject(-1, lang('msg_user_denied') . $refused_reason);
		}
		
		// Notify if user is limited
		if($member_info->limit_date && substr($member_info->limit_date,0,8) >= date("Ymd"))
		{
			$limited_reason = $member_info->limited_reason ? ('<br>' . lang('refused_reason') . ': ' . $member_info->limited_reason) : '';
			return new BaseObject(-9, sprintf(lang('msg_user_limited'), zdate($member_info->limit_date,"Y-m-d")) . $limited_reason);
		}
		
		// Do not allow login as admin if not in allowed IP list
		if($member_info->is_admin === 'Y' && $this->act === 'procMemberLogin')
		{
			$oMemberAdminModel = getAdminModel('member');
			if(!$oMemberAdminModel->getMemberAdminIPCheck())
			{
				return new BaseObject(-1, 'msg_admin_ip_not_allowed');
			}
		}
		
		// Update the latest login time
		$args->member_srl = $member_info->member_srl;
		$output = executeQuery('member.updateLastLogin', $args);

		$site_module_info = Context::get('site_module_info');
		self::clearMemberCache($args->member_srl, $site_module_info->site_srl);

		// Check if there is recoding table.
		$oDB = &DB::getInstance();
		if($oDB->isTableExists('member_count_history') && $config->enable_login_fail_report != 'N')
		{
			// check if there is login fail records.
			$output = executeQuery('member.getLoginCountHistoryByMemberSrl', $args);
			if($output->data && $output->data->content)
			{
				$title = lang('login_fail_report');
				$message = '<ul>';
				$content = unserialize($output->data->content);
				if(count($content) > $config->max_error_count)
				{
					foreach($content as $val)
					{
						$message .= '<li>'.lang('regdate').': '.date('Y-m-d h:i:sa',$val[2]).'<ul><li>'.lang('ipaddress').': '.$val[0].'</li><li>'.lang('message').': '.$val[1].'</li></ul></li>';
					}
					$message .= '</ul>';
					$content = sprintf(lang('login_fail_report_contents'),$message,date('Y-m-d h:i:sa'));

					//send message
					$oCommunicationController = getController('communication');
					$oCommunicationController->sendMessage($args->member_srl, $args->member_srl, $title, $content, true);

					if($member_info->email_address && $member_info->allow_mailing == 'Y')
					{
						$view_url = Context::getRequestUri();
						$content = sprintf("%s<hr /><p>From: <a href=\"%s\" target=\"_blank\">%s</a><br />To: %s(%s)</p>",$content, $view_url, $view_url, $member_info->nick_name, $member_info->email_id);
						$oMail = new \Rhymix\Framework\Mail();
						$oMail->setSubject($title);
						$oMail->setBody($content);
						$oMail->addTo($member_info->email_address, $member_info->email_id.' ('.$member_info->nick_name.')');
						$oMail->send();
					}
					$output = executeQuery('member.deleteLoginCountHistoryByMemberSrl', $args);
				}
			}
		}
		
		// Call a trigger after successfully log-in (after)
		ModuleHandler::triggerCall('member.doLogin', 'after', $member_info);
		
		// When user checked to use auto-login
		if($keep_signed)
		{
			$random_key = Rhymix\Framework\Security::getRandom(48, 'alnum');
			$autologin_args = new stdClass;
			$autologin_args->autologin_key = substr($random_key, 0, 24);
			$autologin_args->security_key = base64_encode(hash_hmac('sha256', substr($random_key, 24, 24), $autologin_args->autologin_key, true));
			$autologin_args->member_srl = $member_info->member_srl;
			$autologin_args->user_agent = json_encode(Rhymix\Framework\UA::getBrowserInfo());
			$autologin_output = executeQuery('member.insertAutologin', $autologin_args);
			if ($autologin_output->toBool())
			{
				Rhymix\Framework\Session::setAutologinKeys(substr($random_key, 0, 24), substr($random_key, 24, 24));
			}
		}

		Rhymix\Framework\Session::login($member_info->member_srl);
		$this->setSessionInfo();
		return $output;
	}

	/**
	 * Update or create session information
	 */
	function setSessionInfo()
	{
		// If your information came through the current session information to extract information from the users
		$member_info = Rhymix\Framework\Session::getMemberInfo(true);
		if (!$member_info->member_srl)
		{
			return;
		}

		// Information stored in the session login user
		Context::set('is_logged', true);
		Context::set('logged_info', $member_info);

		// Only the menu configuration of the user (such as an add-on to the menu can be changed)
		$config = MemberModel::getMemberConfig();
		$this->addMemberMenu( 'dispMemberInfo', 'cmd_view_member_info');
		if ($config->features['scrapped_documents'] !== false)
		{
			$this->addMemberMenu( 'dispMemberScrappedDocument', 'cmd_view_scrapped_document');
		}
		if ($config->features['saved_documents'] !== false)
		{
			$this->addMemberMenu( 'dispMemberSavedDocument', 'cmd_view_saved_document');
		}
		if ($config->features['my_documents'] !== false)
		{
			$this->addMemberMenu( 'dispMemberOwnDocument', 'cmd_view_own_document');
		}
		if ($config->features['my_comments'] !== false)
		{
			$this->addMemberMenu( 'dispMemberOwnComment', 'cmd_view_own_comment');
		}
		if ($config->features['active_logins'] !== false)
		{
			$this->addMemberMenu( 'dispMemberActiveLogins', 'cmd_view_active_logins');
		}
		if ($config->features['nickname_log'] !== false && $config->update_nickname_log == 'Y')
		{
			$this->addMemberMenu( 'dispMemberModifyNicknameLog', 'cmd_modify_nickname_log');
		}
	}

	/**
	 * Logged method for providing a personalized menu
	 * Login information is used in the output widget, or personalized page
	 */
	function addMemberMenu($act, $str)
	{
		$logged_info = Context::get('logged_info');
		
		if(!is_object($logged_info))
		{
			return;
		}
		
		$logged_info->menu_list[$act] = lang($str);
		
		Context::set('logged_info', $logged_info);
	}

	/**
	 * Nickname and click Log In to add a pop-up menu that appears when the method
	 */
	function addMemberPopupMenu($url, $str, $icon = '', $target = 'self', $class = '')
	{
		$member_popup_menu_list = Context::get('member_popup_menu_list');
		if(!is_array($member_popup_menu_list)) $member_popup_menu_list = array();

		$obj = new stdClass;
		$obj->url = $url;
		$obj->str = $str;
		$obj->class = $class;
		$obj->icon = $icon ?: null;
		$obj->target = $target;
		$member_popup_menu_list[] = $obj;

		Context::set('member_popup_menu_list', $member_popup_menu_list);
	}

	/**
	 * Add users to the member table
	 */
	function insertMember(&$args, $password_is_hashed = false)
	{
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('member.insertMember', 'before', $args);
		if(!$output->toBool()) return $output;
		// Terms and Conditions portion of the information set up by members reaffirmed
		$config = MemberModel::getMemberConfig();

		$logged_info = Context::get('logged_info');
		// limit_date format is YYYYMMDD
		if($args->limit_date)
		{
			// mobile input date format can be different
			if($args->limit_date !== intval($args->limit_date))
			{
				$args->limit_date = date('Ymd', strtotime($args->limit_date));
			}
			else
			{
				$args->limit_date = intval($args->limit_date);
			}
		}
		// If the date of the temporary restrictions limit further information on the date of
		if($config->limit_day) $args->limit_date = date("YmdHis", $_SERVER['REQUEST_TIME']+$config->limit_day*60*60*24);

		$args->member_srl = getNextSequence();
		$args->list_order = -1 * $args->member_srl;

		// Execute insert or update depending on the value of member_srl
		if(!$args->user_id) $args->user_id = 't'.$args->member_srl;
		// Enter the user's identity changed to lowercase
		else $args->user_id = strtolower($args->user_id);
		if(!$args->user_name) $args->user_name = $args->member_srl;
		if(!$args->nick_name) $args->nick_name = $args->member_srl;

		// Control of essential parameters
		if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
		if($args->denied!='Y') $args->denied = 'N';
		if(!in_array($args->allow_message, array('Y', 'N', 'F'))) $args->allow_message = 'Y';

		if($logged_info->is_admin == 'Y')
		{
			if($args->is_admin!='Y') $args->is_admin = 'N';
		}
		else
		{
			unset($args->is_admin);
		}

		list($args->email_id, $args->email_host) = explode('@', $args->email_address);

		// Sanitize user ID, username, nickname, homepage, blog
		$args->user_id = escape($args->user_id, false);
		$args->user_name = escape($args->user_name, false);
		$args->nick_name = escape($args->nick_name, false);
		$args->homepage = escape($args->homepage, false);
		$args->blog = escape($args->blog, false);
		if($args->homepage && !preg_match("/^[a-z]+:\/\//i",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
		if($args->blog && !preg_match("/^[a-z]+:\/\//i",$args->blog)) $args->blog = 'http://'.$args->blog;


		$extend_form_list = MemberModel::getJoinFormlist();
		$security = new Security($extend_form_list);
		$security->encodeHTML('..column_title', '..description', '..default_value.');
		if($config->signupForm) {
			foreach($config->signupForm as $no => $formInfo)
			{
				if(!$formInfo->isUse) continue;
				if($formInfo->isDefaultForm)
				{
					// birthday format is YYYYMMDD
					if($formInfo->name === 'birthday' && $args->{$formInfo->name})
					{
						// mobile input date format can be different
						if($args->{$formInfo->name} !== intval($args->{$formInfo->name}))
						{
							$args->{$formInfo->name} = date('Ymd', strtotime($args->{$formInfo->name}));
						}
						else
						{
							$args->{$formInfo->name} = intval($args->{$formInfo->name});
						}
					}
				}
				else
				{
					$extendForm = $extend_form_list[$formInfo->member_join_form_srl];
					// date format is YYYYMMDD
					if($extendForm->column_type == 'date' && $args->{$formInfo->name})
					{
						if($args->{$formInfo->name} !== intval($args->{$formInfo->name}))
						{
							$args->{$formInfo->name} = date('Ymd', strtotime($args->{$formInfo->name}));
						}
						else
						{
							$args->{$formInfo->name} = intval($args->{$formInfo->name});
						}
					}
				}
			}
		}
		
		// Check password strength
		if($args->password && !$password_is_hashed)
		{
			if(!MemberModel::checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new BaseObject(-1, $message[$config->password_strength]);
			}
			$args->password = MemberModel::hashPassword($args->password);
		}
		
		// Check if ID is prohibited
		if($logged_info->is_admin !== 'Y' && MemberModel::isDeniedID($args->user_id))
		{
			return new BaseObject(-1, 'denied_user_id');
		}

		// Check if ID is duplicate
		$member_srl = MemberModel::getMemberSrlByUserID($args->user_id);
		if($member_srl)
		{
			return new BaseObject(-1, 'msg_exists_user_id');
		}

		// Check if nickname is prohibited
		if($logged_info->is_admin !== 'Y' && MemberModel::isDeniedNickName($args->nick_name))
		{
			return new BaseObject(-1, 'denied_nick_name');
		}

		// Check if nickname is duplicate
		if($config->allow_duplicate_nickname !== 'Y')
		{
			$member_srl = MemberModel::getMemberSrlByNickName($args->nick_name);
			if($member_srl)
			{
				return new BaseObject(-1, 'msg_exists_nick_name');
			}
		}

		// Check managed Email Host
		if($logged_info->is_admin !== 'Y' && MemberModel::isDeniedEmailHost($args->email_address))
		{
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = MemberModel::getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			return new BaseObject(-1, $message);
		}
		
		// Format phone number
		if (strval($args->phone_number) !== '')
		{
			$args->phone_country = trim(preg_replace('/[^A-Z]/', '', $args->phone_country), '-');
			$args->phone_number = preg_replace('/[^0-9]/', '', $args->phone_number);
			$args->phone_type = '';
			if ($config->phone_number_hide_country === 'Y' || (!$args->phone_country && $config->phone_number_default_country))
			{
				$args->phone_country = $config->phone_number_default_country;
			}
			if ($args->phone_country && !preg_match('/^[A-Z]{3}$/', $args->phone_country))
			{
				$args->phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($args->phone_country);
			}
			if ($args->phone_country === 'KOR' && !Rhymix\Framework\Korea::isValidPhoneNumber($args->phone_number))
			{
				return new BaseObject(-1, 'msg_invalid_phone_number');
			}
		}
		else
		{
			$args->phone_country = '';
			$args->phone_number = '';
			$args->phone_type = '';
		}

		// Check if email address is duplicate
		$member_srl = MemberModel::getMemberSrlByEmailAddress($args->email_address);
		if($member_srl)
		{
			return new BaseObject(-1, 'msg_exists_email_address');
		}

		// Check if phone number is duplicate
		if ($config->phone_number_allow_duplicate !== 'Y' && $args->phone_number)
		{
			$member_srl = MemberModel::getMemberSrlByPhoneNumber($args->phone_number, $args->phone_country);
			if($member_srl)
			{
				return new BaseObject(-1, 'msg_exists_phone_number');
			}
		}
		
		// Insert data into the DB
		$args->list_order = -1 * $args->member_srl;

		if(!$args->user_id) $args->user_id = 't'.$args->member_srl;
		if(!$args->user_name) $args->user_name = $args->member_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = executeQuery('member.insertMember', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if(is_array($args->group_srl_list)) $group_srl_list = $args->group_srl_list;
		else $group_srl_list = explode('|@|', $args->group_srl_list);
		// If no value is entered the default group, the value of group registration
		if(!$args->group_srl_list)
		{
			$columnList = array('site_srl', 'group_srl');
			$default_group = MemberModel::getDefaultGroup(0, $columnList);
			if($default_group)
			{
				// Add to the default group
				$output = $this->addMemberToGroup($args->member_srl,$default_group->group_srl);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
			// If the value is the value of the group entered the group registration
		}
		else
		{
			for($i=0;$i<count($group_srl_list);$i++)
			{
				$output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);

				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		// When using email authentication mode (when you subscribed members denied a) certified mail sent
		if($args->denied == 'Y')
		{
			// Insert data into the authentication DB
			$auth_args = new stdClass();
			$auth_args->user_id = $args->user_id;
			$auth_args->member_srl = $args->member_srl;
			$auth_args->new_password = $args->password;
			$auth_args->auth_key = Rhymix\Framework\Security::getRandom(40, 'hex');
			$auth_args->is_register = 'Y';

			$output = executeQuery('member.insertAuthMail', $auth_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
			$this->_sendAuthMail($auth_args, $args);
		}
		
		ModuleHandler::triggerCall('member.insertMember', 'after', $args);

		$oDB->commit(true);

		$output->add('member_srl', $args->member_srl);
		return $output;
	}

	/**
	 * Modify member information
	 *
	 * @param bool $is_admin , modified 2013-11-22
	 */
	function updateMember($args, $is_admin = FALSE)
	{
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('member.updateMember', 'before', $args);
		if(!$output->toBool()) return $output;
		// Create a model object
		$config = MemberModel::getMemberConfig();

		$logged_info = Context::get('logged_info');
		
		// Get what you want to modify the original information
		$orgMemberInfo = MemberModel::getMemberInfoByMemberSrl($args->member_srl);
		
		// Control of essential parameters
		if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
		if($args->allow_message && !in_array($args->allow_message, array('Y','N','F'))) $args->allow_message = 'Y';

		if($logged_info->is_admin == 'Y')
		{
			if($args->denied!='Y') $args->denied = 'N';
			if($args->is_admin!='Y' && $logged_info->member_srl != $args->member_srl) $args->is_admin = 'N';
		}
		else
		{
			unset($args->is_admin);
			unset($args->limit_date);
			unset($args->description);
			if($is_admin == false)
			{
				unset($args->denied);
			}
			if($logged_info->member_srl != $args->member_srl && $is_admin == false)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
		}

		// Sanitize user ID, username, nickname, homepage, blog
		if($args->user_id) $args->user_id = escape($args->user_id, false);
		$args->user_name = escape($args->user_name, false);
		$args->nick_name = escape($args->nick_name, false);
		$args->homepage = escape($args->homepage, false);
		$args->blog = escape($args->blog, false);
		if($args->homepage && !preg_match("/^[a-z]+:\/\//is",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
		if($args->blog && !preg_match("/^[a-z]+:\/\//is",$args->blog)) $args->blog = 'http://'.$args->blog;

		// check member identifier form

		// limit_date format is YYYYMMDD
		if($args->limit_date)
		{
			// mobile input date format can be different
			if($args->limit_date !== intval($args->limit_date))
			{
				$args->limit_date = date('Ymd', strtotime($args->limit_date));
			}
			else
			{
				$args->limit_date = intval($args->limit_date);
			}
		}

		$extend_form_list = MemberModel::getJoinFormlist();
		$security = new Security($extend_form_list);
		$security->encodeHTML('..column_title', '..description', '..default_value.');
		if($config->signupForm){
			foreach($config->signupForm as $no => $formInfo)
			{
				if(!$formInfo->isUse) continue;

				if($formInfo->isDefaultForm)
				{
					// birthday format is YYYYMMDD
					if($formInfo->name === 'birthday' && $args->{$formInfo->name})
					{
						if($args->{$formInfo->name} !== intval($args->{$formInfo->name}))
						{
							$args->{$formInfo->name} = date('Ymd', strtotime($args->{$formInfo->name}));
						}
						else
						{
							$args->{$formInfo->name} = intval($args->{$formInfo->name});
						}
					}
				}
				else
				{
					$extendForm = $extend_form_list[$formInfo->member_join_form_srl];
					// date format is YYYYMMDD
					if($extendForm->column_type == 'date' && $args->{$formInfo->name})
					{
						if($args->{$formInfo->name} !== intval($args->{$formInfo->name}))
						{
							$args->{$formInfo->name} = date('Ymd', strtotime($args->{$formInfo->name}));
						}
						else
						{
							$args->{$formInfo->name} = intval($args->{$formInfo->name});
						}
					}
				}
			}
		}
		
		// Format phone number
		if (strval($args->phone_number) !== '')
		{
			$args->phone_country = trim(preg_replace('/[^A-Z]/', '', $args->phone_country), '-');
			$args->phone_number = preg_replace('/[^0-9]/', '', $args->phone_number);
			$args->phone_type = '';
			if ($config->phone_number_hide_country === 'Y' || (!$args->phone_country && $config->phone_number_default_country))
			{
				$args->phone_country = $config->phone_number_default_country;
			}
			if ($args->phone_country && !preg_match('/^[A-Z]{3}$/', $args->phone_country))
			{
				$args->phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($args->phone_country);
			}
			if ($args->phone_country === 'KOR' && !Rhymix\Framework\Korea::isValidPhoneNumber($args->phone_number))
			{
				return new BaseObject(-1, 'msg_invalid_phone_number');
			}
		}
		else
		{
			$args->phone_country = '';
			$args->phone_number = '';
			$args->phone_type = '';
		}

		// Check managed Email Host
		if($logged_info->is_admin !== 'Y' && $logged_info->email_address !== $args->email_address && MemberModel::isDeniedEmailHost($args->email_address))
		{
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = MemberModel::getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			return new BaseObject(-1, $message);
		}

		// Check if email address or user ID is duplicate
		if($config->identifier == 'email_address')
		{
			$member_srl = MemberModel::getMemberSrlByEmailAddress($args->email_address);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new BaseObject(-1, 'msg_exists_email_address');
			}
			$args->email_address = $orgMemberInfo->email_address;
		}
		else
		{
			$member_srl = MemberModel::getMemberSrlByUserID($args->user_id);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new BaseObject(-1, 'msg_exists_user_id');
			}

			$args->user_id = $orgMemberInfo->user_id;
		}

		// Check if phone number is duplicate
		if ($config->phone_number_allow_duplicate !== 'Y' && $args->phone_number)
		{
			$member_srl = MemberModel::getMemberSrlByPhoneNumber($args->phone_number, $args->phone_country);
			if ($member_srl && $args->member_srl != $member_srl)
			{
				return new BaseObject(-1, 'msg_exists_phone_number');
			}
		}
		
		// Check if ID is prohibited
		if($logged_info->is_admin !== 'Y' && $args->user_id && MemberModel::isDeniedID($args->user_id))
		{
			return new BaseObject(-1, 'denied_user_id');
		}

		// Check if ID is duplicate
		if($args->user_id)
		{
			$member_srl = MemberModel::getMemberSrlByUserID($args->user_id);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new BaseObject(-1, 'msg_exists_user_id');
			}
		}

		// Check if nickname is prohibited
		if($logged_info->is_admin !== 'Y' && $args->nick_name && MemberModel::isDeniedNickName($args->nick_name))
		{
			return new BaseObject(-1, 'denied_nick_name');
		}

		// Check if nickname is duplicate
		if($config->allow_duplicate_nickname !== 'Y')
		{
			$member_srl = MemberModel::getMemberSrlByNickName($args->nick_name);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new BaseObject(-1, 'msg_exists_nick_name');
			}
		}

		list($args->email_id, $args->email_host) = explode('@', $args->email_address);

		$oDB = &DB::getInstance();
		$oDB->begin();

		// Check password strength
		if($args->password)
		{
			if(!MemberModel::checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new BaseObject(-1, $message[$config->password_strength]);
			}
			$args->password = MemberModel::hashPassword($args->password);
		}
		else
		{
			$args->password = $orgMemberInfo->password;
		}

		if(!$args->user_name) $args->user_name = $orgMemberInfo->user_name;
		if(!$args->user_id) $args->user_id = $orgMemberInfo->user_id;
		if(!$args->nick_name) $args->nick_name = $orgMemberInfo->nick_name;
		if($logged_info->is_admin !== 'Y')
		{
			$args->description = $orgMemberInfo->description;
		}
		if(!$args->birthday) $args->birthday = $orgMemberInfo->birthday;

		$output = executeQuery('member.updateMember', $args);

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		else
		{
			if($args->nick_name != $orgMemberInfo->nick_name && $config->update_nickname_log == 'Y')
			{
				$log_args = new stdClass();
				$log_args->member_srl = $args->member_srl;
				$log_args->before_nick_name = $orgMemberInfo->nick_name;
				$log_args->after_nick_name = $args->nick_name;
				$log_args->user_id = $args->user_id;
				$log_output = executeQuery('member.insertMemberModifyNickName', $log_args);
			}
		}

		if($args->group_srl_list)
		{
			if(is_array($args->group_srl_list)) $group_srl_list = $args->group_srl_list;
			else $group_srl_list = explode('|@|', $args->group_srl_list);
			// If the group information, group information changes
			if(count($group_srl_list) > 0)
			{
				$args->site_srl = 0;
				// One of its members to delete all the group
				$output = executeQuery('member.deleteMemberGroupMember', $args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
				// Enter one of the loop a
				for($i=0;$i<count($group_srl_list);$i++)
				{
					$output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);
					if(!$output->toBool())
					{
						$oDB->rollback();
						return $output;
					}
				}

				// if group is changed, point changed too.
				$this->_updatePointByGroup($orgMemberInfo->member_srl, $group_srl_list);
			}
		}
		
		// Call a trigger (after)
		ModuleHandler::triggerCall('member.updateMember', 'after', $args);

		$oDB->commit();

		// Remove from cache
		unset($GLOBALS['__member_info__'][$args->member_srl]);
		self::clearMemberCache($args->member_srl, $args->site_srl);

		$output->add('member_srl', $args->member_srl);
		return $output;
	}

	/**
	 * Modify member extra variable
	 */
	function updateMemberExtraVars($member_srl, array $values)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('member.getMemberInfoByMemberSrl', $args, array('extra_vars'));
		if (!$output->toBool())
		{
			return $output;
		}
		
		$extra_vars = $output->data->extra_vars ? unserialize($output->data->extra_vars) : new stdClass;
		foreach ($values as $key => $val)
		{
			$extra_vars->{$key} = $val;
		}
		
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->extra_vars = serialize($extra_vars);
		$output = executeQuery('member.updateMemberExtraVars', $args);
		if (!$output->toBool())
		{
			return $output;
		}
		
		unset($GLOBALS['__member_info__'][$member_srl]);
		self::clearMemberCache($member_srl);

		return $output;
	}

	/**
	 * Modify member password
	 */
	function updateMemberPassword($args)
	{
		if($args->password)
		{
			// check password strength
			$config = MemberModel::getMemberConfig();

			if(!MemberModel::checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new BaseObject(-1, $message[$config->password_strength]);
			}

			$args->password = MemberModel::hashPassword($args->password);
		}
		else if($args->hashed_password)
		{
			$args->password = $args->hashed_password;
		}

		$output = executeQuery('member.updateMemberPassword', $args);
		if($output->toBool())
		{
			$result = executeQuery('member.updateChangePasswordDate', $args);
		}

		unset($GLOBALS['__member_info__'][$args->member_srl]);
		self::clearMemberCache($args->member_srl);

		return $output;
	}

	/**
	 * Delete User
	 */
	function deleteMember($member_srl)
	{
		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->member_srl = $member_srl;
		$output = ModuleHandler::triggerCall('member.deleteMember', 'before', $trigger_obj);
		if(!$output->toBool()) return $output;
		// Bringing the user's information
		$columnList = array('member_srl', 'is_admin');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		if(!$member_info) return new BaseObject(-1, 'msg_not_exists_member');
		// If managers can not be deleted
		if($member_info->is_admin == 'Y') return new BaseObject(-1, 'msg_cannot_delete_admin');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$args = new stdClass();
		$args->member_srl = $member_srl;

		// Delete the entries in member_auth_mail
		$output = executeQuery('member.deleteAuthMail', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete agreement info
		$output = executeQuery('member.deleteAgreed', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete nickname log
		$output = executeQuery('member.deleteMemberModifyNickNameLog', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete the entries in member_group_member
		$output = executeQuery('member.deleteMemberGroupMember', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete main member info
		$output = executeQuery('member.deleteMember', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Call a trigger (after)
		ModuleHandler::triggerCall('member.deleteMember', 'after', $trigger_obj);

		$oDB->commit();
		
		// Name, image, image, mark, sign, delete
		$this->procMemberDeleteImageName($member_srl);
		$this->procMemberDeleteImageMark($member_srl);
		$this->procMemberDeleteProfileImage($member_srl);
		$this->delSignature($member_srl);
		self::clearMemberCache($member_srl);
		
		// Delete all remaining extra info
		$dirs = Rhymix\Framework\Storage::readDirectory(RX_BASEDIR . 'files/member_extra_info', true, true, false);
		foreach ($dirs as $dir)
		{
			$member_dir = $dir . '/' . getNumberingPath($member_srl);
			Rhymix\Framework\Storage::deleteDirectory($member_dir, false);
			Rhymix\Framework\Storage::deleteEmptyDirectory($member_dir, true);
		}

		return $output;
	}

	/**
	 * Destroy all session information
	 */
	function destroySessionInfo()
	{
		Rhymix\Framework\Session::destroy();
	}

	function _updatePointByGroup($memberSrl, $groupSrlList)
	{
		$pointModuleConfig = ModuleModel::getModuleConfig('point');
		$pointGroup = $pointModuleConfig->point_group;

		$levelGroup = array();
		if(is_array($pointGroup) && count($pointGroup)>0)
		{
			$levelGroup = array_flip($pointGroup);
			ksort($levelGroup);
		}
		$maxLevel = 0;
		$resultGroup = array_intersect($levelGroup, $groupSrlList);
		if(count($resultGroup) > 0)
			$maxLevel = max(array_flip($resultGroup));

		if($maxLevel > 0)
		{
			$originPoint = PointModel::getPoint($memberSrl);

			if($pointModuleConfig->level_step[$maxLevel] > $originPoint)
			{
				$oPointController = getController('point');
				$oPointController->setPoint($memberSrl, $pointModuleConfig->level_step[$maxLevel], 'update');
			}
		}
	}

	function procMemberModifyEmailAddress()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\MustLogin;

		$member_info = Context::get('logged_info');
		$newEmail = Context::get('email_address');

		if(!$newEmail) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		// Check managed Email Host
		if(MemberModel::isDeniedEmailHost($newEmail))
		{
			$config = MemberModel::getMemberConfig();
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = MemberModel::getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			throw new Rhymix\Framework\Exception($message);
		}

		// Check if the e-mail address is already registered
		$member_srl = MemberModel::getMemberSrlByEmailAddress($newEmail);
		if($member_srl) throw new Rhymix\Framework\Exception('msg_exists_email_address');

		if($_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		unset($_SESSION['rechecked_password_step']);

		$auth_args = new stdClass();
		$auth_args->user_id = $newEmail;
		$auth_args->member_srl = $member_info->member_srl;
		$auth_args->auth_key = Rhymix\Framework\Security::getRandom(40, 'hex');
		$auth_args->new_password = 'XE_change_emaill_address';

		$oDB = &DB::getInstance();
		$oDB->begin();
		$output = executeQuery('member.insertAuthMail', $auth_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$member_config = ModuleModel::getModuleConfig('member');

		$tpl_path = sprintf('%sskins/%s', $this->module_path, $member_config->skin);
		if(!is_dir($tpl_path)) $tpl_path = sprintf('%sskins/%s', $this->module_path, 'default');

		global $lang;

		$memberInfo = array();
		$memberInfo[$lang->email_address] = $member_info->email_address;
		$memberInfo[$lang->nick_name] = $member_info->nick_name;

		Context::set('memberInfo', $memberInfo);

		Context::set('newEmail', $newEmail);

		$auth_url = getFullUrl('','module','member','act','procMemberAuthEmailAddress','member_srl',$member_info->member_srl, 'auth_key',$auth_args->auth_key);
		Context::set('auth_url', $auth_url);

		$oTemplate = &TemplateHandler::getInstance();
		$content = $oTemplate->compile($tpl_path, 'confirm_member_new_email');

		$oMail = new \Rhymix\Framework\Mail();
		$oMail->setSubject(lang('title_modify_email_address'));
		$oMail->setBody($content);
		$oMail->addTo($newEmail, $member_info->nick_name);
		$oMail->send();

		$msg = sprintf(lang('msg_confirm_mail_sent'), $newEmail);
		$this->setMessage($msg);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
		$this->setRedirectUrl($returnUrl);
	}

	function procMemberAuthEmailAddress()
	{
		$member_srl = Context::get('member_srl');
		$auth_key = Context::get('auth_key');
		if(!$member_srl || !$auth_key) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		// Test logs for finding password by user_id and authkey
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->auth_key = $auth_key;
		$output = executeQuery('member.getAuthMail', $args);
		if(!$output->toBool() || $output->data->auth_key != $auth_key)
		{
			if(strlen($output->data->auth_key) !== strlen($auth_key)) executeQuery('member.deleteAuthChangeEmailAddress', $args);
			throw new Rhymix\Framework\Exception('msg_invalid_modify_email_auth_key');
		}

		$newEmail = $output->data->user_id;
		$args->email_address = $newEmail;
		list($args->email_id, $args->email_host) = explode('@', $newEmail);

		$output = executeQuery('member.updateMemberEmailAddress', $args);
		if(!$output->toBool()) return $output;

		// Remove all values having the member_srl and new_password equal to 'XE_change_emaill_address' from authentication table
		executeQuery('member.deleteAuthChangeEmailAddress',$args);

		self::clearMemberCache($args->member_srl);

		// Call a trigger (after)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $args->member_srl;
		$trigger_obj->email_address = $args->email_address;
		$trigger_output = ModuleHandler::triggerCall('member.updateMemberEmailAddress', 'after', $trigger_obj);
		
		// Redirect to member info page
		$this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberInfo'));
	}

	function procMemberSendVerificationSMS()
	{
		$config = MemberModel::getMemberConfig();
		if ($config->phone_number_verify_by_sms !== 'Y')
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}
		
		$phone_country = Context::get('phone_country');
		$phone_number = Context::get('phone_number');
		
		if ($config->phone_number_default_country && (!$phone_country || $config->phone_number_hide_country === 'Y'))
		{
			$phone_country = $config->phone_number_default_country;
		}
		if (preg_match('/[A-Z]{3}/', $phone_country))
		{
			$phone_country_calling_code = preg_replace('/[^0-9]/', '', Rhymix\Framework\i18n::getCallingCodeByCountryCode($phone_country));
			if (!$phone_country_calling_code)
			{
				return new BaseObject(-1, 'msg_invalid_phone_country');
			}
		}
		else
		{
			return new BaseObject(-1, 'msg_invalid_phone_country');
		}
		
		if (!preg_match('/[0-9]{2,}/', $phone_number))
		{
			return new BaseObject(-1, 'msg_invalid_phone_number');
		}
		if ($phone_country === 'KOR' && !Rhymix\Framework\Korea::isValidPhoneNumber($phone_number))
		{
			return new BaseObject(-1, 'msg_invalid_phone_number');
		}
		
		$code = intval(mt_rand(100000, 999999));
		$_SESSION['verify_by_sms'] = array(
			'country' => $phone_country,
			'number' => $phone_number,
			'code' => $code,
			'status' => false,
		);
		
		$sms = new Rhymix\Framework\SMS;
		$sms->addTo($phone_number, $phone_country_calling_code);
		$content = '[' . Context::get('site_module_info')->settings->title . '] ' . sprintf(lang('member.verify_by_sms_message'), $code);
		$sms->setContent($content);
		$result = $sms->send();
		if ($result && config('sms.type') !== 'dummy')
		{
			return new BaseObject(0, 'verify_by_sms_code_sent');
		}
		else
		{
			return new BaseObject(0, 'verify_by_sms_error');
		}
	}
	
	function procMemberConfirmVerificationSMS()
	{
		$config = MemberModel::getMemberConfig();
		if ($config->phone_number_verify_by_sms !== 'Y')
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}
		
		$code = Context::get('code');
		if(!preg_match('/^[0-9]{6}$/', $code))
		{
			throw new Rhymix\Framework\Exception('verify_by_sms_code_incorrect');
		}
		
		$code = intval($code);
		if(!isset($_SESSION['verify_by_sms']) || $_SESSION['verify_by_sms']['code'] !== $code)
		{
			throw new Rhymix\Framework\Exception('verify_by_sms_code_incorrect');
		}
		
		$_SESSION['verify_by_sms']['status'] = true;
		return new BaseObject(0, 'verify_by_sms_code_confirmed');
	}
	
	/**
	 * Delete a registered device.
	 */
	public function procMemberDeleteDevice()
	{
		$device_srl = intval(Context::get('device_srl'));
		$logged_info = Context::get('logged_info');
		
		$args = new stdClass;
		$args->device_srl = $device_srl;
		$output = executeQuery('member.getMemberDevice', $args);
		if (!$output->data || !is_object($output->data))
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if (!$output->data->member_srl || $output->data->member_srl != $logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		
		$args = new stdClass;
		$args->device_token = $output->data->device_token;
		$output = executeQuery('member.deleteMemberDevice', $args);
		if (!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * trigger for document.getDocumentMenu. Append to popup menu a button for procMemberSpammerManage()
	 *
	 * @param array &$menu_list
	 *
	 * @return object
	**/
	function triggerGetDocumentMenu(&$menu_list)
	{
		if(!Context::get('is_logged')) return;

		$logged_info = Context::get('logged_info');
		$document_srl = Context::get('target_srl');

		$columnList = array('document_srl', 'module_srl', 'member_srl', 'ipaddress');
		$oDocument = DocumentModel::getDocument($document_srl, false, false, $columnList);
		$member_srl = abs($oDocument->get('member_srl'));
		$module_srl = $oDocument->get('module_srl');

		if(!$member_srl || $member_srl == $logged_info->member_srl) return;
		if(!ModuleModel::getGrant(ModuleModel::getModuleInfoByModuleSrl($module_srl), $logged_info)->manager) return;

		$oDocumentController = getController('document');
		$url = getUrl('','module','member','act','dispMemberSpammer','member_srl',$member_srl,'module_srl',$module_srl);
		$oDocumentController->addDocumentPopupMenu($url,'cmd_spammer','','popup');
	}

	/**
	 * trigger for comment.getCommentMenu. Append to popup menu a button for procMemberSpammerManage()
	 *
	 * @param array &$menu_list
	 *
	 * @return object
	**/
	function triggerGetCommentMenu(&$menu_list)
	{
		if(!Context::get('is_logged')) return;

		$logged_info = Context::get('logged_info');
		$comment_srl = Context::get('target_srl');

		$columnList = array('comment_srl', 'module_srl', 'member_srl', 'ipaddress');
		$oComment = CommentModel::getComment($comment_srl, FALSE, $columnList);
		$module_srl = $oComment->get('module_srl');
		$member_srl = abs($oComment->get('member_srl'));

		if(!$member_srl || $member_srl == $logged_info->member_srl) return;
		if(!ModuleModel::getGrant(ModuleModel::getModuleInfoByModuleSrl($module_srl), $logged_info)->manager) return;

		$oCommentController = getController('comment');
		$url = getUrl('','module','member','act','dispMemberSpammer','member_srl',$member_srl,'module_srl',$module_srl);
		$oCommentController->addCommentPopupMenu($url,'cmd_spammer','','popup');
	}

	/**
	 * Spammer manage. Denied user login. And delete or trash all documents. Response Ajax string
	 *
	 * @return object
	**/
	function procMemberSpammerManage()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$logged_info = Context::get('logged_info');
		$member_srl = Context::get('member_srl');
		$module_srl = Context::get('module_srl');
		$cnt_loop = Context::get('cnt_loop');
		$proc_type = Context::get('proc_type');
		$isMoveToTrash = true;
		if($proc_type == "delete")
			$isMoveToTrash = false;

		// check grant
		$columnList = array('module_srl', 'module');
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl, $columnList);
		$grant = ModuleModel::getGrant($module_info, $logged_info);

		if(!$grant->manager) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$proc_msg = "";

		// delete or trash destination
		// proc member
		if($cnt_loop == 1)
			$this->_spammerMember($member_srl);
		// proc document and comment
		elseif($cnt_loop>1)
			$this->_spammerDocuments($member_srl, $isMoveToTrash);

		// get destination count
		$cnt_document = DocumentModel::getDocumentCountByMemberSrl($member_srl);
		$cnt_comment = CommentModel::getCommentCountByMemberSrl($member_srl);

		$total_count = Context::get('total_count');
		$remain_count = $cnt_document + $cnt_comment;
		if($cnt_loop == 1) $total_count = $remain_count;

		// get progress percent
		if($total_count > 0)
			$progress = intval( ( ( $total_count - $remain_count ) / $total_count ) * 100 );
		else
			$progress = 100;

		$this->add('total_count', $total_count);
		$this->add('remain_count', $remain_count);
		$this->add('progress', $progress);
		$this->add('member_srl', $member_srl);
		$this->add('module_srl', $module_srl);
		$this->add('cnt_loop', ++$cnt_loop);
		$this->add('proc_type', $proc_type);

		return new BaseObject(0);
	}

	/**
	 * Denied user login and write description
	 *
	 * @param int $member_srl
	 *
	 * @return object
	**/
	private function _spammerMember($member_srl) {
		$logged_info = Context::get('logged_info');
		$spam_description = trim( Context::get('spam_description') );

		$columnList = array('member_srl', 'email_address', 'user_id', 'nick_name', 'description');
		// get member current infomation
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);

		$cnt_comment = CommentModel::getCommentCountByMemberSrl($member_srl);
		$cnt_document = DocumentModel::getDocumentCountByMemberSrl($member_srl);
		$total_count = $cnt_comment + $cnt_document;

		$args = new stdClass();
		$args->member_srl = $member_info->member_srl;
		$args->email_address = $member_info->email_address;
		$args->user_id = $member_info->user_id;
		$args->nick_name = $member_info->nick_name;
		$args->denied = "Y";
		$args->description = trim( $member_info->description );
		if( $args->description != "" ) $args->description .= "\n";	// add new line

		$args->description .= lang('cmd_spammer') . "[" . date("Y-m-d H:i:s") . " from:" . $logged_info->user_id . " info:" . $spam_description . " docuemnts count:" . $total_count . "]";

		$output = $this->updateMember($args, true);

		self::clearMemberCache($args->member_srl);

		return $output;
	}

	/**
	 * Delete or trash all documents
	 *
	 * @param int $member_srl
	 * @param bool $isMoveToTrash
	 *
	 * @return object
	**/
	private function _spammerDocuments($member_srl, $isMoveToTrash)
	{
		$oDocumentController = getController('document');
		$oCommentController = getController('comment');

		// delete count by one request
		$getContentsCount = 10;

		// 1. proc comment, 2. proc document
		$cnt_comment = CommentModel::getCommentCountByMemberSrl($member_srl);
		$cnt_document = DocumentModel::getDocumentCountByMemberSrl($member_srl);
		if($cnt_comment > 0)
		{
			$columnList = array();
			$commentList = CommentModel::getCommentListByMemberSrl($member_srl, $columnList, 0, false, $getContentsCount);
			if($commentList) {
				foreach($commentList as $v) {
					$oCommentController->deleteComment($v->comment_srl, true, $isMoveToTrash);
				}
			}
		} elseif($cnt_document > 0) {
			$columnList = array();
			$documentList = DocumentModel::getDocumentListByMemberSrl($member_srl, $columnList, 0, false, $getContentsCount);
			if($documentList) {
				foreach($documentList as $v) {
					if($isMoveToTrash) $oDocumentController->moveDocumentToTrash($v);
					else $oDocumentController->deleteDocument($v->document_srl);
				}
			}
		}

		return array();
	}

	public static function _clearMemberCache($member_srl)
	{
		return self::clearMemberCache($member_srl);
	}
	
	public static function clearMemberCache($member_srl)
	{
		$member_srl = intval($member_srl);
		Rhymix\Framework\Cache::delete("member:member_info:$member_srl");
		Rhymix\Framework\Cache::delete("member:member_groups:$member_srl:site:0");
		Rhymix\Framework\Cache::delete("site_and_module:accessible_modules:$member_srl");
		unset($GLOBALS['__member_info__'][$member_srl]);
		unset($GLOBALS['__member_groups__'][$member_srl]);
	}
}
/* End of file member.controller.php */
/* Location: ./modules/member/member.controller.php */
