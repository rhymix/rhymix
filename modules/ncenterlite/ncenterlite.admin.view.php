<?php
class ncenterliteAdminView extends ncenterlite
{
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(str_replace('dispNcenterliteAdmin', '', $this->act));
	}

	function dispNcenterliteAdminConfig()
	{
		$oModuleModel = getModel('module');
		$oNcenterliteModel = getModel('ncenterlite');
		$oLayoutModel = getModel('layout');

		$config = $oNcenterliteModel->getConfig();
		Context::set('config', $config);

		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0, 'M');
		Context::set('mlayout_list', $mobile_layout_list);

		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		if(!$skin_list[$config->skin]) $config->skin = 'default';
		Context::set('colorset_list', $skin_list[$config->skin]->colorset);

		if(!$mskin_list[$config->mskin]) $config->mskin = 'default';
		Context::set('mcolorset_list', $mskin_list[$config->mskin]->colorset);

		$security = new Security();
		$security->encodeHTML('config..');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('colorset_list..name','colorset_list..title');

		$mid_list = $oModuleModel->getMidList(null, array('module_srl', 'mid', 'browser_title', 'module'));

		Context::set('mid_list', $mid_list);

		// 사용환경정보 전송 확인
		$ncenterlite_module_info = $oModuleModel->getModuleInfoXml('ncenterlite');
		$agreement_file = FileHandler::getRealPath(sprintf('%s%s.txt', './files/ncenterlite/ncenterlite-', $ncenterlite_module_info->version));

		$agreement_ver_file = FileHandler::getRealPath(sprintf('%s%s.txt', './files/ncenterlite/ncenterlite_ver-', $ncenterlite_module_info->version));

		if(file_exists($agreement_file))
		{
			$agreement = FileHandler::readFile($agreement_file);
			Context::set('_ncenterlite_env_agreement', $agreement);
			$agreement_ver = FileHandler::readFile($agreement_ver_file);
			if($agreement == 'Y')
			{
				$_ncenterlite_iframe_url = 'http://sosifam.com/index.php?mid=ncenterlite_iframe';
				if(!$agreement_ver)
				{
					$_host_info = urlencode($_SERVER['HTTP_HOST']) . '-NC' . $ncenterlite_module_info->version . '-PHP' . phpversion() . '-XE' . __XE_VERSION__;
				}
				Context::set('_ncenterlite_iframe_url', $_ncenterlite_iframe_url . '&_host='. $_host_info);
				Context::set('ncenterlite_module_info', $ncenterlite_module_info);
			}
			FileHandler::writeFile($agreement_ver_file, 'Y');
		}
		else
		{
			Context::set('_ncenterlite_env_agreement', 'NULL');
		}
	}

	function dispNcenterliteAdminList()
	{
		$oNcenterliteAdminModel = getAdminModel('ncenterlite');

		$output = $oNcenterliteAdminModel->getAdminNotifyList();

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('ncenterlite_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('ncenter_list');
	}

}
