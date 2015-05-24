<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file member_communication.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Promote user communication
 *
 * - Pop-up the message if new message comes in
 * - When calling MemberModel::getMemberMenu, feature to send a message is added
 * - When caliing MemberModel::getMemberMenu, feature to add a friend is added
 */
// Stop if non-logged-in user is
$logged_info = Context::get('logged_info');
if(!$logged_info|| isCrawler())
{
	return;
}

/**
 * Message/Friend munus are added on the pop-up window and member profile. Check if a new message is received
 * */
if($this->module != 'member' && $called_position == 'before_module_init')
{
	$flag_file = _XE_PATH_ . 'files/member_extra_info/new_message_flags/' . getNumberingPath($logged_info->member_srl) . $logged_info->member_srl;
	if($addon_info->use_alarm != 'N' && file_exists($flag_file))
	{
		// Pop-up to display messages if a flag on new message is set
		$new_message_count = (int) trim(FileHandler::readFile($flag_file));
		FileHandler::removeFile($flag_file);
		Context::loadLang(_XE_PATH_ . 'addons/member_communication/lang');
		Context::loadFile(array('./addons/member_communication/tpl/member_communication.js'), true);

		$text = preg_replace('@\r?\n@', '\\n', addslashes(Context::getLang('alert_new_message_arrived')));
		Context::addHtmlFooter("<script type=\"text/javascript\">jQuery(function(){ xeNotifyMessage('{$text}','{$new_message_count}'); });</script>");
	}
}
/* End of file member_communication.addon.php */
/* Location: ./addons/member_communication/member_communication.addon.php */
