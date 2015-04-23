<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  editorView
 * @author NAVER (developers@xpressengine.com)
 * @brief view class of the editor module
 */
class editorView extends editor
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Action to get a request to display compoenet pop-up
	 */
	function dispEditorPopup()
	{
		// add a css file
		Context::loadFile($this->module_path."tpl/css/editor.css", true);
		// List variables
		$editor_sequence = Context::get('editor_sequence');
		$component = Context::get('component');

		$site_module_info = Context::get('site_module_info');
		$site_srl = (int)$site_module_info->site_srl;
		// Get compoenet object
		$oEditorModel = getModel('editor');
		$oComponent = &$oEditorModel->getComponentObject($component, $editor_sequence, $site_srl);
		if(!$oComponent->toBool())
		{
			Context::set('message', sprintf(Context::getLang('msg_component_is_not_founded'), $component));
			$this->setTemplatePath($this->module_path.'tpl');
			$this->setTemplateFile('component_not_founded');
		}
		else
		{
			// Get the result after executing a method to display popup url of the component
			$popup_content = $oComponent->getPopupContent();
			Context::set('popup_content', $popup_content);
			// Set layout to popup_layout
			$this->setLayoutFile('popup_layout');
			// Set a template
			$this->setTemplatePath($this->module_path.'tpl');
			$this->setTemplateFile('popup');
		}
	}

	/**
	 * @brief Get component information
	 */
	function dispEditorComponentInfo()
	{
		$component_name = Context::get('component_name');

		$site_module_info = Context::get('site_module_info');
		$site_srl = (int)$site_module_info->site_srl;

		$oEditorModel = getModel('editor');
		$component = $oEditorModel->getComponent($component_name, $site_srl);
		Context::set('component', $component);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('view_component');
		$this->setLayoutFile("popup_layout");
	}

	/**
	 * @brief Add a form for editor addition setup
	 */
	function triggerDispEditorAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			// Get information of the current module
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return new Object();
		}
		// Get editors settings
		$oEditorModel = getModel('editor');
		$editor_config = $oEditorModel->getEditorConfig($current_module_srl);

		Context::set('editor_config', $editor_config);

		$oModuleModel = getModel('module');
		// Get a list of editor skin
		$editor_skin_list = FileHandler::readDir(_XE_PATH_.'modules/editor/skins');
		Context::set('editor_skin_list', $editor_skin_list);

		$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->editor_skin);
		Context::set('editor_colorset_list', $skin_info->colorset);
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$editor_config->comment_editor_skin);
		Context::set('editor_comment_colorset_list', $skin_info->colorset);

		$contents = FileHandler::readDir(_XE_PATH_.'modules/editor/styles');
		$content_style_list = array();
		for($i=0,$c=count($contents);$i<$c;$i++)
		{
			$style = $contents[$i];
			$info = $oModuleModel->loadSkinInfo($this->module_path,$style,'styles');
			$content_style_list[$style] = new stdClass();
			$content_style_list[$style]->title = $info->title;
		}			
		Context::set('content_style_list', $content_style_list);
		// Get a group list
		$oMemberModel = getModel('member');
		$site_module_info = Context::get('site_module_info');
		$group_list = $oMemberModel->getGroups($site_module_info->site_srl);
		Context::set('group_list', $group_list);

		//Security
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('content_style_list..');
		$security->encodeHTML('editor_comment_colorset_list..title');			

		// Set a template file
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'editor_module_config');
		$obj .= $tpl;

		return new Object();
	}


	function dispEditorPreview()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('preview');
	}

	function dispEditorSkinColorset()
	{
		$skin = Context::get('skin');
		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path,$skin);
		$colorset = $skin_info->colorset;
		Context::set('colorset', $colorset);
	}

	function dispEditorConfigPreview()
	{
		$oEditorModel = getModel('editor');
		$config = $oEditorModel->getEditorConfig();

		$mode = Context::get('mode');

		if($mode != 'main')
		{
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
			$option_com->content_key_name = 'dummy_content';
			$option_com->primary_key_name = 'dummy_key';
			$option_com->content_style = $config->comment_content_style;
			$option_com->colorset = $config->sel_comment_editor_colorset;
			$editor = $oEditorModel->getEditor(0, $option_com);
		}
		else
		{
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
		}

		Context::set('editor', $editor);

		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('config_preview');
	}
}
/* End of file editor.view.php */
/* Location: ./modules/editor/editor.view.php */
