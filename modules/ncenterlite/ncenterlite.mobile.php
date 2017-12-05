<?php

class ncenterliteMobile extends ncenterliteView
{
	function init()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$mskin = $config->mskin;
		if(!$mskin)
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
		}
		elseif($mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
			if(!is_dir($template_path) || !$config->skin)
			{
				$template_path = sprintf("%sskins/%s/", $this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
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
}
