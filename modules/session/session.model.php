<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  sessionModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The Model class of the session module
 */
class sessionModel extends session
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function getLifeTime()
	{
		return $this->lifetime;
	}

	function read($session_key)
	{
		if(!$session_key || !$this->session_started) return;

		$args = new stdClass();
		$args->session_key = $session_key;
		$columnList = array('session_key', 'cur_mid', 'val');
		$output = executeQuery('session.getSession', $args, $columnList);

		if(!$output->data)
		{
			return '';
		}

		return $output->data->val;
	}

	/**
	 * @brief Get a list of currently connected users
	 * Requires "object" argument because multiple arguments are expected
	 * limit_count : the number of objects
	 * page : the page number
	 * period_time: "n" specifies the time range in minutes since the last update
	 * mid: a user who belong to a specified mid
	 */
	function getLoggedMembers($args)
	{
		if(!$args->site_srl)
		{
			$site_module_info = Context::get('site_module_info');
			$args->site_srl = (int)$site_module_info->site_srl;
		}
		if(!$args->list_count) $args->list_count = 20;
		if(!$args->page) $args->page = 1;
		if(!$args->period_time) $args->period_time = 3;
		$args->last_update = date("YmdHis", $_SERVER['REQUEST_TIME'] - $args->period_time*60);

		$output = executeQueryArray('session.getLoggedMembers', $args);
		if(!$output->toBool()) return $output;

		$member_srls = array();
		$member_keys = array();
		if(count($output->data))
		{
			foreach($output->data as $key => $val)
			{
				$member_srls[$key] = $val->member_srl;
				$member_keys[$val->member_srl] = $key;
			}
		}

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			if(!in_array($logged_info->member_srl, $member_srls))
			{
				$member_srls[0] = $logged_info->member_srl;
				$member_keys[$logged_info->member_srl] = 0;
			}
		}

		if(!count($member_srls)) return $output;

		$member_args = new stdClass();
		$member_args->member_srl = implode(',',$member_srls);
		$member_output = executeQueryArray('member.getMembers', $member_args);
		if($member_output->data)
		{
			foreach($member_output->data as $key => $val)
			{
				$output->data[$member_keys[$val->member_srl]] = $val;
			}
		}

		return $output;
	}
}
/* End of file session.model.php */
/* Location: ./modules/session/session.model.php */
