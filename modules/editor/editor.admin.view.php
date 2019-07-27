<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editorAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief editor admin view of the module class
 */
class editorAdminView extends editor
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Administrator Setting page
	 * Settings to enable/disable editor component and other features
	 */
	function dispEditorAdminIndex()
	{
		$component_count = 0;
		$site_module_info = Context::get('site_module_info');
		$site_srl = (int)$site_module_info->site_srl;

		// Get a type of component
		$oEditorModel = getModel('editor');
		$oModuleModel = getModel('module');
		$editor_config = $oModuleModel->getModuleConfig('editor');

		if(!$editor_config)
		{
			$editor_config = new stdClass();
		}

		// Use default config for missing values.
		foreach ($this->default_editor_config as $key => $val)
		{
			if (!$editor_config->$key)
			{
				$editor_config->$key = $val;
			}
		}

		$component_list = $oEditorModel->getComponentList(false, $site_srl, true);
		$editor_skin_list = FileHandler::readDir(_XE_PATH_.'modules/editor/skins');
		$editor_skin_list = array_filter($editor_skin_list, function($name) { return !starts_with('xpresseditor', $name) && !starts_with('dreditor', $name); });

		$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->editor_skin);
		$comment_skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->comment_editor_skin);

		$contents = FileHandler::readDir(_XE_PATH_.'modules/editor/styles');
		$content_style_list = array();
		for($i=0,$c=count($contents);$i<$c;$i++)
		{
			$style = $contents[$i];
			$info = $oModuleModel->loadSkinInfo($this->module_path,$style,'styles');
			$content_style_list[$style] = new stdClass();
			$content_style_list[$style]->title = $info->title;
		}

		// Get install info, update info, count
		$oAutoinstallModel = getModel('autoinstall');
		foreach($component_list as $component_name => $xml_info)
		{
			$component_count++;
			$xml_info->path = './modules/editor/components/'.$xml_info->component_name;
			$xml_info->delete_url = $oAutoinstallModel->getRemoveUrlByPath($xml_info->path);
			$xml_info->package_srl = $oAutoinstallModel->getPackageSrlByPath($xml_info->path);
			if($xml_info->package_srl) $targetpackages[$xml_info->package_srl] = 0;
		}

		if(is_array($targetpackages))	$packages = $oAutoinstallModel->getInstalledPackages(array_keys($targetpackages));

		foreach($component_list as $component_name => $xml_info)
		{
			if($packages[$xml_info->package_srl])	$xml_info->need_update = $packages[$xml_info->package_srl]->need_update;
		}
		
		Context::set('editor_config', $editor_config);
		Context::set('editor_skin_list', $editor_skin_list);
		Context::set('editor_colorset_list', $skin_info->colorset);
		Context::set('comment_editor_colorset_list', $comment_skin_info->colorset);
		Context::set('content_style_list', $content_style_list);
		Context::set('component_list', $component_list);
		Context::set('component_count', $component_count);

		$security = new Security();
		$security->encodeHTML('component_list....');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('admin_index');
	}

	/**
	 * @brief Component setup
	 */
	function dispEditorAdminSetupComponent()
	{
		$site_module_info = Context::get('site_module_info');
		$site_srl = (int)$site_module_info->site_srl;

		$component_name = Context::get('component_name');
		// Get information of the editor component
		$oEditorModel = getModel('editor');
		$component = $oEditorModel->getComponent($component_name,$site_srl);

		if(!$component->component_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		Context::set('component', $component);
		// Get a group list to set a group
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups($site_srl);
		Context::set('group_list', $group_list);
		// Get a mid list
		$oModuleModel = getModel('module');

		$args =new stdClass();
		$args->site_srl = $site_srl;
		$columnList = array('module_srl', 'mid', 'module_category_srl', 'browser_title');
		$mid_list = $oModuleModel->getMidList($args, $columnList);
		// Combination of module_category and module
		if(!$args->site_srl)
		{
			// Get a list of module category
			$module_categories = $oModuleModel->getModuleCategories();

			if(!is_array($mid_list)) $mid_list = array($mid_list);
			foreach($mid_list as $module_srl => $module)
			{
				if($module) $module_categories[$module->module_category_srl]->list[$module_srl] = $module; 
			}
		}
		else
		{
			$module_categories[0]->list = $mid_list;
		}

		Context::set('mid_list',$module_categories);

		//Security
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('component...', 'component_name');
		$security->encodeHTML('mid_list..title','mid_list..list..browser_title');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('setup_component');
	}
}
/* End of file editor.admin.view.php */
/* Location: ./modules/editor/editor.admin.view.php */
