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
	// Load a language file from the communication module
	Context::loadLang(_XE_PATH_ . 'modules/communication/lang');
	// Add menus on the member login information
	$oMemberController = getController('member');
	$oMemberController->addMemberMenu('dispCommunicationFriend', 'cmd_view_friend');
	$oMemberController->addMemberMenu('dispCommunicationMessages', 'cmd_view_message_box');

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
elseif($this->act == 'getMemberMenu' && $called_position == 'before_module_proc')
{
	$member_srl = Context::get('target_srl');
	$oCommunicationModel = getModel('communication');

	// Add a feature to display own message box.
	if($logged_info->member_srl == $member_srl)
	{
		$mid = Context::get('cur_mid');
		$oMemberController = getController('member');
		// Add your own viewing Note Template
		$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationMessages'), 'cmd_view_message_box', '', 'self');
		// Display a list of friends
		$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationFriend'), 'cmd_view_friend', '', 'self');
		// If not, Add menus to send message and to add friends
	}
	else
	{
		// Get member information
		$oMemberModel = getModel('member');
		$target_member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		if(!$target_member_info->member_srl)
		{
			return;
		}

		$oMemberController = getController('member');
		// Add a menu for sending message
		if($logged_info->is_admin == 'Y' || $target_member_info->allow_message == 'Y' || ($target_member_info->allow_message == 'F' && $oCommunicationModel->isFriend($member_srl)))
			$oMemberController->addMemberPopupMenu(getUrl('', 'mid', Context::get('cur_mid'), 'act', 'dispCommunicationSendMessage', 'receiver_srl', $member_srl), 'cmd_send_message', '', 'popup');
		// Add a menu for listing friends (if a friend is new)
		if(!$oCommunicationModel->isAddedFriend($member_srl))
			$oMemberController->addMemberPopupMenu(getUrl('', 'mid', Context::get('cur_mid'), 'act', 'dispCommunicationAddFriend', 'target_srl', $member_srl), 'cmd_add_friend', '', 'popup');
	}
}
/* End of file member_communication.addon.php */
/* Location: ./addons/member_communication/member_communication.addon.php */
