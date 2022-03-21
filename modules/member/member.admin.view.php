<?php	
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberAdminView
 * @author NAVER (developers@xpressengine.com)
 * member module's admin view class
 */
class memberAdminView extends member
{
	/**
	 * Group list
	 *
	 * @var array
	 */
	var $group_list = NULL;

	/**
	 * Selected member info
	 *
	 * @var array
	 */
	var $memberInfo = NULL;

	/**
	 * Member module config.
	 *
	 * @var Object
	 */
	var $memberConfig = NULL;

	/**
	 * initialization
	 *
	 * @return void
	 */
	function init()
	{
		$oMemberModel = getModel('member');
		$this->memberConfig = $oMemberModel->getMemberConfig();
		Context::set('config', $this->memberConfig);
		$oSecurity = new Security();
		$oSecurity->encodeHTML('config.signupForm..');

		// if member_srl exists, set memberInfo
		$member_srl = Context::get('member_srl');
		if($member_srl)
		{
			$this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if(!$this->memberInfo)
			{
				Context::set('member_srl','');
			}
			else
			{
				Context::set('member_info',$this->memberInfo);
			}
		}

		// retrieve group list
		$this->group_list = $oMemberModel->getGroups();
		Context::set('group_list', $this->group_list);

		$security = new Security();
		$security->encodeHTML('group_list..', 'config..');

		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * display member list
	 *
	 * @return void
	 */
	function dispMemberAdminList()
	{
		$oMemberAdminModel = getAdminModel('member');
		$oMemberModel = getModel('member');
		$output = $oMemberAdminModel->getMemberList();

		$filter = Context::get('filter_type');
		global $lang;
		switch($filter)
		{
			case 'super_admin' : Context::set('filter_type_title', $lang->cmd_show_super_admin_member);break;
			case 'site_admin' : Context::set('filter_type_title', $lang->cmd_show_site_admin_member);break;
			default : Context::set('filter_type_title', $lang->cmd_show_all_member);break;
		}
		// retrieve list of groups for each member
		if($output->data)
		{
			foreach($output->data as $key => $member)
			{
				$output->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl,0);
				$output->data[$key]->profile_image = $oMemberModel->getProfileImage($member->member_srl);
			}
		}
		$config = $this->memberConfig;
		$memberIdentifiers = array(
			'user_id' => 'user_id',
			'email_address' => 'email_address',
			'phone_number' => 'phone_number',
			'user_name' => 'user_name',
			'nick_name' => 'nick_name'
		);
		$usedIdentifiers = array();	

		if(is_array($config->signupForm))
		{
			foreach($config->signupForm as $signupItem)
			{
				if(!count($memberIdentifiers)) break;
				if(in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		
		// Get list of new members who have not completed email auth
		$check_list = array();
		foreach ($output->data as $member)
		{
			if ($member->denied !== 'N')
			{
				$check_list[$member->member_srl] = false;
			}
		}
		if (count($check_list))
		{
			$args2 = new stdClass;
			$args2->member_srl = array_keys($check_list);
			$output2 = executeQueryArray('member.getAuthMailType', $args2);
			foreach ($output2->data as $item)
			{
				if ($item->is_register === 'Y')
				{
					$check_list[$item->member_srl] = true;
				}
			}
		}

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('member_config', $oMemberModel->getMemberConfig());
		Context::set('member_list', $output->data);
		Context::set('new_member_check_list', $check_list);
		Context::set('usedIdentifiers', $usedIdentifiers);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('profileImageConfig', $config->profile_image);

		$security = new Security();
		$security->encodeHTML('member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');
		$security->encodeHTML('search_target', 'search_keyword');

		$this->setTemplateFile('member_list');
	}

	/**
	 * Set the default config.
	 *
	 * @return void
	 */
	public function dispMemberAdminConfig()
	{
		// Get supported password algorithms.
		$oDB = DB::getInstance();
		$column_info = $oDB->getColumnInfo('member', 'password');
		$password_maxlength = intval($column_info->size);
		$password_algos = Rhymix\Framework\Password::getSupportedAlgorithms();
		if ($password_maxlength < 128 && isset($password_algos['sha512']))
		{
			$password_algos['sha512'] = false;
		}
		if ($password_maxlength < 64 && isset($password_algos['sha256']))
		{
			$password_algos['sha256'] = false;
		}
		Context::set('password_hashing_algos', $password_algos);
		
		$this->setTemplateFile('default_config');
	}

	/**
	 * Set the features config.
	 *
	 * @return void
	 */
	public function dispMemberAdminFeaturesConfig()
	{
		$this->setTemplateFile('features_config');
	}

	/**
	 * Set the agreements config.
	 *
	 * @return void
	 */
	public function dispMemberAdminAgreementsConfig()
	{
		$this->setTemplateFile('agreements_config');
	}

	public function dispMemberAdminSignUpConfig()
	{
		$config = $this->memberConfig;

		if($config->redirect_url)
		{
			if(!$config->redirect_mid)
			{
				$mid = str_ireplace(Context::getDefaultUrl(), '', $config->redirect_url);
			}
			else
			{
				$mid = $config->redirect_mid;
			}

			$moduleInfo = ModuleModel::getModuleInfoByMid($mid);
			$config->redirect_url = $moduleInfo->module_srl;
			Context::set('config', $config);
		}

		$oMemberModel = getModel('member');
		// retrieve skins of editor
		$oEditorModel = getModel('editor');
		Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

		// get an editor
		$option = new stdClass;
		$option->primary_key_name = 'temp_srl';
		$option->content_key_name = 'agreement';
		$option->allow_fileupload = false;
		$option->enable_autosave = false;
		$option->enable_default_component = true;
		$option->enable_component = false;
		$option->resizable = true;
		$option->height = 300;
		$option->editor_toolbar_hide = 'Y';
		Context::set('editor', $oEditorModel->getEditor(0, $option));
		
		$signupForm = $config->signupForm;
		foreach($signupForm as $val)
		{
			if($val->name == 'user_id')
			{
				$userIdInfo = $val;
				break;
			}
		}

		$oSecurity = new Security();
		if($userIdInfo->isUse)
		{
			// get denied ID list
			Context::set('useUserID', 1);
			$denied_list = $oMemberModel->getDeniedIDs();
			Context::set('deniedIDs', $denied_list);
			$oSecurity->encodeHTML('deniedIDs..user_id');
		}

		// get denied NickName List
		$deniedNickNames = $oMemberModel->getDeniedNickNames();
		Context::set('deniedNickNames', $deniedNickNames);
		$oSecurity->encodeHTML('deniedNickNames..nick_name');

		//get managed Email Hosts
		$managedEmailHost = $oMemberModel->getManagedEmailHosts();
		Context::set('managedEmailHost', $managedEmailHost);
		$oSecurity->encodeHTML('managedEmailHost..email_host');
		
		// Get country calling code list
		$country_list = Rhymix\Framework\i18n::listCountries(Context::get('lang_type') === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
		Context::set('country_list', $country_list);
		if(!$config->phone_number_default_country && Context::get('lang_type') === 'ko')
		{
			$config->phone_number_default_country = 'KOR';
		}

		$this->setTemplateFile('signup_config');
	}

	public function dispMemberAdminLoginConfig()
	{
		$this->setTemplateFile('login_config');
	}

	public function dispMemberAdminDesignConfig()
	{
		$oModuleModel = getModel('module');
		// Get a layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();

		Context::set('layout_list', $layout_list);

		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');

		Context::set('mlayout_list', $mlayout_list);

		// list of skins for member module
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// list of skins for member module
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		$this->setTemplateFile('design_config');
	}

	/**
	 * default configuration for member management
	 *
	 * @return void
	 */
	function dispMemberAdminConfigOLD()
	{
		$oModuleModel = getModel('module');
		$oMemberModel = getModel('member');

		// Get a layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();

		Context::set('layout_list', $layout_list);

		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');

		Context::set('mlayout_list', $mlayout_list);

		// list of skins for member module
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// list of skins for member module
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// retrieve skins of editor
		$oEditorModel = getModel('editor');
		Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

		// get an editor
		$option->skin = $oEditorModel->getEditorConfig()->editor_skin;
		$option->primary_key_name = 'temp_srl';
		$option->content_key_name = 'agreement';
		$option->allow_fileupload = false;
		$option->enable_autosave = false;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->resizable = true;
		$option->height = 300;
		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);

		$signupForm = $config->signupForm;
		foreach($signupForm as $val)
		{
			if($val->name == 'user_id')
			{
				$userIdInfo = $val;
				break;
			}
		}

		if($userIdInfo->isUse)
		{
			// get denied ID list
			Context::set('useUserID', 1);
			$denied_list = $oMemberModel->getDeniedIDs();
			Context::set('deniedIDs', $denied_list);
		}

		// get denied NickName List
		$deniedNickNames = $oMemberModel->getDeniedNickNames();
		Context::set('deniedNickNames', $deniedNickNames);

		$security = new Security();
		$security->encodeHTML('config..');

		$this->setTemplateFile('member_config');
	}

	/**
	 * display member information
	 *
	 * @return void
	 */
	function dispMemberAdminInfo()
	{
		$oMemberModel = getModel('member');
		$oModuleModel = getModel('module');

		$member_config = $oModuleModel->getModuleConfig('member');
		Context::set('member_config', $member_config);
		$extendForm = $oMemberModel->getCombineJoinForm($this->memberInfo);
		Context::set('extend_form_list', $extendForm);
		$memberInfo = get_object_vars(Context::get('member_info'));
		if (!is_array($memberInfo['group_list'])) $memberInfo['group_list'] = array();
		Context::set('memberInfo', $memberInfo);

		$disableColumns = array('password', 'find_account_question', 'find_account_answer');
		Context::set('disableColumns', $disableColumns);

		$security = new Security();
		$security->encodeHTML('member_config..');
		$security->encodeHTML('extend_form_list...');

		$oMemberView = getView('member');

		$oMemberView->_getDisplayedMemberInfo($this->memberInfo, $extendForm, $member_config);

		$this->setTemplateFile('member_info');
	}

	/**
	 * display member insert form
	 *
	 * @return void
	 */
	function dispMemberAdminInsert()
	{
		$oMemberModel = getModel('member');
		$member_config = $this->memberConfig;
		
		if($member_info = Context::get('member_info'))
		{
			$member_info->signature = $oMemberModel->getSignature($this->memberInfo->member_srl);
		}
		else
		{
			$member_info = new stdClass;
		}
		
		Context::set('member_info', $member_info);
		
		$formTags = $this->_getMemberInputTag($member_info, true);
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
		
		if ($member_info->limit_date < date('Ymd'))
		{
			$member_info->limit_date = '';
		}
		
		$member_unauthenticated = false;
		if ($member_info->member_srl && $member_info->denied !== 'N')
		{
			$args2 = new stdClass;
			$args2->member_srl = $member_info->member_srl;
			$output2 = executeQueryArray('member.getAuthMailType', $args2);
			foreach ($output2->data as $item)
			{
				if ($item->is_register === 'Y')
				{
					$member_unauthenticated = true;
				}
			}
		}
		Context::set('member_unauthenticated', $member_unauthenticated);
		
		$this->setTemplateFile('insert_member');
	}

	/**
	 * Get tags by the member info type
	 *
	 * @param object $memberInfo
	 * @param boolean $isAdmin (true : admin, false : not admin)
	 *
	 * @return array
	 */
	function _getMemberInputTag($memberInfo = null, $isAdmin = false)
	{
		$logged_info = Context::get('logged_info');
		$oMemberModel = getModel('member');
		$extend_form_list = $oMemberModel->getCombineJoinForm($memberInfo);
		$security = new Security($extend_form_list);
		$security->encodeHTML('..column_title', '..description', '..default_value.');
		
		if ($memberInfo)
		{
			$memberInfo = get_object_vars($memberInfo);
		}
		else
		{
			$memberInfo = array();
		}

		$member_config = $this->memberConfig;
		if(!$this->memberConfig)
		{
			$member_config = $this->memberConfig = $oMemberModel->getMemberConfig();
		}
		
		global $lang;
		$formTags = array();
		
		foreach($member_config->signupForm as $no=>$formInfo)
		{
			if(!$formInfo->isUse || $formInfo->name == $member_config->identifier || $formInfo->name == 'password')
			{
				continue;
			}
			
			$formTag = new stdClass();
			$inputTag = '';
			$formTag->title = $formInfo->title;
			if($isAdmin)
			{
				if($formInfo->mustRequired || $formInfo->required) $formTag->title = '<em style="color:red">*</em> '.$formTag->title;
			}
			else
			{
				if ($formInfo->required && $formInfo->name != 'password') $formTag->title = '<em style="color:red">*</em> '.$formTag->title;
			}
			$formTag->name = $formInfo->name;

			if($formInfo->isDefaultForm)
			{
				if($formInfo->imageType)
				{
					$formTag->type = 'image';
					if($formInfo->name == 'profile_image')
					{
						$target = $memberInfo['profile_image'];
						$functionName = 'doDeleteProfileImage';
					}
					else if($formInfo->name == 'image_name')
					{
						$target = $memberInfo['image_name'];
						$functionName = 'doDeleteImageName';
					}
					else if($formInfo->name == 'image_mark')
					{
						$target = $memberInfo['image_mark'];
						$functionName = 'doDeleteImageMark';
					}

					if($target->src)
					{
						$inputTag = sprintf('<input type="hidden" name="__%s_exist" value="true" /><span id="%s"><img src="%s" alt="%s" /> <button type="button" onclick="%s(%d);return false;">%s</button></span>',
							$formInfo->name,
							$formInfo->name.'tag',
							$target->src,
							$formInfo->title,
							$functionName,
							$memberInfo['member_srl'],
							$lang->cmd_delete);
					}
					else
					{
						$inputTag = sprintf('<input type="hidden" name="__%s_exist" value="false" />', $formInfo->name);
					}
					
					$max_filesize = min(FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')));
					if (isset($member_config->{$formInfo->name.'_max_filesize'}))
					{
						$max_filesize = min($max_filesize, $member_config->{$formInfo->name.'_max_filesize'} * 1024);
					}
					$inputTag .= sprintf('<input type="file" name="%s" id="%s" value="" accept="image/*" data-max-filesize="%d" data-max-filesize-error="%s" /><p class="help-block">%s: %s, %s: %dpx, %s: %dpx</p>',
						$formInfo->name,
						$formInfo->name,
						$max_filesize,
						escape(lang('file.allowed_filesize_exceeded')),
						lang('file.allowed_filesize'),
						FileHandler::filesize($max_filesize),
						$lang->{$formInfo->name.'_max_width'},
						$member_config->{$formInfo->name.'_max_width'},
						$lang->{$formInfo->name.'_max_height'},
						$member_config->{$formInfo->name.'_max_height'});
					}//end imageType
					else if($formInfo->name == 'birthday')
					{
						$formTag->type = 'date';
						$inputTag = sprintf('<input type="hidden" name="birthday" id="date_birthday" value="%s" />' .
							'<input type="date" placeholder="YYYY-MM-DD" name="birthday_ui" class="inputDate" id="birthday" value="%s" ' .
							'min="' . date('Y-m-d',strtotime('-200 years')) . '"  max="' . date('Y-m-d',strtotime('+10 years')) . '" ' .
							'onchange="jQuery(\'#date_birthday\').val(this.value.replace(/-/g,\'\'));" readonly="readonly" /> ' .
							'<input type="button" value="%s" class="btn dateRemover" />',
							$memberInfo['birthday'],
							zdate($memberInfo['birthday'], 'Y-m-d', false),
							$lang->cmd_delete);
					}
					else if($formInfo->name == 'find_account_question')
					{
						continue;
					}
					else if($formInfo->name == 'email_address')
					{
						$formTag->type = 'email';
						$inputTag = '<input type="email" name="email_address" id="email_address" value="'.$memberInfo['email_address'].'" />';
					}
					else if($formInfo->name == 'phone_number')
					{
						$formTag->type = 'phone';
						$match_country = $memberInfo['phone_country'];
						if(!$match_country && $member_config->phone_number_default_country)
						{
							$match_country = $member_config->phone_number_default_country;
						}
						if($match_country && !preg_match('/^[A-Z]{3}$/', $match_country))
						{
							$match_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($match_country);
						}
						if(!$match_country && Context::get('lang_type') === 'ko')
						{
							$match_country = 'KOR';
						}
						if($member_config->phone_number_hide_country !== 'Y')
						{
							$inputTag = '<select name="phone_country" id="phone_country" class="phone_country">';
							$country_list = Rhymix\Framework\i18n::listCountries(Context::get('lang_type') === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
							foreach($country_list as $country)
							{
								if($country->calling_code)
								{
									$inputTag .= '<option value="' . $country->iso_3166_1_alpha3 . '"' . ($country->iso_3166_1_alpha3 === $match_country ? ' selected="selected"' : '') . '>';
									$inputTag .= escape(Context::get('lang_type') === 'ko' ? $country->name_korean : $country->name_english) . ' (+' . $country->calling_code . ')</option>';
								}
							}
							$inputTag .= '</select>' . "\n";
						}
						if($memberInfo['phone_number'])
						{
							if($match_country === 'KOR')
							{
								$phone_number = Rhymix\Framework\Korea::formatPhoneNumber($memberInfo['phone_number']);
							}
							else
							{
								$phone_number = $memberInfo['phone_number'];
							}
						}
						else
						{
							$phone_number = '';
						}
						$inputTag .= '<input type="tel" name="phone_number" id="phone_number" class="phone_number" value="'.$phone_number .'" />';
						if($member_config->phone_number_verify_by_sms === 'Y')
						{
							$inputTag .= "\n" . '<button type="button" class="btn verifySMS" style="display:none">' . lang('member.verify_by_sms') . '</button>';
							$inputTag .= "\n" . '<div class="verifySMS_input_area" style="display:none">';
							$inputTag .= '<input type="number" class="verifySMS_input_number" />';
							$inputTag .= '<button type="button" class="btn verifySMS_input_button">' . lang('member.verify_by_sms_confirm') . '</button>';
							$inputTag .= '</div>';
						}
					}
					else if($formInfo->name == 'homepage')
					{
						$formTag->type = 'url';
						$inputTag = '<input type="url" name="homepage" id="homepage" value="'.$memberInfo['homepage'].'" />';
					}
					else if($formInfo->name == 'blog')
					{
						$formTag->type = 'url';
						$inputTag = '<input type="url" name="blog" id="blog" value="'.$memberInfo['blog'].'" />';
					}
					else
					{
						$formTag->type = 'text';
						$inputTag = sprintf('<input type="text" name="%s" id="%s" value="%s" />',
							$formInfo->name,
							$formInfo->name,
							$memberInfo[$formInfo->name]);
					}
				}//end isDefaultForm
				else
				{
					$extendForm = $extend_form_list[$formInfo->member_join_form_srl];
					$replace = array('column_name' => $extendForm->column_name, 'value' => $extendForm->value);
					$extentionReplace = array();

					$formTag->type = $extendForm->column_type;
					if($extendForm->column_type == 'text')
					{
						$template = '<input type="text" class="rx_ev_text" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'homepage')
					{
						$template = '<input type="url" class="rx_ev_url" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'email_address')
					{
						$template = '<input type="email" class="rx_ev_email" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'tel')
					{
						$extentionReplace = array('tel_0' => $extendForm->value[0],
							'tel_1' => $extendForm->value[1],
							'tel_2' => $extendForm->value[2]);
						$template = '<input type="tel" class="rx_ev_tel1" name="%column_name%[]" id="%column_name%" value="%tel_0%" size="4" maxlength="4" style="width:30px" title="First Number" /> - <input type="tel" class="rx_ev_tel2" name="%column_name%[]" value="%tel_1%" size="4" maxlength="4" style="width:35px" title="Second Number" /> - <input type="tel" class="rx_ev_tel3" name="%column_name%[]" value="%tel_2%" size="4" maxlength="4" style="width:35px" title="Third Number" />';
					}
					else if($extendForm->column_type == 'textarea')
					{
						$template = '<textarea class="rx_ev_textarea" name="%column_name%" id="%column_name%" rows="4" cols="42">%value%</textarea>';
					}
					else if($extendForm->column_type == 'password')
					{
						$template = '<input type="password" class="rx_ev_password" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'checkbox')
					{
						$template = '';
						if($extendForm->default_value)
						{
							$template = '<div class="rx_ev_checkbox" style="padding-top:5px">%s</div>';
							$__i = 0;
							$optionTag = array();
							foreach($extendForm->default_value as $v)
							{
								$checked = '';
								if(is_array($extendForm->value) && in_array($v, $extendForm->value))$checked = 'checked="checked"';
								$optionTag[] = '<label for="%column_name%'.$__i.'"><input type="checkbox" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.$v.'" '.$checked.' /> '.$v.'</label>';
								$__i++;
							}
							$template = sprintf($template, implode('', $optionTag));
						}
					}
					else if($extendForm->column_type == 'radio')
					{
						$template = '';
						if($extendForm->default_value)
						{
							$template = '<div class="rx_ev_radio" style="padding-top:5px">%s</div>';
							$optionTag = array();
							foreach($extendForm->default_value as $v)
							{
								if($extendForm->value == $v)$checked = 'checked="checked"';
								else $checked = '';
								$optionTag[] = '<label><input type="radio" name="%column_name%" value="'.$v.'" '.$checked.' /> '.$v.'</label>';
							}
							$template = sprintf($template, implode('', $optionTag));
						}
					}
					else if($extendForm->column_type == 'select')
					{
						$template = '<select class="rx_ev_select" name="'.$formInfo->name.'" id="'.$formInfo->name.'">%s</select>';
						$optionTag = array();
						$optionTag[] = sprintf('<option value="">%s</option>', $lang->cmd_select);
						if($extendForm->default_value)
						{
							foreach($extendForm->default_value as $v)
							{
								if($v == $extendForm->value) $selected = 'selected="selected"';
								else $selected = '';
								$optionTag[] = sprintf('<option value="%s" %s >%s</option>', $v, $selected, $v);
							}
						}
						$template = sprintf($template, implode('', $optionTag));
					}
					else if($extendForm->column_type == 'kr_zip')
					{
						$krzipModel = getModel('krzip');
						if($krzipModel && method_exists($krzipModel , 'getKrzipCodeSearchHtml' ))
						{
							$template = $krzipModel->getKrzipCodeSearchHtml($extendForm->column_name, $extendForm->value);
						}
					}
					else if($extendForm->column_type == 'jp_zip')
					{
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'date')
					{
						$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'), 'cmd_delete' => $lang->cmd_delete);
						$template = '<input type="hidden" class="rx_ev_date" name="%column_name%" id="date_%column_name%" value="%value%" />' .
							'<input type="date" placeholder="YYYY-MM-DD" class="inputDate" value="%date%" ' .
							'onchange="jQuery(\'#date_%column_name%\').val(this.value.replace(/-/g,\'\'));" readonly="readonly" /> ' .
							'<input type="button" value="%cmd_delete%" class="btn dateRemover" />';
					}

					$replace = array_merge($extentionReplace, $replace);
					$inputTag = preg_replace_callback('@%(\w+)%@', function($n) use($replace) { return $replace[$n[1]]; }, $template);

					if($extendForm->description)
						$inputTag .= '<p class="help-block">'.$extendForm->description.'</p>';
				}
				$formTag->inputTag = $inputTag;
				$formTags[] = $formTag;
		}
		return $formTags;
	}

	/**
	 * display group list
	 *
	 * @return void
	 */
	function dispMemberAdminGroupList()
	{
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleFileBoxList();
		Context::set('fileBoxList', $output->data);

		$this->setTemplateFile('group_list');
	}

	/**
	 * Display an admin page for memebr join forms
	 *
	 * @return void
	 */
	function dispMemberAdminInsertJoinForm() {
		// Get the value of join_form
		$member_join_form_srl = Context::get('member_join_form_srl');
		if($member_join_form_srl)
		{
			$oMemberModel = getModel('member');
			$join_form = $oMemberModel->getJoinForm($member_join_form_srl);

			if(!$join_form) Context::set('member_join_form_srl','',true);
			else
			{
				Context::set('join_form', $join_form);
				$security = new Security();
				$security->encodeHTML('join_form..');
			}

		}
		$this->setTemplateFile('insert_join_form');
	}

	function dispMemberAdminNickNameLog()
	{
		$page = Context::get('page');
		$output = getModel('member')->getMemberModifyNicknameLog($page);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('nickname_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('nick_name_log');
	}
}
/* End of file member.admin.view.php */
/* Location: ./modules/member/member.admin.view.php */
