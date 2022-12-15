<?php

class tagAdminView extends tag
{
	public function dispTagAdminConfig()
	{
		$config = ModuleModel::getModuleConfig($this->module) ?: new stdClass;
		Context::set('tag_config', $config);
		
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('config');
	}
}
