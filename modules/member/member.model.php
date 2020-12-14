<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberModel
 * @author NAVER (developers@xpressengine.com)
 * @brief Model class of the member module
 */
class memberModel extends member
{
	/**
	 * @brief Keep data internally which may be frequently called ...
	 */
	protected static $_denied_id_list;
	protected static $_join_form_list;
	protected static $_managed_email_hosts;
	protected static $_member_config;

	/**
	 * @brief Initialization
	 */
	public function init()
	{
		
	}

	/**
	 * @brief Return member's configuration
	 */
	public static function getMemberConfig()
	{
		if (self::$_member_config)
		{
			return self::$_member_config;
		}

		// Get member configuration stored in the DB
		$config = ModuleModel::getModuleConfig('member') ?: new stdClass;

		if(!isset($config->signupForm) || !is_array($config->signupForm))
		{
			$oMemberAdminController = getAdminController('member');
			$identifier = ($config->identifier) ? $config->identifier : 'email_address';
			$config->signupForm = $oMemberAdminController->createSignupForm($identifier);
		}
		//for multi language
		foreach($config->signupForm AS $key=>$value)
		{
			$config->signupForm[$key]->title = ($value->isDefaultForm) ? lang($value->name) : $value->title;
			if($config->signupForm[$key]->isPublic != 'N') $config->signupForm[$key]->isPublic = 'Y';
			if($value->name == 'find_account_question') $config->signupForm[$key]->isPublic = 'N';
		}

		// Get terms of user
		if(!$config->agreements)
		{
			$config->agreement = self::_getAgreement();
			$config->agreements[1] = new stdClass;
			$config->agreements[1]->title = lang('agreement');
			$config->agreements[1]->content = $config->agreement;
			$config->agreements[1]->type = $config->agreement ? 'required' : 'disabled';
		}

		if(!$config->webmaster_name) $config->webmaster_name = 'webmaster';

		if(!$config->image_name_max_width) $config->image_name_max_width = 90;
		if(!$config->image_name_max_height) $config->image_name_max_height = 20;
		if(!$config->image_name_max_filesize) $config->image_name_max_filesize = null;
		if(!$config->image_mark_max_width) $config->image_mark_max_width = 20;
		if(!$config->image_mark_max_height) $config->image_mark_max_height = 20;
		if(!$config->image_mark_max_filesize) $config->image_mark_max_filesize = null;
		if(!$config->profile_image_max_width) $config->profile_image_max_width = 90;
		if(!$config->profile_image_max_height) $config->profile_image_max_height = 90;
		if(!$config->profile_image_max_filesize) $config->profile_image_max_filesize = null;

		if(!$config->skin) $config->skin = 'default';
		if(!$config->colorset) $config->colorset = 'white';
		if(!$config->editor_skin || $config->editor_skin == 'default') $config->editor_skin = 'ckeditor';
		if(!$config->group_image_mark) $config->group_image_mark = "N";

		if(!$config->identifier) $config->identifier = 'user_id';

		if(!$config->emailhost_check) $config->emailhost_check = 'allowed';

		if(!$config->max_error_count) $config->max_error_count = 10;
		if(!$config->max_error_count_time) $config->max_error_count_time = 300;

		if(!$config->signature_editor_skin || $config->signature_editor_skin == 'default') $config->signature_editor_skin = 'ckeditor';
		if(!$config->sel_editor_colorset) $config->sel_editor_colorset = 'moono-lisa';
		if(!$config->member_allow_fileupload) $config->member_allow_fileupload = 'N';
		if(!$config->member_profile_view) $config->member_profile_view = 'N';

		if(isset($config->redirect_mid) && $config->redirect_mid)
		{
			$config->redirect_url = getNotEncodedFullUrl('','mid',$config->redirect_mid);
		}

		return self::$_member_config = $config;
	}

	/**
	 * @deprecated
	 */
	protected static function _getAgreement()
	{
		$agreement_file = _XE_PATH_.'files/member_extra_info/agreement_' . Context::get('lang_type') . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$agreement_file = _XE_PATH_.'files/member_extra_info/agreement_' . config('locale.default_lang') . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$lang_selected = Context::loadLangSelected();
		foreach($lang_selected as $key => $val)
		{
			$agreement_file = _XE_PATH_.'files/member_extra_info/agreement_' . $key . '.txt';
			if(is_readable($agreement_file))
			{
				return FileHandler::readFile($agreement_file);
			}
		}

		return null;
	}

