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
			return new Object(-1, 'null_user_id');
		}

		// Variables
		if(!$user_id) $user_id = Context::get('user_id');
		$user_id = trim($user_id);

		if(!$password) $password = Context::get('password');
		$password = trim($password);

		if(!$keep_signed) $keep_signed = Context::get('keep_signed');
		// Return an error when id and password doesn't exist
		if(!$user_id) return new Object(-1,'null_user_id');
		if(!$password) return new Object(-1,'null_password');

		$output = $this->doLogin($user_id, $password, $keep_signed=='Y'?true:false);
		if (!$output->toBool()) return $output;

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		$member_info = Context::get('logged_info');

		// Check change_password_date
		$limit_date = $config->change_password_date;

		// Check if change_password_date is set
		if($limit_date > 0)
		{
			$oMemberModel = getModel('member');
			if($member_info->change_password_date < date ('YmdHis', strtotime ('-' . $limit_date . ' day')))
			{
				$msg = sprintf(lang('msg_change_password_date'), $limit_date);
				return $this->setRedirectUrl(getNotEncodedUrl('','vid',Context::get('vid'),'mid',Context::get('mid'),'act','dispMemberModifyPassword'), new Object(-1, $msg));
			}
		}

		// Delete all previous authmail if login is successful
		$args = new stdClass();
		$args->member_srl = $member_info->member_srl;
		executeQuery('member.deleteAuthMail', $args);

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
		$this->_clearMemberCache($logged_info->member_srl);
		
		// Call a trigger after log-out (after)
		ModuleHandler::triggerCall('member.doLogout', 'after', $logged_info);

		$output = new Object();

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
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
			return new Object(-1,'msg_invalid_request');
		}
		
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		
		// Check document
		if($oDocument->isSecret() && !$oDocument->isGranted())
		{
			return new Object(-1, 'msg_is_secret');
		}
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($oDocument->get('module_srl'));
		
		$logged_info = Context::get('logged_info');
		$grant = $oModuleModel->getGrant($module_info, $logged_info);
		
		// Check access to module of the document
		if(!$grant->access)
		{
			return new Object(-1, 'msg_not_permitted');
		}
		
		// Check grant to module of the document
		if(isset($grant->list) && isset($grant->view) && (!$grant->list || !$grant->view))
		{
			return new Object(-1, 'msg_not_permitted');
		}
		
		// Check consultation option
		if(isset($grant->consultation_read) && $module_info->consultation == 'Y' && !$grant->consultation_read && !$oDocument->isGranted())
		{
			return new Object(-1, 'msg_not_permitted');
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
			return new Object(-1, 'msg_alreay_scrapped');
		}
		
		// Insert
		$output = executeQuery('member.addScrapDocument', $args);
		if(!$output->toBool()) return $output;
		
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		if(!$document_srl) return new Object(-1,'msg_invalid_request');
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		$folder_srl = (int)Context::get('folder_srl');
		if(!$document_srl || !$folder_srl)
		{
			return new Object(-1,'msg_invalid_request');
		}
		
		// Check that the target folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			return new Object(-1, 'msg_invalid_request');
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');
		
		// Get new folder name
		$folder_name = Context::get('name');
		$folder_name = escape(trim(utf8_normalize_spaces($folder_name)));
		if(!$folder_name)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Check existing folder with same name
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->name = $folder_name;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(count($output->data) || $folder_name === lang('default_folder'))
		{
			return new Object(-1, 'msg_folder_alreay_exists');
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');
		
		// Get new folder name
		$folder_srl = intval(Context::get('folder_srl'));
		$folder_name = Context::get('name');
		$folder_name = escape(trim(utf8_normalize_spaces($folder_name)));
		if(!$folder_srl || !$folder_name)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Check that the original folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			return new Object(-1, 'msg_invalid_request');
		}
		if(array_first($output->data)->name === '/DEFAULT/')
		{
			return new Object(-1, 'msg_folder_is_default');
		}
		
		// Check existing folder with same name
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->not_folder_srl = $folder_srl;
		$args->name = $folder_name;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(count($output->data) || $folder_name === lang('default_folder'))
		{
			return new Object(-1, 'msg_folder_alreay_exists');
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');
		
		// Get folder_srl to delete
		$folder_srl = intval(Context::get('folder_srl'));
		if(!$folder_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		// Check that the folder exists and belongs to member
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		if(!count($output->data))
		{
			return new Object(-1, 'msg_invalid_request');
		}
		if(array_first($output->data)->name === '/DEFAULT/')
		{
			return new Object(-1, 'msg_folder_is_default');
		}
		
		// Check that the folder is empty
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$output = executeQueryArray('member.getScrapDocumentList', $args);
		if(count($output->data))
		{
			return new Object(-1, 'msg_folder_not_empty');
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
		return new Object(0, 'Deprecated method');
	}

	/**
	 * Delete the post
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberDeleteSavedDocument()
	{
		// Check login information
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');

		$document_srl = (int)Context::get('document_srl');
		if(!$document_srl) return new Object(-1,'msg_invalid_request');
		
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if ($oDocument->get('member_srl') != $logged_info->member_srl)
		{
			return new Object(-1,'msg_invalid_request');
		}
		$configStatusList = $oDocumentModel->getStatusList();
		if ($oDocument->get('status') != $configStatusList['temp'])
		{
			return new Object(-1,'msg_invalid_request');
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
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
		$logged_info = Context::get('logged_info');
		
		$autologin_id = intval(Context::get('autologin_id'));
		$autologin_key = Context::get('autologin_key');
		if (!$autologin_id || !$autologin_key)
		{
			return new Object(-1, 'msg_invalid_request');
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

		$oMemberModel = getModel('member');
		// Check if logged-in
		$logged_info = Context::get('logged_info');


		switch($name)
		{
			case 'user_id' :
				// Check denied ID
				if($oMemberModel->isDeniedID($value)) return new Object(0,'denied_user_id');
				// Check if duplicated
				$member_srl = $oMemberModel->getMemberSrlByUserID($value);
				if($member_srl && $logged_info->member_srl != $member_srl ) return new Object(0,'msg_exists_user_id');
				break;
			case 'nick_name' :
				// Check denied ID
				if($oMemberModel->isDeniedNickName($value))
				{
					return new Object(0,'denied_nick_name');
				}
				// Check if duplicated
				$member_srl = $oMemberModel->getMemberSrlByNickName($value);
				if($member_srl && $logged_info->member_srl != $member_srl ) return new Object(0,'msg_exists_nick_name');

				break;
			case 'email_address' :
				// Check managed Email Host
				if($oMemberModel->isDeniedEmailHost($value))
				{
					$config = $oMemberModel->getMemberConfig();
					$emailhost_check = $config->emailhost_check;

					$managed_email_host = lang('managed_email_host');

					$email_hosts = $oMemberModel->getManagedEmailHosts();
					foreach ($email_hosts as $host)
					{
						$hosts[] = $host->email_host;
					}
					$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
					return new Object(0,$message);
				}

				// Check if duplicated
				$member_srl = $oMemberModel->getMemberSrlByEmailAddress($value);
				if($member_srl && $logged_info->member_srl != $member_srl ) return new Object(0,'msg_exists_email_address');
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
		if (Context::getRequestMethod () == "GET") return new Object (-1, "msg_invalid_request");
		$oMemberModel = &getModel ('member');
		$config = $oMemberModel->getMemberConfig();

		// call a trigger (before)
		$trigger_output = ModuleHandler::triggerCall ('member.procMemberInsert', 'before', $config);
		if(!$trigger_output->toBool ()) return $trigger_output;
		// Check if an administrator allows a membership
		if($config->enable_join != 'Y') return $this->stop ('msg_signup_disabled');
		// Check if the user accept the license terms (only if terms exist)
		if($config->agreement && Context::get('accept_agreement')!='Y') return $this->stop('msg_accept_agreement');

		// Extract the necessary information in advance
		$getVars = array();
		if($config->signupForm)
		{
			foreach($config->signupForm as $formInfo)
			{
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
			
			if($val == 'birthday')
			{
				$args->birthday_ui = Context::get('birthday_ui');
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

		// check password strength
		if(!$oMemberModel->checkPasswordStrength($args->password, $config->password_strength))
		{
			$message = lang('about_password_strength');
			return new Object(-1, $message[$config->password_strength]);
		}

		// Remove some unnecessary variables from all the vars
		$all_args = Context::getRequestVars();
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
		unset($all_args->error_return_url);
		unset($all_args->ruleset);
		unset($all_args->captchaType);
		unset($all_args->secret_text);

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
				$args->{$val} = preg_replace('/[\pZ\pC]+/u', '', html_entity_decode($args->{$val}));
			}
		}
		$output = $this->insertMember($args);
		if(!$output->toBool()) return $output;

		// insert ProfileImage, ImageName, ImageMark
		$profile_image = $_FILES['profile_image'];
		if(is_uploaded_file($profile_image['tmp_name']))
		{
			$this->insertProfileImage($args->member_srl, $profile_image['tmp_name']);
		}

		$image_mark = $_FILES['image_mark'];
		if(is_uploaded_file($image_mark['tmp_name']))
		{
			$this->insertImageMark($args->member_srl, $image_mark['tmp_name']);
		}

		$image_name = $_FILES['image_name'];
		if(is_uploaded_file($image_name['tmp_name']))
		{
			$this->insertImageName($args->member_srl, $image_name['tmp_name']);
		}

		// Save Signature
		$signature = Context::get('signature');
		$this->putSignature($args->member_srl, $signature);

		// If a virtual site, join the site
		$site_module_info = Context::get('site_module_info');
		if($site_module_info->site_srl > 0)
		{
			$columnList = array('site_srl', 'group_srl');
			$default_group = $oMemberModel->getDefaultGroup($site_module_info->site_srl, $columnList);
			if($default_group->group_srl)
			{
				$this->addMemberToGroup($args->member_srl, $default_group->group_srl, $site_module_info->site_srl);
			}

		}
		// Log-in
		if($config->enable_confirm != 'Y')
		{
			if($config->identifier == 'email_address')
			{
				$output = $this->doLogin($args->email_address);
			}
			else
			{
				$output = $this->doLogin($args->user_id);
			}
			if(!$output->toBool()) {
				if($output->error == -9)
					$output->error = -11;
				return $this->setRedirectUrl(getUrl('', 'act', 'dispMemberLoginForm'), $output);
			}
		}

		// Results
		$this->add('member_srl', $args->member_srl);
		if($config->redirect_url) $this->add('redirect_url', $config->redirect_url);
		if($config->enable_confirm == 'Y')
		{
			$msg = sprintf(lang('msg_confirm_mail_sent'), $args->email_address);
			$this->setMessage($msg);
			return $this->setRedirectUrl(getUrl('', 'act', 'dispMemberLoginForm'), new Object(-12, $msg));
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

		$this->_clearMemberCache($args->member_srl, $site_module_info->site_srl);

		$this->setRedirectUrl($returnUrl);
	}

	function procMemberModifyInfoBefore()
	{
		if($_SESSION['rechecked_password_step'] != 'INPUT_PASSWORD')
		{
			return $this->stop('msg_invalid_request');
		}

		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$password = Context::get('password');

		if(!$password)
		{
			return $this->stop('msg_invalid_request');
		}

		$oMemberModel = getModel('member');

		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$columnList = array('member_srl', 'password');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		
		// Verify the current password
		if(!$oMemberModel->isValidPassword($member_info->password, $password))
		{
			return new Object(-1, 'invalid_password');
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
			return $this->stop('msg_not_logged');
		}

		if($_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			return $this->stop('msg_invalid_request');
		}
		unset($_SESSION['rechecked_password_step']);

		// Extract the necessary information in advance
		$oMemberModel = &getModel ('member');
		$config = $oMemberModel->getMemberConfig ();
		$getVars = array('allow_mailing','allow_message');
		if($config->signupForm)
		{
			foreach($config->signupForm as $formInfo)
			{
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
			
			if($val == 'birthday')
			{
				$args->birthday_ui = Context::get('birthday_ui');
			}
		}
		
		// Login Information
		$logged_info = Context::get('logged_info');
		$args->member_srl = $logged_info->member_srl;

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

		// Remove some unnecessary variables from all the vars
		$all_args = Context::getRequestVars();
		unset($all_args->module);
		unset($all_args->act);
		unset($all_args->member_srl);
		unset($all_args->is_admin);
		unset($all_args->description);
		unset($all_args->group_srl_list);
		unset($all_args->body);
		unset($all_args->accept_agreement);
		unset($all_args->signature);
		unset($all_args->_filter);
		unset($all_args->mid);
		unset($all_args->error_return_url);
		unset($all_args->ruleset);
		unset($all_args->password);

		// Add extra vars after excluding necessary information from all the requested arguments
		$extra_vars = delObjectVars($all_args, $args);
		$args->extra_vars = serialize($extra_vars);

		// remove whitespace
		$checkInfos = array('user_id', 'user_name', 'nick_name', 'email_address');
		foreach($checkInfos as $val)
		{
			if(isset($args->{$val}))
			{
				$args->{$val} = preg_replace('/[\pZ\pC]+/u', '', $args->{$val});
			}
		}

		// Execute insert or update depending on the value of member_srl
		$output = $this->updateMember($args);
		if(!$output->toBool()) return $output;

		$profile_image = $_FILES['profile_image'];
		if(is_uploaded_file($profile_image['tmp_name']))
		{
			$this->insertProfileImage($args->member_srl, $profile_image['tmp_name']);
		}

		$image_mark = $_FILES['image_mark'];
		if(is_uploaded_file($image_mark['tmp_name']))
		{
			$this->insertImageMark($args->member_srl, $image_mark['tmp_name']);
		}

		$image_name = $_FILES['image_name'];
		if(is_uploaded_file($image_name['tmp_name']))
		{
			$this->insertImageName($args->member_srl, $image_name['tmp_name']);
		}

		// Save Signature
		$signature = Context::get('signature');
		$this->putSignature($args->member_srl, $signature);

		// Get user_id information
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

		// Call a trigger after successfully modified (after)
		ModuleHandler::triggerCall('member.procMemberModifyInfo', 'after', $member_info);
		$this->setSessionInfo();
		
		// Return result
		$this->add('member_srl', $args->member_srl);
		$this->setMessage('success_updated');

		$site_module_info = Context::get('site_module_info');
		$this->_clearMemberCache($args->member_srl, $site_module_info->site_srl);

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
		if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
		// Extract the necessary information in advance
		$current_password = trim(Context::get('current_password'));
		$password = trim(Context::get('password1'));
		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		// Create a member model object
		$oMemberModel = getModel('member');
		// Get information of member_srl
		$columnList = array('member_srl', 'password');

		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		// Verify the cuttent password
		if(!$oMemberModel->isValidPassword($member_info->password, $current_password, $member_srl)) return new Object(-1, 'invalid_password');

		// Check if a new password is as same as the previous password
		if($current_password == $password) return new Object(-1, 'invalid_new_password');

		// Execute insert or update depending on the value of member_srl
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->password = $password;
		$output = $this->updateMemberPassword($args);
		if(!$output->toBool()) return $output;
		
		// Log out all other sessions.
		$oModuleModel = getModel('module');
		$member_config = $oModuleModel->getModuleConfig('member');
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
		if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
		// Extract the necessary information in advance
		$password = trim(Context::get('password'));
		// Get information of logged-in user
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		// Create a member model object
		$oMemberModel = getModel('member');
		// Get information of member_srl
		$columnList = array('member_srl', 'password');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		// Verify the cuttent password
		if(!$oMemberModel->isValidPassword($member_info->password, $password)) return new Object(-1, 'invalid_password');

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
		$file = $_FILES['profile_image'];
		if(!is_uploaded_file($file['tmp_name'])) return $this->stop('msg_not_uploaded_profile_image');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) return $this->stop('msg_not_uploaded_profile_image');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) return $this->stop('msg_not_uploaded_profile_image');
		// Return if member module is set not to use an image name or the user is not an administrator ;
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		if($logged_info->is_admin != 'Y' && $config->profile_image != 'Y') return $this->stop('msg_not_uploaded_profile_image');

		$this->insertProfileImage($member_srl, $file['tmp_name']);
		// Page refresh
		//$this->setRefreshPage();

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
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();

		// Get an image size
		$max_width = $config->profile_image_max_width;
		if(!$max_width) $max_width = "90";
		$max_height = $config->profile_image_max_height;
		if(!$max_height) $max_height = "90";
		// Get a target path to save
		$target_path = sprintf('files/member_extra_info/profile_image/%s', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		// Get file information
		list($width, $height, $type, $attrs) = @getimagesize($target_file);
		if(IMAGETYPE_PNG == $type) $ext = 'png';
		elseif(IMAGETYPE_JPEG == $type) $ext = 'jpg';
		elseif(IMAGETYPE_GIF == $type) $ext = 'gif';
		else
		{
			return;
		}

		FileHandler::removeFilesInDir($target_path);

		$target_filename = sprintf('%s%d.%s', $target_path, $member_srl, $ext);
		// Convert if the image size is larger than a given size
		if($width > $max_width || $height > $max_height)
		{
			FileHandler::createImageFile($target_file, $target_filename, $max_width, $max_height, $ext);
		}
		else
		{
			@copy($target_file, $target_filename);
		}
	}

	/**
	 * Add an image name
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertImageName()
	{
		// Check if the file is successfully uploaded
		$file = $_FILES['image_name'];
		if(!is_uploaded_file($file['tmp_name'])) return $this->stop('msg_not_uploaded_image_name');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) return $this->stop('msg_not_uploaded_image_name');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) return $this->stop('msg_not_uploaded_image_name');
		// Return if member module is set not to use an image name or the user is not an administrator ;
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		if($logged_info->is_admin != 'Y' && $config->image_name != 'Y') return $this->stop('msg_not_uploaded_image_name');

		$this->insertImageName($member_srl, $file['tmp_name']);
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
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		// Get an image size
		$max_width = $config->image_name_max_width;
		if(!$max_width) $max_width = "90";
		$max_height = $config->image_name_max_height;
		if(!$max_height) $max_height = "20";
		// Get a target path to save
		$target_path = sprintf('files/member_extra_info/image_name/%s/', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		$target_filename = sprintf('%s%d.gif', $target_path, $member_srl);
		// Get file information
		list($width, $height, $type, $attrs) = @getimagesize($target_file);
		// Convert if the image size is larger than a given size or if the format is not a gif
		if($width > $max_width || $height > $max_height || $type!=1) FileHandler::createImageFile($target_file, $target_filename, $max_width, $max_height, 'gif');
		else @copy($target_file, $target_filename);
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
			return new Object(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$oMemberModel = getModel('member');
			$profile_image = $oMemberModel->getProfileImage($member_srl);
			FileHandler::removeFile($profile_image->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($profile_image->file)), true);
			$this->_clearMemberCache($member_srl);
		}
		return new Object(0,'success');
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
			return new Object(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$oMemberModel = getModel('member');
			$image_name = $oMemberModel->getImageName($member_srl);
			FileHandler::removeFile($image_name->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($image_name->file)), true);
		}
		return new Object(0,'success');
	}

	/**
	 * Add an image to mark
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberInsertImageMark()
	{
		// Check if the file is successfully uploaded
		$file = $_FILES['image_mark'];
		if(!is_uploaded_file($file['tmp_name'])) return $this->stop('msg_not_uploaded_image_mark');
		// Ignore if member_srl is invalid or doesn't exist.
		$member_srl = Context::get('member_srl');
		if(!$member_srl) return $this->stop('msg_not_uploaded_image_mark');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) return $this->stop('msg_not_uploaded_image_mark');
		// Membership in the images mark the module using the ban was set by an administrator or return;
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		if($logged_info->is_admin != 'Y' && $config->image_mark != 'Y') return $this->stop('msg_not_uploaded_image_mark');

		$this->insertImageMark($member_srl, $file['tmp_name']);
		// Page refresh
		//$this->setRefreshPage();

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
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');
		// Get an image size
		$max_width = $config->image_mark_max_width;
		if(!$max_width) $max_width = "20";
		$max_height = $config->image_mark_max_height;
		if(!$max_height) $max_height = "20";

		$target_path = sprintf('files/member_extra_info/image_mark/%s/', getNumberingPath($member_srl));
		FileHandler::makeDir($target_path);

		$target_filename = sprintf('%s%d.gif', $target_path, $member_srl);
		// Get file information
		list($width, $height, $type, $attrs) = @getimagesize($target_file);

		if($width > $max_width || $height > $max_height || $type!=1) FileHandler::createImageFile($target_file, $target_filename, $max_width, $max_height, 'gif');
		else @copy($target_file, $target_filename);
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
			return new Object(0,'success');
		}

		$logged_info = Context::get('logged_info');

		if($logged_info && ($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl))
		{
			$oMemberModel = getModel('member');
			$image_mark = $oMemberModel->getImageMark($member_srl);
			FileHandler::removeFile($image_mark->file);
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($image_mark->file)), true);
		}
		return new Object(0,'success');
	}

	/**
	 * Find ID/Password
	 *
	 * @return Object
	 */
	function procMemberFindAccount()
	{
		$email_address = Context::get('email_address');
		if(!$email_address) return new Object(-1, 'msg_invalid_request');

		$oMemberModel = getModel('member');
		$oModuleModel = getModel('module');

		// Check if a member having the same email address exists
		$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email_address);
		if(!$member_srl) return new Object(-1, 'msg_email_not_exists');

		// Get information of the member
		$columnList = array('denied', 'member_srl', 'user_id', 'user_name', 'email_address', 'nick_name');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);

		// Check if possible to find member's ID and password
		if($member_info->denied == 'Y')
		{
			$chk_args = new stdClass;
			$chk_args->member_srl = $member_info->member_srl;
			$output = executeQuery('member.chkAuthMail', $chk_args);
			if($output->toBool() && $output->data->count != '0') return new Object(-1, 'msg_user_not_confirmed');
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

		$member_config = $oModuleModel->getModuleConfig('member');
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
		$oModuleModel = getModel('module');
		$member_config = $oModuleModel->getModuleConfig('member');

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
		return new Object(0,$msg);
	}

	/**
	 * Generate a temp password by answering to the pre-determined question
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberFindAccountByQuestion()
	{
		return new Object(-1, 'msg_question_not_allowed');
	}

	/**
	 * Execute finding ID/Passoword
	 * When clicking the link in the verification email, a method is called to change the old password and to authenticate it
	 *
	 * @return void|Object (void : success, Object : fail)
	 */
	function procMemberAuthAccount()
	{
		$oMemberModel = getModel('member');

		// Test user_id and authkey
		$member_srl = Context::get('member_srl');
		$auth_key = Context::get('auth_key');

		if(!$member_srl || !$auth_key)
		{
			return $this->stop('msg_invalid_request');
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $member_srl;
		$trigger_obj->auth_key = $auth_key;
		$trigger_output = ModuleHandler::triggerCall('member.procMemberAuthAccount', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $this->stop($trigger_output->getMessage());
		}

		// Test logs for finding password by user_id and authkey
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->auth_key = $auth_key;
		$output = executeQuery('member.getAuthMail', $args);

		if(!$output->toBool() || $output->data->auth_key !== $auth_key)
		{
			executeQuery('member.deleteAuthMail', $args);
			return $this->stop('msg_invalid_auth_key');
		}

		if(ztime($output->data->regdate) < time() - (86400 * 3))
		{
			executeQuery('member.deleteAuthMail', $args);
			return $this->stop('msg_invalid_auth_key');
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
			$args->password = $oMemberModel->hashPassword($output->data->new_password);
		}

		$output = executeQuery('member.updateMemberPassword', $args);
		if(!$output->toBool())
		{
			return $this->stop($output->getMessage());
		}

		// Remove all values having the member_srl from authentication table
		executeQuery('member.deleteAuthMail',$args);

		$this->_clearMemberCache($args->member_srl);

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
		if(!$email_address) return new Object(-1, 'msg_invalid_request');
		// Log test by using email_address
		$oMemberModel = getModel('member');

		$args = new stdClass;
		$args->email_address = $email_address;
		$memberSrl = $oMemberModel->getMemberSrlByEmailAddress($email_address);
		if(!$memberSrl) return new Object(-1, 'msg_not_exists_member');

		$columnList = array('member_srl', 'user_id', 'user_name', 'nick_name', 'email_address');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($memberSrl, 0, $columnList);

		$oModuleModel = getModel('module');
		$member_config = $oModuleModel->getModuleConfig('member');
		if(!$member_config->skin) $member_config->skin = "default";
		if(!$member_config->colorset) $member_config->colorset = "white";

		// Check if a authentication mail has been sent previously
		$chk_args = new stdClass;
		$chk_args->member_srl = $member_info->member_srl;
		$output = executeQuery('member.chkAuthMail', $chk_args);
		if($output->toBool() && $output->data->count == '0') return new Object(-1, 'msg_invalid_request');

		$auth_args = new stdClass;
		$auth_args->member_srl = $member_info->member_srl;
		$output = executeQueryArray('member.getAuthMailInfo', $auth_args);
		if(!$output->data || !$output->data[0]->auth_key)  return new Object(-1, 'msg_invalid_request');
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

	function procMemberResetAuthMail()
	{
		$memberInfo = $_SESSION['auth_member_info'];
		unset($_SESSION['auth_member_info']);

		if(!$memberInfo)
		{
			return $this->stop('msg_invalid_request');
		}

		$newEmail = Context::get('email_address');

		if(!$newEmail)
		{
			return $this->stop('msg_invalid_request');
		}

		$oMemberModel = getModel('member');
		$member_srl = $oMemberModel->getMemberSrlByEmailAddress($newEmail);
		if($member_srl)
		{
			return new Object(-1,'msg_exists_email_address');
		}

		// remove all key by member_srl
		$args = new stdClass;
		$args->member_srl = $memberInfo->member_srl;
		$output = executeQuery('member.deleteAuthMail', $args);

		if(!$output->toBool())
		{
			return $output;
		}

		// update member info
		$args->email_address = $newEmail;
		list($args->email_id, $args->email_host) = explode('@', $newEmail);

		$output = executeQuery('member.updateMemberEmailAddress', $args);
		if(!$output->toBool())
		{
			return $this->stop($output->getMessage());
		}

		$this->_clearMemberCache($args->member_srl);
		
		// Call a trigger (after)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $args->member_srl;
		$trigger_obj->email_address = $args->email_address;
		$trigger_output = ModuleHandler::triggerCall('member.updateMemberEmailAddress', 'after', $trigger_obj);

		// generate new auth key
		$auth_args = new stdClass();
		$auth_args->user_id = $memberInfo->user_id;
		$auth_args->member_srl = $memberInfo->member_srl;
		$auth_args->new_password = $memberInfo->password;
		$auth_args->auth_key = Rhymix\Framework\Security::getRandom(40, 'hex');
		$auth_args->is_register = 'Y';

		$output = executeQuery('member.insertAuthMail', $auth_args);
		if(!$output->toBool()) return $output;

		$memberInfo->email_address = $newEmail;

		// resend auth mail.
		$this->_sendAuthMail($auth_args, $memberInfo);

		$msg = sprintf(lang('msg_confirm_mail_sent'), $memberInfo->email_address);
		$this->setMessage($msg);

		$returnUrl = getUrl('');
		$this->setRedirectUrl($returnUrl);
	}

	function _sendAuthMail($auth_args, $member_info)
	{
		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();
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
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) return new Object(-1,'msg_invalid_request');

		$oMemberModel = getModel('member');
		$columnList = array('site_srl', 'group_srl', 'title');
		$default_group = $oMemberModel->getDefaultGroup($site_module_info->site_srl, $columnList);
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
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) return new Object(-1,'msg_invalid_request');

		$args = new stdClass;
		$args->site_srl= $site_module_info->site_srl;
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('member.deleteMembersGroup', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_deleted');
		$this->_clearMemberCache($args->member_srl, $site_module_info->site_srl);
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

		return new Object();
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
		$config = getModel('member')->getMemberConfig();
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

		$this->_clearMemberCache($member_srl, $site_srl);

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

			$this->_clearMemberCache($obj->member_srl, $args->site_srl);
		}

		return new Object();
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
		if(!$user_id) return new Object(-1, 'null_user_id');
		// Call a trigger before log-in (before)
		$trigger_obj = new stdClass();
		$trigger_obj->user_id = $user_id;
		$trigger_obj->password = $password;
		$trigger_output = ModuleHandler::triggerCall('member.doLogin', 'before', $trigger_obj);
		if(!$trigger_output->toBool()) return $trigger_output;
		// Create a member model object
		$oMemberModel = getModel('member');

		// check IP access count.
		$config = $oMemberModel->getMemberConfig();
		$args = new stdClass();
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];

		// check identifier
		if($config->identifier == 'email_address' || strpos($user_id, '@') !== false)
		{
			// Get user_id information
			$member_info = $oMemberModel->getMemberInfoByEmailAddress($user_id);
			// Set an invalid user if no value returned
			if(!$user_id || strtolower($member_info->email_address) != strtolower($user_id)) return $this->recordLoginError(-1, 'invalid_email_address');

		}
		else
		{
			// Get user_id information
			$member_info = $oMemberModel->getMemberInfoByUserID($user_id);
			// Set an invalid user if no value returned
			if(!$user_id || strtolower($member_info->user_id) != strtolower($user_id)) return $this->recordLoginError(-1, 'invalid_user_id');
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

				return new Object(-1, sprintf(lang('excess_ip_access_count'),$term));
			}
			else
			{
				$args->ipaddress = $_SERVER['REMOTE_ADDR'];
				$output = executeQuery('member.deleteLoginCountByIp', $args);
			}
		}

		// Password Check
		if($password && !$oMemberModel->isValidPassword($member_info->password, $password, $member_info->member_srl))
		{
			return $this->recordMemberLoginError(-1, 'invalid_password', $member_info);
		}

		// If denied == 'Y', notify
		if($member_info->denied == 'Y')
		{
			$args->member_srl = $member_info->member_srl;
			$output = executeQuery('member.chkAuthMail', $args);
			if ($output->toBool() && $output->data->count != '0')
			{
				$_SESSION['auth_member_srl'] = $member_info->member_srl;
				$redirectUrl = getUrl('', 'act', 'dispMemberResendAuthMail');
				return $this->setRedirectUrl($redirectUrl, new Object(-1,'msg_user_not_confirmed'));
			}
			
			$refused_reason = $member_info->refused_reason ? ('<br>' . lang('refused_reason') . ': ' . $member_info->refused_reason) : '';
			return new Object(-1, lang('msg_user_denied') . $refused_reason);
		}
		
		// Notify if user is limited
		if($member_info->limit_date && substr($member_info->limit_date,0,8) >= date("Ymd"))
		{
			$limited_reason = $member_info->limited_reason ? ('<br>' . lang('refused_reason') . ': ' . $member_info->limited_reason) : '';
			return new Object(-9, sprintf(lang('msg_user_limited'), zdate($member_info->limit_date,"Y-m-d")) . $limited_reason);
		}
		
		// Do not allow login as admin if not in allowed IP list
		if($member_info->is_admin === 'Y' && $this->act === 'procMemberLogin')
		{
			$oMemberAdminModel = getAdminModel('member');
			if(!$oMemberAdminModel->getMemberAdminIPCheck())
			{
				return new Object(-1, 'msg_admin_ip_not_allowed');
			}
		}
		
		// Update the latest login time
		$args->member_srl = $member_info->member_srl;
		$output = executeQuery('member.updateLastLogin', $args);

		$site_module_info = Context::get('site_module_info');
		$this->_clearMemberCache($args->member_srl, $site_module_info->site_srl);

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
		$config = getModel('member')->getMemberConfig();
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
	function addMemberPopupMenu($url, $str, $icon = '', $target = 'self')
	{
		$member_popup_menu_list = Context::get('member_popup_menu_list');
		if(!is_array($member_popup_menu_list)) $member_popup_menu_list = array();

		$obj = new stdClass;
		$obj->url = $url;
		$obj->str = $str;
		$obj->icon = $icon;
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
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();

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
		$args->user_id = htmlspecialchars($args->user_id, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->user_name = htmlspecialchars($args->user_name, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->nick_name = htmlspecialchars($args->nick_name, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->homepage = htmlspecialchars($args->homepage, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->blog = htmlspecialchars($args->blog, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		if($args->homepage && !preg_match("/^[a-z]+:\/\//i",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
		if($args->blog && !preg_match("/^[a-z]+:\/\//i",$args->blog)) $args->blog = 'http://'.$args->blog;


		$extend_form_list = $oMemberModel->getJoinFormlist();
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
		
		// Create a model object
		$oMemberModel = getModel('member');
		
		// Check password strength
		if($args->password && !$password_is_hashed)
		{
			if(!$oMemberModel->checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new Object(-1, $message[$config->password_strength]);
			}
			$args->password = $oMemberModel->hashPassword($args->password);
		}
		
		// Check if ID is prohibited
		if($logged_info->is_admin !== 'Y' && $oMemberModel->isDeniedID($args->user_id))
		{
			return new Object(-1,'denied_user_id');
		}

		// Check if ID is duplicate
		$member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
		if($member_srl)
		{
			return new Object(-1,'msg_exists_user_id');
		}

		// Check if nickname is prohibited
		if($logged_info->is_admin !== 'Y' && $oMemberModel->isDeniedNickName($args->nick_name))
		{
			return new Object(-1,'denied_nick_name');
		}

		// Check if nickname is duplicate
		$member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
		if($member_srl)
		{
			return new Object(-1,'msg_exists_nick_name');
		}

		// Check managed Email Host
		if($logged_info->is_admin !== 'Y' && $oMemberModel->isDeniedEmailHost($args->email_address))
		{
			$config = $oMemberModel->getMemberConfig();
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = $oMemberModel->getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			return new Object(-1, $message);
		}

		// Check if email address is duplicate
		$member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
		if($member_srl)
		{
			return new Object(-1,'msg_exists_email_address');
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
			$default_group = $oMemberModel->getDefaultGroup(0, $columnList);
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
		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();

		$logged_info = Context::get('logged_info');
		
		// Get what you want to modify the original information
		$orgMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);
		
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
			if($is_admin == false)
				unset($args->denied);
			if($logged_info->member_srl != $args->member_srl && $is_admin == false)
			{
				return $this->stop('msg_invalid_request');
			}
		}

		// Sanitize user ID, username, nickname, homepage, blog
		if($args->user_id) $args->user_id = htmlspecialchars($args->user_id, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->user_name = htmlspecialchars($args->user_name, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->nick_name = htmlspecialchars($args->nick_name, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->homepage = htmlspecialchars($args->homepage, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$args->blog = htmlspecialchars($args->blog, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
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

		$extend_form_list = $oMemberModel->getJoinFormlist();
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

		// Check managed Email Host
		if($logged_info->is_admin !== 'Y' && $oMemberModel->isDeniedEmailHost($args->email_address))
		{
			$config = $oMemberModel->getMemberConfig();
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = $oMemberModel->getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			return new Object(-1, $message);
		}

		// Check if email address or user ID is duplicate
		if($config->identifier == 'email_address')
		{
			$member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new Object(-1,'msg_exists_email_address');
			}
			$args->email_address = $orgMemberInfo->email_address;
		}
		else
		{
			$member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new Object(-1,'msg_exists_user_id');
			}

			$args->user_id = $orgMemberInfo->user_id;
		}

		// Check if ID is prohibited
		if($logged_info->is_admin !== 'Y' && $args->user_id && $oMemberModel->isDeniedID($args->user_id))
		{
			return new Object(-1,'denied_user_id');
		}

		// Check if ID is duplicate
		if($args->user_id)
		{
			$member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
			if($member_srl && $args->member_srl != $member_srl)
			{
				return new Object(-1,'msg_exists_user_id');
			}
		}

		// Check if nickname is prohibited
		if($logged_info->is_admin !== 'Y' && $args->nick_name && $oMemberModel->isDeniedNickName($args->nick_name))
		{
			return new Object(-1, 'denied_nick_name');
		}

		// Check if nickname is duplicate
		$member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
 		if($member_srl && $args->member_srl != $member_srl)
 		{
 			return new Object(-1,'msg_exists_nick_name');
 		}

		list($args->email_id, $args->email_host) = explode('@', $args->email_address);

		$oDB = &DB::getInstance();
		$oDB->begin();

		// Check password strength
		if($args->password)
		{
			if(!$oMemberModel->checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new Object(-1, $message[$config->password_strength]);
			}
			$args->password = $oMemberModel->hashPassword($args->password);
		}
		else
		{
			$args->password = $orgMemberInfo->password;
		}

		if(!$args->user_name) $args->user_name = $orgMemberInfo->user_name;
		if(!$args->user_id) $args->user_id = $orgMemberInfo->user_id;
		if(!$args->nick_name) $args->nick_name = $orgMemberInfo->nick_name;
		if(!$args->description) $args->description = $orgMemberInfo->description;
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
		$this->_clearMemberCache($args->member_srl, $args->site_srl);

		$output->add('member_srl', $args->member_srl);
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
			$oMemberModel = getModel('member');
			$config = $oMemberModel->getMemberConfig();

			if(!$oMemberModel->checkPasswordStrength($args->password, $config->password_strength))
			{
				$message = lang('about_password_strength');
				return new Object(-1, $message[$config->password_strength]);
			}

			$args->password = $oMemberModel->hashPassword($args->password);
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

		$this->_clearMemberCache($args->member_srl);

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
		// Create a model object
		$oMemberModel = getModel('member');
		// Bringing the user's information
		$columnList = array('member_srl', 'is_admin');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		if(!$member_info) return new Object(-1, 'msg_not_exists_member');
		// If managers can not be deleted
		if($member_info->is_admin == 'Y') return new Object(-1, 'msg_cannot_delete_admin');

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
		executeQuery('member.deleteMemberModifyNickNameLog', $args);

		// TODO: If the table is not an upgrade may fail.
		/*
		   if(!$output->toBool()) {
		   $oDB->rollback();
		   return $output;
		   }
		 */
		// Delete the entries in member_group_member
		$output = executeQuery('member.deleteMemberGroupMember', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// member removed from the table
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
		$this->_clearMemberCache($member_srl);
		
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
		$oModuleModel = getModel('module');
		$pointModuleConfig = $oModuleModel->getModuleConfig('point');
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
			$oPointModel = getModel('point');
			$originPoint = $oPointModel->getPoint($memberSrl);

			if($pointModuleConfig->level_step[$maxLevel] > $originPoint)
			{
				$oPointController = getController('point');
				$oPointController->setPoint($memberSrl, $pointModuleConfig->level_step[$maxLevel], 'update');
			}
		}
	}

	function procMemberModifyEmailAddress()
	{
		if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

		$member_info = Context::get('logged_info');
		$newEmail = Context::get('email_address');

		if(!$newEmail) return $this->stop('msg_invalid_request');

		$oMemberModel = getModel('member');
		// Check managed Email Host
		if($oMemberModel->isDeniedEmailHost($newEmail))
		{
			$config = $oMemberModel->getMemberConfig();
			$emailhost_check = $config->emailhost_check;

			$managed_email_host = lang('managed_email_host');
			$email_hosts = $oMemberModel->getManagedEmailHosts();
			foreach ($email_hosts as $host)
			{
				$hosts[] = $host->email_host;
			}
			$message = sprintf($managed_email_host[$emailhost_check],implode(', ',$hosts),'id@'.implode(', id@',$hosts));
			return new Object(-1, $message);
		}

		// Check if the e-mail address is already registered
		$member_srl = $oMemberModel->getMemberSrlByEmailAddress($newEmail);
		if($member_srl) return new Object(-1,'msg_exists_email_address');

		if($_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			return $this->stop('msg_invalid_request');
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

		$oModuleModel = getModel('module');
		$member_config = $oModuleModel->getModuleConfig('member');

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
		if(!$member_srl || !$auth_key) return $this->stop('msg_invalid_request');

		// Test logs for finding password by user_id and authkey
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->auth_key = $auth_key;
		$output = executeQuery('member.getAuthMail', $args);
		if(!$output->toBool() || $output->data->auth_key != $auth_key)
		{
			if(strlen($output->data->auth_key) !== strlen($auth_key)) executeQuery('member.deleteAuthChangeEmailAddress', $args);
			return $this->stop('msg_invalid_modify_email_auth_key');
		}

		$newEmail = $output->data->user_id;
		$args->email_address = $newEmail;
		list($args->email_id, $args->email_host) = explode('@', $newEmail);

		$output = executeQuery('member.updateMemberEmailAddress', $args);
		if(!$output->toBool()) return $this->stop($output->getMessage());

		// Remove all values having the member_srl and new_password equal to 'XE_change_emaill_address' from authentication table
		executeQuery('member.deleteAuthChangeEmailAddress',$args);

		$this->_clearMemberCache($args->member_srl);

		// Call a trigger (after)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $args->member_srl;
		$trigger_obj->email_address = $args->email_address;
		$trigger_output = ModuleHandler::triggerCall('member.updateMemberEmailAddress', 'after', $trigger_obj);
		
		// Redirect to member info page
		$this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberInfo'));
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
		if(!Context::get('is_logged')) return new Object();

		$logged_info = Context::get('logged_info');
		$document_srl = Context::get('target_srl');

		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'member_srl', 'ipaddress');
		$oDocument = $oDocumentModel->getDocument($document_srl, false, false, $columnList);
		$member_srl = $oDocument->get('member_srl');
		$module_srl = $oDocument->get('module_srl');

		if(!$member_srl) return new Object();
		if($oDocumentModel->grant->manager != 1 || $member_srl==$logged_info->member_srl) return new Object();

		$oDocumentController = getController('document');
		$url = getUrl('','module','member','act','dispMemberSpammer','member_srl',$member_srl,'module_srl',$module_srl);
		$oDocumentController->addDocumentPopupMenu($url,'cmd_spammer','','popup');

		return new Object();
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
		if(!Context::get('is_logged')) return new Object();

		$logged_info = Context::get('logged_info');
		$comment_srl = Context::get('target_srl');

		$oCommentModel = getModel('comment');
		$columnList = array('comment_srl', 'module_srl', 'member_srl', 'ipaddress');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, $columnList);
		$module_srl = $oComment->get('module_srl');
		$member_srl = $oComment->get('member_srl');

		if(!$member_srl) return new Object();
		if($oCommentModel->grant->manager != 1 || $member_srl==$logged_info->member_srl) return new Object();

		$oCommentController = getController('comment');
		$url = getUrl('','module','member','act','dispMemberSpammer','member_srl',$member_srl,'module_srl',$module_srl);
		$oCommentController->addCommentPopupMenu($url,'cmd_spammer','','popup');

		return new Object();
	}

	/**
	 * Spammer manage. Denied user login. And delete or trash all documents. Response Ajax string
	 *
	 * @return object
	**/
	function procMemberSpammerManage()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');

		$logged_info = Context::get('logged_info');
		$member_srl = Context::get('member_srl');
		$module_srl = Context::get('module_srl');
		$cnt_loop = Context::get('cnt_loop');
		$proc_type = Context::get('proc_type');
		$isMoveToTrash = true;
		if($proc_type == "delete")
			$isMoveToTrash = false;

		// check grant
		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		$grant = $oModuleModel->getGrant($module_info, $logged_info);

		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$proc_msg = "";

		$oDocumentModel = getModel('document');
		$oCommentModel = getModel('comment');

		// delete or trash destination
		// proc member
		if($cnt_loop == 1)
			$this->_spammerMember($member_srl);
		// proc document and comment
		elseif($cnt_loop>1)
			$this->_spammerDocuments($member_srl, $isMoveToTrash);

		// get destination count
		$cnt_document = $oDocumentModel->getDocumentCountByMemberSrl($member_srl);
		$cnt_comment = $oCommentModel->getCommentCountByMemberSrl($member_srl);

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

		return new Object(0);
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

		$oMemberModel = getModel('member');
		$columnList = array('member_srl', 'email_address', 'user_id', 'nick_name', 'description');
		// get member current infomation
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);

		$oDocumentModel = getModel('document');
		$oCommentModel = getModel('comment');
		$cnt_comment = $oCommentModel->getCommentCountByMemberSrl($member_srl);
		$cnt_document = $oDocumentModel->getDocumentCountByMemberSrl($member_srl);
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

		$this->_clearMemberCache($args->member_srl);

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
	private function _spammerDocuments($member_srl, $isMoveToTrash) {
		$oDocumentController = getController('document');
		$oDocumentModel = getModel('document');
		$oCommentController = getController('comment');
		$oCommentModel = getModel('comment');

		// delete count by one request
		$getContentsCount = 10;

		// 1. proc comment, 2. proc document
		$cnt_comment = $oCommentModel->getCommentCountByMemberSrl($member_srl);
		$cnt_document = $oDocumentModel->getDocumentCountByMemberSrl($member_srl);
		if($cnt_comment > 0)
		{
			$columnList = array();
			$commentList = $oCommentModel->getCommentListByMemberSrl($member_srl, $columnList, 0, false, $getContentsCount);
			if($commentList) {
				foreach($commentList as $v) {
					$oCommentController->deleteComment($v->comment_srl, true, $isMoveToTrash);
				}
			}
		} elseif($cnt_document > 0) {
			$columnList = array();
			$documentList = $oDocumentModel->getDocumentListByMemberSrl($member_srl, $columnList, 0, false, $getContentsCount);
			if($documentList) {
				foreach($documentList as $v) {
					if($isMoveToTrash) $oDocumentController->moveDocumentToTrash($v);
					else $oDocumentController->deleteDocument($v->document_srl);
				}
			}
		}

		return array();
	}

	function _clearMemberCache($member_srl, $site_srl = 0)
	{
		$member_srl = intval($member_srl);
		Rhymix\Framework\Cache::delete("member:member_info:$member_srl");
		Rhymix\Framework\Cache::delete("member:member_groups:$member_srl:site:$site_srl");
		if ($site_srl != 0)
		{
			Rhymix\Framework\Cache::delete("member:member_groups:$member_srl:site:0");
		}
	}
}
/* End of file member.controller.php */
/* Location: ./modules/member/member.controller.php */
