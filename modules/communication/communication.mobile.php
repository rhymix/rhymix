<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

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
	 * Display list of message box
	 * @return void
	 */
	function dispCommunicationMessageBoxList()
	{
		$this->setTemplateFile('message_box');
	}
}
/* End of file communication.mobile.php */
/* Location: ./modules/comment/communication.mobile.php */
