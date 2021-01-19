<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  memberAdminModel
 * @author NAVER (developers@xpressengine.com)
 * admin model class of member module
 */
class memberAdminModel extends member
{
	/**
	 * info of member
	 * @var object
	 */
	var $member_info = NULL;

	/**
	 * info of member groups
	 * @var array
	 */
	var $member_groups = NULL;

	/**
	 * info of sign up form
	 * @var array
	 */
	var $join_form_list = NULL;

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Get a member list
	 * 
	 * @return object|array (object : when member count is 1, array : when member count is more than 1)
	 */
	function getMemberList()
	{
		// Search option
		$args = new stdClass();
		$args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
		$args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
		$args->selected_group_srl = Context::get('selected_group_srl');

		$filter = Context::get('filter_type');
		switch($filter)
		{
			case 'super_admin' : $args->is_admin = 'Y';break;
			case 'site_admin' : $args->member_srls = $this->getSiteAdminMemberSrls();break;
			case 'enable' : $args->is_denied = 'N';break;
			case 'disable' : $args->is_denied = 'Y';break;
		}

		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					break;
				case 'user_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_name = $search_keyword;
					break;
				case 'nick_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_nick_name = $search_keyword;
					$args->html_nick_name = htmlspecialchars($search_keyword, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
					break;
				case 'email_address' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_email_address = $search_keyword;
					break;
				case 'phone_number' :
					if($search_keyword) $search_keyword = preg_replace('/[^0-9]/', '', $search_keyword);
					$args->s_phone_number = $search_keyword;
					break;
				case 'regdate' :
					$args->s_regdate = preg_replace("/[^0-9]/","",$search_keyword);
					break;
				case 'regdate_more' :
					$args->s_regdate_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'regdate_less' :
					$args->s_regdate_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'ipaddress' :
					$args->s_ipaddress = preg_replace('/[^0-9a-z.:]/', '', $search_keyword) . '%';
					break;
				case 'last_login' :
					$args->s_last_login = preg_replace("/[^0-9]/","",$search_keyword);
					//$args->s_last_login = $search_keyword;
					break;
				case 'last_login_more' :
					$args->s_last_login_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login_less' :
					$args->s_last_login_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login_ipaddress' :
					$args->s_last_login_ipaddress = preg_replace('/[^0-9a-z.:]/', '', $search_keyword) . '%';
					break;
				case 'birthday' :
					$args->s_birthday = preg_replace("/[^0-9]/","",$search_keyword);
					break;
				case 'extra_vars' :
					$args->s_extra_vars = $search_keyword;
					break;
			}
		}

		// Change the query id if selected_group_srl exists (for table join)
		$sort_order = Context::get('sort_order');
		$sort_index = Context::get('sort_index');
		if(!$sort_index || !in_array($sort_index, ['user_id', 'email_address', 'phone_number', 'user_name', 'nick_name', 'regdate', 'last_login']))
		{
			$sort_index = "list_order";
		}

		if(!$sort_order)
		{
			$sort_order = 'asc';
		}

		if($sort_order != 'asc')
		{
			$sort_order = 'desc';
		}

		if($args->selected_group_srl)
		{
			$query_id = 'member.getMemberListWithinGroup';
			$args->sort_index = "member.".$sort_index;
		}
		else
		{
			$query_id = 'member.getMemberList';
			$args->sort_index = $sort_index; 
		}

		$args->sort_order = $sort_order;
		Context::set('sort_order', $sort_order);
		// Other variables
		$args->page = Context::get('page');
		$args->list_count = 40;
		$args->page_count = 10;
		$output = executeQuery($query_id, $args);

		return $output;
	}

	/**
	 * Get a memebr list for each site
	 * 
	 * @param int $site_srl
	 * @param int $page
	 *
	 * @return array
	 */
	function getSiteMemberList($site_srl = 0, $page = 1)
	{
		$args = new stdClass();
		$args->page = $page;
		$args->list_count = 40;
		$args->page_count = 10;
		$query_id = 'member.getSiteMemberList';
		$output = executeQueryArray($query_id, $args);
		return $output;
	}

	/**
	 * Get member_srls lists about site admins
	 * 
	 * @return array 
	 */
	function getSiteAdminMemberSrls()
	{
		$output = executeQueryArray('member.getSiteAdminMemberSrls');
		if(!$output->toBool() || !$output->data) return array();

		$member_srls = array();
		foreach($output->data as $member_info)
		{
			$member_srls[] = $member_info->member_srl;
		}

		return $member_srls;
	}

	/**
	 * Return colorset list of a skin in the member module
	 * 
	 * @return void 
	 */
	function getMemberAdminColorset()
	{
		$skin = Context::get('skin');
		if(!$skin) $tpl = "";
		else
		{
			$oModuleModel = getModel('module');
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);
			Context::set('skin_info', $skin_info);

			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('member');
			if(!$config->colorset) $config->colorset = "white";
			Context::set('config', $config);

			$oTemplate = &TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path.'tpl', 'new_colorset_list');
		}

		$this->add('tpl', $tpl);
	}


	/**
	 * Return member count with date
	 * 
	 * @param string $date
	 *
	 * @return int
	 */
	public function getMemberCountByDate($date = '')
	{
		$args = new stdClass();
		if($date) $args->regDate = date('Ymd', strtotime($date));

		$output = executeQuery('member.getMemberCountByDate', $args);
		if(!$output->toBool()) return 0;

		return $output->data->count;
	}

	/**
	 * Return site join member count with date
	 *
	 * @param string $date
	 *
	 * @return int
	 */
	function getMemberGroupMemberCountByDate($date = '')
	{
		$args = new stdClass();
		if($date)
		{
			$args->regDate = date('Ymd', strtotime($date));
		}

		$output = executeQuery('member.getMemberGroupMemberCountByDate', $args);
		if(!$output->toBool())
		{
			return 0;
		}

		return count($output->data);
	}

	/**
	 * Return add join Form
	 *
	 * @return void
	 */
	function getMemberAdminInsertJoinForm()
	{
		$member_join_form_srl = Context::get('member_join_form_srl');

		$args = new stdClass();
		$args->member_join_form_srl = $member_join_form_srl;
		$output = executeQuery('member.getJoinForm', $args);

		if($output->toBool() && $output->data)
		{
			$formInfo = $output->data;
			$default_value = $formInfo->default_value;
			if($default_value)
			{
				$default_value = unserialize($default_value);
				Context::set('default_value', $default_value);
			}
			Context::set('formInfo', $output->data);
		}

		$oMemberModel = getModel('member');
		$config = $oMemberModel->getMemberConfig();
		foreach($config->signupForm as $item) 
		{
			$list[] = $item->name;
		}

		$id_list = implode(',',$list);
		Context::set('id_list',$id_list);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'insert_join_form');

		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * check allowed target ip address when  login for admin. 
	 *
	 * @return boolean (true : allowed, false : refuse)
	 */
	function getMemberAdminIPCheck($allow_list = null, $deny_list = null)
	{
		if ($allow_list = ($allow_list === null) ? config('admin.allow') : $allow_list)
		{
			return Rhymix\Framework\Filters\IpFilter::inRanges(RX_CLIENT_IP, $allow_list);
		}
		
		if ($deny_list = ($deny_list === null) ? config('admin.deny') : $deny_list)
		{
			return !Rhymix\Framework\Filters\IpFilter::inRanges(RX_CLIENT_IP, $deny_list);
		}
		
		return true;
	}
}
/* End of file member.admin.model.php */
/* Location: ./modules/member/member.admin.model.php */
