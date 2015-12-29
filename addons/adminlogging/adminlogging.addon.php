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
if(Context::get('is_logged') && $logged_info->is_admin == 'Y' && stripos(Context::get('act'), 'admin') !== false && $called_position == 'before_module_proc')
{
	$oAdminloggingController = getController('adminlogging');
	$oAdminloggingController->insertLog($this->module, $this->act);
}
/* End of file adminlogging.php */
/* Location: ./addons/adminlogging */