	/**
	 * @brief Display menus of the member
	 */
	public function getMemberMenu()
	{
		// Get member_srl of he target member and logged info of the current user
		$member_srl = Context::get('target_srl');
		$mid = Context::get('cur_mid');
		$logged_info = Context::get('logged_info');
		$module_config = self::getMemberConfig();
		$act = Context::get('cur_act');
		// When click user's own nickname
		if($member_srl == $logged_info->member_srl) $member_info = $logged_info;
		// When click other's nickname
		else $member_info = self::getMemberInfoByMemberSrl($member_srl);

		$member_srl = $member_info->member_srl;
		if(!$member_srl) return;

		// List variables
		$user_id = $member_info->user_id;
		$user_name = $member_info->user_name;
		$icon_path = '';

		ModuleHandler::triggerCall('member.getMemberMenu', 'before', $member_info);

		$oMemberController = MemberController::getInstance();
		// Display member information (Don't display to non-logged user)
		if($logged_info->member_srl)
		{
			$url = getUrl('','mid',$mid,'act','dispMemberInfo','member_srl',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'cmd_view_member_info',$icon_path,'self');
		}

		// When click other's nickname
		if($member_srl != $logged_info->member_srl && $logged_info->member_srl)
		{
			// Get email config
			foreach($module_config->signupForm as $field)
			{
				if($field->name == 'email_address')
				{
					$email_config = $field;
					break;
				}
			}

			// Send an email only if email address is public
			if($email_config->isPublic == 'Y' && $member_info->email_address)
			{
				$oCommunicationModel = CommunicationModel::getInstance();
				if($logged_info->is_admin == 'Y' || $oCommunicationModel->isFriend($member_info->member_srl))
				{
					$url = 'mailto:'.escape($member_info->email_address);
					$oMemberController->addMemberPopupMenu($url,'cmd_send_email',$icon_path);
				}
			}
		}
		
		// Check if homepage and blog are public
		$homepage_is_public = false;
		$blog_is_public = false;
		if ($logged_info->is_admin === 'Y')
		{
			$homepage_is_public = true;
			$blog_is_public = true;
		}
		else
		{
			foreach ($module_config->signupForm as $field)
			{
				if ($field->name === 'homepage' && $field->isPublic === 'Y')
				{
					$homepage_is_public = true;
				}
				if ($field->name === 'blog' && $field->isPublic === 'Y')
				{
					$blog_is_public = true;
				}
			}
		}
		
		// View homepage info
		if($member_info->homepage && $homepage_is_public)
		{
			$oMemberController->addMemberPopupMenu(escape($member_info->homepage, false), 'homepage', '', 'blank', 'homepage');
		}
		
		// View blog info
		if($member_info->blog && $blog_is_public)
		{
			$oMemberController->addMemberPopupMenu(escape($member_info->blog, false), 'blog', '', 'blank', 'blog');
		}
		
		// Call a trigger (after)
		ModuleHandler::triggerCall('member.getMemberMenu', 'after', $member_info);
		// Display a menu for editting member info to a top administrator
		if($logged_info->is_admin == 'Y')
		{
			$url = getUrl('','module','admin','act','dispMemberAdminInsert','member_srl',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'cmd_manage_member_info',$icon_path,'MemberModifyInfo');

			$url = getUrl('','module','member','act','dispMemberSpammer','member_srl',$member_srl,'module_srl',0);
			$oMemberController->addMemberPopupMenu($url,'cmd_spammer',$icon_path,'popup');
			
			$url = getUrl('','module','admin','act','dispDocumentAdminList','search_target','member_srl','search_keyword',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'cmd_trace_document',$icon_path,'TraceMemberDocument');

			$url = getUrl('','module','admin','act','dispCommentAdminList','search_target','member_srl','search_keyword',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'cmd_trace_comment',$icon_path,'TraceMemberComment');
		}
		// Change a language of pop-up menu
		$menus = Context::get('member_popup_menu_list');
		$menus_count = count($menus);
		for($i=0;$i<$menus_count;$i++)
		{
			$menus[$i]->str = lang($menus[$i]->str);
		}
		// Get a list of finalized pop-up menu
		$this->add('menus', $menus);
	}

	/**
	 * @brief Check if logged-in
	 */
	public static function isLogged()
	{
		return Rhymix\Framework\Session::getMemberSrl() ? true : false;
	}

	/**
	 * @brief Return session information of the logged-in user
	 */
	public static function getLoggedInfo()
	{
		return Context::get('logged_info');
	}

	/**
	 * @brief Return member information with user_id
	 * 
	 * @return object|null
	 */
	public static function getMemberInfoByUserID($user_id)
	{
		if(!$user_id) return;

		$args = new stdClass;
		$args->user_id = $user_id;
		$output = executeQuery('member.getMemberInfo', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data) return;

		$member_info = self::arrangeMemberInfo($output->data);

		return $member_info;
	}

