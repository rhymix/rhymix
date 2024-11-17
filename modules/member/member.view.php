<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberView
 * @author NAVER (developers@xpressengine.com)
 * @brief View class of member module
 */
class MemberView extends Member
{
	public $member_config;
	public $member_info;

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

		// Set layout and skin paths
		$this->setLayoutAndTemplatePaths('P', $this->member_config);
	}

	/**
	 * Check the referer for login and signup pages.
	 */
	public function checkRefererUrl()
	{
		// Get the referer URL from Context var or HTTP header.
		$referer_url = Context::get('referer_url') ?: ($_SERVER['HTTP_REFERER'] ?? '');

		// Check if the referer is an internal URL.
		$is_valid_referer = !empty($referer_url) && Rhymix\Framework\URL::isInternalURL($referer_url);

		// Check if the referer is the login or signup page, to prevent redirect loops.
		if (preg_match('!\b(dispMemberLoginForm|dispMemberSignUpForm|dispMemberFindAccount|dispMemberResendAuthMail|procMember)!', $referer_url))
		{
			$is_valid_referer = false;
		}
		if (preg_match('!/(auth|login|signup)\b!', $referer_url))
		{
			$is_valid_referer = false;
		}

		// Store valid referer info in the session.
		if ($is_valid_referer)
		{
			return $_SESSION['member_auth_referer'] = $referer_url;
		}
		elseif (isset($_SESSION['member_auth_referer']))
		{
			return $_SESSION['member_auth_referer'];
		}
		elseif ($this->mid && !empty($this->member_config->mid) && $this->mid === $this->member_config->mid)
		{
			return getNotEncodedUrl('');
		}
		else
		{
			return getNotEncodedUrl('act', '');
		}
	}

	/**
	 * Check redirect to member mid.
	 */
	public function checkMidAndRedirect()
	{
		if (!$this->member_config)
		{
			$this->member_config = MemberModel::getMemberConfig();
		}
		if (empty($this->member_config->mid) || empty($this->member_config->force_mid))
		{
			return true;
		}
		if (ModuleModel::getModuleInfoByMid($this->member_config->mid)->module !== $this->module)
		{
			return true;
		}
		if (Context::get('mid') === $this->member_config->mid)
		{
			return true;
		}

		$vars = get_object_vars(Context::getRequestVars());
		$vars['mid'] = $this->member_config->mid;
		$this->setRedirectUrl(getNotEncodedUrl($vars));
		return false;
	}

	/**
	 * Get the module_srl for the member mid.
	 *
	 * @return int
	 */
	public function getMemberModuleSrl(): int
	{
		if (!$this->member_config)
		{
			$this->member_config = MemberModel::getMemberConfig();
		}
		if (!empty($this->member_config->mid))
		{
			return ModuleModel::getModuleInfoByMid($this->member_config->mid)->module_srl ?? 0;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Module index
	 */
	public function dispMemberIndex()
	{
		if ($this->user->isMember())
		{
			$this->setRedirectUrl(getNotEncodedUrl(['mid' => $this->mid, 'act' => 'dispMemberInfo']));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl(['mid' => $this->mid, 'act' => 'dispMemberLoginForm']));
		}
	}

	/**
	 * @brief Display member information
	 */
	function dispMemberInfo()
	{
		if (!$this->checkMidAndRedirect())
		{
			return;
		}

		// Don't display member info to non-logged user
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$member_srl = Context::get('member_srl') ?: $logged_info->member_srl;
		if(!$member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl);
		if (!$member_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}

		unset($member_info->password);
		unset($member_info->email_id);
		unset($member_info->email_host);

		if($logged_info->is_admin != 'Y' && ($member_info->member_srl != $logged_info->member_srl))
		{
			list($email_id, $email_host) = explode('@', $member_info->email_address);
			if (strlen($email_id) <= 3)
			{
				$protect_id = '***';
			}
			else
			{
				$protect_id = substr($email_id, 0, 2) . str_repeat('*', strlen($email_id) - 2);
			}
			$member_info->email_address = sprintf('%s@%s', $protect_id, $email_host);
		}

		foreach ($member_info->group_list ?? [] as $key => $val)
		{
			$member_info->group_list[$key] = Context::replaceUserLang($val, true);
		}

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
				$item->title = $formInfo->title;
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
				elseif($formInfo->name == 'birthday' && $memberInfo->birthday && preg_match('/^[0-9]{8}/', $item->value))
				{
					$item->value = sprintf('%s-%s-%s', substr($item->value, 0, 4), substr($item->value, 4, 2), substr($item->value, 6, 2));
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
				$item->title = $extendFormInfo[$formInfo->member_join_form_srl]->column_title ?? null;
				$extvalue = new Rhymix\Modules\Extravar\Models\Value(0, 1, '', $formInfo->type);
				$extvalue->parent_type = 'member';
				$extvalue->input_name = $formInfo->name;
				$extvalue->input_id = $formInfo->name;
				$extvalue->value = $extendFormInfo[$formInfo->member_join_form_srl]->value ?? null;
				$extvalue->default = $extendFormInfo[$formInfo->member_join_form_srl]->default_value ?? null;
				$item->value = $extvalue->getValueHTML();
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
		// Check referer URL
		$referer_url = $this->checkRefererUrl();

		// Redirect to member mid if necessary.
		if (!$this->checkMidAndRedirect())
		{
			return;
		}

		// Return to previous screen if already logged in.
		if($this->user->isMember())
		{
			$this->setRedirectUrl($referer_url);
			return;
		}

		// call a trigger (before)
		$member_config = $this->member_config;
		$trigger_output = ModuleHandler::triggerCall('member.dispMemberSignUpForm', 'before', $member_config);
		if(!$trigger_output->toBool()) return $trigger_output;

		// Error appears if the member is not allowed to join
		if ($member_config->enable_join !== 'Y')
		{
			if (!empty($member_config->enable_join_key))
			{
				if (strpos(escape(rawurldecode(\RX_REQUEST_URL)), $member_config->enable_join_key) !== false)
				{
					$_SESSION['signup_allowed'] = true;
				}
				else
				{
					$_SESSION['signup_allowed'] = false;
					throw new Rhymix\Framework\Exceptions\FeatureDisabled('msg_signup_disabled');
				}
			}
			else
			{
				$_SESSION['signup_allowed'] = false;
				throw new Rhymix\Framework\Exceptions\FeatureDisabled('msg_signup_disabled');
			}
		}

		$formTags = getAdminView('member')->_getMemberInputTag();
		Context::set('formTags', $formTags);
		Context::set('email_confirmation_required', $member_config->enable_confirm);

		// Editor of the module set for signing by calling getEditor
		foreach($formTags as $formTag)
		{
			if($formTag->name == 'signature')
			{
				$option = ModuleModel::getModuleConfig('editor') ?: new stdClass;
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
				if (!$option->allow_html)
				{
					$option->editor_skin = 'textarea';
				}
				if ($option->allow_fileupload)
				{
					$option->module_srl = $this->getMemberModuleSrl();
					$option->upload_target_type = 'sig';
				}
				if (!empty($member_config->member_max_filesize))
				{
					$option->allowed_filesize = $member_config->member_max_filesize * 1024;
				}

				Context::set('editor', getModel('editor')->getEditor(0, $option));
			}
		}

		$identifier = array_first($member_config->identifiers);
		$identifierForm = new stdClass;
		$identifierForm->title = lang($identifier);
		$identifierForm->name = $identifier;
		Context::set('identifierForm', $identifierForm);

		$this->addExtraFormValidatorMessage();

		// Set a copy of the agreement for compatibility with old skins
		$member_config->agreement = $member_config->agreements[1]->content ?? '';

		// Set a template file
		$this->setTemplateFile('signup_form');
	}

	function dispMemberModifyInfoBefore()
	{
		if (!$this->checkMidAndRedirect())
		{
			return;
		}

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
		if (!isset($_SESSION['rechecked_password_step']) || !in_array($_SESSION['rechecked_password_step'], ['VALIDATE_PASSWORD', 'INPUT_DATA']))
		{
			$this->dispMemberModifyInfoBefore();
			return;
		}

		if (!$this->checkMidAndRedirect())
		{
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
				$option = ModuleModel::getModuleConfig('editor') ?: new stdClass;
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
				if (!$option->allow_html)
				{
					$option->editor_skin = 'textarea';
				}
				if ($option->allow_fileupload)
				{
					$option->module_srl = $this->getMemberModuleSrl();
					$option->upload_target_type = 'sig';
				}
				if (!empty($member_config->member_max_filesize))
				{
					$option->allowed_filesize = $member_config->member_max_filesize * 1024;
				}

				Context::set('editor', getModel('editor')->getEditor($member_info->member_srl, $option));
			}
		}

		$identifier = array_first($member_config->identifiers);
		$identifierForm = new stdClass;
		$identifierForm->title = lang($identifier);
		$identifierForm->name = $identifier;
		$identifierForm->value = $member_info->$identifier;
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

		if (!$this->checkMidAndRedirect())
		{
			return;
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
		$args->use_division = false;

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

		if (!$this->checkMidAndRedirect())
		{
			return;
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

		if (!$this->checkMidAndRedirect())
		{
			return;
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
			if ($output instanceof BaseObject && !$output->toBool())
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

		// If viewing default folder, check for additional scraps to migrate.
		if (isset($folder_info->folder_srl) && $folder_info->name === '/DEFAULT/')
		{
			$output = executeQuery('member.updateScrapFolderFromNull', [
				'folder_srl' => $folder_info->folder_srl,
				'member_srl' => $logged_info->member_srl,
			]);
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

		if (!$this->checkMidAndRedirect())
		{
			return;
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

		if (!$this->checkMidAndRedirect())
		{
			return;
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
		// Check referer URL
		$referer_url = $this->checkRefererUrl();
		Context::set('referer_url', $referer_url);

		// Redirect to member mid if necessary.
		if (!$this->checkMidAndRedirect())
		{
			return;
		}

		// Return to previous screen if already logged in.
		if($this->user->isMember())
		{
			$this->setRedirectUrl($referer_url);
			return;
		}

		// get member module configuration.
		$config = $this->member_config;
		Context::set('identifier', $config->identifier);

		// Get validator status
		$XE_VALIDATOR_MESSAGE = Context::get('XE_VALIDATOR_MESSAGE');
		$XE_VALIDATOR_ERROR = Context::get('XE_VALIDATOR_ERROR');
		if($XE_VALIDATOR_ERROR == -11)
		{
			Context::set('XE_VALIDATOR_MESSAGE', $XE_VALIDATOR_MESSAGE . $config->limit_day_description);
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

		if (!$this->checkMidAndRedirect())
		{
			return;
		}

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

		if (!$this->checkMidAndRedirect())
		{
			return;
		}

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
		// Redirect if not logged in.
		if(!Context::get('is_logged'))
		{
			$this->setRedirectUrl(getNotEncodedUrl('act', '', 'redirect_url', ''));
			return;
		}

		$output = MemberController::getInstance()->procMemberLogout();
		if (!empty($output->redirect_url))
		{
			$this->setRedirectUrl($output->redirect_url);
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('act', '', 'redirect_url', ''));
		}
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

		if (!$this->checkMidAndRedirect())
		{
			return;
		}

		Context::set('member_config', $this->member_config);
		Context::set('identifier', $this->member_config->identifier);
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

		if (!$this->checkMidAndRedirect())
		{
			return;
		}

		$this->setTemplateFile('resend_auth_mail');
	}

	function dispMemberModifyEmailAddress()
	{
		if (!isset($_SESSION['rechecked_password_step']) || !in_array($_SESSION['rechecked_password_step'], ['VALIDATE_PASSWORD', 'INPUT_DATA']))
		{
			Context::set('success_return_url', getUrl('', 'mid', Context::get('mid'), 'act', 'dispMemberModifyEmailAddress'));
			$this->dispMemberModifyInfoBefore();
			return;
		}

		if (!$this->checkMidAndRedirect())
		{
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
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		if (!$this->checkMidAndRedirect())
		{
			return;
		}

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

		if (!$this->checkMidAndRedirect())
		{
			return;
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
