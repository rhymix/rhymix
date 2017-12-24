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

		$this->config = $oCommunicationModel->getConfig();
		Context::set('communication_config', $this->config);

		$mskin = $this->config->mskin;
		if(!$mskin)
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
		}
		elseif($mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf("%sskins/%s/", $this->module_path, $this->config->skin);
			if(!is_dir($template_path) || !$this->config->skin)
			{
				$template_path = sprintf("%sskins/%s/", $this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->config->mlayout_srl);
		if($layout_info)
		{
			$this->module_info->mlayout_srl = $this->config->mlayout_srl;
			$this->setLayoutPath($layout_info->path);
		}
		
		$this->setTemplatePath($template_path);
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