	/**
	 * @brief Return member information with email_address
	 * 
	 * @return object|null
	 */
	public static function getMemberInfoByEmailAddress($email_address)
	{
		if(!$email_address) return;

		$args = new stdClass();
		$args->email_address = $email_address;
		$output = executeQuery('member.getMemberInfoByEmailAddress', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data) return;

		$member_info = self::arrangeMemberInfo($output->data);
		return $member_info;
	}

	/**
	 * @brief Return member information with phone number
	 * 
	 * @return object|null
	 */
	public static function getMemberInfoByPhoneNumber($phone_number, $phone_country = null)
	{
		if(!$phone_number) return;
		if($phone_country)
		{
			if(preg_match('/^[A-Z]{3}$/', $phone_country))
			{
				$phone_country = array($phone_country, preg_replace('/[^0-9]/', '', Rhymix\Framework\i18n::getCallingCodeByCountryCode($phone_country)));
			}
			else
			{
				$phone_country = array(preg_replace('/[^0-9]/', '', $phone_country), Rhymix\Framework\i18n::getCountryCodeByCallingCode($phone_country));
			}
		}
		
		$args = new stdClass();
		$args->phone_number = $phone_number;
		$args->phone_country = $phone_country;
		$output = executeQuery('member.getMemberInfoByPhoneNumber', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data) return;

		$member_info = self::arrangeMemberInfo($output->data);
		return $member_info;
	}

	/**
	 * @brief Return member information with member_srl
	 * 
	 * @return object
	 */
	public static function getMemberInfoByMemberSrl($member_srl, $site_srl = 0)
	{
		if(!$member_srl) return new stdClass;

		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__'][$member_srl]))
		{
			$cache_key = sprintf('member:member_info:%d', $member_srl);
			$GLOBALS['__member_info__'][$member_srl] = Rhymix\Framework\Cache::get($cache_key);
			if(!$GLOBALS['__member_info__'][$member_srl])
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$output = executeQuery('member.getMemberInfoByMemberSrl', $args);
				if(!$output->data)
				{
					return new stdClass;
				}
				
				$member_info = self::arrangeMemberInfo($output->data, $site_srl);
				if($output->toBool())
				{
					Rhymix\Framework\Cache::set($cache_key, $member_info);
				}
			}
		}

