<?php
class ncenterliteAdminView extends ncenterlite
{
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispNcenterliteAdmin', '', $this->act)));
	}

	function dispNcenterliteAdminConfig()
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$config = $oNcenterliteModel->getConfig();
		Context::set('config', $config);
		Context::set('notify_types', NcenterliteModel::getNotifyTypes());
		Context::set('sms_available', Rhymix\Framework\SMS::getDefaultDriver()->getName() !== 'Dummy');
		Context::set('push_available', count(Rhymix\Framework\Config::get('push.types') ?? []) > 0);
	}

	function dispNcenterliteAdminSeletedmid()
	{
		$oModuleModel = getModel('module');
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$mid_list = $oModuleModel->getMidList(null, array('module_srl', 'mid', 'browser_title', 'module'));

		Context::set('mid_list', $mid_list);
		Context::set('config', $config);
	}
	
	function dispNcenterliteAdminOtherComment()
	{
		$oModuleModel = getModel('module');
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$mid_list = $oModuleModel->getMidList(null, array('module_srl', 'mid', 'browser_title', 'module'));

		Context::set('mid_list', $mid_list);
		Context::set('config', $config);
	}

	function dispNcenterliteAdminSkinsetting()
	{
		$oModuleModel = getModel('module');
		$oLayoutModel = getModel('layout');
		$oNcenterliteModel = getModel('ncenterlite');

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
	}

	function dispNcenterliteAdminAdvancedconfig()
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $value)
		{
			if($value->type == 'tel')
			{
				$variable_name[] = $value->name;
			}
		}

		$config = $oNcenterliteModel->getConfig();
		Context::set('config', $config);
		Context::set('variable_name', $variable_name);
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
	}

	function dispNcenterliteAdminTest()
	{

	}

	function dispNcenterliteAdminCustomList()
	{
		$oNcenterliteAdminModel = getAdminModel('ncenterlite');

		$output = $oNcenterliteAdminModel->getNotifyType();
		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('type_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
	}
}
