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
		$this->member_config = MemberModel::getMemberConfig();
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

		$layout_info = LayoutModel::getInstance()->getLayout($this->member_config->layout_srl);
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
		$logged_info = Context::get('logged_info');
		// Don't display member info to non-logged user
		if(!$logged_info->member_srl) throw new Rhymix\Framework\Exceptions\MustLogin;

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
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, $site_module_info->site_srl, $columnList);
		unset($member_info->password);
		unset($member_info->email_id);
		unset($member_info->email_host);

		if($logged_info->is_admin != 'Y' && ($member_info->member_srl != $logged_info->member_srl))
		{
			list($email_id, $email_host) = explode('@', $member_info->email_address);
			$protect_id = substr($email_id, 0, 2) . str_repeat('*', strlen($email_id)-2);
			$member_info->email_address = sprintf('%s@%s', $protect_id, $email_host);
		}

		if(!$member_info->member_srl) return $this->dispMemberSignUpForm();

		Context::set('memberInfo', get_object_vars($member_info));

		$extendForm = MemberModel::getCombineJoinForm($member_info);
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
				$item->title = lang($formInfo->name);
				$item->value = $memberInfo->{$formInfo->name};

				if($formInfo->name == 'profile_image' && $memberInfo->profile_image)
				{
					$target = $memberInfo->profile_image;
					$item->value = '<img src="'.$target->src.'" alt="' . lang('member.profile_image') . '" />';
				}
				elseif($formInfo->name == 'image_name' && $memberInfo->image_name)
				{
					$target = $memberInfo->image_name;
					$item->value = '<img src="'.$target->src.'" alt="' . lang('member.image_name') . ' ('.escape($memberInfo->nick_name, false).')' . '" />';
				}
				elseif($formInfo->name == 'image_mark' && $memberInfo->image_mark)
				{
					$target = $memberInfo->image_mark;
					$item->value = '<img src="'.$target->src.'" alt="' . lang('member.image_mark') . '" />';
				}
				elseif($formInfo->name == 'birthday' && $memberInfo->birthday)
				{
					$item->value = zdate($item->value, 'Y-m-d');
				}
				elseif($formInfo->name == 'phone_number' && $memberInfo->phone_number)
				{
					if($memberConfig->phone_number_hide_country !== 'Y')
					{
						$item->value = Rhymix\Framework\i18n::formatPhoneNumber($item->value, $memberInfo->phone_country);
					}
					elseif($memberConfig->phone_number_default_country === 'KOR' && ($memberInfo->phone_country === 'KOR' || $memberInfo->phone_country == '82'))
					{
						$item->value = Rhymix\Framework\Korea::formatPhoneNumber($item->value);
					}
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
		setcookie("XE_REDIRECT_URL", $_SERVER['HTTP_REFERER'], 0, '/', null, !!config('session.use_ssl_cookies'));

		$member_config = $this->member_config;

		// Get the member information if logged-in
		if($this->user->member_srl) throw new Rhymix\Framework\Exception('msg_already_logged');
		// call a trigger (before) 
		$trigger_output = ModuleHandler::triggerCall('member.dispMemberSignUpForm', 'before', $member_config);
		if(!$trigger_output->toBool()) return $trigger_output;
		// Error appears if the member is not allowed to join
		if($member_config->enable_join != 'Y') throw new Rhymix\Framework\Exceptions\FeatureDisabled('msg_signup_disabled');
		
		$formTags = getAdminView('member')->_getMemberInputTag();
		Context::set('formTags', $formTags);
		Context::set('email_confirmation_required', $member_config->enable_confirm);
		
		// Editor of the module set for signing by calling getEditor
		foreach($formTags as $formTag)
		{
			if($formTag->name == 'signature')
			{
				$option = new stdClass;
				$option->primary_key_name = 'member_srl';
				$option->content_key_name = 'signature';
				$option->allow_html = $member_config->signature_html !== 'N';
				$option->allow_fileupload = false;
				$option->enable_autosave = false;
				$option->enable_default_component = true;
				$option->enable_component = false;
				$option->resizable = false;
				$option->disable_html = true;
				$option->height = 200;
				$option->editor_toolbar = 'simple';
				$option->editor_toolbar_hide = 'Y';
				$option->editor_skin = $member_config->signature_editor_skin;
				$option->sel_editor_colorset = $member_config->sel_editor_colorset;
				
				Context::set('editor', getModel('editor')->getEditor(0, $option));
			}
		}
		
		$identifierForm = new stdClass;
		$identifierForm->title = lang($member_config->identifier);
		$identifierForm->name = $member_config->identifier;
		Context::set('identifierForm', $identifierForm);
		
		$this->addExtraFormValidatorMessage();
		
		// Set a template file
		$this->setTemplateFile('signup_form');
	}

	function dispMemberModifyInfoBefore()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
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
			Context::set('identifierTitle', lang('email_address'));
			Context::set('identifierValue', $logged_info->email_address); 
		}
		else
		{
			Context::set('identifierTitle', lang('user_id'));
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

		// A message appears if the user is not logged-in
		if(!$this->user->member_srl) throw new Rhymix\Framework\Exceptions\MustLogin;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$columnList = array('member_srl', 'user_id', 'user_name', 'nick_name', 'email_address', 'find_account_answer', 'homepage', 'blog', 'birthday', 'allow_mailing');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
		$member_info->signature = MemberModel::getSignature($member_srl);
		Context::set('member_info', $member_info);
		
		$formTags = getAdminView('member')->_getMemberInputTag($member_info);
		Context::set('formTags', $formTags);
		
		// Editor of the module set for signing by calling getEditor
		foreach($formTags as $formTag)
		{
			if($formTag->name == 'signature')
			{
				$option = new stdClass;
				$option->primary_key_name = 'member_srl';
				$option->content_key_name = 'signature';
				$option->allow_html = $member_config->signature_html !== 'N';
				$option->allow_fileupload = $member_config->member_allow_fileupload === 'Y';
				$option->enable_autosave = false;
				$option->enable_default_component = true;
				$option->enable_component = false;
				$option->resizable = false;
				$option->disable_html = true;
				$option->height = 200;
				$option->editor_toolbar = 'simple';
				$option->editor_toolbar_hide = 'Y';
				$option->editor_skin = $member_config->signature_editor_skin;
				$option->sel_editor_colorset = $member_config->sel_editor_colorset;
				
				Context::set('editor', getModel('editor')->getEditor($member_info->member_srl, $option));
			}
		}
		
		$identifierForm = new stdClass;
		$identifierForm->title = lang($member_config->identifier);
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
		if ($this->member_config->features['my_documents'] === false)
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		// A message appears if the user is not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$args = new stdClass;
		$args->list_count = 20;
		$args->page_count = 5;
		$args->page = intval(Context::get('page')) ?: 1;
		if(in_array(Context::get('search_target'), array('title', 'title_content', 'content')))
		{
			$args->search_target = Context::get('search_target');
			$args->search_keyword = escape(trim(utf8_normalize_spaces(Context::get('search_keyword'))));
		}
		$args->member_srl = array($this->user->member_srl, $this->user->member_srl * -1);
		$args->module_srl = intval(Context::get('selected_module_srl')) ?: null;
		$args->sort_index = 'list_order';
		$args->statusList = array('PUBLIC', 'SECRET');
		
		$columnList = array('document_srl', 'module_srl', 'category_srl', 'member_srl', 'title', 'nick_name', 'comment_count', 'trackback_count', 'readed_count', 'voted_count', 'blamed_count', 'regdate', 'ipaddress', 'status');
		$output = DocumentModel::getDocumentList($args, false, false, $columnList);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('document_list', $output->data);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_list...title', 'search_target', 'search_keyword');

		$this->setTemplateFile('document_list');
	}

	/**
	 * @brief Display comments written by the member
	 */
	function dispMemberOwnComment()
	{
		if ($this->member_config->features['my_comments'] === false)
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		// A message appears if the user is not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$args = new stdClass;
		$args->list_count = 20;
		$args->page_count = 5;
		$args->page = intval(Context::get('page')) ?: 1;
		if(Context::get('search_keyword'))
		{
			$args->search_target = 'content';
			$args->search_keyword = escape(trim(utf8_normalize_spaces(Context::get('search_keyword'))));
		}
		$args->member_srl = array($this->user->member_srl, $this->user->member_srl * -1);
		$args->module_srl = intval(Context::get('selected_module_srl')) ?: null;
		$args->sort_index = 'list_order';
		
		$output = CommentModel::getTotalCommentList($args);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('comment_list', $output->data);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('search_target', 'search_keyword');

		$this->setTemplateFile('comment_list');
	}

	/**
	 * @brief Display documents scrapped by the member
	 */
	function dispMemberScrappedDocument()
	{
		if ($this->member_config->features['scrapped_documents'] === false)
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		// A message appears if the user is not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');
		
		// Check folders
		$args = new stdClass;
		$args->member_srl = $logged_info->member_srl;
		$output = executeQueryArray('member.getScrapFolderList', $args);
		$folders = $output->data;
		if(!count($folders))
		{
			$output = MemberController::getInstance()->migrateMemberScrappedDocuments($logged_info->member_srl);
			if($output && !$output->toBool())
			{
				return $output;
			}
			
			$output = executeQueryArray('member.getScrapFolderList', $args);
			$folders = $output->data;
		}
		
		// Get default folder if no folder is selected
		$folder_srl = (int)Context::get('folder_srl');
		if($folder_srl && !array_filter($folders, function($folder) use($folder_srl) { return $folder->folder_srl == $folder_srl; }))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if(!$folder_srl && count($folders))
		{
			$folder_srl = array_first($folders)->folder_srl;
		}
		
		// Get folder info
		$folder_info = new stdClass;
		foreach($folders as $folder)
		{
			if($folder->folder_srl == $folder_srl)
			{
				$folder_info = $folder;
				break;
			}
		}

		// Get scrapped documents in selected folder
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->folder_srl = $folder_srl;
		$args->page = Context::get('page');
		$search_keyword = str_replace(' ', '_', escape(trim(utf8_normalize_spaces(Context::get('search_keyword')))));
		switch (Context::get('search_target'))
		{
			case 'title':
				$args->s_title = $search_keyword;
				break;
			case 'title_content':
				$args->s_title = $search_keyword;
				$args->s_content = $search_keyword;
				break;
			case 'content':
				$args->s_content = $search_keyword;
				break;
			default:
				break;
		}
		$output = executeQueryArray('member.getScrapDocumentList', $args);
		
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('scrap_folders', $folders);
		Context::set('folder_info', $folder_info);
		Context::set('folder_srl', $folder_srl);

		$security = new Security($output->data);
		$security->encodeHTML('..nick_name');

		$this->setTemplateFile('scrapped_list');
	}

	/**
	 * @brief Display documents saved by the member
	 */
	function dispMemberSavedDocument()
	{
		if ($this->member_config->features['saved_documents'] === false)
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		// A message appears if the user is not logged-in
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl) throw new Rhymix\Framework\Exceptions\MustLogin;
		// Get the saved document(module_srl is set to member_srl instead)
		
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->page = Context::get('page');
		$args->statusList = array('TEMP');
		$output = DocumentModel::getDocumentList($args, true);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('saved_list');
	}

	/**
	 * @brief Display the login management page
	 */
	function dispMemberActiveLogins()
	{
		if ($this->member_config->features['active_logins'] === false)
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		$logged_info = Context::get('logged_info');
		if (!$logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}
		
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->page = Context::get('page');
		$output = executeQueryArray('member.getAutologin', $args);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('active_logins', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$output = executeQueryArray('member.getMemberDevice', $args);
		Context::set('registered_devices', $output->data);

		$this->setTemplateFile('active_logins');
	}
	
	/**
	 * @brief Display the login form 
	 */
	function dispMemberLoginForm()
	{
		if(Context::get('is_logged'))
		{
			$this->setRedirectUrl(getNotEncodedUrl('act',''));
			return;
		}

		// get member module configuration.
		$config = $this->member_config;
		Context::set('identifier', $config->identifier);

		$XE_VALIDATOR_MESSAGE = Context::get('XE_VALIDATOR_MESSAGE');
		$XE_VALIDATOR_ERROR = Context::get('XE_VALIDATOR_ERROR');
		if($XE_VALIDATOR_ERROR == -11)
		{
			Context::set('XE_VALIDATOR_MESSAGE', $XE_VALIDATOR_MESSAGE . $config->limit_day_description);
		}

		if(strpos(Context::get('referer_url'), 'procMember') !== false || ($XE_VALIDATOR_ERROR < -10 && $XE_VALIDATOR_ERROR > -21))
		{
			Context::set('referer_url', getUrl(''));
		}
		else
		{
			Context::set('referer_url', escape($_SERVER['HTTP_REFERER']));
		}

		// Set a template file
		$this->setTemplateFile('login_form');
	}

	/**
	 * @brief Change the user password
	 */
	function dispMemberModifyPassword()
	{
		// A message appears if the user is not logged-in
		if(!$this->user->member_srl) throw new Rhymix\Framework\Exceptions\MustLogin;

		$memberConfig = $this->member_config;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$columnList = array('member_srl', 'user_id');
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl, 0, $columnList);
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
		// A message appears if the user is not logged-in
		if(!$this->user->member_srl) throw new Rhymix\Framework\Exceptions\MustLogin;

		$memberConfig = $this->member_config;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl);
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
		$output = MemberController::getInstance()->procMemberLogout();
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
		return new BaseObject(0, 'Deplicated method');
	}

	/**
	 * @brief Find user ID and password
	 */
	function dispMemberFindAccount()
	{
		if(Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exception('already_logged');
		}

		$config = $this->member_config;

		Context::set('identifier', $config->identifier);
		Context::set('enable_find_account_question', 'N');

		$this->setTemplateFile('find_member_account');
	}

	/**
	 * @brief Page of re-sending an authentication mail
	 */
	function dispMemberResendAuthMail()
	{
		if(Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exception('already_logged');
		}

		$this->setTemplateFile('resend_auth_mail');
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
		$extraList = MemberModel::getUsedJoinFormList();

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
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$member_srl = Context::get('member_srl');
		$module_srl = Context::get('module_srl');

		// check grant
		$columnList = array('module_srl', 'module');
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl, $columnList);
		$grant = ModuleModel::getGrant($module_info, Context::get('logged_info'));

		if(!$grant->manager) throw new Rhymix\Framework\Exceptions\NotPermitted;

		Context::loadLang('modules/document/lang/');
		Context::set('spammer_info', MemberModel::getMemberInfoByMemberSrl($member_srl));
		Context::set('module_srl', $module_srl);

		// Select Pop-up layout
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('spammer');
	}

	/**
	 * Member Nickname Log
	 * @return void
	 */
	function dispMemberModifyNicknameLog()
	{
		if ($this->member_config->features['nickname_log'] === false || $this->member_config->update_nickname_log != 'Y')
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		$member_srl = Context::get('member_srl');
		$logged_info = Context::get('logged_info');
		if(!$member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}
		else
		{
			if($logged_info->is_admin != 'Y')
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		$page = Context::get('page');
		$output = MemberModel::getMemberModifyNicknameLog($page, $member_srl);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('nickname_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('member_nick');
	}
}
/* End of file member.view.php */
/* Location: ./modules/member/member.view.php */
