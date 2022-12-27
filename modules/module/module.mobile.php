<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
class ModuleMobile extends ModuleObject
{
	function dispModuleChangeLang()
	{
		$this->setTemplatePath(sprintf("%stpl/",$this->module_path));
		$this->setTemplateFile('lang.html');
	}
}
/* End of file module.mobile.php */
/* Location: ./modules/module/module.mobile.php */
