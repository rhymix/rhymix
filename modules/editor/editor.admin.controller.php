<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editorAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief editor of the module admin controller class
 */
class editorAdminController extends editor
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief 컴포넌트 사용설정, 목록 순서 변경
	 */
	function procEditorAdminCheckUseListOrder()
	{
		$site_module_info = Context::get('site_module_info');
		$enables = Context::get('enables');
		$component_names = Context::get('component_names');

		if(!is_array($component_names)) $component_names = array();
		if(!is_array($enables)) $enables = array();

		$unables = array_diff($component_names, $enables);
		$componentList = array();

		foreach($enables as $component_name)
		{
			$componentList[$component_name] = 'Y';
		}
		foreach($unables as $component_name)
		{
			$componentList[$component_name] = 'N';
		}

		$output = $this->editorListOrder($component_names,$site_module_info->site_srl);
		if(!$output->toBool()) return new BaseObject();

		$output = $this->editorCheckUse($componentList,$site_module_info->site_srl);
		if(!$output->toBool()) return new BaseObject();

		$oEditorController = getController('editor');
		$oEditorController->removeCache($site_module_info->site_srl);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * @brief check use component
	 */
	function editorCheckUse($componentList, $site_srl = 0)
	{
		$args = new stdClass();
		$args->site_srl = $site_srl;

		foreach($componentList as $componentName => $value)
		{
			$args->component_name = $componentName;
			$args->enabled = $value;
			if($site_srl == 0)
			{
				$output = executeQuery('editor.updateComponent', $args);
			}
			else
			{
				$output = executeQuery('editor.updateSiteComponent', $args);
			}
		}
		if(!$output->toBool()) return new BaseObject();

		unset($componentList);
		return $output;
	}

	/**
	 * @brief list order componet
	 */
	function editorListOrder($component_names, $site_srl = 0)
	{
		$args = new stdClass();
		$args->site_srl = $site_srl;
		$list_order_num = '30';
		if(is_array($component_names))
		{
			foreach($component_names as $name)
			{
				$args->list_order = $list_order_num;
				$args->component_name = $name;
				if($site_srl == 0)
				{
					$output = executeQuery('editor.updateComponent', $args);
				}
				else
				{
					$output = executeQuery('editor.updateSiteComponent', $args);
				}

				if(!$output->toBool()) return new BaseObject();
				$list_order_num++;
			}
		}
		unset($component_names);
		return $output;
	}

	/**
	 * @brief Set components
	 */
	function procEditorAdminSetupComponent()
	{
		$site_module_info = Context::get('site_module_info');

		$component_name = Context::get('component_name');
		$extra_vars = Context::getRequestVars();
		unset($extra_vars->component_name);
		unset($extra_vars->module);
		unset($extra_vars->act);
		unset($extra_vars->body);

		$args = new stdClass;
		$args->component_name = $component_name;
		$args->extra_vars = serialize($extra_vars);
		$args->site_srl = (int)$site_module_info->site_srl;

		if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $args);
		else $output = executeQuery('editor.updateSiteComponent', $args);
		if(!$output->toBool()) return $output;

		$oEditorController = getController('editor');
		$oEditorController->removeCache($args->site_srl);

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * @brief Config components
	 */
	function procEditorAdminGeneralConfig()
	{
		$oModuleController = getController('module');
		$configVars = Context::getRequestVars();
		
		$config = new stdClass;
		$config->editor_skin = $configVars->editor_skin;
		$config->editor_colorset = $configVars->editor_colorset;
		$config->editor_height = $configVars->editor_height;
		$config->editor_toolbar = $configVars->editor_toolbar;
		$config->editor_toolbar_hide = $configVars->editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->mobile_editor_skin = $configVars->mobile_editor_skin;
		$config->mobile_editor_colorset = $configVars->mobile_editor_colorset;
		$config->mobile_editor_height = $configVars->mobile_editor_height;
		$config->mobile_editor_toolbar = $configVars->mobile_editor_toolbar;
		$config->mobile_editor_toolbar_hide = $configVars->mobile_editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->comment_editor_skin = $configVars->comment_editor_skin;
		$config->comment_editor_colorset = $configVars->comment_editor_colorset;
		$config->comment_editor_height = $configVars->comment_editor_height;
		$config->comment_editor_toolbar = $configVars->comment_editor_toolbar;
		$config->comment_editor_toolbar_hide = $configVars->comment_editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->mobile_comment_editor_skin = $configVars->mobile_comment_editor_skin;
		$config->mobile_comment_editor_colorset = $configVars->mobile_comment_editor_colorset;
		$config->mobile_comment_editor_height = $configVars->mobile_comment_editor_height;
		$config->mobile_comment_editor_toolbar = $configVars->mobile_comment_editor_toolbar;
		$config->mobile_comment_editor_toolbar_hide = $configVars->mobile_comment_editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		
		if ($configVars->font_defined === 'Y')
		{
			$config->font_defined = 'Y';
			$config->content_font = $configVars->content_font_defined;
		}
		else
		{
			$config->font_defined = $configVars->font_defined = 'N';
			$config->content_font = $configVars->content_font;
		}
		
		if ($configVars->additional_css)
		{
			$additional_css = array_map('trim', explode("\n", $configVars->additional_css));
			$additional_css = array_filter($additional_css, function($str) { return !empty($str); });
			$config->additional_css = $additional_css;
		}
		else
		{
			$config->additional_css = array();
		}
		
		if ($configVars->additional_mobile_css)
		{
			$additional_mobile_css = array_map('trim', explode("\n", $configVars->additional_mobile_css));
			$additional_mobile_css = array_filter($additional_mobile_css, function($str) { return !empty($str); });
			$config->additional_mobile_css = $additional_mobile_css;
		}
		else
		{
			$config->additional_mobile_css = array();
		}
		
		if ($configVars->additional_plugins)
		{
			$additional_plugins = array_map('trim', explode(',', $configVars->additional_plugins));
			$additional_plugins = array_filter($additional_plugins, function($str) { return !empty($str); });
			$config->additional_plugins = $additional_plugins;
		}
		else
		{
			$config->additional_plugins = array();
		}
		
		if ($configVars->remove_plugins)
		{
			$remove_plugins = array_map('trim', explode(',', $configVars->remove_plugins));
			$remove_plugins = array_filter($remove_plugins, function($str) { return !empty($str); });
			$config->remove_plugins = $remove_plugins;
		}
		else
		{
			$config->remove_plugins = array();
		}
		
		$config->content_font_size = trim($configVars->content_font_size);
		$config->content_font_size = ctype_digit($config->content_font_size) ? ($config->content_font_size . 'px') : $config->content_font_size;
		$config->content_line_height = trim($configVars->content_line_height);
		$config->content_line_height = ctype_digit($config->content_line_height) ? ($config->content_line_height . '%') : $config->content_line_height;
		$config->content_paragraph_spacing = trim($configVars->content_paragraph_spacing);
		$config->content_paragraph_spacing = ctype_digit($config->content_paragraph_spacing) ? ($config->content_paragraph_spacing . 'px') : $config->content_paragraph_spacing;
		$config->content_word_break = $configVars->content_word_break;
		$config->content_word_break = in_array($config->content_word_break, array('normal', 'keep-all', 'break-all', 'none')) ? $config->content_word_break : 'normal';
		$config->enable_autosave = $configVars->enable_autosave ?: 'Y';
		$config->auto_dark_mode = $configVars->auto_dark_mode ?: 'Y';
		$config->allow_html = $configVars->allow_html ?: 'Y';
		$config->autoinsert_types = array();
		foreach ($configVars->autoinsert_types as $type)
		{
			$config->autoinsert_types[$type] = true;
		}
		$config->autoinsert_position = in_array($configVars->autoinsert_position, array('paragraph', 'inline')) ? $configVars->autoinsert_position : 'paragraph';

		$oModuleController->insertModuleConfig('editor', $config);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * @brief Add a component to DB
	 */
	function insertComponent($component_name, $enabled = false, $site_srl = 0)
	{
		if($enabled) $enabled = 'Y';
		else $enabled = 'N';

		$args = new stdClass;
		$args->component_name = $component_name;
		$args->enabled = $enabled;
		$args->site_srl = $site_srl;
		// Check if the component exists
		if(!$site_srl)
		{
			$output = executeQuery('editor.isComponentInserted', $args);
		}
		else
		{
			$output = executeQuery('editor.isSiteComponentInserted', $args);
		}
		if($output->data->count)
		{
			return new BaseObject(-1, 'msg_component_is_not_founded');
		}
		// Inert a component
		$args->list_order = getNextSequence();
		if(!$site_srl) $output = executeQuery('editor.insertComponent', $args);
		else $output = executeQuery('editor.insertSiteComponent', $args);

		$oEditorController = getController('editor');
		$oEditorController->removeCache($site_srl);
		return $output;
	}
}
/* End of file editor.admin.controller.php */
/* Location: ./modules/editor/editor.admin.controller.php */
