<?php
require_once(_XE_PATH_.'modules/communication/communication.view.php');
class communicationMobile extends communicationView {
	function init() {
		$oCommunicationModel = &getModel('communication');

		$this->communication_config = $oCommunicationModel->getConfig();
		$skin = $this->communication_config->mskin;

		Context::set('communication_config', $this->communication_config);

		$tpl_path = sprintf('%sm.skins/%s', $this->module_path, $skin);
		$this->setTemplatePath($tpl_path);

		$oLayoutModel = &getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->communication_config->mlayout_srl);
		if($layout_info)
		{
			$this->module_info->mlayout_srl = $this->communication_config->mlayout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

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
		$message_type = 'R';
		Context::set('message_type', $message_type);

		$oCommunicationModel = &getModel('communication');
		// extract contents if message_srl exists
		if($message_srl) 
		{
			$templateFile = 'read_message';
			$columnList = array('message_srl', 'sender_srl', 'receiver_srl', 'message_type', 'title', 'content', 'readed', 'regdate');
			$message = $oCommunicationModel->getSelectedMessage($message_srl, $columnList);
			if($message->message_srl == $message_srl && ($message->receiver_srl == $logged_info->member_srl) ) 
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
}
