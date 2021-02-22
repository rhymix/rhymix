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
		// Get module config
		$oEditorModel = getModel('editor');
		$editor_config = ModuleModel::getModuleConfig('editor');
		if (!is_object($editor_config))
		{
			$editor_config = new stdClass();
		}

		// Use default config for missing values.
		foreach (self::$default_editor_config as $key => $val)
		{
			if (!isset($editor_config->$key))
			{
				$editor_config->$key = $val;
			}
		}
		
		// Get skin info
		$editor_skin_list = array();
		$skin_dir_list = FileHandler::readDir($this->module_path . 'skins');
		foreach ($skin_dir_list as $skin)
		{
			if (starts_with('xpresseditor', $skin) || starts_with('dreditor', $skin))
			{
				continue;
			}
			
			$skin_info = ModuleModel::loadSkinInfo($this->module_path, $skin);
			foreach ($skin_info->colorset ?: [] as $colorset)
			{
				unset($colorset->screenshot);
			}
			$editor_skin_list[$skin] = $skin_info;
		}

		// Get editor component info
		$oAutoinstallModel = getModel('autoinstall');
		$component_list = $oEditorModel->getComponentList(false, 0, true);
		$component_count = countobj($component_list);
		$targetpackages = array();
		foreach ($component_list as $xml_info)
		{
			$xml_info->path = './modules/editor/components/'.$xml_info->component_name;
			$xml_info->delete_url = $oAutoinstallModel->getRemoveUrlByPath($xml_info->path);
			$xml_info->package_srl = $oAutoinstallModel->getPackageSrlByPath($xml_info->path);
			if ($xml_info->package_srl)
			{
				$targetpackages[$xml_info->package_srl] = 0;
			}
		}
		if (count($targetpackages))
		{
			$packages = $oAutoinstallModel->getInstalledPackages(array_keys($targetpackages));
		}
		foreach ($component_list as $xml_info)
		{
			if ($packages[$xml_info->package_srl])
			{
				$xml_info->need_update = $packages[$xml_info->package_srl]->need_update;
			}
		}
		
		Context::set('editor_config', $editor_config);
		Context::set('editor_skin_list', $editor_skin_list);
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
		// Get information of the editor component
		$oEditorModel = getModel('editor');
		$component_name = Context::get('component_name');
		$component = $oEditorModel->getComponent($component_name);
		if(!$component->component_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		Context::set('component', $component);
		
		// Get a group list to set a group
		$group_list = MemberModel::getGroups(0);
		Context::set('group_list', $group_list);

		// Get a mid list
		$args =new stdClass();
		$args->site_srl = 0;
		$columnList = array('module_srl', 'mid', 'module_category_srl', 'browser_title');
		$mid_list = ModuleModel::getMidList($args, $columnList);

		// Combination of module_category and module
		if(!$args->site_srl)
		{
			// Get a list of module category
			$module_categories = ModuleModel::getModuleCategories();

			if(!is_array($mid_list)) $mid_list = array($mid_list);
			foreach($mid_list as $module_srl => $module)
			{
				if($module && isset($module_categories[$module->module_category_srl]))
				{
					$module_categories[$module->module_category_srl]->list[$module_srl] = $module;
				}
			}
		}
		else
		{
			$module_categories = array(new stdClass);
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
