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
	 * Insert log
	 * @return void
	 */
	public function insertLog($module, $act)
	{
		if (!$module || !$act)
		{
			return;
		}

		$args = new stdClass();
		$args->member_srl = $this->user->member_srl;
		$args->module = $module;
		$args->act = $act;
		$args->request_vars = json_encode(Context::getRequestVars(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		$args->regdate = date('YmdHis');
		$args->ipaddress = \RX_CLIENT_IP;
		$output = executeQuery('adminlogging.insertLog', $args);
	}
}
/* End of file adminlogging.controller.php */
/* Location: ./modules/adminlogging/adminlogging.controller.php */