		return $GLOBALS['__member_info__'][$member_srl];
	}

	/**
	 * @brief Shortcut to getMemberInfoByMemberSrl()
	 * 
	 * @param int $member_srl
	 * @return object
	 */
	public static function getMemberInfo($member_srl)
	{
		return self::getMemberInfoByMemberSrl(intval($member_srl));
	}

	/**
	 * @brief Add member info from extra_vars and other information
	 */
	public static function arrangeMemberInfo($info, $site_srl = 0)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__'][$info->member_srl]))
		{
			$config = self::getMemberConfig();

			$info->profile_image = self::getProfileImage($info->member_srl);
			$info->image_name = self::getImageName($info->member_srl);
			$info->image_mark = self::getImageMark($info->member_srl);
			if($config->group_image_mark=='Y')
			{
				$info->group_mark = self::getGroupImageMark($info->member_srl,$site_srl);
			}
			$info->signature = self::getSignature($info->member_srl);
			$info->group_list = self::getMemberGroups($info->member_srl, $site_srl);
			$info->is_site_admin = ModuleModel::isSiteAdmin($info) ? true : false;

			$extra_vars = unserialize($info->extra_vars);
			unset($info->extra_vars);
			if($extra_vars)
			{
				foreach($extra_vars as $key => $val)
				{
					if(!is_array($val) && !is_object($val) && strpos($val, '|@|') !== FALSE)
					{
						$val = explode('|@|', $val);
					}
					if(!isset($info->{$key}))
					{
						$info->{$key} = $val;
					}
				}
			}

			if(strlen($info->find_account_answer) == 32 && preg_match('/[a-zA-Z0-9]+/', $info->find_account_answer))
			{
				$info->find_account_answer = null;
			}

			// XSS defence
			$oSecurity = new Security($info);
			$oSecurity->encodeHTML('user_id', 'user_name', 'nick_name', 'find_account_answer', 'description', 'address.', 'group_list..');

			$info->homepage = strip_tags($info->homepage);
			$info->blog = strip_tags($info->blog);

			if($extra_vars)
			{
				foreach($extra_vars as $key => $val)
				{
					if(is_array($val))
					{
						$oSecurity->encodeHTML($key . '.');
					}
					else
					{
						$oSecurity->encodeHTML($key);
					}
				}
			}

			// Check format.
			$oValidator = new Validator();
			if(!$oValidator->applyRule('url', $info->homepage))
			{
				$info->homepage = '';
			}

			if(!$oValidator->applyRule('url', $info->blog))
			{
				$info->blog = '';
			}

			$GLOBALS['__member_info__'][$info->member_srl] = $info;
		}

		return $GLOBALS['__member_info__'][$info->member_srl];
	}

	/**
	 * @brief Get member_srl corresponding to userid
	 */
	public static function getMemberSrlByUserID($user_id)
	{
		$args = new stdClass();
		$args->user_id = $user_id;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Get member_srl corresponding to EmailAddress
	 */
	public static function getMemberSrlByEmailAddress($email_address)
	{
		$args = new stdClass();
		$args->email_address = $email_address;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Get member_srl corresponding to phone number
	 */
	public static function getMemberSrlByPhoneNumber($phone_number, $phone_country = null)
	{
		$args = new stdClass();
		$args->phone_number = $phone_number;
		$args->phone_country = $phone_country;
		$output = executeQueryArray('member.getMemberSrl', $args);
		return count($output->data) ? array_first($output->data)->member_srl : null;
	}

	/**
	 * @brief Get member_srl corresponding to nickname
	 */
	public static function getMemberSrlByNickName($nick_name)
	{
		$args = new stdClass();
		$args->nick_name = $nick_name;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Return member_srl of the current logged-in user
	 */
	public static function getLoggedMemberSrl()
	{
		return Rhymix\Framework\Session::getMemberSrl();
	}

	/**
	 * @brief Return user_id of the current logged-in user
	 */
	public static function getLoggedUserID()
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info || !$logged_info->member_srl) return;
		return $logged_info->user_id;
	}

	/**
	 * @brief Get a list of groups which the member_srl belongs to
	 */
	public static function getMemberGroups($member_srl, $site_srl = 0, $force_reload = false)
	{
		// cache controll
		$cache_key = sprintf('member:member_groups:%d:site:%d', $member_srl, $site_srl);
		$group_list = Rhymix\Framework\Cache::get($cache_key);

		if(!isset($GLOBALS['__member_groups__'][$member_srl]) || $force_reload)
		{
			if(!$group_list)
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->site_srl = $site_srl;
				$output = executeQueryArray('member.getMemberGroups', $args);
				$group_list = $output->data;
				if (!count($group_list))
				{
					$default_group = self::getDefaultGroup($site_srl);
					MemberController::getInstance()->addMemberToGroup($member_srl, $default_group->group_srl, $site_srl);
					$group_list[$default_group->group_srl] = $default_group->title;
				}
				//insert in cache
				if ($output->toBool())
				{
					Rhymix\Framework\Cache::set($cache_key, $group_list, 0, true);
				}
			}
			if(!$group_list) return array();

			foreach($group_list as $group)
			{
				$result[$group->group_srl] = $group->title;
			}
			$GLOBALS['__member_groups__'][$member_srl] = $result;
		}
		return $GLOBALS['__member_groups__'][$member_srl];
	}

	/**
	 * @brief Get a list of groups which member_srls belong to
	 */
	public static function getMembersGroups($member_srls, $site_srl = 0)
	{
		$args = new stdClass;
		$args->member_srls = implode(',',$member_srls);
		$args->site_srl = $site_srl;
		$args->sort_index = 'list_order';
		$output = executeQueryArray('member.getMembersGroups', $args);
		if(!$output->data) return array();

		$result = array();
		foreach($output->data as $key=>$val)
		{
			$result[$val->member_srl][] = $val->title;
		}
		return $result;
	}

	/**
	 * @brief Get a default group
	 */
	public static function getDefaultGroup($site_srl = 0)
	{
		$cache_key = sprintf('member:default_group:site:%d', $site_srl);
		$default_group = Rhymix\Framework\Cache::get($cache_key);

		if(!$default_group)
		{
			$args = new stdClass();
			$args->site_srl = $site_srl;
			$output = executeQuery('member.getDefaultGroup', $args);
			$default_group = $output->data;
			if($output->toBool())
			{
				Rhymix\Framework\Cache::set($cache_key, $default_group, 0, true);
			}
		}

		return $default_group;
	}

	/**
	 * @brief Get an admin group
	 */
	public static function getAdminGroup($columnList = array())
	{
		$args = new stdClass;
		$output = executeQuery('member.getAdminGroup', $args, $columnList);
		return $output->data;
	}

	/**
	 * @brief Get group info corresponding to group_srl
	 */
	public static function getGroup($group_srl, $columnList = array())
	{
		$args = new stdClass;
		$args->group_srl = $group_srl;
		$output = executeQuery('member.getGroup', $args, $columnList);
		return $output->data;
	}

	/**
	 * @brief Get a list of groups
	 */
	public static function getGroups($site_srl = 0)
	{
		if(!isset($GLOBALS['__group_info__'][$site_srl]))
		{
			$result = array();

			if(!isset($site_srl))
			{
				$site_srl = 0;
			}

			$group_list = Rhymix\Framework\Cache::get("member:member_groups:site:$site_srl");

			if(!$group_list)
			{
				$args = new stdClass();
				$args->site_srl = $site_srl;
				$args->sort_index = 'list_order';
				$args->order_type = 'asc';
				$output = executeQueryArray('member.getGroups', $args);
				$group_list = $output->data;
				if($output->toBool())
				{
					Rhymix\Framework\Cache::set("member:member_groups:site:$site_srl", $group_list, 0, true);
				}
			}

			if(!$group_list)
			{
				return array();
			}

			foreach($group_list as $val)
			{
				$result[$val->group_srl] = $val;
			}

			$GLOBALS['__group_info__'][$site_srl] = $result;
		}
		return $GLOBALS['__group_info__'][$site_srl];
	}

	/**
	 * @deprecated
	 */
	public static function getApiGroups()
	{
		$siteSrl = Context::get('siteSrl');
		$groupInfo = self::getGroups($siteSrl);
		//$this->add($groupInfo);
	}

	/**
	 * @brief Get a list of member join forms
	 *
	 * This method works as an extend filter of modules/member/tpl/filter/insert.xml.
	 * To use as extend_filter, the argument should be boolean.
	 * When the argument is true, it returns object result in type of filter.
	 */
	public static function getJoinFormList($filter_response = false)
	{
		global $lang;
		// Set to ignore if a super administrator.
		$logged_info = Context::get('logged_info');

		if(!self::$_join_form_list)
		{
			// Argument setting to sort list_order column
			$args = new stdClass();
			$args->sort_index = "list_order";
			$output = executeQueryArray('member.getJoinFormList', $args);
			// NULL if output data deosn't exist
			$join_form_list = $output->data;
			if(!$join_form_list) return NULL;
			// Need to unserialize because serialized array is inserted into DB in case of default_value
			if(!is_array($join_form_list)) $join_form_list = array($join_form_list);
			$join_form_count = count($join_form_list);
			for($i=0;$i<$join_form_count;$i++)
			{
				$join_form_list[$i]->column_name = strtolower($join_form_list[$i]->column_name);

				$member_join_form_srl = $join_form_list[$i]->member_join_form_srl;
				$column_type = $join_form_list[$i]->column_type;
				$column_name = $join_form_list[$i]->column_name;
				$column_title = $join_form_list[$i]->column_title;
				$default_value = $join_form_list[$i]->default_value;
				// Add language variable
				if(!isset($lang->extend_vars)) $lang->extend_vars = array();
				$lang->extend_vars[$column_name] = $column_title;
				// unserialize if the data type if checkbox, select and so on
				if(in_array($column_type, array('checkbox','select','radio')))
				{
					$join_form_list[$i]->default_value = unserialize($default_value);
					if(!$join_form_list[$i]->default_value[0]) $join_form_list[$i]->default_value = '';
				}
				else
				{
					$join_form_list[$i]->default_value = '';
				}

				$list[$member_join_form_srl] = $join_form_list[$i];
			}
			self::$_join_form_list = $list;
		}
		// Get object style if the filter_response is true
		if($filter_response && count(self::$_join_form_list))
		{
			foreach(self::$_join_form_list as $key => $val)
			{
				if($val->is_active != 'Y') continue;
				$obj = new stdClass;
				$obj->type = $val->column_type;
				$obj->name = $val->column_name;
				$obj->lang = $val->column_title;
				if($logged_info->is_admin != 'Y') $obj->required = $val->required=='Y'?true:false;
				else $obj->required = false;
				$filter_output[] = $obj;

				$open_obj = new stdClass;
				$open_obj->name = 'open_'.$val->column_name;
				$open_obj->required = false;
				$filter_output[] = $open_obj;

			}
			return $filter_output;
		}
		// Return the result
		return self::$_join_form_list;
	}

	/**
	 * get used join form list.
	 *
	 * @return array $joinFormList
	 */
	public static function getUsedJoinFormList()
	{
		$args = new stdClass();
		$args->sort_index = "list_order";
		$output = executeQueryArray('member.getJoinFormList', $args);

		if(!$output->toBool())
		{
			return array();
		}

		$joinFormList = array();
		foreach($output->data as $val)
		{
			if($val->is_active != 'Y')
			{
				continue;
			}

			$joinFormList[] = $val;
		}

		return $joinFormList;
	}

	/**
	 * @brief Combine extend join form and member information (used to modify member information)
	 */
	public static function getCombineJoinForm($member_info)
	{
		$extend_form_list = self::getJoinFormlist();
		if(!$extend_form_list) return;
		// Member info is open only to an administrator and him/herself when is_private is true.
		$logged_info = Context::get('logged_info');

		foreach($extend_form_list as $srl => $item)
		{
			$column_name = $item->column_name;
			$value = $member_info->{$column_name};

			// Change values depening on the type of extend form
			switch($item->column_type)
			{
				case 'checkbox' :
					if($value && !is_array($value)) $value = array($value);
					break;
				case 'text' :
				case 'homepage' :
				case 'email_address' :
				case 'tel' :
				case 'textarea' :
				case 'select' :
				case 'kr_zip' :
					break;
			}

			$extend_form_list[$srl]->value = $value;

			if($member_info->{'open_'.$column_name}=='Y') $extend_form_list[$srl]->is_opened = true;
			else $extend_form_list[$srl]->is_opened = false;
		}
		return $extend_form_list;
	}

	/**
	 * @brief Get a join form
	 */
	public static function getJoinForm($member_join_form_srl)
	{
		$args = new stdClass();
		$args->member_join_form_srl = $member_join_form_srl;
		$output = executeQuery('member.getJoinForm', $args);
		$join_form = $output->data;
		if(!$join_form) return NULL;

		$column_type = $join_form->column_type;
		$default_value = $join_form->default_value;

		if(in_array($column_type, array('checkbox','select','radio')))
		{
			$join_form->default_value = unserialize($default_value);
		}
		else
		{
			$join_form->default_value = '';
		}

		return $join_form;
	}

	/**
	 * @brief Get a list of denied IDs
	 */
	public static function getDeniedIDList()
	{
		if(!isset(self::$_denied_id_list))
		{
			$args = new stdClass();
			$args->sort_index = "list_order";
			$args->page = Context::get('page');
			$args->list_count = 40;
			$args->page_count = 10;

			$output = executeQueryArray('member.getDeniedIDList', $args);
			self::$_denied_id_list = $output;
		}
		return self::$_denied_id_list;
	}

	public static function getDeniedIDs()
	{
		$output = executeQueryArray('member.getDeniedIDs');
		if(!$output->toBool()) return array();
		return $output->data;
	}

	public static function getDeniedNickNames()
	{
		$output = executeQueryArray('member.getDeniedNickNames');
		if(!$output->toBool())
		{
			return array();
		}

		return $output->data;
	}

	public static function getManagedEmailHosts()
	{
		if(isset(self::$_managed_email_hosts)) {
			return self::$_managed_email_hosts;
		}
		$output = executeQueryArray('member.getManagedEmailHosts');
		if(!$output->toBool())
		{
			return self::$_managed_email_hosts = array();
		}

		return self::$_managed_email_hosts = $output->data;
	}

	/**
	 * @brief Verify if ID is denied
	 */
	public static function isDeniedID($user_id)
	{
		$args = new stdClass();
		$args->user_id = $user_id;
		$output = executeQuery('member.chkDeniedID', $args);
		if($output->data->count) return true;
		return false;
	}

	/**
	 * @brief Verify if nick name is denied
	 */
	public static function isDeniedNickName($nickName)
	{
		$args = new stdClass();
		$args->nick_name = $nickName;
		$output = executeQuery('member.chkDeniedNickName', $args);
		if($output->data->count) return true;
		if(!$output->toBool())
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief Verify if email_host from email_address is denied
	 */
	public static function isDeniedEmailHost($email_address)
	{
		$email_address = trim($email_address);
		$config = self::getMemberConfig();
		$emailhost_check = $config->emailhost_check;
		$managedHosts = self::getManagedEmailHosts();
		if(count($managedHosts) < 1) return FALSE;

		static $return;
		if(!isset($return[$email_address]))
		{
			$email = explode('@',$email_address);
			$email_hostname = $email[1];
			if(!$email_hostname) return TRUE;

			foreach($managedHosts as $managedHost)
			{
				if($managedHost->email_host && strtolower($managedHost->email_host) == strtolower($email_hostname))
				{
					$return[$email_address] = TRUE;
				}
			}
			if(!$return[$email_address])
			{
				$return[$email_address] = FALSE;
			}
		}

		if($emailhost_check == 'prohibited')
		{
			return $return[$email_address];
		}
		else
		{
			return (!$return[$email_address]);
		}
	}

	/**
	 * @brief Get information of the profile image
	 */
	public static function getProfileImage($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['profile_image']))
		{
			$GLOBALS['__member_info__']['profile_image'] = [];
		}
		if(!array_key_exists($member_srl, $GLOBALS['__member_info__']['profile_image']))
		{
			$GLOBALS['__member_info__']['profile_image'][$member_srl] = null;
			foreach(['jpg', 'jpeg', 'gif', 'png'] as $ext)
			{
				$image_name_file = sprintf('files/member_extra_info/profile_image/%s%d.%s', getNumberingPath($member_srl), $member_srl, $ext);
				if(file_exists($image_name_file))
				{
					list($width, $height, $type, $attrs) = getimagesize($image_name_file);
					$info = new stdClass();
					$info->width = $width;
					$info->height = $height;
					$info->src = Context::getRequestUri().$image_name_file . '?' . date('YmdHis', filemtime($image_name_file));
					$info->file = './'.$image_name_file;
					$GLOBALS['__member_info__']['profile_image'][$member_srl] = $info;
					break;
				}
			}
		}

		return $GLOBALS['__member_info__']['profile_image'][$member_srl];
	}

	/**
	 * @brief Get the image name
	 */
	public static function getImageName($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['image_name']))
		{
			$GLOBALS['__member_info__']['image_name'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['image_name'][$member_srl]))
		{
			$image_name_file = sprintf('files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if(file_exists($image_name_file))
			{
				list($width, $height, $type, $attrs) = getimagesize($image_name_file);
				$info = new stdClass;
				$info->width = $width;
				$info->height = $height;
				$info->src = Context::getRequestUri().$image_name_file. '?' . date('YmdHis', filemtime($image_name_file));
				$info->file = './'.$image_name_file;
				$GLOBALS['__member_info__']['image_name'][$member_srl] = $info;
			}
			else
			{
				$GLOBALS['__member_info__']['image_name'][$member_srl] = '';
			}
		}
		return $GLOBALS['__member_info__']['image_name'][$member_srl];
	}

	/**
	 * @brief Get the image mark
	 */
	public static function getImageMark($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['image_mark']))
		{
			$GLOBALS['__member_info__']['image_mark'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['image_mark'][$member_srl]))
		{
			$image_mark_file = sprintf('files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if(file_exists($image_mark_file))
			{
				list($width, $height, $type, $attrs) = getimagesize($image_mark_file);
				$info = new stdClass;
				$info->width = $width;
				$info->height = $height;
				$info->src = Context::getRequestUri().$image_mark_file . '?' . date('YmdHis', filemtime($image_mark_file));
				$info->file = './'.$image_mark_file;
				$GLOBALS['__member_info__']['image_mark'][$member_srl] = $info;
			}
			else
			{
				$GLOBALS['__member_info__']['image_mark'][$member_srl] = '';
			}
		}

		return $GLOBALS['__member_info__']['image_mark'][$member_srl];
	}


	/**
	 * @brief Get the image mark of the group
	 */
	public static function getGroupImageMark($member_srl,$site_srl=0)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['group_image_mark']))
		{
			$GLOBALS['__member_info__']['group_image_mark'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['group_image_mark'][$member_srl]))
		{
			$config = ModuleModel::getModuleConfig('member');
			if($config->group_image_mark!='Y')
			{
				return null;
			}
			
			$info = null;
			$member_group = self::getMemberGroups($member_srl, $site_srl);
			$groups_info = self::getGroups($site_srl);
			if(count($member_group) > 0 && is_array($member_group))
			{
				$memberGroups = array_keys($member_group);
				foreach($groups_info as $group_srl=>$group_info)
				{
					if(in_array($group_srl, $memberGroups))
					{
						if($group_info->image_mark)
						{
							$info = new stdClass();
							$info->title = $group_info->title;
							$info->description = $group_info->description;
							$info->src = $group_info->image_mark;
							if(preg_match('@^https?://@', $info->src))
							{
								$localpath = str_replace('/./', '/', parse_url($info->src, PHP_URL_PATH));
								if(file_exists($_SERVER['DOCUMENT_ROOT'] . $localpath))
								{
									$info->src = $localpath . '?' . date('YmdHis', filemtime($_SERVER['DOCUMENT_ROOT'] . $localpath));
								}
							}
							$GLOBALS['__member_info__']['group_image_mark'][$member_srl] = $info;
							break;
						}
					}
				}
			}
			if (!$info)
			{
				$GLOBALS['__member_info__']['group_image_mark'][$member_srl] = '';
			}
		}
		
		return $GLOBALS['__member_info__']['group_image_mark'][$member_srl];
	}

	/**
	 * @brief Get user's signature
	 */
	public static function getSignature($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']))
		{
			$GLOBALS['__member_info__'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['signature']))
		{
			$GLOBALS['__member_info__']['signature'] = [];
		}
		if(!isset($GLOBALS['__member_info__']['signature'][$member_srl]))
		{
			$filename = sprintf('files/member_extra_info/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
			if(file_exists($filename))
			{
				$signature = preg_replace('/<\?.*\?>/', '', FileHandler::readFile($filename));
				
				// retroact
				$config = self::getMemberConfig();
				if($config->signature_html_retroact == 'Y' && $config->signature_html == 'N' && preg_match('/<[^br]+>/i', $signature))
				{
					$signature = preg_replace('/(\r?\n)+/', "\n", $signature);
					return MemberController::getInstance()->putSignature($member_srl, $signature);
				}
				
				$GLOBALS['__member_info__']['signature'][$member_srl] = $signature;
			}
			else
			{
				$GLOBALS['__member_info__']['signature'][$member_srl] = '';
			}
		}
		
		return $GLOBALS['__member_info__']['signature'][$member_srl];
	}

	/**
	 * @brief Compare plain text password to the password saved in DB
	 * @param string $hashed_password The hash that was saved in DB
	 * @param string $password_text The password to check
	 * @param int $member_srl Set this to member_srl when comparing a member's password (optional)
	 * @return bool
	 */
	public static function isValidPassword($hashed_password, $password_text, $member_srl=null)
	{
		// False if no password in entered
		if(!$password_text)
		{
			return false;
		}
		
		// Check the password
		$password_match = false;
		$current_algorithm = false;
		$possible_algorithms = Rhymix\Framework\Password::checkAlgorithm($hashed_password);
		foreach ($possible_algorithms as $algorithm)
		{
			if (Rhymix\Framework\Password::checkPassword($password_text, $hashed_password, $algorithm))
			{
				$password_match = true;
				$current_algorithm = $algorithm;
				break;
			}
		}
		if (!$password_match)
		{
			return false;
		}
		
		// Update the encryption method if necessary
		$config = self::getMemberConfig();
		if($member_srl > 0 && $config->password_hashing_auto_upgrade != 'N')
		{
			$required_algorithm = Rhymix\Framework\Password::getDefaultAlgorithm();
			if ($required_algorithm !== $current_algorithm)
			{
				$need_upgrade = true;
			}
			else
			{
				$required_work_factor = Rhymix\Framework\Password::getWorkFactor();
				$current_work_factor = Rhymix\Framework\Password::checkWorkFactor($hashed_password);
				if ($current_work_factor !== false && $required_work_factor > $current_work_factor)
				{
					$need_upgrade = true;
				}
				else
				{
					$need_upgrade = false;
				}
			}
			
			if ($need_upgrade)
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->hashed_password = self::hashPassword($password_text, $required_algorithm);
				MemberController::getInstance()->updateMemberPassword($args);
			}
		}
		
		return true;
	}
	
	/**
	 * @brief Create a hash of plain text password
	 * @param string $password_text The password to hash
	 * @param string $algorithm The algorithm to use (optional, only set this when you want to use a non-default algorithm)
	 * @return string
	 */
	public static function hashPassword($password_text, $algorithm = null)
	{
		return Rhymix\Framework\Password::hashPassword($password_text, $algorithm);
	}
	
	public static function checkPasswordStrength($password, $strength)
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y') return true;
		
		if($strength == NULL)
		{
			$config = self::getMemberConfig();
			$strength = $config->password_strength?$config->password_strength:'normal';
		}
		
		$length = strlen($password);
		
		switch ($strength) {
			case 'high':
				if($length < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) return false;
				/* no break */
				
			case 'normal':
				if($length < 6 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) return false;
				break;
				
			case 'low':
				if($length < 4) return false;
				break;
		}
		
		return true;
	}
	
	public static function getAdminGroupSrl($site_srl = 0)
	{
		$groupSrl = 0;
		$output = self::getGroups($site_srl);
		if(is_array($output))
		{
			foreach($output AS $key=>$value)
			{
				if($value->is_admin == 'Y')
				{
					$groupSrl = $value->group_srl;
					break;
				}
			}
		}
		return $groupSrl;
	}
	
	public static function getMemberModifyNicknameLog($page = 1, $member_srl = null)
	{
		$search_keyword = Context::get('search_keyword');
		$search_target = Context::get('search_target');
		
		// $this->user 에 재대로 된 회원 정보가 들어 가지 않음.
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->page = $page;
		if($logged_info->is_admin == 'Y')
		{
			if($search_keyword && $search_keyword)
			{
				switch ($search_target)
				{
					case "before":
						$args->before_nick_name = $search_keyword;
						break;
					case "after":
						$args->after_nick_name = $search_keyword;
						break;
					case "user_id":
						if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
						$args->user_id = $search_keyword;
						break;
					case "member_srl":
						$args->member_srl = intval($search_keyword);
						break;
					default:
						break;
				}
				$output = executeQuery('member.getMemberModifyNickName', $args);
				
				return $output;
			}
		}
		
		$args->member_srl = $member_srl;
		$output = executeQuery('member.getMemberModifyNickName', $args);
		
		return $output;
	}
}
/* End of file member.model.php */
/* Location: ./modules/member/member.model.php */
