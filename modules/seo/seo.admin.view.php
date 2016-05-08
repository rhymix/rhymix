<?php
class seoAdminView extends seo
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(str_replace('dispSeo', '', $this->act));
	}

	function dispSeoAdminDashboard()
	{
		$oModuleModel = getModel('module');
	}

	function dispSeoAdminSetting()
	{
		$vars = Context::getRequestVars();
		if (!$vars->setting_section)
		{
			Context::set('setting_section', 'general');
		}

		$config = $this->getConfig();

		$db_info = Context::getDBInfo();
		$hostname = parse_url($db_info->default_url);
		$hostname = $hostname['host'];

		Context::set('config', $config);
		Context::set('hostname', $hostname);
	}
}
/* !End of file */
