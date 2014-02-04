<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  sessionController
 * @author NAVER (developers@xpressengine.com)
 * @brief The controller class of the session module
 */
class sessionController extends session
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function open()
	{
		return true;
	}

	function close()
	{
		return true;
	}

	function write($session_key, $val)
	{
		if(!$session_key || !$this->session_started) return;

		$args = new stdClass;
		$args->session_key = $session_key;

		$output = executeQuery('session.getSession', $args);
		$session_info = $output->data;

		//if ip has changed delete the session from db
		if($session_info->session_key == $session_key && $session_info->ipaddress != $_SERVER['REMOTE_ADDR'])
		{
			executeQuery('session.deleteSession', $args);

			return true;
		}

		$args->expired = date("YmdHis", $_SERVER['REQUEST_TIME'] + $this->lifetime);
		$args->val = $val;
		$args->cur_mid = Context::get('mid');

		if(!$args->cur_mid)
		{
			$module_info = Context::get('current_module_info');
			$args->cur_mid = $module_info->mid;
		}

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->member_srl = 0;
		}
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->last_update = date("YmdHis", $_SERVER['REQUEST_TIME']);

		//put session into db
		if($session_info->session_key)
		{
			$output = executeQuery('session.updateSession', $args);
		}
		else
		{
			$output = executeQuery('session.insertSession', $args);
		}

		return true;
	}

	function destroy($session_key)
	{
		if(!$session_key || !$this->session_started) return;

		//remove session from db
		$args = new stdClass();
		$args->session_key = $session_key;
		executeQuery('session.deleteSession', $args);

		return true;
	}

	function gc($maxlifetime)
	{
		if(!$this->session_started) return;

		executeQuery('session.gcSession');
		return true;
	}
}
/* End of file session.controller.php */
/* Location: ./modules/session/session.controller.php */
