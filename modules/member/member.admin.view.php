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
			}
		}
		$config = $this->memberConfig;
		$memberIdentifiers = array('user_id'=>'user_id', 'user_name'=>'user_name', 'nick_name'=>'nick_name');
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
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('member_list', $output->data);
		Context::set('usedIdentifiers', $usedIdentifiers);
		Context::set('page_navigation', $output->page_navigation);

		$security = new Security();
		$security->encodeHTML('member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');

		$this->setTemplateFile('member_list');
	}

	/**
	 * Set the default config.
	 *
	 * @return void
	 */
	public function dispMemberAdminConfig()
	{
		$oPassword = new Password();
		Context::set('password_hashing_algos', $oPassword->getSupportedAlgorithms());
		
		$this->setTemplateFile('default_config');
	}

	public function dispMemberAdminSignUpConfig()
	{
		$config = $this->memberConfig;

		if($config->redirect_url)
		{
			$mid = str_ireplace(Context::getDefaultUrl(), '', $config->redirect_url);

			$siteModuleInfo = Context::get('site_module_info');

			$oModuleModel = getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByMid($mid, (int)$siteModuleInfo->site_srl);

			$config->redirect_url = $moduleInfo->module_srl;
			Context::set('config', $config);
		}

		$oMemberModel = getModel('member');
		// retrieve skins of editor
		$oEditorModel = getModel('editor');
		Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

		// get an editor
		$option = new stdClass();
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

		$disableColumns = array('password', 'find_account_question');
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
		// retrieve extend form
		$oMemberModel = getModel('member');

		$memberInfo = Context::get('member_info');
		if(isset($memberInfo))
		{
			$memberInfo->signature = $oMemberModel->getSignature($this->memberInfo->member_srl);
		}
		Context::set('member_info', $memberInfo);

		// get an editor for the signature
		if($memberInfo->member_srl)
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
			$option->height = 200;
			$editor = $oEditorModel->getEditor($this->memberInfo->member_srl, $option);
			Context::set('editor', $editor);
		}

		$formTags = $this->_getMemberInputTag($memberInfo, true);
		Context::set('formTags', $formTags);
		$member_config = $this->memberConfig;

		global $lang;
		$identifierForm = new stdClass();
		$identifierForm->title = $lang->{$member_config->identifier};
		$identifierForm->name = $member_config->identifier;
		$identifierForm->value = $memberInfo->{$member_config->identifier};
		Context::set('identifierForm', $identifierForm);
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
	function _getMemberInputTag($memberInfo, $isAdmin = false)
	{
		$oMemberModel = getModel('member');
		$extend_form_list = $oMemberModel->getCombineJoinForm($memberInfo);
		$security = new Security($extend_form_list);
		$security->encodeHTML('..column_title', '..description', '..default_value.');

		if ($memberInfo)
		{
			$memberInfo = get_object_vars($memberInfo);
		}

		$member_config = $this->memberConfig;
		if(!$this->memberConfig)
		{
			$member_config = $this->memberConfig = $oMemberModel->getMemberConfig();
		}

		$formTags = array();
		global $lang;

		foreach($member_config->signupForm as $no=>$formInfo)
		{
			if(!$formInfo->isUse)continue;
			if($formInfo->name == $member_config->identifier || $formInfo->name == 'password') continue;
			$formTag = new stdClass();
			$inputTag = '';
			$formTag->title = ($formInfo->isDefaultForm) ? $lang->{$formInfo->name} : $formInfo->title;
			if($isAdmin)
			{
				if($formInfo->mustRequired) $formTag->title = '<em style="color:red">*</em> '.$formTag->title;
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
					$inputTag .= sprintf('<input type="file" name="%s" id="%s" value="" accept="image/*" /><p class="help-block">%s: %dpx, %s: %dpx</p>',
						$formInfo->name,
						$formInfo->name,
						$lang->{$formInfo->name.'_max_width'},
						$member_config->{$formInfo->name.'_max_width'},
						$lang->{$formInfo->name.'_max_height'},
						$member_config->{$formInfo->name.'_max_height'});
					}//end imageType
					else if($formInfo->name == 'birthday')
					{
						$formTag->type = 'date';
						$inputTag = sprintf('<input type="hidden" name="birthday" id="date_birthday" value="%s" /><input type="text" placeholder="YYYY-MM-DD" name="birthday_ui" class="inputDate" id="birthday" value="%s" readonly="readonly" /> <input type="button" value="%s" class="btn dateRemover" />',
							$memberInfo['birthday'],
							zdate($memberInfo['birthday'], 'Y-m-d', false),
							$lang->cmd_delete);
					}
					else if($formInfo->name == 'find_account_question')
					{
						$formTag->type = 'select';
						$inputTag = '<select name="find_account_question" id="find_account_question" style="display:block;margin:0 0 8px 0">%s</select>';
						$optionTag = array();
						foreach($lang->find_account_question_items as $key=>$val)
						{
							if($key == $memberInfo['find_account_question']) $selected = 'selected="selected"';
							else $selected = '';
							$optionTag[] = sprintf('<option value="%s" %s >%s</option>',
								$key,
								$selected,
								$val);
						}
						$inputTag = sprintf($inputTag, implode('', $optionTag));
						$inputTag .= '<input type="text" name="find_account_answer" id="find_account_answer" title="'.Context::getLang('find_account_answer').'" value="'.$memberInfo['find_account_answer'].'" />';
					}
					else if($formInfo->name == 'email_address')
					{
						$formTag->type = 'email';
						$inputTag = '<input type="email" name="email_address" id="email_address" value="'.$memberInfo['email_address'].'" />';
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
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'homepage')
					{
						$template = '<input type="url" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'email_address')
					{
						$template = '<input type="email" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'tel')
					{
						$extentionReplace = array('tel_0' => $extendForm->value[0],
							'tel_1' => $extendForm->value[1],
							'tel_2' => $extendForm->value[2]);
						$template = '<input type="tel" name="%column_name%[]" id="%column_name%" value="%tel_0%" size="4" maxlength="4" style="width:30px" title="First Number" /> - <input type="tel" name="%column_name%[]" value="%tel_1%" size="4" maxlength="4" style="width:35px" title="Second Number" /> - <input type="tel" name="%column_name%[]" value="%tel_2%" size="4" maxlength="4" style="width:35px" title="Third Number" />';
					}
					else if($extendForm->column_type == 'textarea')
					{
						$template = '<textarea name="%column_name%" id="%column_name%" rows="4" cols="42">%value%</textarea>';
					}
					else if($extendForm->column_type == 'checkbox')
					{
						$template = '';
						if($extendForm->default_value)
						{
							$template = '<div style="padding-top:5px">%s</div>';
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
							$template = '<div style="padding-top:5px">%s</div>';
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
						$template = '<select name="'.$formInfo->name.'" id="'.$formInfo->name.'">%s</select>';
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
						$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" placeholder="YYYY-MM-DD" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="btn dateRemover" />';
					}

					$replace = array_merge($extentionReplace, $replace);
					$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

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
}
/* End of file member.admin.view.php */
/* Location: ./modules/member/member.admin.view.php */
