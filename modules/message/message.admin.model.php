<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
class messageAdminModel extends message{
	public function getMessageAdminColorset()
	{
		$skin = Context::get('skin');
		$type = Context::get('type') == 'M' ? 'M' : 'P';
		Context::set('type', $type);
		$dir = $type == 'P' ? 'skins' : 'm.skins';

		if(!$skin)
		{
			$tpl = '';
		}
		else
		{
			$oModuleModel = getModel('module'); /* @var $oModuleModel moduleModel */
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin, $dir);
			Context::set('skin_info', $skin_info);

			$config = $oModuleModel->getModuleConfig('message');
			Context::set('config', $config);

			$oTemplate = TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path.'tpl', 'colorset_list');
		}

		$this->add('tpl', $tpl);
	}
}
