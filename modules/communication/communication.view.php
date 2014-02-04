<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationView
 * @author NAVER (developers@xpressengine.com)
 * View class of communication module
 */
class communicationView extends communication
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		$oCommunicationModel = getModel('communication');

		$this->communication_config = $oCommunicationModel->getConfig();
		$skin = $this->communication_config->skin;

		Context::set('communication_config', $this->communication_config);

		$config_parse = explode('|@|', $skin);

		if(count($config_parse) > 1)
		{
			$tpl_path = sprintf('./themes/%s/modules/communication/', $config_parse[0]);
		}
		else
		{
			$tpl_path = sprintf('%sskins/%s', $this->module_path, $skin);
		}

		$this->setTemplatePath($tpl_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->communication_config->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $this->communication_config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	/**
	 * Display message box
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationMessages()
	{
		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		if(!array_key_exists('dispCommunicationMessages', $logged_info->menu_list))
		{
			return $this->stop('msg_invalid_request');
		}

		// Set the variables
		$message_srl = Context::get('message_srl');
		$message_type = Context::get('message_type');

		if(!in_array($message_type, array('R', 'S', 'T')))
		{
			$message_type = 'R';
			Context::set('message_type', $message_type);
		}

		$oCommunicationModel = getModel('communication');

		// extract contents if message_srl exists
		if($message_srl)
		{
			$columnList = array('message_srl', 'sender_srl', 'receiver_srl', 'message_type', 'title', 'content', 'readed', 'regdate');
			$message = $oCommunicationModel->getSelectedMessage($message_srl, $columnList);

			switch($message->message_type)
			{
				case 'R':
					if($message->receiver_srl != $logged_info->member_srl)
					{
						return $this->stop('msg_invalid_request');
					}
					break;

				case 'S':
					if($message->sender_srl != $logged_info->member_srl)
					{
						return $this->stop('msg_invalid_request');
					}
					break;

				case 'T':
					if($message->receiver_srl != $logged_info->member_srl && $message->sender_srl != $logged_info->member_srl)
					{
						return $this->stop('msg_invalid_request');
					}
					break;
			}

			if($message->message_srl == $message_srl && ($message->receiver_srl == $logged_info->member_srl || $message->sender_srl == $logged_info->member_srl))
			{
				stripEmbedTagForAdmin($message->content, $message->sender_srl);
				Context::set('message', $message);
			}
		}

		// Extract a list
		$columnList = array('message_srl', 'readed', 'title', 'member.member_srl', 'member.nick_name', 'message.regdate', 'readed_date');
		$output = $oCommunicationModel->getMessages($message_type, $columnList);

		// set a template file
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('message_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('message_list..nick_name');

		$this->setTemplateFile('messages');
	}

	/**
	 * display a new message
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationNewMessage()
	{
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile('popup_layout');

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		$oCommunicationModel = getModel('communication');

		// get a new message
		$columnList = array('message_srl', 'member_srl', 'nick_name', 'title', 'content', 'sender_srl');
		$message = $oCommunicationModel->getNewMessage($columnList);
		if($message)
		{
			stripEmbedTagForAdmin($message->content, $message->sender_srl);
			Context::set('message', $message);
		}

		// Delete a flag
		$flag_path = './files/communication_extra_info/new_message_flags/' . getNumberingPath($logged_info->member_srl);
		$flag_file = sprintf('%s%s', $flag_path, $logged_info->member_srl);
		FileHandler::removeFile($flag_file);

		$this->setTemplateFile('new_message');
	}

	/**
	 * Display message sending
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationSendMessage()
	{
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile("popup_layout");

		$oCommunicationModel = getModel('communication');
		$oMemberModel = getModel('member');

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// get receipient's information
		// check inalid request
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			return $this->stop('msg_invalid_request');
		}

		// check receiver and sender are same
		if($logged_info->member_srl == $receiver_srl)
		{
			return $this->stop('msg_cannot_send_to_yourself');
		}

		// get message_srl of the original message if it is a reply
		$message_srl = Context::get('message_srl');
		if($message_srl)
		{
			$source_message = $oCommunicationModel->getSelectedMessage($message_srl);
			if($source_message->message_srl == $message_srl && $source_message->sender_srl == $receiver_srl)
			{
				$source_message->title = "[re] " . $source_message->title;
				$source_message->content = "\r\n<br />\r\n<br /><div style=\"padding-left:5px; border-left:5px solid #DDDDDD;\">" . trim($source_message->content) . "</div>";
				Context::set('source_message', $source_message);
			}
		}

		$receiver_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
		if(!$receiver_info)
		{
			return $this->stop('msg_invalid_request');
		}

		Context::set('receiver_info', $receiver_info);

		// set a signiture by calling getEditor of the editor module
		$oEditorModel = getModel('editor');
		$option = new stdClass();
		$option->primary_key_name = 'receiver_srl';
		$option->content_key_name = 'content';
		$option->allow_fileupload = FALSE;
		$option->enable_autosave = FALSE;
		$option->enable_default_component = TRUE; // FALSE;
		$option->enable_component = FALSE;
		$option->resizable = FALSE;
		$option->disable_html = TRUE;
		$option->height = 300;
		$option->skin = $this->communication_config->editor_skin;
		$option->colorset = $this->communication_config->editor_colorset;
		$editor = $oEditorModel->getEditor($logged_info->member_srl, $option);
		Context::set('editor', $editor);

		$this->setTemplateFile('send_message');
	}

	/**
	 * display a list of friends
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationFriend()
	{
		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$oCommunicationModel = getModel('communication');

		// get a group list
		$tmp_group_list = $oCommunicationModel->getFriendGroups();
		$group_count = count($tmp_group_list);

		for($i = 0; $i < $group_count; $i++)
		{
			$friend_group_list[$tmp_group_list[$i]->friend_group_srl] = $tmp_group_list[$i];
		}

		Context::set('friend_group_list', $friend_group_list);

		// get a list of friends
		$friend_group_srl = Context::get('friend_group_srl');
		$columnList = array('friend_srl', 'friend_group_srl', 'target_srl', 'member.nick_name', 'friend.regdate');

		$output = $oCommunicationModel->getFriends($friend_group_srl, $columnList);
		$friend_count = count($output->data);

		if($friend_count)
		{
			foreach($output->data as $key => $val)
			{
				$group_srl = $val->friend_group_srl;
				$group_title = $friend_group_list[$group_srl]->title;
				if(!$group_title)
				{
					$group_title = Context::get('default_friend_group');
				}
				$output->data[$key]->group_title = $group_title;
			}
		}

		// set a template file
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('friend_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('friends');
	}

	/**
	 * display Add a friend
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationAddFriend()
	{
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile("popup_layout");

		// error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$logged_info = Context::get('logged_info');
		$target_srl = Context::get('target_srl');

		if(!$target_srl)
		{
			return $this->stop('msg_invalid_request');
		}

		// get information of the member
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');
		$communication_info = $oMemberModel->getMemberInfoByMemberSrl($target_srl);

		if($communication_info->member_srl != $target_srl)
		{
			return $this->stop('msg_invalid_request');
		}

		Context::set('target_info', $communication_info);

		// get a group list
		$friend_group_list = $oCommunicationModel->getFriendGroups();
		Context::set('friend_group_list', $friend_group_list);

		$this->setTemplateFile('add_friend');
	}

	/**
	 * display add a group of friends
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationAddFriendGroup()
	{
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile("popup_layout");

		// error apprears if not logged-in
		if(!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}

		$logged_info = Context::get('logged_info');

		// change to edit mode when getting the group_srl
		$friend_group_srl = Context::get('friend_group_srl');
		if($friend_group_srl)
		{
			$oCommunicationModel = getModel('communication');
			$friend_group = $oCommunicationModel->getFriendGroupInfo($friend_group_srl);
			if($friend_group->friend_group_srl == $friend_group_srl)
			{
				Context::set('friend_group', $friend_group);
			}
		}

		$this->setTemplateFile('add_friend_group');
	}

}
/* End of file communication.view.php */
/* Location: ./modules/comment/communication.view.php */
