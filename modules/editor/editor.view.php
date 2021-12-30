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
	 * @brief Display editor in an iframe
	 */
	function dispEditorFrame()
	{
		// Check parent input ID
		$parent_input_id = Context::get('parent_input_id');
		Context::set('parent_input_id', preg_replace('/[^a-z0-9_]/i', '', $parent_input_id));
		Context::addBodyClass('disable_debug_panel');
		
		// Load editor
		$oEditorModel = getModel('editor');
		$option = $oEditorModel->getEditorConfig();
		$option->editor_skin = 'ckeditor';
		$option->sel_editor_colorset = 'moono-lisa';
		$option->primary_key_name = 'primary_key';
		$option->content_key_name = 'content';
		$option->allow_fileupload = FALSE;
		$option->enable_autosave = FALSE;
		$option->enable_default_component = TRUE;
		$option->enable_component = FALSE;
		$option->height = 300;
		$option->editor_focus = 'Y';
		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);
		
		// Set template
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile("default_layout");
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('editor_frame');
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
			Context::set('message', sprintf($oComponent->getMessage(), $component));
			$this->setLayoutFile('popup_layout');
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

		if(!$component->component_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

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
			if(!$current_module_srl) return new BaseObject();
		}
		
		// Get editors settings
		$oEditorModel = getModel('editor');
		$editor_config = $oEditorModel->getEditorConfig($current_module_srl);
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

		Context::set('editor_config', $editor_config);
		Context::set('editor_skin_list', $editor_skin_list);
		
		// Get a group list
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		//Security
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('editor_comment_colorset_list..title');

		// Set a template file
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'editor_module_config');
		$obj .= $tpl;

		return new BaseObject();
	}


	function dispEditorPreview()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('preview');
	}

	function dispEditorSkinColorset()
	{
		$skin = Context::get('skin');
		$skin_info = ModuleModel::loadSkinInfo($this->module_path,$skin);
		$colorset = $skin_info->colorset;
		Context::set('colorset', $colorset);
	}

	function dispEditorConfigPreview()
	{
		Context::set('editor', getModel('editor')->getModuleEditor(Context::get('type'), 0, 0, 'dummy_key', 'dummy_content'));
		
		$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('config_preview');
	}
}
/* End of file editor.view.php */
/* Location: ./modules/editor/editor.view.php */
