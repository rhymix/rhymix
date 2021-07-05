<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationModel
 * @author NAVER (developers@xpressengine.com)
 * communication module of the Model class
 */
class communicationModel extends communication
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * get the configuration
	 * @return object config of communication module
	 */
	public static function getConfig()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('communication');
		if(!$config)
		{
			$config = new stdClass();
		}
		
		$config->enable_message = $config->enable_message ?? 'Y';
		$config->enable_friend = $config->enable_friend ?? 'Y';
		$config->enable_attachment = $config->enable_attachment ?? 'N';
		$config->editor_skin = $config->editor_skin ?? 'ckeditor';
		$config->layout_srl = $config->layout_srl ?? 0;
		$config->skin = $config->skin ?? 'default';
		$config->colorset = $config->colorset ?? 'white';
		$config->mlayout_srl = $config->mlayout_srl ?? 0;
		$config->mskin = $config->mskin ?? 'default';
		$config->mcolorset = $config->mcolorset ?? 'white';
		$config->grant_send = $config->grant_send ?? array('default' => 'member');
		
		return $config;
	}

	/**
	  * @brief get grant array for insert to database. table module_config's config field 
	  * @param string $default
	  * @param array $group
	  * @return array
	  */
	public static function getGrantArray($default, $group)
	{
		$grant = array();
		if($default)
		{
			$grant = array('default' => $default);
		}
		else if(is_array($group)) 
		{
			$grant_group = array();
			foreach($group as $group_srl)
			{
				$grant_group[$group_srl] = true;
			}
			
			$grant = array('group' => $grant_group);
		} 
		
		return $grant;
	}

	/**
	  * @brief Check Grant
	  * @param array $arrGrant
	  * @return boolean
	  */
	public static function checkGrant($arrGrant)
	{
		if(!$arrGrant) return false;
		
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y') return true;

		if($arrGrant['default'])
		{
			if($arrGrant['default'] == 'member')
			{
				if(Context::get('is_logged')) return true;
			}
			else if($arrGrant['default'] == 'manager')
			{
				if($logged_info->is_admin == 'Y') return true;
			}
		}
		else if(is_array($arrGrant['group']))
		{
			foreach($logged_info->group_list as $group_srl => $title)
			{
				if(isset($arrGrant['group'][$group_srl])) return true;
			}
		}

		return false;
	}

	/**
	 * get the message contents
	 * @param int $message_srl
	 * @param array $columnList
	 * @return object message information
	 */
	public static function getSelectedMessage($message_srl, $columnList = array())
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->message_srl = $message_srl;
		$output = executeQuery('communication.getMessage', $args, $columnList);

		$message = $output->data;
		if(!$message)
		{
			return;
		}

		// get recipient's information if it is a sent message
		$oMemberModel = getModel('member');

		if($message->sender_srl == $logged_info->member_srl && $message->message_type == 'S')
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($message->receiver_srl);
		}
		// get sendor's information if it is a received/archived message
		else
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($message->sender_srl);
		}

		if($member_info->member_srl)
		{
			foreach($member_info as $key => $val)
			{
				if($key === 'title') continue;
				if($key === 'content') continue;
				if($key === 'sender_srl') continue;
				if($key === 'password') continue;
				if($key === 'regdate') continue;

				$message->{$key} = $val;
			}
		}
		else
		{
			$message->member_srl = ($message->sender_srl == $logged_info->member_srl) ? $message->receiver_srl : $message->sender_srl;
			$message->user_id = '';
			$message->nick_name = lang('communication.cmd_message_from_non_member');
			$message->user_name = $message->nick_name;
		}

		// change the status if is a received and not yet read message
		if($message->message_type == 'R' && $message->readed != 'Y')
		{
			$oCommunicationController = getController('communication');
			$oCommunicationController->setMessageReaded($message_srl);
		}

		return $message;
	}

	/**
	 * get a new message
	 * @param array $columnList
	 * @return object message information
	 */
	public static function getNewMessage($columnList = array())
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->receiver_srl = $logged_info->member_srl;
		$args->readed = 'N';

		$output = executeQuery('communication.getNewMessage', $args, $columnList);
		if(!count($output->data))
		{
			return;
		}

		$message = array_pop($output->data);

		$oCommunicationController = getController('communication');
		$oCommunicationController->setMessageReaded($message->message_srl);
		
		if (!$message->member_srl)
		{
			$message->member_srl = $message->sender_srl;
			$message->user_id = '';
			$message->nick_name = lang('communication.cmd_message_from_non_member');
			$message->user_name = $message->nick_name;
		}

		return $message;
	}

	public static function getNewMessageCount($member_srl = null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		$args = new stdClass();
		$args->receiver_srl = $member_srl;
		$args->readed = 'N';

		$output = executeQuery('communication.getNewMessageCount', $args);
		return $output->data->count;
	}

	/**
	 * get a message list
	 * @param string $message_type (R: Received Message, S: Sent Message, T: Archive)
	 * @param array $columnList
	 * @return Object
	 */
	public static function getMessages($message_type = "R", $columnList = array())
	{
		$logged_info = Context::get('logged_info');
		$args = new stdClass();

		switch($message_type)
		{
			case 'R' :
				$args->member_srl = $logged_info->member_srl;
				$args->message_type = 'R';
				$query_id = 'communication.getReceivedMessages';
				break;

			case 'T' :
				$args->member_srl = $logged_info->member_srl;
				$args->message_type = 'T';
				$query_id = 'communication.getStoredMessages';
				break;

			case 'N' :
				$args->member_srl = $logged_info->member_srl;
				$args->readed = 'N';
				$query_id = 'communication.getReadedMessages';
				break;

			default :
				$args->member_srl = $logged_info->member_srl;
				$args->message_type = 'S';
				$query_id = 'communication.getSendedMessages';
				break;
		}

		// Other variables
		$args->sort_index = 'message.list_order';
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;

		// Get messages from DB
		$output = executeQueryArray($query_id, $args, $columnList);

		// Add placeholder for non-members
		foreach ($output->data as $message)
		{
			if (!$message->member_srl)
			{
				$message->member_srl = ($message->sender_srl == $logged_info->member_srl) ? $message->receiver_srl : $message->sender_srl;
				$message->user_id = '';
				$message->nick_name = lang('communication.cmd_message_from_non_member');
				$message->user_name = $message->nick_name;
			}
		}
		
		return $output;
	}
	
	/**
	 * Get a list of files attached to a message.
	 * 
	 * @param object $message
	 * @return array
	 */
	public static function getMessageFiles($message)
	{
		$upload_target_srl = $message->message_type === 'S' ? $message->message_srl : $message->related_srl;
		$file_list = getModel('file')->getFiles($upload_target_srl);
		return $file_list ?: [];
	}

	/**
	 * Get a list of friends
	 * @param int $friend_group_srl (default 0)
	 * @param array $columnList
	 * @return Object
	 */
	public static function getFriends($friend_group_srl = 0, $columnList = array())
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->friend_group_srl = $friend_group_srl;
		$args->member_srl = $logged_info->member_srl;

		// Other variables
		$args->page = Context::get('page');
		$args->sort_index = 'friend.list_order';
		$args->list_count = 10;
		$args->page_count = 10;

		$output = executeQuery('communication.getFriends', $args, $columnList);
		
		return $output;
	}

	/**
	 * check if a friend is already added
	 * @param int $member_srl
	 * @return int
	 */
	public static function isAddedFriend($member_srl)
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->target_srl = $member_srl;
		
		$output = executeQuery('communication.isAddedFriend', $args);

		return $output->data->count;
	}

	/**
	 * Get a group of friends
	 * @param int $friend_group_srl
	 * @return object
	 */
	public static function getFriendGroupInfo($friend_group_srl)
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->friend_group_srl = $friend_group_srl;

		$output = executeQuery('communication.getFriendGroup', $args);
		
		return $output->data;
	}

	/**
	 * Get a list of groups
	 * @return array
	 */
	public static function getFriendGroups()
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$output = executeQueryArray('communication.getFriendGroups', $args);
		$friend_group_list = array();
		foreach ($output->data as $item)
		{
			$friend_group_list[$item->friend_group_srl] = $item;		
		}
		return $friend_group_list;
	}

	/**
	 * check whether to be added in the friend list
	 * @param int $target_srl 
	 * @return boolean (true : friend, false : not friend) 
	 */
	public static function isFriend($target_srl)
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->member_srl = $target_srl;
		$args->target_srl = $logged_info->member_srl;

		$output = executeQuery('communication.isAddedFriend', $args);

		if($output->data->count)
		{
			return TRUE;
		}

		return FALSE;
	}

}
/* End of file communication.model.php */
/* Location: ./modules/comment/communication.model.php */
