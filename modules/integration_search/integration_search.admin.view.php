<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin view class of the integration_search module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_searchAdminView extends integration_search
{
	/**
	 * Cofiguration of integration serach module
	 *
	 * @var object module config
	 */
	var $config = null;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$this->config = $oModuleModel->getModuleConfig('integration_search');
		Context::set('config',$this->config);

		$this->setTemplatePath($this->module_path."/tpl/");
	}

	/**
	 * Module selection and skin set
	 *
	 * @return Object
	 */
	function dispIntegration_searchAdminContent()
	{
		// Get a list of skins(themes)
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		// Get a list of module categories
		$module_categories = $oModuleModel->getModuleCategories();
		// Generated mid Wanted list
		$obj = new stdClass();
		$obj->site_srl = 0;

		// Shown below as obsolete comments - modify by cherryfilter
		/*$mid_list = $oModuleModel->getMidList($obj);
		// module_category and module combination
		if($module_categories) {
		foreach($mid_list as $module_srl => $module) {
		$module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
		}
		} else {
		$module_categories[0]->list = $mid_list;
		}

		Context::set('mid_list',$module_categories);*/

		$security = new Security();
		$security->encodeHTML('skin_list..title');

		// Sample Code
		Context::set('sample_code', htmlspecialchars('<form action="{getUrl()}" method="get"><input type="hidden" name="vid" value="{$vid}" /><input type="hidden" name="mid" value="{$mid}" /><input type="hidden" name="act" value="IS" /><input type="text" name="is_keyword"  value="{$is_keyword}" /><input class="btn" type="submit" value="{$lang->cmd_search}" /></form>', ENT_COMPAT | ENT_HTML401, 'UTF-8', false) );

		$this->setTemplateFile("index");
	}

	/**
	 * Skin Settings
	 *
	 * @return Object
	 */
	function dispIntegration_searchAdminSkinInfo()
	{
		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $this->config->skin);
		$skin_vars = unserialize($this->config->skin_vars);
		// value for skin_info extra_vars
		if(count($skin_info->extra_vars))
		{
			foreach($skin_info->extra_vars as $key => $val)
			{
				$name = $val->name;
				$type = $val->type;
				$value = $skin_vars->{$name};
				if($type=="checkbox"&&!$value) $value = array();
				$skin_info->extra_vars[$key]->value= $value;
			}
		}
		Context::set('skin_info', $skin_info);
		Context::set('skin_vars', $skin_vars);

		$config = $oModuleModel->getModuleConfig('integration_search');
		Context::set('module_info', unserialize($config->skin_vars));

		$security = new Security();
		$security->encodeHTML('skin_info...');

		$this->setTemplateFile("skin_info");
	}
}
/* End of file integration_search.admin.view.php */
/* Location: ./modules/integration_search/integration_search.admin.view.php */
