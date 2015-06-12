<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberView
 * @author NAVER (developers@xpressengine.com)
 * @brief View class of member module
 */
class memberView extends member
{
	var $group_list = NULL; // /< Group list information
	var $member_info = NULL; // /< Member information of the user
	var $skin = 'default';

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Get the member configuration
		$oMemberModel = getModel('member');
		$this->member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $this->member_config);
		$oSecurity = new Security();
		$oSecurity->encodeHTML('member_config.signupForm..');

		$skin = $this->member_config->skin;
		// Set the template path
		if(!$skin)
		{
			$skin = 'default';
			$template_path = sprintf('%sskins/%s', $this->module_path, $skin);
		}
		else
		{
			//check theme
			$config_parse = explode('|@|', $skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/member/', $config_parse[0]);
			}
			else
			{
				$template_path = sprintf('%sskins/%s', $this->module_path, $skin);
			}
		}
		// Template path
		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->member_config->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $this->member_config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	/**
	 * @brief Display member information
	 */
	function dispMemberInfo()
	{
		$oMemberModel = getModel('member');
		$logged_info = Context::get('logged_info');
		// Don't display member info to non-logged user
		if(!$logged_info->member_srl) return $this->stop('msg_not_permitted');

		$member_srl = Context::get('member_srl');
		if(!$member_srl && Context::get('is_logged'))
		{
			$member_srl = $logged_info->member_srl;
		}
		elseif(!$member_srl)
		{
			return $this->dispMemberSignUpForm();
		}

		$site_module_info = Context::get('site_module_info');
		$columnList = array('member_srl', 'user_id', 'email_address', 'user_name', 'nick_name', 'homepage', 'blog', 'birthday', 'regdate', 'last_login', 'extra_vars');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, $site_module_info->site_srl, $columnList);
		unset($member_info->password);
		unset($member_info->email_id);
		unset($member_info->email_host);

		if($logged_info->is_admin != 'Y' && ($member_info->member_srl != $logged_info->member_srl))
		{
			$start = strpos($member_info->email_address, '@')+1;
			$replaceStr = str_repeat('*', (strlen($member_info->email_address) - $start));
			$member_info->email_address = substr_replace($member_info->email_address, $replaceStr, $start);
		}

		if(!$member_info->member_srl) return $this->dispMemberSignUpForm();

		Context::set('memberInfo', get_object_vars($member_info));

		$extendForm = $oMemberModel->getCombineJoinForm($member_info);
		unset($extendForm->find_member_account);
		unset($extendForm->find_member_answer);
		Context::set('extend_form_list', $extendForm);

		$this->_getDisplayedMemberInfo($member_info, $extendForm, $this->member_config);

		$this->setTemplateFile('member_info');
	}

	function _getDisplayedMemberInfo($memberInfo, $extendFormInfo, $memberConfig)
	{
		$logged_info = Context::get('logged_info');
		$displayDatas = array();
		foreach($memberConfig->signupForm as $no=>$formInfo)
		{
			if(!$formInfo->isUse)
			{
				continue;
			}

			if($formInfo->name == 'password' || $formInfo->name == 'find_account_question')
			{
				continue;
			}

			if($logged_info->is_admin != 'Y' && $memberInfo->member_srl != $logged_info->member_srl && $formInfo->isPublic != 'Y')
			{
				continue;
			}

			$item = $formInfo;

			if($formInfo->isDefaultForm)
			{
				$item->title = Context::getLang($formInfo->name);
				$item->value = $memberInfo->{$formInfo->name};

				if($formInfo->name == 'profile_image' && $memberInfo->profile_image)
				{
					$target = $memberInfo->profile_image;
					$item->value = '<img src="'.$target->src.'" />';
				}
				elseif($formInfo->name == 'image_name' && $memberInfo->image_name)
				{
					$target = $memberInfo->image_name;
					$item->value = '<img src="'.$target->src.'" />';
				}
				elseif($formInfo->name == 'image_mark' && $memberInfo->image_mark)
				{
					$target = $memberInfo->image_mark;
					$item->value = '<img src="'.$target->src.'" />';
				}
				elseif($formInfo->name == 'birthday' && $memberInfo->birthday)
				{
					$item->value = zdate($item->value, 'Y-m-d');
				}
			}
			else
			{
				$item->title = $extendFormInfo[$formInfo->member_join_form_srl]->column_title;
				$orgValue = $extendFormInfo[$formInfo->member_join_form_srl]->value;
				if($formInfo->type=='tel' && is_array($orgValue))
				{
					$item->value = implode('-', $orgValue);
				}
				elseif($formInfo->type=='kr_zip' && is_array($orgValue))
				{
					$item->value = implode(' ', $orgValue);
				}
				elseif($formInfo->type=='checkbox' && is_array($orgValue))
				{
					$item->value = implode(", ",$orgValue);
				}
				elseif($formInfo->type=='date')
				{
					$item->value = zdate($orgValue, "Y-m-d");
				}
				else
				{
					$item->value = nl2br($orgValue);
				}
			}

			$displayDatas[] = $item;
		}

		Context::set('displayDatas', $displayDatas);
		$oSecurity = new Security();
		$oSecurity->encodeHTML('displayDatas..title', 'displayDatas..description');
		return $displayDatas;
	}

	/**
	 * @brief Display member join form
	 */
	function dispMemberSignUpForm()
	{
		//setcookie for redirect url in case of going to member sign up
		setcookie("XE_REDIRECT_URL", $_SERVER['HTTP_REFERER']);

		$member_config = $this->member_config;

		$oMemberModel = getModel('member');
		// Get the member information if logged-in
		if($oMemberModel->isLogged()) return $this->stop('msg_already_logged');
		// call a trigger (before) 
		$trigger_output = ModuleHandler::triggerCall('member.dispMemberSignUpForm', 'before', $member_config);
		if(!$trigger_output->toBool()) return $trigger_output;
		// Error appears if the member is not allowed to join
		if($member_config->enable_join != 'Y') return $this->stop('msg_signup_disabled');

		$oMemberAdminView = getAdminView('member');
		$formTags = $oMemberAdminView->_getMemberInputTag($member_info);
		Context::set('formTags', $formTags);

		global $lang;
		$identifierForm = new stdClass();
		$identifierForm->title = $lang->{$member_config->identifier};
		$identifierForm->name = $member_config->identifier;
		$identifierForm->value = $member_info->{$member_config->identifier};
		Context::set('identifierForm', $identifierForm);

		$this->addExtraFormValidatorMessage();

		// Set a template file
		$this->setTemplateFile('signup_form');
	}

	function dispMemberModifyInfoBefore()
	{
		$logged_info = Context::get('logged_info');
		$oMemberModel = getModel('member');
		if(!$oMemberModel->isLogged() || empty($logged_info))
		{
			return $this->stop('msg_not_logged');
		}

		$_SESSION['rechecked_password_step'] = 'INPUT_PASSWORD';

		$templateFile = $this->getTemplatePath().'rechecked_password.html';
		if(!is_readable($templateFile))
		{
			$templatePath = sprintf('%sskins/default', $this->module_path);
			$this->setTemplatePath($templatePath);
		}

		if ($this->member_config->identifier == 'email_address')
		{
			Context::set('identifierTitle', Context::getLang('email_address'));
			Context::set('identifierValue', $logged_info->email_address); 
		}
		else
		{
			Context::set('identifierTitle', Context::getLang('user_id'));
			Context::set('identifierValue', $logged_info->user_id);
		}

		$this->setTemplateFile('rechecked_password');
	}

	/**
	 * @brief Modify member information
	 */
	function dispMemberModifyInfo() 
	{
		if($_SESSION['rechecked_password_step'] != 'VALIDATE_PASSWORD' && $_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			$this->dispMemberModifyInfoBefore();
			return;
		}

		$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

		$member_config = $this->member_config;

		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$columnList = array('member_srl', 'user_id', 'user_name', 'nick_name', 'email_address', 'find_account_answer', 'homepage', 'blog', 'birthday', 'allow_mailing');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		$member_info->signature = $oMemberModel->getSignature($member_srl);
		Context::set('member_info',$member_info);

		// Get a list of extend join form
		Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

		// Editor of the module set for signing by calling getEditor
		if($member_info->member_srl)
		{
			$oEditorModel = getModel('editor');
			$option = new stdClass();
			$option->primary_key_name = 'member_srl';
			$option->content_key_name = 'signature';
			$option->allow_fileupload = false;
			$option->enable_autosave = false;
			$option->enable_default_component = true;
			$option->enable_component = false;
			$option->resizable = false;
			$option->disable_html = true;
			$option->height = 200;
			$option->skin = $member_config->signature_editor_skin;
			$option->colorset = $member_config->sel_editor_colorset;
			$editor = $oEditorModel->getEditor($member_info->member_srl, $option);
			Context::set('editor', $editor);
		}

		$this->member_info = $member_info;

		$oMemberAdminView = getAdminView('member');
		$formTags = $oMemberAdminView->_getMemberInputTag($member_info);
		Context::set('formTags', $formTags);

		global $lang;
		$identifierForm = new stdClass();
		$identifierForm->title = $lang->{$member_config->identifier};
		$identifierForm->name = $member_config->identifier;
		$identifierForm->value = $member_info->{$member_config->identifier};
		Context::set('identifierForm', $identifierForm);

		$this->addExtraFormValidatorMessage();

		// Set a template file
		$this->setTemplateFile('modify_info');
	}

	/**
	 * @brief Display documents written by the member
	 */
	function dispMemberOwnDocument()
	{
		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$module_srl = Context::get('module_srl');
		Context::set('module_srl',Context::get('selected_module_srl'));
		Context::set('search_target','member_srl');
		Context::set('search_keyword',$member_srl);

		$oDocumentAdminView = getAdminView('document');
		$oDocumentAdminView->dispDocumentAdminList();

		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_list...title', 'search_target', 'search_keyword');

		Context::set('module_srl', $module_srl);
		$this->setTemplateFile('document_list');
	}

	/**
	 * @brief Display documents scrapped by the member
	 */
	function dispMemberScrappedDocument()
	{
		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->page = (int)Context::get('page');

		$output = executeQuery('member.getScrapDocumentList', $args);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('scrapped_list');
	}

	/**
	 * @brief Display documents saved by the member
	 */
	function dispMemberSavedDocument()
	{
		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');
		// Get the saved document(module_srl is set to member_srl instead)
		$logged_info = Context::get('logged_info');
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->page = (int)Context::get('page');
		$args->statusList = array('TEMP');

		$oDocumentModel = getModel('document');
		$output = $oDocumentModel->getDocumentList($args, true);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('saved_list');
	}

	/**
	 * @brief Display the login form 
	 */
	function dispMemberLoginForm()
	{
		if(Context::get('is_logged'))
		{
			Context::set('redirect_url', getNotEncodedUrl('act',''));
			$this->setTemplatePath($this->module_path.'tpl');
			$this->setTemplateFile('redirect.html');
			return;
		}

		// get member module configuration.
		$oMemberModel = getModel('member');
		$config = $this->member_config;
		Context::set('identifier', $config->identifier);

		$XE_VALIDATOR_MESSAGE = Context::get('XE_VALIDATOR_MESSAGE');
		$XE_VALIDATOR_ERROR = Context::get('XE_VALIDATOR_ERROR');
		if($XE_VALIDATOR_ERROR == -11)
			Context::set('XE_VALIDATOR_MESSAGE', $XE_VALIDATOR_MESSAGE . $config->limit_day_description);

		if($XE_VALIDATOR_ERROR < -10 && $XE_VALIDATOR_ERROR > -21)
			Context::set('referer_url', getUrl('')); 
		else
			Context::set('referer_url', htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false));

		// Set a template file
		$this->setTemplateFile('login_form');
	}

	/**
	 * @brief Change the user password
	 */
	function dispMemberModifyPassword()
	{
		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$memberConfig = $this->member_config;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$columnList = array('member_srl', 'user_id');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		Context::set('member_info',$member_info);

		if($memberConfig->identifier == 'user_id')
		{
			Context::set('identifier', 'user_id');
			Context::set('formValue', $member_info->user_id);
		}
		else
		{
			Context::set('identifier', 'email_address');
			Context::set('formValue', $member_info->email_address);
		}
		// Set a template file
		$this->setTemplateFile('modify_password');
	}

	/**
	 * @brief Member withdrawl
	 */
	function dispMemberLeave()
	{
		$oMemberModel = getModel('member');
		// A message appears if the user is not logged-in
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$memberConfig = $this->member_config;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		Context::set('member_info',$member_info);

		if($memberConfig->identifier == 'user_id')
		{
			Context::set('identifier', 'user_id');
			Context::set('formValue', $member_info->user_id);
		}
		else
		{
			Context::set('identifier', 'email_address');
			Context::set('formValue', $member_info->email_address);
		}
		// Set a template file
		$this->setTemplateFile('leave_form');
	}

	/**
	 * @brief Member log-out
	 */
	function dispMemberLogout()
	{
		$oMemberController = getController('member');
		$output = $oMemberController->procMemberLogout();
		if(!$output->redirect_url)
			$this->setRedirectUrl(getNotEncodedUrl('act', ''));
		else
			$this->setRedirectUrl($output->redirect_url);

		return;
	}

	/**
	 * @brief Display a list of saved articles
	 * @Deplicated - instead Document View - dispTempSavedList method use
	 */
	function dispSavedDocumentList()
	{
		return new Object(0, 'Deplicated method');
	}

	/**
	 * @brief Find user ID and password
	 */
	function dispMemberFindAccount()
	{
		if(Context::get('is_logged')) return $this->stop('already_logged');

		$config = $this->member_config;

		Context::set('identifier', $config->identifier);

		$this->setTemplateFile('find_member_account');
	}

	/**
	 * @brief Generate a temporary password
	 */
	function dispMemberGetTempPassword()
	{
		if(Context::get('is_logged')) return $this->stop('already_logged');

		$user_id = Context::get('user_id');
		$temp_password = $_SESSION['xe_temp_password_'.$user_id];
		unset($_SESSION['xe_temp_password_'.$user_id]);

		if(!$user_id||!$temp_password) return new Object(-1,'msg_invaild_request');

		Context::set('temp_password', $temp_password);

		$this->setTemplateFile('find_temp_password');
	}

	/**
	 * @brief Page of re-sending an authentication mail
	 */
	function dispMemberResendAuthMail() 
	{
		$authMemberSrl = $_SESSION['auth_member_srl'];
		unset($_SESSION['auth_member_srl']);

		if(Context::get('is_logged')) 
		{
			return $this->stop('already_logged');
		}

		if($authMemberSrl)
		{
			$oMemberModel = getModel('member');
			$memberInfo = $oMemberModel->getMemberInfoByMemberSrl($authMemberSrl);

			$_SESSION['auth_member_info'] = $memberInfo;
			Context::set('memberInfo', $memberInfo);
			$this->setTemplateFile('reset_mail');
		}
		else
		{
			$this->setTemplateFile('resend_auth_mail');
		}
	}

	function dispMemberModifyEmailAddress()
	{
		if($_SESSION['rechecked_password_step'] != 'VALIDATE_PASSWORD' && $_SESSION['rechecked_password_step'] != 'INPUT_DATA')
		{
			Context::set('success_return_url', getUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyEmailAddress'));
			$this->dispMemberModifyInfoBefore();
			return;
		}

		$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

		$this->setTemplateFile('modify_email_address');
	}

	/**
	 * Add javascript codes into the header by checking values of member join form, required and others
	 * @return void
	 */
	function addExtraFormValidatorMessage()
	{
		$oMemberModel = getModel('member');
		$extraList = $oMemberModel->getUsedJoinFormList();

		$js_code = array();
		$js_code[] = '<script>//<![CDATA[';
		$js_code[] = '(function($){';
		$js_code[] = 'var validator = xe.getApp("validator")[0];';
		$js_code[] = 'if(!validator) return false;';

		$errorLang = array();
		foreach($extraList as $val) 
		{
			$title = str_ireplace(array('<script', '</script'), array('<scr"+"ipt', '</scr"+"ipt'), addslashes($val->column_title));
			if($val->column_type == 'kr_zip' || $val->column_type == 'tel')
			{
				$js_code[] = sprintf('validator.cast("ADD_MESSAGE", ["%s[]","%s"]);', $val->column_name, $title);
			}
			else
			{
				$js_code[] = sprintf('validator.cast("ADD_MESSAGE", ["%s","%s"]);', $val->column_name, $title);
			}
			$errorLang[$val->column_name] = $val->column_title;
		}
		$_SESSION['XE_VALIDATOR_ERROR_LANG'] = $errorLang;

		$js_code[] = '})(jQuery);';
		$js_code[] = '//]]></script>';
		$js_code   = implode("\n", $js_code);

		Context::addHtmlHeader($js_code);
	}

	/**
	 * Spammer manage popup
	 * 
	 * @return void
	**/
	function dispMemberSpammer()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');

		$member_srl = Context::get('member_srl');
		$module_srl = Context::get('module_srl');

		// check grant
		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));

		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$oMemberModel = getModel('member');

		Context::loadLang('modules/document/lang/');
		Context::set('spammer_info', $oMemberModel->getMemberInfoByMemberSrl($member_srl));
		Context::set('module_srl', $module_srl);

		// Select Pop-up layout
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('spammer');
	}
	
}
/* End of file member.view.php */
/* Location: ./modules/member/member.view.php */
