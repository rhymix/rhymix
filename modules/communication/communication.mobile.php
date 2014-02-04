<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once(_XE_PATH_ . 'modules/communication/communication.view.php');

/**
 * @class  communicationMobile
 * @author NAVER (developers@xpressengine.com)
 * Mobile class of communication module
 */
class communicationMobile extends communicationView
{

	function init()
	{
		$oCommunicationModel = getModel('communication');

		$this->communication_config = $oCommunicationModel->getConfig();
		$skin = $this->communication_config->mskin;

		Context::set('communication_config', $this->communication_config);

		$tpl_path = sprintf('%sm.skins/%s', $this->module_path, $skin);
		$this->setTemplatePath($tpl_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->communication_config->mlayout_srl);
		if($layout_info)
		{
			$this->module_info->mlayout_srl = $this->communication_config->mlayout_srl;
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
			$templateFile = 'read_message';
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
		else
		{
			$templateFile = 'messages';
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

		$this->setTemplateFile($templateFile);
	}

	/**
	 * Display list of message box
	 * @return void
	 */
	function dispCommunicationMessageBoxList()
	{
		$this->setTemplateFile('message_box');
	}

	/**
	 * Display message sending
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispCommunicationSendMessage()
	{
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
		$this->setTemplateFile('send_message');
	}

}
/* End of file communication.mobile.php */
/* Location: ./modules/comment/communication.mobile.php */
