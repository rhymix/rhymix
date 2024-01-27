<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationView
 * @author NAVER (developers@xpressengine.com)
 * View class of communication module
 */
class CommunicationView extends communication
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		$this->config = CommunicationModel::getConfig();
		Context::set('communication_config', $this->config);
		$this->setLayoutAndTemplatePaths('P', $this->config);
	}

	/**
	 * Display message box
	 * @return object (Object : fail)
	 */
	function dispCommunicationMessages()
	{
		if($this->config->enable_message == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		$logged_info = Context::get('logged_info');

		// Set the variables
		$message_srl = intval(Context::get('message_srl'));
		$message_type = Context::get('message_type');

		if(!in_array($message_type, array('R', 'S', 'T', 'N')))
		{
			$message_type = 'R';
			Context::set('message_type', $message_type);
		}

		// extract contents if message_srl exists
		$oCommunicationModel = getModel('communication');
		$template_filename = 'messages';
		if($message_srl)
		{
			$columnList = array('message_srl', 'message_type', 'related_srl', 'sender_srl', 'receiver_srl', 'title', 'content', 'readed', 'regdate');
			$message = $oCommunicationModel->getSelectedMessage($message_srl, $columnList);

			switch($message->message_type)
			{
				case 'R':
					if($message->receiver_srl != $logged_info->member_srl)
					{
						throw new Rhymix\Framework\Exceptions\TargetNotFound;
					}
					break;

				case 'S':
					if($message->sender_srl != $logged_info->member_srl)
					{
						throw new Rhymix\Framework\Exceptions\TargetNotFound;
					}
					break;

				case 'T':
					if($message->receiver_srl != $logged_info->member_srl && $message->sender_srl != $logged_info->member_srl)
					{
						throw new Rhymix\Framework\Exceptions\TargetNotFound;
					}
					break;

				case 'N':
					if($message->receiver_srl != $logged_info->member_srl)
					{
						throw new Rhymix\Framework\Exceptions\TargetNotFound;
					}
					break;
			}

			if($message->message_srl == $message_srl && ($message->receiver_srl == $logged_info->member_srl || $message->sender_srl == $logged_info->member_srl))
			{
				stripEmbedTagForAdmin($message->content, $message->sender_srl);
				Context::set('message', $message);
				Context::set('message_files', CommunicationModel::getMessageFiles($message));

				if(Mobile::isFromMobilePhone() && file_exists($this->getTemplatePath() . 'read_message.html'))
				{
					$template_filename = 'read_message';
				}
			}
			else
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}
		}

		// Set search conditions
		$search_target = Context::get('search_target');
		if (!in_array($search_target, ['title', 'title_content', 'content']))
		{
			$search_target = null;
		}
		$search_keyword = utf8_clean(Context::get('search_keyword'));
		Context::set('search_target', $search_target);
		Context::set('search_keyword', $search_keyword !== '' ? $search_keyword : null);

		// Extract a list
		$columnList = array('message_srl', 'message_type', 'related_srl', 'sender_srl', 'receiver_srl', 'title', 'member.member_srl', 'member.nick_name', 'message.regdate', 'readed', 'readed_date');
		$page = max(1, intval(Context::get('page')));
		$output = $oCommunicationModel->getMessages($message_type, $columnList, $search_target, $search_keyword, $page);

		// set a template file
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('message_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('message_list..nick_name');

		$this->setTemplateFile($template_filename);
	}

	/**
	 * display a new message
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationNewMessage()
	{
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile('popup_layout');

		if($this->config->enable_message == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		// get a new message
		$columnList = array('message_srl', 'member_srl', 'nick_name', 'title', 'content', 'sender_srl');
		$message = CommunicationModel::getInstance()->getNewMessage($columnList);
		if($message)
		{
			stripEmbedTagForAdmin($message->content, $message->sender_srl);
			Context::set('message', $message);
		}

		$this->setTemplateFile('new_message');
	}

	/**
	 * Display message sending
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationSendMessage()
	{
		// If window type is self, use member module layout.
		// Otherwise, assume it's a popup window on PC for backward compatibility.
		if(Context::get('window_type') === 'self')
		{
			$oMemberView = MemberView::getInstance();
			if (!$oMemberView->checkMidAndRedirect())
			{
				$this->setRedirectUrl($oMemberView->getRedirectUrl());
				return;
			}
		}
		elseif(!Context::get('m'))
		{
			$this->setLayoutPath('./common/tpl/');
			$this->setLayoutFile("popup_layout");
		}

		if($this->config->enable_message == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check permission
		if(!getModel('communication')->checkGrant($this->config->grant_send))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		// Fix missing mid (it causes errors when uploading)
		if(!Context::get('mid'))
		{
			Context::set('mid', Context::get('site_module_info')->mid);
		}

		// Check receipient info
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$logged_info = Context::get('logged_info');
		if($logged_info->member_srl == $receiver_srl)
		{
			throw new Rhymix\Framework\Exception('msg_cannot_send_to_yourself');
		}
		$receiver_info = MemberModel::getMemberInfoByMemberSrl($receiver_srl);
		if(!$receiver_info || empty($receiver_info->member_srl))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest('msg_invalid_recipient');
		}

		Context::set('receiver_info', $receiver_info);

		// get message_srl of the original message if it is a reply
		$message_srl = Context::get('message_srl');
		if($message_srl)
		{
			$source_message = CommunicationModel::getSelectedMessage($message_srl);
			if($source_message->message_srl == $message_srl && $source_message->sender_srl == $receiver_srl && $source_message->receiver_srl == $logged_info->member_srl)
			{
				if(strncasecmp('[re]', $source_message->title, 4) !== 0)
				{
					$source_message->title = '[re] ' . $source_message->title;
				}
				$source_message->content = "\r\n<br />\r\n<br /><div style=\"padding-left:5px; border-left:5px solid #DDDDDD;\">" . trim($source_message->content) . "</div>";
				Context::set('source_message', $source_message);
			}
			else
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}
		}

		// set a signiture by calling getEditor of the editor module
		$oEditorModel = getModel('editor');
		$option = $oEditorModel->getEditorConfig();
		$option->default_editor_settings = false;
		$option->primary_key_name = 'temp_srl';
		$option->content_key_name = 'content';
		$option->allow_fileupload = $this->config->enable_attachment === 'Y';
		$option->allowed_filesize = ($this->config->attachment_size_limit ?? 0) * 1024;
		$option->enable_autosave = FALSE;
		$option->enable_default_component = TRUE; // FALSE;
		$option->enable_component = FALSE;
		$option->resizable = FALSE;
		$option->disable_html = TRUE;
		$option->height = 300;
		$option->editor_skin = $this->config->editor_skin;
		$option->sel_editor_colorset = $this->config->editor_colorset;
		$option->editor_focus = Context::get('source_message') ? 'Y' : 'N';
		if(Context::get('m'))
		{
			$option->editor_toolbar_hide = 'Y';
		}
		if ($option->allow_fileupload)
		{
			$option->module_srl = MemberView::getInstance()->getMemberModuleSrl();
			$option->upload_target_type = 'msg';
		}
		$editor = $oEditorModel->getEditor(getNextSequence(), $option);
		$editor = $editor . "\n" . '<input type="hidden" name="temp_srl" value="" />' . "\n";
		Context::set('editor', $editor);
		$this->setTemplateFile('send_message');

		// Fix for skins that don't support window_type=self
		if(Context::get('window_type') === 'self')
		{
			Context::loadFile([$this->module_path . 'tpl/js/window_type.js', 'body']);
		}
	}

	/**
	 * display a list of friends
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationFriend()
	{
		if($this->config->enable_friend == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		$oCommunicationModel = getModel('communication');

		// get a group list
		$friend_group_list = $oCommunicationModel->getFriendGroups();
		Context::set('friend_group_list', $friend_group_list);

		// get a list of friends
		$friend_group_srl = Context::get('friend_group_srl') ?: 0;
		Context::set('friend_group_srl', $friend_group_srl);
		$columnList = array('friend_srl', 'friend_group_srl', 'target_srl', 'member.nick_name', 'friend.regdate');

		$output = $oCommunicationModel->getFriends($friend_group_srl, $columnList);
		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				$group_srl = $val->friend_group_srl;
				if (isset($friend_group_list[$group_srl]->title))
				{
					$output->data[$key]->group_title = $friend_group_list[$group_srl]->title;
				}
				else
				{
					$output->data[$key]->group_title = lang('default_friend_group');
				}
			}
		}
		else
		{
			$output->data = [];
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
		// If window type is self, use member module layout.
		// Otherwise, assume it's a popup window on PC for backward compatibility.
		if(Context::get('window_type') === 'self')
		{
			$oMemberView = MemberView::getInstance();
			if (!$oMemberView->checkMidAndRedirect())
			{
				$this->setRedirectUrl($oMemberView->getRedirectUrl());
				return;
			}
		}
		elseif(!Context::get('m'))
		{
			$this->setLayoutPath('./common/tpl/');
			$this->setLayoutFile("popup_layout");
		}

		if($this->config->enable_friend == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// error appears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		$logged_info = Context::get('logged_info');
		$target_srl = Context::get('target_srl');

		if(!$target_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if($target_srl == $logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exception('msg_no_self_friend');
		}

		// get information of the member
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');
		$communication_info = $oMemberModel->getMemberInfoByMemberSrl($target_srl);
		if(!$communication_info || !$communication_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		Context::set('target_info', $communication_info);

		// get a group list
		$friend_group_list = $oCommunicationModel->getFriendGroups();
		Context::set('friend_group_list', $friend_group_list);

		$this->setTemplateFile('add_friend');

		// Fix for skins that don't support window_type=self
		if(Context::get('window_type') === 'self')
		{
			Context::loadFile([$this->module_path . 'tpl/js/window_type.js', 'body']);
		}
	}

	/**
	 * display add a group of friends
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationAddFriendGroup()
	{
		// If window type is self, use member module layout.
		// Otherwise, assume it's a popup window on PC for backward compatibility.
		if(Context::get('window_type') === 'self')
		{
			$oMemberView = MemberView::getInstance();
			if (!$oMemberView->checkMidAndRedirect())
			{
				$this->setRedirectUrl($oMemberView->getRedirectUrl());
				return;
			}
		}
		elseif(!Context::get('m'))
		{
			$this->setLayoutPath('./common/tpl/');
			$this->setLayoutFile("popup_layout");
		}

		if($this->config->enable_friend == 'N')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// error apprears if not logged-in
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

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

		// Fix for skins that don't support window_type=self
		if(Context::get('window_type') === 'self')
		{
			Context::loadFile([$this->module_path . 'tpl/js/window_type.js', 'body']);
		}
	}
}
/* End of file communication.view.php */
/* Location: ./modules/comment/communication.view.php */
