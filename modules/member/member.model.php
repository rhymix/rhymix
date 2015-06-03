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
	var $join_form_list = NULL;

	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Return member's configuration
	 */
	function getMemberConfig()
	{
		static $member_config;

		if($member_config)
		{
			return $member_config;
		}

		// Get member configuration stored in the DB
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('member');

		if(!$config->signupForm || !is_array($config->signupForm))
		{
			$oMemberAdminController = getAdminController('member');
			$identifier = ($config->identifier) ? $config->identifier : 'email_address';
			$config->signupForm = $oMemberAdminController->createSignupForm($identifier);
		}
		//for multi language
		foreach($config->signupForm AS $key=>$value)
		{
			$config->signupForm[$key]->title = ($value->isDefaultForm) ? Context::getLang($value->name) : $value->title;
			if($config->signupForm[$key]->isPublic != 'N') $config->signupForm[$key]->isPublic = 'Y';
			if($value->name == 'find_account_question') $config->signupForm[$key]->isPublic = 'N';
		}

		// Get terms of user
		$config->agreement = memberModel::_getAgreement();

		if(!$config->webmaster_name) $config->webmaster_name = 'webmaster';
		if(!$config->image_name_max_width) $config->image_name_max_width = 90;
		if(!$config->image_name_max_height) $config->image_name_max_height = 20;
		if(!$config->image_mark_max_width) $config->image_mark_max_width = 20;
		if(!$config->image_mark_max_height) $config->image_mark_max_height = 20;
		if(!$config->profile_image_max_width) $config->profile_image_max_width = 90;
		if(!$config->profile_image_max_height) $config->profile_image_max_height = 90;
		if(!$config->skin) $config->skin = 'default';
		if(!$config->colorset) $config->colorset = 'white';
		if(!$config->editor_skin || $config->editor_skin == 'default') $config->editor_skin = 'ckeditor';
		if(!$config->group_image_mark) $config->group_image_mark = "N";

		if(!$config->identifier) $config->identifier = 'user_id';

		if(!$config->max_error_count) $config->max_error_count = 10;
		if(!$config->max_error_count_time) $config->max_error_count_time = 300;

		if(!$config->signature_editor_skin || $config->signature_editor_skin == 'default') $config->signature_editor_skin = 'ckeditor';
		if(!$config->sel_editor_colorset) $config->sel_editor_colorset = 'moono';

		$member_config = $config;

		return $config;
	}

	function _getAgreement()
	{
		$agreement_file = _XE_PATH_.'files/member_extra_info/agreement_' . Context::get('lang_type') . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$db_info = Context::getDBInfo();
		$agreement_file = _XE_PATH_.'files/member_extra_info/agreement_' . $db_info->lang_type . '.txt';
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
	function getMemberMenu()
	{
		// Get member_srl of he target member and logged info of the current user
		$member_srl = Context::get('target_srl');
		$mid = Context::get('cur_mid');
		$logged_info = Context::get('logged_info');
		$act = Context::get('cur_act');
		// When click user's own nickname
		if($member_srl == $logged_info->member_srl) $member_info = $logged_info;
		// When click other's nickname
		else $member_info = $this->getMemberInfoByMemberSrl($member_srl);

		$member_srl = $member_info->member_srl;
		if(!$member_srl) return;
		// List variables
		$user_id = $member_info->user_id;
		$user_name = $member_info->user_name;

		ModuleHandler::triggerCall('member.getMemberMenu', 'before', $null);

		$oMemberController = getController('member');
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
			foreach($this->module_config->signupForm as $field)
			{
				if($field->name == 'email_address')
				{
					$email_config = $field;
					break;
				}
			}

			// Send an email only if email address is public
			if(($logged_info->is_admin == 'Y' || $email_config->isPublic == 'Y') && $member_info->email_address)
			{
				$url = 'mailto:'.htmlspecialchars($member_info->email_address, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
				$oMemberController->addMemberPopupMenu($url,'cmd_send_email',$icon_path);
			}
		}
		// View homepage info
		if($member_info->homepage)
			$oMemberController->addMemberPopupMenu(htmlspecialchars($member_info->homepage, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), 'homepage', '', 'blank');
		// View blog info
		if($member_info->blog)
			$oMemberController->addMemberPopupMenu(htmlspecialchars($member_info->blog, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), 'blog', '', 'blank');
		// Call a trigger (after)
		ModuleHandler::triggerCall('member.getMemberMenu', 'after', $null);
		// Display a menu for editting member info to a top administrator
		if($logged_info->is_admin == 'Y')
		{
			$url = getUrl('','module','admin','act','dispMemberAdminInsert','member_srl',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'cmd_manage_member_info',$icon_path,'MemberModifyInfo');

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
			$menus[$i]->str = Context::getLang($menus[$i]->str);
		}
		// Get a list of finalized pop-up menu
		$this->add('menus', $menus);
	}

	/**
	 * @brief Check if logged-in
	 */
	function isLogged() {
		if($_SESSION['is_logged'])
		{
			if(Mobile::isFromMobilePhone())
			{
				return true;
			}
			else
			{
				if(ip2long($_SESSION['ipaddress']) >> 8 == ip2long($_SERVER['REMOTE_ADDR']) >> 8)
				{
					return true;
				}
			}
		}

		$_SESSION['is_logged'] = false;
		return false;
	}

	/**
	 * @brief Return session information of the logged-in user
	 */
	function getLoggedInfo()
	{
		// Return session info if session info is requested and the user is logged-in
		if($this->isLogged())
		{
			$logged_info = Context::get('logged_info');
			// Admin/Group list defined depending on site_module_info
			$site_module_info = Context::get('site_module_info');
			if($site_module_info->site_srl)
			{
				$logged_info->group_list = $this->getMemberGroups($logged_info->member_srl, $site_module_info->site_srl);
				// Add is_site_admin bool variable into logged_info if site_administrator is
				$oModuleModel = getModel('module');
				if($oModuleModel->isSiteAdmin($logged_info)) $logged_info->is_site_admin = true;
				else $logged_info->is_site_admin = false;
			}
			else
			{
				// Register a default group if the site doesn't have a member group
				if(count($logged_info->group_list) === 0)
				{
					$default_group = $this->getDefaultGroup(0);
					$oMemberController = getController('member');
					$oMemberController->addMemberToGroup($logged_info->member_srl, $default_group->group_srl, 0);
					$groups[$default_group->group_srl] = $default_group->title;
					$logged_info->group_list = $groups;
				}

				$logged_info->is_site_admin = false;
			}
			Context::set('logged_info', $logged_info);

			return $logged_info;
		}
		return NULL;
	}

	/**
	 * @brief Return member information with user_id
	 */
	function getMemberInfoByUserID($user_id, $columnList = array())
	{
		if(!$user_id) return;

		$args = new stdClass;
		$args->user_id = $user_id;
		$output = executeQuery('member.getMemberInfo', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data) return;

		$member_info = $this->arrangeMemberInfo($output->data);

		return $member_info;
	}

	/**
	 * @brief Return member information with email_address
	 */
	function getMemberInfoByEmailAddress($email_address)
	{
		if(!$email_address) return;

		$args = new stdClass();
		
		$db_info = Context::getDBInfo ();
		if($db_info->master_db['db_type'] == "cubrid")
		{
			$args->email_address = strtolower($email_address);
			$output = executeQuery('member.getMemberInfoByEmailAddressForCubrid', $args);
		}
		else
		{
			$args->email_address = $email_address;
			$output = executeQuery('member.getMemberInfoByEmailAddress', $args);
		}
		
		if(!$output->toBool()) return $output;
		if(!$output->data) return;

		$member_info = $this->arrangeMemberInfo($output->data);
		return $member_info;
	}

	/**
	 * @brief Return member information with member_srl
	 */
	function getMemberInfoByMemberSrl($member_srl, $site_srl = 0, $columnList = array())
	{
		if(!$member_srl) return;

		//columnList size zero... get full member info
		if(!$GLOBALS['__member_info__'][$member_srl] || count($columnList) == 0)
		{
			$GLOBALS['__member_info__'][$member_srl] = false;

			$oCacheHandler = CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				$columnList = array();
				$object_key = 'member_info:' . getNumberingPath($member_srl) . $member_srl;
				$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
				$GLOBALS['__member_info__'][$member_srl] = $oCacheHandler->get($cache_key);
			}

			if($GLOBALS['__member_info__'][$member_srl] === false)
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$output = executeQuery('member.getMemberInfoByMemberSrl', $args, $columnList);
				if(!$output->data) 
				{
					if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, new stdClass);
					return;
				}
				$this->arrangeMemberInfo($output->data, $site_srl);

				//insert in cache
				if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $GLOBALS['__member_info__'][$member_srl]);
			}
		}

		return $GLOBALS['__member_info__'][$member_srl];
	}

	/**
	 * @brief Add member info from extra_vars and other information
	 */
	function arrangeMemberInfo($info, $site_srl = 0)
	{
		if(!$GLOBALS['__member_info__'][$info->member_srl])
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('member');


			$info->profile_image = $this->getProfileImage($info->member_srl);
			$info->image_name = $this->getImageName($info->member_srl);
			$info->image_mark = $this->getImageMark($info->member_srl);
			if($config->group_image_mark=='Y')
			{
				$info->group_mark = $this->getGroupImageMark($info->member_srl,$site_srl);
			}
			$info->signature = $this->getSignature($info->member_srl);
			$info->group_list = $this->getMemberGroups($info->member_srl, $site_srl);

			$extra_vars = unserialize($info->extra_vars);
			unset($info->extra_vars);
			if($extra_vars)
			{
				foreach($extra_vars as $key => $val)
				{
					if(!is_array($val) && strpos($val, '|@|') !== FALSE) $val = explode('|@|', $val);
					if(!$info->{$key}) $info->{$key} = $val;
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
	function getMemberSrlByUserID($user_id)
	{
		$args = new stdClass();
		$args->user_id = $user_id;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Get member_srl corresponding to EmailAddress
	 */
	function getMemberSrlByEmailAddress($email_address)
	{
		$args = new stdClass();
		$args->email_address = $email_address;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Get member_srl corresponding to nickname
	 */
	function getMemberSrlByNickName($nick_name)
	{
		$args = new stdClass();
		$args->nick_name = $nick_name;
		$output = executeQuery('member.getMemberSrl', $args);
		return $output->data->member_srl;
	}

	/**
	 * @brief Return member_srl of the current logged-in user
	 */
	function getLoggedMemberSrl()
	{
		if(!$this->isLogged()) return;
		return $_SESSION['member_srl'];
	}

	/**
	 * @brief Return user_id of the current logged-in user
	 */
	function getLoggedUserID()
	{
		if(!$this->isLogged()) return;
		$logged_info = Context::get('logged_info');
		return $logged_info->user_id;
	}

	/**
	 * @brief Get a list of groups which the member_srl belongs to
	 */
	function getMemberGroups($member_srl, $site_srl = 0, $force_reload = false)
	{
		static $member_groups = array();

		// cache controll
		$group_list = false;
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'member_groups:' . getNumberingPath($member_srl) . $member_srl . '_'.$site_srl;
			$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
			$group_list = $oCacheHandler->get($cache_key);
		}

		if(!$member_groups[$member_srl][$site_srl] || $force_reload)
		{
			if($group_list === false)
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->site_srl = $site_srl;
				$output = executeQueryArray('member.getMemberGroups', $args);
				$group_list = $output->data;
				//insert in cache
				if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $group_list);
			}
			if(!$group_list) return array();

			foreach($group_list as $group)
			{
				$result[$group->group_srl] = $group->title;
			}
			$member_groups[$member_srl][$site_srl] = $result;
		}
		return $member_groups[$member_srl][$site_srl];
	}

	/**
	 * @brief Get a list of groups which member_srls belong to
	 */
	function getMembersGroups($member_srls, $site_srl = 0)
	{
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
	function getDefaultGroup($site_srl = 0, $columnList = array())
	{
		$default_group = false;
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$columnList = array();
			$object_key = 'default_group_' . $site_srl;
			$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
			$default_group = $oCacheHandler->get($cache_key);
		}

		if($default_group === false)
		{
			$args = new stdClass();
			$args->site_srl = $site_srl;
			$output = executeQuery('member.getDefaultGroup', $args, $columnList);
			$default_group = $output->data;
			if($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($cache_key, $default_group);
			}
		}

		return $default_group;
	}

	/**
	 * @brief Get an admin group
	 */
	function getAdminGroup($columnList = array())
	{
		$output = executeQuery('member.getAdminGroup', $args, $columnList);
		return $output->data;
	}

	/**
	 * @brief Get group info corresponding to group_srl
	 */
	function getGroup($group_srl, $columnList = array())
	{
		$args = new stdClass;
		$args->group_srl = $group_srl;
		$output = executeQuery('member.getGroup', $args, $columnList);
		return $output->data;
	}

	/**
	 * @brief Get a list of groups
	 */
	function getGroups($site_srl = 0)
	{
		if(!$GLOBALS['__group_info__'][$site_srl])
		{
			$result = array();

			if(!isset($site_srl))
			{
				$site_srl = 0;
			}

			$group_list = false;
			$oCacheHandler = CacheHandler::getInstance('object', null, true);
			if($oCacheHandler->isSupport())
			{
				$object_key = 'member_groups:site_'.$site_srl;
				$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
				$group_list = $oCacheHandler->get($cache_key);
			}

			if($group_list === false)
			{
				$args = new stdClass();
				$args->site_srl = $site_srl;
				$args->sort_index = 'list_order';
				$args->order_type = 'asc';
				$output = executeQueryArray('member.getGroups', $args);
				$group_list = $output->data;
				//insert in cache
				if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $group_list);
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

	public function getApiGroups()
	{
		$siteSrl = Context::get('siteSrl');
		$groupInfo = $this->getGroups($siteSrl);

		$this->add($groupInfo);
	}

	/**
	 * @brief Get a list of member join forms
	 *
	 * This method works as an extend filter of modules/member/tpl/filter/insert.xml.
	 * To use as extend_filter, the argument should be boolean.
	 * When the argument is true, it returns object result in type of filter.
	 */
	function getJoinFormList($filter_response = false)
	{
		global $lang;
		// Set to ignore if a super administrator.
		$logged_info = Context::get('logged_info');

		if(!$this->join_form_list)
		{
			// Argument setting to sort list_order column
			$args = new stdClass();
			$args->sort_index = "list_order";
			$output = executeQuery('member.getJoinFormList', $args);
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
			$this->join_form_list = $list;
		}
		// Get object style if the filter_response is true
		if($filter_response && count($this->join_form_list))
		{
			foreach($this->join_form_list as $key => $val)
			{
				if($val->is_active != 'Y') continue;
				unset($obj);
				$obj->type = $val->column_type;
				$obj->name = $val->column_name;
				$obj->lang = $val->column_title;
				if($logged_info->is_admin != 'Y') $obj->required = $val->required=='Y'?true:false;
				else $obj->required = false;
				$filter_output[] = $obj;

				unset($open_obj);
				$open_obj->name = 'open_'.$val->column_name;
				$open_obj->required = false;
				$filter_output[] = $open_obj;

			}
			return $filter_output;
		}
		// Return the result
		return $this->join_form_list;
	}

	/**
	 * get used join form list.
	 *
	 * @return array $joinFormList
	 */
	function getUsedJoinFormList()
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
	function getCombineJoinForm($member_info)
	{
		$extend_form_list = $this->getJoinFormlist();
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
	function getJoinForm($member_join_form_srl)
	{
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
	function getDeniedIDList()
	{
		if(!$this->denied_id_list)
		{
			$args->sort_index = "list_order";
			$args->page = Context::get('page');
			$args->list_count = 40;
			$args->page_count = 10;

			$output = executeQuery('member.getDeniedIDList', $args);
			$this->denied_id_list = $output;
		}
		return $this->denied_id_list;
	}

	function getDeniedIDs()
	{
		$output = executeQueryArray('member.getDeniedIDs');
		if(!$output->toBool()) return array();
		return $output->data;
	}

	function getDeniedNickNames()
	{
		$output = executeQueryArray('member.getDeniedNickNames');
		if(!$output->toBool())
		{
			return array();
		}

		return $output->data;
	}

	/**
	 * @brief Verify if ID is denied
	 */
	function isDeniedID($user_id)
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
	function isDeniedNickName($nickName)
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
	 * @brief Get information of the profile image
	 */
	function getProfileImage($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']['profile_image'][$member_srl]))
		{
			$GLOBALS['__member_info__']['profile_image'][$member_srl] = null;
			$exts = array('gif','jpg','png');
			for($i=0;$i<3;$i++)
			{
				$image_name_file = sprintf('files/member_extra_info/profile_image/%s%d.%s', getNumberingPath($member_srl), $member_srl, $exts[$i]);
				if(file_exists($image_name_file))
				{
					list($width, $height, $type, $attrs) = getimagesize($image_name_file);
					$info = new stdClass();
					$info->width = $width;
					$info->height = $height;
					$info->src = Context::getRequestUri().$image_name_file;
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
	function getImageName($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']['image_name'][$member_srl]))
		{
			$image_name_file = sprintf('files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if(file_exists($image_name_file))
			{
				list($width, $height, $type, $attrs) = getimagesize($image_name_file);
				$info = new stdClass;
				$info->width = $width;
				$info->height = $height;
				$info->src = Context::getRequestUri().$image_name_file;
				$info->file = './'.$image_name_file;
				$GLOBALS['__member_info__']['image_name'][$member_srl] = $info;
			}
			else $GLOBALS['__member_info__']['image_name'][$member_srl] = null;
		}
		return $GLOBALS['__member_info__']['image_name'][$member_srl];
	}

	/**
	 * @brief Get the image mark
	 */
	function getImageMark($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']['image_mark'][$member_srl]))
		{
			$image_mark_file = sprintf('files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if(file_exists($image_mark_file))
			{
				list($width, $height, $type, $attrs) = getimagesize($image_mark_file);
				$info->width = $width;
				$info->height = $height;
				$info->src = Context::getRequestUri().$image_mark_file;
				$info->file = './'.$image_mark_file;
				$GLOBALS['__member_info__']['image_mark'][$member_srl] = $info;
			}
			else $GLOBALS['__member_info__']['image_mark'][$member_srl] = null;
		}

		return $GLOBALS['__member_info__']['image_mark'][$member_srl];
	}


	/**
	 * @brief Get the image mark of the group
	 */
	function getGroupImageMark($member_srl,$site_srl=0)
	{
		if(!isset($GLOBALS['__member_info__']['group_image_mark'][$member_srl]))
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('member');
			if($config->group_image_mark!='Y')
			{
				return null;
			}
			$member_group = $this->getMemberGroups($member_srl,$site_srl);
			$groups_info = $this->getGroups($site_srl);
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
							$GLOBALS['__member_info__']['group_image_mark'][$member_srl] = $info;
							break;
						}
					}
				}
			}
			if (!$info) $GLOBALS['__member_info__']['group_image_mark'][$member_srl] == 'N';
		}
		if ($GLOBALS['__member_info__']['group_image_mark'][$member_srl] == 'N') return null;

		return $GLOBALS['__member_info__']['group_image_mark'][$member_srl];
	}

	/**
	 * @brief Get user's signature
	 */
	function getSignature($member_srl)
	{
		if(!isset($GLOBALS['__member_info__']['signature'][$member_srl]))
		{
			$filename = sprintf('files/member_extra_info/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
			if(file_exists($filename))
			{
				$buff = FileHandler::readFile($filename);
				$signature = preg_replace('/<\?.*\?>/', '', $buff);
				$GLOBALS['__member_info__']['signature'][$member_srl] = $signature;
			}
			else $GLOBALS['__member_info__']['signature'][$member_srl] = null;
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
	function isValidPassword($hashed_password, $password_text, $member_srl=null)
	{
		// False if no password in entered
		if(!$password_text)
		{
			return false;
		}
		
		// Check the password
		$oPassword = new Password();
		$current_algorithm = $oPassword->checkAlgorithm($hashed_password);
		$match = $oPassword->checkPassword($password_text, $hashed_password, $current_algorithm);
		if(!$match)
		{
			return false;
		}
		
		// Update the encryption method if necessary
		$config = $this->getMemberConfig();
		if($member_srl > 0 && $config->password_hashing_auto_upgrade != 'N')
		{
			$need_upgrade = false;
			
			if(!$need_upgrade)
			{
				$required_algorithm = $oPassword->getCurrentlySelectedAlgorithm();
				if($required_algorithm !== $current_algorithm) $need_upgrade = true;
			}
			
			if(!$need_upgrade)
			{
				$required_work_factor = $oPassword->getWorkFactor();
				$current_work_factor = $oPassword->checkWorkFactor($hashed_password);
				if($current_work_factor !== false && $required_work_factor > $current_work_factor) $need_upgrade = true;
			}
			
			if($need_upgrade === true)
			{
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->hashed_password = $this->hashPassword($password_text, $required_algorithm);
				$oMemberController = getController('member');
				$oMemberController->updateMemberPassword($args);
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
	function hashPassword($password_text, $algorithm = null)
	{
		$oPassword = new Password();
		return $oPassword->createHash($password_text, $algorithm);
	}
	
	function checkPasswordStrength($password, $strength)
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y') return true;
		
		if($strength == NULL)
		{
			$config = $this->getMemberConfig();
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
	
	function getAdminGroupSrl($site_srl = 0)
	{
		$groupSrl = 0;
		$output = $this->getGroups($site_srl);
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
}
/* End of file member.model.php */
/* Location: ./modules/member/member.model.php */
