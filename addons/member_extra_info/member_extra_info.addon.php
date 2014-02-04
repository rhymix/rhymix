<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
{
	exit();
}

/**
 * @file image_name.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Display user image name/image mark
 *
 * Find member_srl in the part with <div class="member_MemberSerialNumber"> .... </div>
 * Check if ther is image name and image mark. Then change it.
 */
/**
 * Just before displaying, change image name/ image mark
 */
if($called_position != "before_display_content" || Context::get('act') == 'dispPageAdminContentModify' || Context::getResponseMethod() != 'HTML' || isCrawler())
{
	return;
}
// Include a file having functions to replace member image name/mark
require_once('./addons/member_extra_info/member_extra_info.lib.php');
// 1. Find a part <div class="member_MemberSerialNumber"> content </div> in the output document, change it to image name/mark by using MemberController::transImageName()
$temp_output = preg_replace_callback('!<(div|span|a)([^\>]*)member_([0-9]+)([^\>]*)>(.*?)\<\/(div|span|a)\>!is', 'memberTransImageName', $output);
if($temp_output)
{
	$output = $temp_output;
}
unset($temp_output);

/* End of file member_extra_info.addon.php */
/* Location: ./addons/member_extra_info/member_extra_info.addon.php */
