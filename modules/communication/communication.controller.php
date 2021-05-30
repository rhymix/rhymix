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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$args = new stdClass();
		$args->allow_message = Context::get('allow_message');

		if(!in_array($args->allow_message, array('Y', 'N', 'F')))
		{
			$args->allow_message = 'Y';
		}

		$args->member_srl = $this->user->member_srl;

		$output = executeQuery('communication.updateAllowMessage', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		
		MemberController::clearMemberCache($args->member_srl);

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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');

		// Check variables
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			throw new Rhymix\Framework\Exception('msg_not_exists_member');
		}

		$title = trim(escape(Context::get('title')));
		if(!$title)
		{
			throw new Rhymix\Framework\Exception('msg_title_is_null');
		}

		$content = trim(Context::get('content'));
		if(!$content)
		{
			throw new Rhymix\Framework\Exception('msg_content_is_null');
		}
		
		$temp_srl = intval(Context::get('temp_srl')) ?: null;
		if($temp_srl && !$_SESSION['upload_info'][$temp_srl]->enabled)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Check if there is a member to receive a message
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');
		$config = $oCommunicationModel->getConfig();

		if(!$oCommunicationModel->checkGrant($config->grant_send))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
		if($receiver_member_info->member_srl != $receiver_srl)
		{
			throw new Rhymix\Framework\Exception('msg_not_exists_member');
		}

		// check whether to allow to receive the message(pass if a top-administrator)
		if($logged_info->is_admin != 'Y')
		{
			if($receiver_member_info->allow_message == 'F')
			{
				if(!$oCommunicationModel->isFriend($receiver_member_info->member_srl))
				{
					throw new Rhymix\Framework\Exception('msg_allow_message_to_friend');
				}
			}
			else if($receiver_member_info->allow_message == 'N')
			{
				throw new Rhymix\Framework\Exception('msg_disallow_message');
			}
		}

		// send a message
		$output = $this->sendMessage($logged_info->member_srl, $receiver_srl, $title, $content, true, $temp_srl);

		if(!$output->toBool())
		{
			return $output;
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
	 * @param bool $sender_log (default true)
	 * @param int|null $temp_srl (default null)
	 * @param bool $use_spamfilter (default true)
	 * @return Object
	 */
	function sendMessage($sender_srl, $receiver_srl, $title, $content, $sender_log = true, $temp_srl = null, $use_spamfilter = true)
	{
		// Encode the title and content.
		$title = escape($title, false);
		$content = removeHackTag($content);
		$title = utf8_mbencode($title);
		$content = utf8_mbencode($content);

		$message_srl = $temp_srl ?: getNextSequence();
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
		$receiver_args->related_srl = $message_srl;
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
		$trigger_obj->use_spamfilter = $use_spamfilter;
		$trigger_output = ModuleHandler::triggerCall('communication.sendMessage', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
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
		
		// update attached files
		if ($temp_srl)
		{
			$oFileController = getController('file');
			$oFileController->setFilesValid($message_srl, 'msg');
		}

		// Call a trigger (after)
		ModuleHandler::triggerCall('communication.sendMessage', 'after', $trigger_obj);
		
		$oDB->commit();
		
		// create a flag that message is sent (in file format) 
		$this->updateFlagFile($receiver_srl);

		return new BaseObject(0, 'success_sended');
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}
		$logged_info = Context::get('logged_info');

		// Check variable
		$message_srl = Context::get('message_srl');
		if(!$message_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// get the message
		$oCommunicationModel = getModel('communication');
		$message = $oCommunicationModel->getSelectedMessage($message_srl);
		if(!$message || $message->message_type != 'R')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$args = new stdClass();
		$args->message_srl = $message_srl;
		$args->receiver_srl = $logged_info->member_srl;
		$output = executeQuery('communication.setMessageStored', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$this->updateFlagFile($logged_info->member_srl);
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		// Check the variable
		$message_srl = Context::get('message_srl');
		if(!$message_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Get the message
		$oCommunicationModel = getModel('communication');
		$message = $oCommunicationModel->getSelectedMessage($message_srl);
		if(!$message)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Check the grant
		switch($message->message_type)
		{
			case 'S':
				if($message->sender_srl != $member_srl)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
				break;

			case 'R':
				if($message->receiver_srl != $member_srl)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
		
		// Delete attachment, only if related message has also been deleted
		$related = $message->related_srl ? $oCommunicationModel->getSelectedMessage($message->related_srl) : true;
		if (!$related)
		{
			$oFileController = getController('file');
			$oFileController->deleteFiles($message->message_srl);
			$oFileController->deleteFiles($message->related_srl);
		}
		
		$this->updateFlagFile($member_srl);
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		// check variables
		if(!Context::get('message_srl_list'))
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		$message_srl_list = Context::get('message_srl_list');
		if(!is_array($message_srl_list))
		{
			$message_srl_list = explode('|@|', trim($message_srl_list));
		}

		if(!count($message_srl_list))
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		$message_type = Context::get('message_type');
		if(!$message_type || !in_array($message_type, array('R', 'S', 'T', 'N')))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		// Organize variables
		$args = new stdClass();
		$args->message_srls = implode(',', $target);
		
		if ($message_type === 'N')
		{
			$args->message_type = 'R';
		}
		else
		{
			$args->message_type = $message_type;
		}
		
		if($message_type == 'S')
		{
			$args->sender_srl = $member_srl;
		}
		else
		{
			$args->receiver_srl = $member_srl;
		}

		// Find related messages
		$related = array();
		$output = executeQueryArray('communication.getRelatedMessages', $args);
		foreach ($output->data as $item)
		{
			$related[$item->related_srl] = $item->message_srl;
		}
		if (count($related))
		{
			$output = executeQueryArray('communication.getMessages', (object)array(
				'message_srl_list' => array_keys($related)
			), array('message_srl'));
			foreach ($output->data as $item)
			{
				unset($related[$item->message_srl]);
			}
		}
		
		// Delete
		$output = executeQuery('communication.deleteMessages', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		
		// Delete attachment, only if related message has also been deleted
		$oFileController = getController('file');
		foreach ($related as $message_srl => $related_srl)
		{
			$oFileController->deleteFiles($message_srl);
			$oFileController->deleteFiles($related_srl);
		}
		
		$this->updateFlagFile($member_srl);
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');

		$target_srl = (int) trim(Context::get('target_srl'));
		if(!$target_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if($target_srl == $logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exception('msg_no_self_friend');
		}
		
		// Check duplicate friend
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->target_srl = $target_srl;
		$output = executeQuery('communication.isAddedFriend', $args);
		if($output->data->count)
		{
			throw new Rhymix\Framework\Exception('msg_already_friend');
		}

		// Call trigger (before)
		$args->friend_group_srl = intval(Context::get('friend_group_srl'));
		$trigger_output = ModuleHandler::triggerCall('communication.addFriend', 'before', $args);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}
		
		// Variable
		$args->friend_srl = getNextSequence();
		$args->list_order = $args->friend_srl * -1;
		$output = executeQuery('communication.addFriend', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		// Call trigger (after)
		$trigger_output = ModuleHandler::triggerCall('communication.addFriend', 'after', $args);
		
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');

		// Check variables
		$friend_srl_list = Context::get('friend_srl_list');
		if(!$friend_srl_list)
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		if(!is_array($friend_srl_list))
		{
			$friend_srl_list = explode('|@|', $friend_srl_list);
		}

		if(!count($friend_srl_list))
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
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
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');

		// Check variables
		$friend_srl_list = Context::get('friend_srl_list');
		if(!is_array($friend_srl_list))
		{
			$friend_srl_list = explode('|@|', $friend_srl_list);
		}
		$friend_srl_list = array_map(function($str) { return intval(trim($str)); }, $friend_srl_list);
		$friend_srl_list = array_filter($friend_srl_list, function($friend_srl) { return $friend_srl > 0; });
		if(!count($friend_srl_list))
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		// Prepare arguments
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->friend_srl_list = $friend_srl_list;
		
		// Call trigger (before)
		$trigger_output = ModuleHandler::triggerCall('communication.deleteFriend', 'before', $args);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}
		
		// Delete
		$output = executeQuery('communication.deleteFriend', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		// Call trigger (after)
		$trigger_output = ModuleHandler::triggerCall('communication.deleteFriend', 'after', $args);
		
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$friend_group_srl = intval(trim(Context::get('friend_group_srl')));

		// Variables
		$args = new stdClass();
		$args->member_srl = $this->user->member_srl;
		$args->title = escape(Context::get('title'));

		if(!$args->title)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// modify if friend_group_srl exists.
		if($friend_group_srl)
		{
			$args->friend_group_srl = $friend_group_srl;
			$output = executeQuery('communication.renameFriendGroup', $args);
			$msg_code = 'success_updated';
		}
		// add if not exists
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$logged_info = Context::get('logged_info');

		// Variables
		$args = new stdClass();
		$args->friend_group_srl = Context::get('friend_group_srl');
		$args->member_srl = $logged_info->member_srl;
		$args->title = escape(Context::get('title'));

		if(!$args->title)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
			throw new Rhymix\Framework\Exceptions\MustLogin;
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
		$args = new stdClass;
		$args->message_srl = $message_srl;
		$args->related_srl = $message_srl;
		$output = executeQuery('communication.setMessageReaded', $args);
		
		// Update flag file
		$logged_info = Context::get('logged_info');
		$this->updateFlagFile($logged_info->member_srl);
		
		return $output;
	}
	
	/**
	 * Update flag file
	 * @param int $member_srl
	 * @return void
	 */
	function updateFlagFile($member_srl)
	{
		$flag_path = \RX_BASEDIR . 'files/member_extra_info/new_message_flags/' . getNumberingPath($member_srl);
		$flag_file = $flag_path . $member_srl;
		$new_message_count = getModel('communication')->getNewMessageCount($member_srl);
		if($new_message_count > 0)
		{
			FileHandler::writeFile($flag_file, $new_message_count);
		}
		else
		{
			FileHandler::removeFile($flag_file);
		}
	}

	function triggerModuleHandlerBefore($obj)
	{
		// Add menus on the member login information
		$config = getModel('communication')->getConfig();
		$oMemberController = getController('member');
		
		if($config->enable_message == 'Y')
		{
			$oMemberController->addMemberMenu('dispCommunicationMessages', 'cmd_view_message_box');
		}
		
		if($config->enable_friend == 'Y')
		{
			$oMemberController->addMemberMenu('dispCommunicationFriend', 'cmd_view_friend');
		}
		else
		{
			$allow_message_type = lang('communication.allow_message_type');
			unset($allow_message_type['F']);
			$GLOBALS['lang']->set('communication.allow_message_type', $allow_message_type);
		}
	}

	function triggerMemberMenu()
	{
		if(!Context::get('is_logged'))
		{
			return;
		}
		
		$oCommunicationModel = getModel('communication');
		$config = $oCommunicationModel->getConfig();
		
		if($config->enable_message == 'N' && $config->enable_friend == 'N')
		{
			return;
		}
		if(!$oCommunicationModel->checkGrant($config->grant_send))
		{
			return;
		}
		
		$mid = Context::get('cur_mid');
		$member_srl = Context::get('target_srl');
		$logged_info = Context::get('logged_info');
		$oMemberController = getController('member');
		
		// Add a feature to display own message box.
		if($logged_info->member_srl == $member_srl)
		{
			// Add your own viewing Note Template
			if($config->enable_message == 'Y')
			{
				$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationMessages'), 'cmd_view_message_box', '', 'self');
			}
			
			// Display a list of friends
			if($config->enable_friend == 'Y')
			{
				$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationFriend'), 'cmd_view_friend', '', 'self');
			}
		}
		// If not, Add menus to send message and to add friends
		else
		{
			// Get member information
			$oMemberModel = getModel('member');
			$target_member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if(!$target_member_info->member_srl)
			{
				return;
			}

			// Add a menu for sending message
			if($config->enable_message == 'Y' && ($logged_info->is_admin == 'Y' || $target_member_info->allow_message == 'Y' || ($target_member_info->allow_message == 'F' && $oCommunicationModel->isFriend($member_srl))))
			{
				$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationSendMessage', 'receiver_srl', $member_srl), 'cmd_send_message', '', 'popup');
			}
			
			// Add a menu for listing friends (if a friend is new)
			if($config->enable_friend == 'Y' && !$oCommunicationModel->isAddedFriend($member_srl))
			{
				$oMemberController->addMemberPopupMenu(getUrl('', 'mid', $mid, 'act', 'dispCommunicationAddFriend', 'target_srl', $member_srl), 'cmd_add_friend', '', 'popup');
			}
		}
	}
}
/* End of file communication.controller.php */
/* Location: ./modules/comment/communication.controller.php */
