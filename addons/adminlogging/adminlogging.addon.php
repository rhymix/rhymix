<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file adminlogging.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief admin log
 */
$logged_info = Context::get('logged_info');
if ($called_position === 'before_module_proc' && $logged_info->is_admin === 'Y' && stripos(Context::get('act') ?? '', 'admin') !== false)
{
	$oAdminloggingController = adminloggingController::getInstance();
	$oAdminloggingController->insertLog($this->module, $this->act);
}
/* End of file adminlogging.php */
/* Location: ./addons/adminlogging */
