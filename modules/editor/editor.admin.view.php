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

		//editor_config init
		if(!$editor_config->editor_height) $editor_config->editor_height = 300;
		if(!$editor_config->comment_editor_height) $editor_config->comment_editor_height = 100;
		if(!$editor_config->editor_skin) $editor_config->editor_skin = 'ckeditor';
		if(!$editor_config->comment_editor_skin) $editor_config->comment_editor_skin = 'ckeditor';
		if(!$editor_config->sel_editor_colorset) $editor_config->sel_editor_colorset= 'moono';
		if(!$editor_config->sel_comment_editor_colorset) $editor_config->sel_comment_editor_colorset= 'moono';

		$component_list = $oEditorModel->getComponentList(false, $site_srl, true);
		$editor_skin_list = FileHandler::readDir(_XE_PATH_.'modules/editor/skins');

		$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->editor_skin);

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
		$editor_config_default = array( "editor_height" => "300", "comment_editor_height" => "100","content_font_size"=>"13");

		//editor preview
		$config = $oEditorModel->getEditorConfig();

		$option = new stdClass();
		$option->allow_fileupload = false;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->enable_autosave = false;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = $config->editor_height;
		$option->skin = $config->editor_skin;
		$option->content_key_name = 'dummy_content';
		$option->primary_key_name = 'dummy_key';
		$option->colorset = $config->sel_editor_colorset;
		$editor = $oEditorModel->getEditor(0, $option);

		Context::set('preview_editor', $editor);

		$option_com = new stdClass();
		$option_com->allow_fileupload = false;
		$option_com->content_style = $config->content_style;
		$option_com->content_font = $config->content_font;
		$option_com->content_font_size = $config->content_font_size;
		$option_com->enable_autosave = false;
		$option_com->enable_default_component = true;
		$option_com->enable_component = true;
		$option_com->disable_html = false;
		$option_com->height = $config->comment_editor_height;
		$option_com->skin = $config->comment_editor_skin;
		$option_com->content_key_name = 'dummy_content2';
		$option_com->primary_key_name = 'dummy_key2';
		$option_com->content_style = $config->comment_content_style;
		$option_com->colorset = $config->sel_comment_editor_colorset;

		$editor_comment = $oEditorModel->getEditor(0, $option_com);

		Context::set('preview_editor_comment', $editor_comment);

		Context::set('editor_config', $editor_config);
		Context::set('editor_skin_list', $editor_skin_list);
		Context::set('editor_colorset_list', $skin_info->colorset);
		Context::set('content_style_list', $content_style_list);
		Context::set('component_list', $component_list);
		Context::set('component_count', $component_count);
		Context::set('editor_config_default', $editor_config_default);

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
		$security->encodeHTML('component...');
		$security->encodeHTML('mid_list..title','mid_list..list..browser_title');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('setup_component');
	}
}
/* End of file editor.admin.view.php */
/* Location: ./modules/editor/editor.admin.view.php */
