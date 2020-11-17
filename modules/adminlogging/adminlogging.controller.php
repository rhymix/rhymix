<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminloggingController class
 * controller class of adminlogging module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/adminlogging
 * @version 0.1
 */
class adminloggingController extends adminlogging
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		// forbit access if the user is not an administrator
		$oMemberModel = getModel('member');
		$logged_info = $oMemberModel->getLoggedInfo();
		if($logged_info->is_admin != 'Y')
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('admin.msg_is_not_administrator');
		}
	}

	/**
	 * Insert log
	 * @return void
	 */
	function insertLog($module, $act)
	{
		if(!$module || !$act)
		{
			return;
		}

		$args = new stdClass();
		$args->module = $module;
		$args->act = $act;
		$args->ipaddress = \RX_CLIENT_IP;
		$args->regdate = date('YmdHis');
		$args->requestVars = print_r(Context::getRequestVars(), TRUE);

		$output = executeQuery('adminlogging.insertLog', $args);
	}

}
/* End of file adminlogging.controller.php */
/* Location: ./modules/adminlogging/adminlogging.controller.php */
