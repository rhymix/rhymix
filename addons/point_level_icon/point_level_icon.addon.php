<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file point.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Icon-on-point level
 *
 * Display point level icon before user name when point system is enabled.
 * */
// return unless before_display_content
if($called_position != "before_display_content" || Context::get('act') == 'dispPageAdminContentModify' || Context::getResponseMethod() != 'HTML' || isCrawler())
{
	return;
}

require_once(_XE_PATH_ . 'addons/point_level_icon/point_level_icon.lib.php');

$temp_output = preg_replace_callback('!<(div|span|a)([^\>]*)member_([0-9\-]+)([^\>]*)>(.*?)\<\/(div|span|a)\>!is', 'pointLevelIconTrans', $output);
if($temp_output)
{
	$output = $temp_output;
}
unset($temp_output);

/* End of file point_level_icon.addon.php */
/* Location: ./addons/point_level_icon/point_level_icon.addon.php */
