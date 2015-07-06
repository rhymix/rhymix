<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationController
 * @author NAVER (developers@xpressengine.com)
 * communication module of the Controller class
 */
class communicationController extends communication
{

	/**
	 * Initialization
	 */
	function init()
	{

	}

	/**
	 * change the settings of message box
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationUpdateAllowMessage()
	{
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$args = new stdClass();
		$args->allow_message = Context::get('allow_message');

		if(!in_array($args->allow_message, array('Y', 'N', 'F')))
		{
			$args->allow_message = 'Y';
		}

		$logged_info = Context::get('logged_info');
		$args->member_srl = $logged_info->member_srl;

		$output = executeQuery('communication.updateAllowMessage', $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispCommunicationMessages', 'message_type', Context::get('message_type'));

		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Send a message
	 * @return Object
	 */
	function procCommunicationSendMessage()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// Check variables
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			return new Object(-1, 'msg_not_exists_member');
		}

		$title = trim(Context::get('title'));
		if(!$title)
		{
			return new Object(-1, 'msg_title_is_null');
		}

		$content = trim(Context::get('content'));
		if(!$content)
		{
			return new Object(-1, 'msg_content_is_null');
		}

		$send_mail = Context::get('send_mail');
		if($send_mail != 'Y')
		{
			$send_mail = 'N';
		}

		// Check if there is a member to receive a message
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');
		$config = $oCommunicationModel->getConfig();

		if(!$oCommunicationModel->checkGrant($config->grant_write))
		{
			return new Object(-1, 'msg_not_permitted');
		}

		$receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
		if($receiver_member_info->member_srl != $receiver_srl)
		{
			return new Object(-1, 'msg_not_exists_member');
		}

		// check whether to allow to receive the message(pass if a top-administrator)
		if($logged_info->is_admin != 'Y')
		{
			if($receiver_member_info->allow_message == 'F')
			{
				if(!$oCommunicationModel->isFriend($receiver_member_info->member_srl))
				{
					return new object(-1, 'msg_allow_message_to_friend');
				}
			}
			else if($receiver_member_info->allow_message == 'N')
			{
				return new object(-1, 'msg_disallow_message');
			}
		}

		// send a message
		$output = $this->sendMessage($logged_info->member_srl, $receiver_srl, $title, $content);

		if(!$output->toBool())
		{
			return $output;
		}

		// send an e-mail
		if($send_mail == 'Y')
		{
			$view_url = Context::getRequestUri();
			$content = sprintf("%s<br /><br />From : <a href=\"%s\" target=\"_blank\">%s</a>", $content, $view_url, $view_url);
			$oMail = new Mail();
			$oMail->setTitle($title);
			$oMail->setContent($content);
			$oMail->setSender($logged_info->nick_name, $logged_info->email_address);
			$oMail->setReceiptor($receiver_member_info->nick_name, $receiver_member_info->email_address);
			$oMail->send();
		}

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			if(Context::get('is_popup') != 'Y')
			{
				global $lang;
				htmlHeader();
				alertScript($lang->success_sended);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
			else
			{
				$this->setMessage('success_sended');
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('','act', 'dispCommunicationMessages', 'message_type', 'S', 'receiver_srl', $receiver_srl, 'message_srl', '');
				$this->setRedirectUrl($returnUrl);
			}
		}

		return $output;
	}

	/**
	 * Send a message (DB control)
	 * @param int $sender_srl member_srl of sender
	 * @param int $receiver_srl member_srl of receiver_srl
	 * @param string $title
	 * @param string $content
	 * @param boolean $sender_log (default true)
	 * @return Object
	 */
	function sendMessage($sender_srl, $receiver_srl, $title, $content, $sender_log = TRUE)
	{
		$content = removeHackTag($content);
		$title = htmlspecialchars($title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

		$message_srl = getNextSequence();
		$related_srl = getNextSequence();

		// messages to save in the sendor's message box
		$sender_args = new stdClass();
		$sender_args->sender_srl = $sender_srl;
		$sender_args->receiver_srl = $receiver_srl;
		$sender_args->message_type = 'S';
		$sender_args->title = $title;
		$sender_args->content = $content;
		$sender_args->readed = 'N';
		$sender_args->regdate = date("YmdHis");
		$sender_args->message_srl = $message_srl;
		$sender_args->related_srl = $related_srl;
		$sender_args->list_order = $sender_args->message_srl * -1;

		// messages to save in the receiver's message box
		$receiver_args = new stdClass();
		$receiver_args->message_srl = $related_srl;
		$receiver_args->related_srl = 0;
		$receiver_args->list_order = $related_srl * -1;
		$receiver_args->sender_srl = $sender_srl;
		if(!$receiver_args->sender_srl)
		{
			$receiver_args->sender_srl = $receiver_srl;
		}
		$receiver_args->receiver_srl = $receiver_srl;
		$receiver_args->message_type = 'R';
		$receiver_args->title = $title;
		$receiver_args->content = $content;
		$receiver_args->readed = 'N';
		$receiver_args->regdate = date("YmdHis");

		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->sender_srl = $sender_srl;
		$trigger_obj->receiver_srl = $receiver_srl;
		$trigger_obj->message_srl = $message_srl;
		$trigger_obj->related_srl = $related_srl;
		$trigger_obj->title = $title;
		$trigger_obj->content = $content;
		$trigger_obj->sender_log = $sender_log;
		$triggerOutput = ModuleHandler::triggerCall('communication.sendMessage', 'before', $trigger_obj);
		if(!$triggerOutput->toBool())
		{
			return $triggerOutput;
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// messages to save in the sendor's message box
		if($sender_srl && $sender_log)
		{
			$output = executeQuery('communication.sendMessage', $sender_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// messages to save in the receiver's message box
		$output = executeQuery('communication.sendMessage', $receiver_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Call a trigger (after)
		$trigger_output = ModuleHandler::triggerCall('communication.sendMessage', 'after', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			$oDB->rollback();
			return $trigger_output;
		}

		// create a flag that message is sent (in file format) 
		$flag_path = './files/member_extra_info/new_message_flags/' . getNumberingPath($receiver_srl);
		FileHandler::makeDir($flag_path);
		$flag_file = sprintf('%s%s', $flag_path, $receiver_srl);
		$flag_count = FileHandler::readFile($flag_file);
		FileHandler::writeFile($flag_file, ++$flag_count);

		$oDB->commit();

		return new Object(0, 'success_sended');
	}

	/**
	 * store a specific message into the archive
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationStoreMessage()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}
		$logged_info = Context::get('logged_info');

		// Check variable
		$message_srl = Context::get('message_srl');
		if(!$message_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// get the message
		$oCommunicationModel = getModel('communication');
		$message = $oCommunicationModel->getSelectedMessage($message_srl);
		if(!$message || $message->message_type != 'R')
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$args = new stdClass();
		$args->message_srl = $message_srl;
		$args->receiver_srl = $logged_info->member_srl;
		$output = executeQuery('communication.setMessageStored', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_registed');
	}

	/**
	 * Delete a message
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationDeleteMessage()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		// Check the variable
		$message_srl = Context::get('message_srl');
		if(!$message_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// Get the message
		$oCommunicationModel = getModel('communication');
		$message = $oCommunicationModel->getSelectedMessage($message_srl);
		if(!$message)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// Check the grant
		switch($message->message_type)
		{
			case 'S':
				if($message->sender_srl != $member_srl)
				{
					return new Object(-1, 'msg_invalid_request');
				}
				break;

			case 'R':
				if($message->receiver_srl != $member_srl)
				{
					return new Object(-1, 'msg_invalid_request');
				}
				break;
		}

		// Delete
		$args = new stdClass();
		$args->message_srl = $message_srl;
		$output = executeQuery('communication.deleteMessage', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');
	}

	/**
	 * Delete the multiple messages
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationDeleteMessages()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		// check variables
		if(!Context::get('message_srl_list'))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		$message_srl_list = Context::get('message_srl_list');
		if(!is_array($message_srl_list))
		{
			$message_srl_list = explode('|@|', trim($message_srl_list));
		}

		if(!count($message_srl_list))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		$message_type = Context::get('message_type');
		if(!$message_type || !in_array($message_type, array('R', 'S', 'T')))
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$message_count = count($message_srl_list);
		$target = array();
		for($i = 0; $i < $message_count; $i++)
		{
			$message_srl = (int) trim($message_srl_list[$i]);
			if(!$message_srl)
			{
				continue;
			}

			$target[] = $message_srl;
		}
		if(!count($target))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		// Delete
		$args = new stdClass();
		$args->message_srls = implode(',', $target);
		$args->message_type = $message_type;

		if($message_type == 'S')
		{
			$args->sender_srl = $member_srl;
		}
		else
		{
			$args->receiver_srl = $member_srl;
		}

		$output = executeQuery('communication.deleteMessages', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispCommunicationMessages', 'message_type', Context::get('message_type'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Add a friend
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationAddFriend()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		$target_srl = (int) trim(Context::get('target_srl'));
		if(!$target_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// Variable
		$args = new stdClass();
		$args->friend_srl = getNextSequence();
		$args->list_order = $args->friend_srl * -1;
		$args->friend_group_srl = Context::get('friend_group_srl');
		$args->member_srl = $logged_info->member_srl;
		$args->target_srl = $target_srl;
		$output = executeQuery('communication.addFriend', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('member_srl', $target_srl);
		$this->setMessage('success_registed');

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			global $lang;
			htmlHeader();
			alertScript($lang->success_registed);
			closePopupScript();
			htmlFooter();
			Context::close();
			exit;
		}
	}

	/**
	 * Move a group of the friend
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationMoveFriend()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// Check variables
		$friend_srl_list = Context::get('friend_srl_list');
		if(!$friend_srl_list)
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		if(!is_array($friend_srl_list))
		{
			$friend_srl_list = explode('|@|', $friend_srl_list);
		}

		if(!count($friend_srl_list))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		$friend_count = count($friend_srl_list);
		$target = array();
		for($i = 0; $i < $friend_count; $i++)
		{
			$friend_srl = (int) trim($friend_srl_list[$i]);
			if(!$friend_srl)
			{
				continue;
			}

			$target[] = $friend_srl;
		}

		if(!count($target))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		// Variables
		$args = new stdClass();
		$args->friend_srls = implode(',', $target);
		$args->member_srl = $logged_info->member_srl;
		$args->friend_group_srl = Context::get('target_friend_group_srl');

		$output = executeQuery('communication.moveFriend', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_moved');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispCommunicationFriend');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete a friend 
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationDeleteFriend()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		// Check variables
		$friend_srl_list = Context::get('friend_srl_list');

		if(!is_array($friend_srl_list))
		{
			$friend_srl_list = explode('|@|', $friend_srl_list);
		}

		if(!count($friend_srl_list))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		$friend_count = count($friend_srl_list);
		$target = array();

		for($i = 0; $i < $friend_count; $i++)
		{
			$friend_srl = (int) trim($friend_srl_list[$i]);
			if(!$friend_srl)
			{
				continue;
			}

			$target[] = $friend_srl;
		}

		if(!count($target))
		{
			return new Object(-1, 'msg_cart_is_null');
		}

		// Delete
		$args = new stdClass();
		$args->friend_srls = implode(',', $target);
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('communication.deleteFriend', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispCommunicationFriend');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Add a group of friends
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationAddFriendGroup()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// Variables
		$args = new stdClass();
		$args->friend_group_srl = trim(Context::get('friend_group_srl'));
		$args->member_srl = $logged_info->member_srl;
		$args->title = Context::get('title');
		$args->title = htmlspecialchars($args->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

		if(!$args->title)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// modify if friend_group_srl exists.
		if($args->friend_group_srl)
		{
			$output = executeQuery('communication.renameFriendGroup', $args);
			$msg_code = 'success_updated';
			// add if not exists
		}
		else
		{
			$output = executeQuery('communication.addFriendGroup', $args);
			$msg_code = 'success_registed';
		}

		if(!$output->toBool())
		{
			if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
			{
				global $lang;
				htmlHeader();
				alertScript($lang->fail_to_registed);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
			else
			{
				return $output;
			}
		}
		else
		{
			if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
			{
				global $lang;
				htmlHeader();
				alertScript($lang->success_registed);
				reload(true);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
			else
			{
				$this->setMessage($msg_code);
			}
		}
	}

	/**
	 * change a name of friend group
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationRenameFriendGroup()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// Variables
		$args = new stdClass();
		$args->friend_group_srl = Context::get('friend_group_srl');
		$args->member_srl = $logged_info->member_srl;
		$args->title = Context::get('title');
		$args->title = htmlspecialchars($args->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

		if(!$args->title)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$output = executeQuery('communication.renameFriendGroup', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');
	}

	/**
	 * Delete a group of friends
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationDeleteFriendGroup()
	{
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// Variables
		$args = new stdClass();
		$args->friend_group_srl = Context::get('friend_group_srl');
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('communication.deleteFriendGroup', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');
	}

	/**
	 * set a message status to be 'already read'
	 * @param int $message_srl 
	 * @return Object
	 */
	function setMessageReaded($message_srl)
	{
		$args = new stdClass();
		$args->message_srl = $message_srl;
		$args->related_srl = $message_srl;
		return executeQuery('communication.setMessageReaded', $args);
	}

}
/* End of file communication.controller.php */
/* Location: ./modules/comment/communication.controller.php */
