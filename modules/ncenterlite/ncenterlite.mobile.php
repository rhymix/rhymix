<?php

class ncenterliteMobile extends ncenterliteView
{
	function init()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $config->mskin);
		if(!is_dir($template_path)||!$config->mskin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $config->mskin);
		}
		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->mlayout_srl);

		if($layout_info)
		{
			$this->module_info->mlayout_srl = $config->mlayout_srl;
			$this->setLayoutPath($layout_info->path);
		}

	}

	function dispNcenterliteNotifyList()
	{
		parent::dispNcenterliteNotifyList();
	}

	function dispNcenterliteUserConfig()
	{
		parent::dispNcenterliteUserConfig();
	}

}
