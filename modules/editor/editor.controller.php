<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editor
 * @author NAVER (developers@xpressengine.com)
 * @brief editor module's controller class
 */
class editorController extends editor
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief AutoSave
	 */
	function procEditorSaveDoc()
	{

		$this->deleteSavedDoc(false);

		$args = new stdClass;
		$args->document_srl = Context::get('document_srl');
		$args->content = Context::get('content');
		$args->title = Context::get('title');
		$output = $this->doSaveDoc($args);

		$this->setMessage('msg_auto_saved');
	}

	/**
	 * @brief Delete autosaved documents
	 */
	function procEditorRemoveSavedDoc()
	{
		$oEditorController = getController('editor');
		$oEditorController->deleteSavedDoc(true);
	}

	/**
	 * @brief Execute a method of the component when the component requests ajax
	 */
	function procEditorCall()
	{
		$component = Context::get('component');
		$method = Context::get('method');
		if(!$component)
		{
			throw new Rhymix\Framework\Exception('msg_component_is_not_founded', $component);
		}

		$oEditorModel = getModel('editor');
		$oComponent = &$oEditorModel->getComponentObject($component);
		if(!$oComponent->toBool()) return $oComponent;

		if(!method_exists($oComponent, $method))
		{
			throw new Rhymix\Framework\Exception('msg_component_is_not_founded', $component);
		}

		//$output = call_user_method($method, $oComponent);
		//$output = call_user_func(array($oComponent, $method));
		$output = $oComponent->{$method}();

		if($output instanceof BaseObject && !$output->toBool()) return $output;

		$this->setError($oComponent->getError());
		$this->setMessage($oComponent->getMessage());

		$vars = $oComponent->getVariables();
		if(count($vars))
		{
			foreach($vars as $key => $val)
			{
				$this->add($key, $val);
			}
		}
	}

	/**
	 * @brief Save Editor's additional form for each module
	 */
	function procEditorInsertModuleConfig()
	{
		// Get request vars
		$vars = Context::getRequestVars();
		
		// To configure many of modules at once
		$target_module_srl = $vars->target_module_srl;
		$target_module_srl = array_map('trim', explode(',', $target_module_srl));
		$logged_info = Context::get('logged_info');
		$module_srl = array();
		foreach ($target_module_srl as $srl)
		{
			if (!$srl) continue;

			$module_info = ModuleModel::getModuleInfoByModuleSrl($srl);
			if (!$module_info->module_srl)
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}

			$module_grant = ModuleModel::getGrant($module_info, $logged_info);
			if (!$module_grant->manager)
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}

			$module_srl[] = $srl;
		}

		// Apply default settings?
		$config = new stdClass;
		$config->default_editor_settings = $vars->default_editor_settings;
		if ($config->default_editor_settings !== 'Y')
		{
			$config->default_editor_settings = 'N';
		}
		
		// Apply module-specific editor settings.
		$config->editor_skin = $vars->editor_skin;
		$config->editor_colorset = $vars->editor_colorset;
		$config->editor_height = $vars->editor_height;
		$config->editor_toolbar = $vars->editor_toolbar;
		$config->editor_toolbar_hide = $vars->editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->mobile_editor_skin = $vars->mobile_editor_skin;
		$config->mobile_editor_colorset = $vars->mobile_editor_colorset;
		$config->mobile_editor_height = $vars->mobile_editor_height;
		$config->mobile_editor_toolbar = $vars->mobile_editor_toolbar;
		$config->mobile_editor_toolbar_hide = $vars->mobile_editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->comment_editor_skin = $vars->comment_editor_skin;
		$config->comment_editor_colorset = $vars->comment_editor_colorset;
		$config->comment_editor_height = $vars->comment_editor_height;
		$config->comment_editor_toolbar = $vars->comment_editor_toolbar;
		$config->comment_editor_toolbar_hide = $vars->comment_editor_toolbar_hide === 'Y' ? 'Y' : 'N';
		$config->mobile_comment_editor_skin = $vars->mobile_comment_editor_skin;
		$config->mobile_comment_editor_colorset = $vars->mobile_comment_editor_colorset;
		$config->mobile_comment_editor_height = $vars->mobile_comment_editor_height;
		$config->mobile_comment_editor_toolbar = $vars->mobile_comment_editor_toolbar;
		$config->mobile_comment_editor_toolbar_hide = $vars->mobile_comment_editor_toolbar_hide === 'Y' ? 'Y' : 'N';

		if ($vars->font_defined === 'Y')
		{
			$config->font_defined = 'Y';
			$config->content_font = $vars->content_font_defined;
		}
		else
		{
			$config->font_defined = $vars->font_defined = 'N';
			$config->content_font = $vars->content_font;
		}
		
		$config->content_font_size = trim($vars->content_font_size);
		$config->enable_autosave = $vars->enable_autosave ?: 'Y';
		$config->auto_dark_mode = $vars->auto_dark_mode ?: 'Y';
		$config->allow_html = $vars->allow_html ?: 'Y';

		// Apply module-specific permissions.
		$grants = array(
			'enable_html_grant',
			'enable_comment_html_grant',
			'upload_file_grant',
			'comment_upload_file_grant',
			'enable_default_component_grant',
			'enable_comment_default_component_grant',
			'enable_component_grant',
			'enable_comment_component_grant',
		);

		foreach($grants as $key)
		{
			$grant = Context::get($key);
			if(!$grant)
			{
				$config->{$key} = array();
			}
			else if(is_array($grant))
			{
				$config->{$key} = $grant;
			}
			else
			{
				$config->{$key} = explode('|@|', $grant);
			}
		}

		// Save settings.
		$oModuleController = getController('module');
		foreach ($module_srl as $srl)
		{
			$oModuleController->insertModulePartConfig('editor', $srl, $config);
		}

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
	}


	/**
	 * @brief Load editor style
	 */
	function procLoadEditorStyle()
	{
		$module_info = Context::get('module_info');
		$module_srl = $module_info->module_srl ?? 0;
		if($module_srl)
		{
			$editor_config = getModel('editor')->getEditorConfig($module_srl);
		}
		else
		{
			$editor_config = getModel('module')->getModuleConfig('editor');
		}
		
		if ($editor_config)
		{
			$default_font_config = self::$default_font_config;
			if ($editor_config->content_font) $default_font_config['default_font_family'] = $editor_config->content_font;
			if ($editor_config->content_font_size) $default_font_config['default_font_size'] = $editor_config->content_font_size;
			if ($editor_config->content_line_height) $default_font_config['default_line_height'] = $editor_config->content_line_height;
			if ($editor_config->content_paragraph_spacing) $default_font_config['default_paragraph_spacing'] = $editor_config->content_paragraph_spacing;
			if ($editor_config->content_word_break) $default_font_config['default_word_break'] = $editor_config->content_word_break;
			Context::set('default_font_config', $default_font_config);
		}
		else
		{
			Context::set('default_font_config', self::$default_font_config);
		}
	}


	/**
	 * @brief convert editor component codes to be returned and specify content style.
	 */
	function triggerEditorComponentCompile(&$content)
	{
		if(Context::getResponseMethod() !== 'HTML') return;

		$this->procLoadEditorStyle();

		$content = $this->transComponent($content);
	}

	/**
	 * @brief Convert editor component codes to be returned
	 */
	function transComponent($content)
	{
		$content = preg_replace_callback('!<(?:(div)|img)([^>]*)editor_component=([^>]*)>(?(1)(.*?)</div>)!is', array($this,'transEditorComponent'), $content);
		return $content;
	}

	/**
	 * @brief Convert editor component code of the contents
	 */
	function transEditorComponent($match)
	{
		$script = " {$match[2]} editor_component={$match[3]}";
		$script = preg_replace('/([\w:-]+)\s*=(?:\s*(["\']))?((?(2).*?|[^ ]+))\2/i', '\1="\3"', $script);
		preg_match_all('/([a-z0-9_-]+)="([^"]+)"/is', $script, $m);

		$xml_obj = new stdClass;
		$xml_obj->attrs = new stdClass;
		for($i=0,$c=count($m[0]);$i<$c;$i++)
		{
			if(!isset($xml_obj->attrs)) $xml_obj->attrs = new stdClass;
			$xml_obj->attrs->{$m[1][$i]} = $m[2][$i];
		}
		$xml_obj->body = $match[4] ?? null;

		if(!$xml_obj->attrs->editor_component) return $match[0];

		// Get converted codes by using component::transHTML()
		$oEditorModel = getModel('editor');
		$oComponent = &$oEditorModel->getComponentObject($xml_obj->attrs->editor_component, 0);
		if(!is_object($oComponent)||!method_exists($oComponent, 'transHTML')) return $match[0];

		return $oComponent->transHTML($xml_obj);
	}

	/**
	 * @brief AutoSave
	 */
	function doSaveDoc($args)
	{
		if(!$args->document_srl) $args->document_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

		// Get the current module if module_srl doesn't exist
		if(!$args->module_srl) $args->module_srl = Context::get('module_srl');
		if(!$args->module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$args->module_srl = $current_module_info->module_srl;
		}

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = RX_CLIENT_IP;
			$args->certify_key = Rhymix\Framework\Security::getRandom(32);
			setcookie('autosave_certify_key_' . $args->module_srl, $args->certify_key, time() + 86400, null, null, RX_SSL, true);
		}

		return executeQuery('editor.insertSavedDoc', $args);
	}

	/**
	 * @brief Load the srl of autosaved document - for those who uses XE older versions.
	 */
	function procEditorLoadSavedDocument()
	{
		$editor_sequence = Context::get('editor_sequence');
		$primary_key = Context::get('primary_key');
		$oEditorModel = getModel('editor');
		$oFileController = getController('file');

		$saved_doc = $oEditorModel->getSavedDoc(null);

		$oFileController->setUploadInfo($editor_sequence, $saved_doc->document_srl);
		$vars = $this->getVariables();
		$this->add("editor_sequence", $editor_sequence);
		$this->add("key", $primary_key);
		$this->add("title", $saved_doc->title);
		$this->add("content", $saved_doc->content);
		$this->add("document_srl", $saved_doc->document_srl);
	}

	/**
	 * @brief A trigger to remove auto-saved document when inserting/updating the document
	 */
	function triggerDeleteSavedDoc(&$obj)
	{
		$this->deleteSavedDoc(false);
	}

	/**
	 * @brief Delete the auto-saved document
	 * Based on the current logged-in user
	 */
	function deleteSavedDoc($mode = false)
	{
		$args = new stdClass();
		$args->module_srl = Context::get('module_srl');

		// Get the current module if module_srl doesn't exist
		if(!$args->module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$args->module_srl = $current_module_info->module_srl;
		}
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		elseif($_COOKIE['autosave_certify_key_' . $args->module_srl])
		{
			$args->certify_key = $_COOKIE['autosave_certify_key_' . $args->module_srl];
		}
		else
		{
			$args->ipaddress = RX_CLIENT_IP;
		}

		// Check if the auto-saved document already exists
		$output = executeQuery('editor.getSavedDocument', $args);
		$saved_doc = $output->data;
		if(!$saved_doc) return;
		if($saved_doc->certify_key && !isset($args->certify_key))
		{
			return;
		}

		$oSaved = DocumentModel::getDocument($saved_doc->document_srl);
		if(!$oSaved->isExists())
		{
			if($mode)
			{
				setcookie('autosave_certify_key_' . $args->module_srl, 'deleted', time() - 86400);
				$output = executeQuery('editor.getSavedDocument', $args);
				$output = ModuleHandler::triggerCall('editor.deleteSavedDoc', 'after', $saved_doc);
			}
		}

		$output = executeQuery('editor.deleteSavedDoc', $args);
		return $output;
	}

	/**
	 * @brief ERemove editor component information used on the virtual site
	 */
	function removeEditorConfig($site_srl)
	{
		$args = new stdClass();
		$args->site_srl = $site_srl;
		executeQuery('editor.deleteSiteComponent', $args);
	}

	/**
	 * @brief Caching a list of editor component (editorModel::getComponentList)
	 * For the editor component list, use a caching file because of DB query and Xml parsing
	 */
	function makeCache($filter_enabled = true)
	{
		$oEditorModel = getModel('editor');
		$args = new stdClass;
		if($filter_enabled) $args->enabled = "Y";
		$output = executeQuery('editor.getComponentList', $args);
		$db_list = $output->data;

		// Get a list of editor component folders
		$downloaded_list = FileHandler::readDir(RX_BASEDIR . 'modules/editor/components');
		$downloaded_list = array_filter($downloaded_list, function($component_name) {
			return is_dir(RX_BASEDIR . 'modules/editor/components/' . $component_name);
		});

		// Get xml information for looping DB list
		if(!is_array($db_list)) $db_list = array($db_list);
		$component_list = new stdClass();
		foreach($db_list as $component)
		{
			if(in_array($component->component_name, array('colorpicker_text','colorpicker_bg'))) continue;

			$component_name = $component->component_name;
			if(!$component_name) continue;

			if(!in_array($component_name, $downloaded_list)) continue;

			unset($xml_info);
			$xml_info = $oEditorModel->getComponentXmlInfo($component_name);
			$xml_info->enabled = $component->enabled;

			if($component->extra_vars)
			{
				$extra_vars = unserialize($component->extra_vars);
				if($extra_vars->target_group)
				{
					$xml_info->target_group = $extra_vars->target_group;
				}

				if($extra_vars->mid_list && count($extra_vars->mid_list))
				{
					$xml_info->mid_list = $extra_vars->mid_list;
				}
				
				// Check the configuration of the editor component
				if($xml_info->extra_vars)
				{
					foreach($xml_info->extra_vars as $key => $val)
					{
						$xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
					}
				}
			}

			$component_list->{$component_name} = $xml_info;
			
			// Get buttons, icons, images
			$icon_file = RX_BASEDIR . 'modules/editor/components/'.$component_name.'/icon.gif';
			$component_icon_file = RX_BASEDIR . 'modules/editor/components/'.$component_name.'/component_icon.gif';
			if(file_exists($icon_file)) $component_list->{$component_name}->icon = true;
			if(file_exists($component_icon_file)) $component_list->{$component_name}->component_icon = true;
		}

		// Get xml_info of downloaded list
		if(!$filter_enabled)
		{
			foreach($downloaded_list as $component_name)
			{
				if(in_array($component_name, array('colorpicker_text','colorpicker_bg'))) continue;
				if(!is_dir(\RX_BASEDIR.'modules/editor/components/'.$component_name)) continue;
				// Pass if configured
				if($component_list->{$component_name}) continue;
				// Insert data into the DB
				$oEditorController = getAdminController('editor');
				$oEditorController->insertComponent($component_name, false, 0);
				// Add to component_list
				$xml_info = $oEditorModel->getComponentXmlInfo($component_name);
				$xml_info->enabled = 'N';
				$component_list->{$component_name} = $xml_info;
			}
			Rhymix\Framework\Cache::set('editor:components:enabled', $component_list, 0, true);
		}
		else
		{
			Rhymix\Framework\Cache::set('editor:components:all', $component_list, 0, true);
		}
		
		return $component_list;
	}

	/**
	 * @brief Delete cache files
	 */
	function removeCache()
	{
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'files/cache/editor/cache');
		Rhymix\Framework\Cache::delete('editor:components:enabled');
		Rhymix\Framework\Cache::delete('editor:components:all');
	}

	function triggerCopyModule(&$obj)
	{
		$editorConfig = ModuleModel::getModulePartConfig('editor', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList AS $key=>$moduleSrl)
			{
				$oModuleController->insertModulePartConfig('editor', $moduleSrl, $editorConfig);
			}
		}
	}
}
/* End of file editor.controller.php */
/* Location: ./modules/editor/editor.controller.php */
