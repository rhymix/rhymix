<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editorModel
 * @author NAVER (developers@xpressengine.com)
 * @brief model class of the editor odule
 */
class editorModel extends editor
{
	var $loaded_component_list = array();
	/**
	 * @brief Return the editor
	 *
	 * Editor internally generates editor_sequence from 1 to 30 for temporary use.
	 * That means there is a limitation that more than 30 editors cannot be displayed on a single page.
	 *
	 * However, editor_sequence can be value from getNextSequence() in case of the modified or the auto-saved for file upload
	 *
	 */

	/**
	 * @brief Return editor config for each module
	 */
	function getEditorConfig($module_srl = null)
	{
		// Load editor config for current module.
		$oModuleModel = getModel('module');
		if ($module_srl)
		{
			if (!$GLOBALS['__editor_module_config__'][$module_srl])
			{
				$GLOBALS['__editor_module_config__'][$module_srl] = $oModuleModel->getModulePartConfig('editor', $module_srl);
			}
			$editor_config = $GLOBALS['__editor_module_config__'][$module_srl];
			if (!is_object($editor_config))
			{
				$editor_config = new stdClass;
			}
		}
		else
		{
			$editor_config = new stdClass;
		}
		
		// Fill in some other values.
		if(!is_array($editor_config->enable_html_grant)) $editor_config->enable_html_grant = array();
		if(!is_array($editor_config->enable_comment_html_grant)) $editor_config->enable_comment_html_grant = array();
		if(!is_array($editor_config->upload_file_grant)) $editor_config->upload_file_grant = array();
		if(!is_array($editor_config->comment_upload_file_grant)) $editor_config->comment_upload_file_grant = array();
		if(!is_array($editor_config->enable_default_component_grant)) $editor_config->enable_default_component_grant = array();
		if(!is_array($editor_config->enable_comment_default_component_grant)) $editor_config->enable_comment_default_component_grant = array();
		if(!is_array($editor_config->enable_component_grant)) $editor_config->enable_component_grant = array();
		if(!is_array($editor_config->enable_comment_component_grant)) $editor_config->enable_comment_component_grant= array();
		
		// Load the default config for editor module.
		$editor_default_config = $oModuleModel->getModuleConfig('editor');
		
		// Check whether we should use the default config.
		if($editor_config->default_editor_settings !== 'Y' && $editor_default_config->editor_skin && $editor_config->editor_skin && $editor_default_config->editor_skin !== $editor_config->editor_skin)
		{
			$editor_config->default_editor_settings = 'N';
		}
		if(!$editor_config->default_editor_settings)
		{
			$editor_config->default_editor_settings = 'Y';
		}
		
		// Apply the default config for missing values.
		foreach ($this->default_editor_config as $key => $val)
		{
			if ($editor_config->default_editor_settings === 'Y' || !$editor_config->$key)
			{
				$editor_config->$key = $editor_default_config->$key ?: $val;
			}
		}
		
		return $editor_config;
	}

	function getSkinConfig($skin_name)
	{
		$skin_config = new stdClass;
		
		if($skin_info = getModel('module')->loadSkinInfo($this->module_path, $skin_name))
		{
			foreach ($skin_info->extra_vars as $val)
			{
				$skin_config->{$val->name} = $val->value;
			}
		}
		
		return $skin_config;
	}

	/**
	 * @brief Return the editor template
	 * You can call upload_target_srl when modifying content
	 * The upload_target_srl is used for a routine to check if an attachment exists
	 */
	function getEditor($upload_target_srl = 0, $option = null)
	{
		// Set editor sequence and upload options.
		if ($upload_target_srl)
		{
			$option->editor_sequence = $upload_target_srl;
		}
		else
		{
			if(!$_SESSION['_editor_sequence_']) $_SESSION['_editor_sequence_'] = 1;
			$option->editor_sequence = $_SESSION['_editor_sequence_']++;
		}
		Context::set('allow_fileupload', $option->allow_fileupload = toBool($option->allow_fileupload));
		Context::set('upload_target_srl', $upload_target_srl);
		Context::set('editor_sequence', $option->editor_sequence);
		
		// Check that the skin and content style exist.
		if (!$option->editor_skin)
		{
			$option->editor_skin = $option->skin;
		}
		if (!$option->editor_skin || !file_exists($this->module_path . 'skins/' . $option->editor_skin . '/editor.html') || starts_with('xpresseditor', $option->editor_skin) || starts_with('dreditor', $option->editor_skin))
		{
			$option->editor_skin = $this->default_editor_config['editor_skin'];
		}
		if (!$option->content_style || !file_exists($this->module_path . 'styles/' . $option->content_style))
		{
			$option->content_style = $this->default_editor_config['content_style'];
		}
		if (!$option->sel_editor_colorset)
		{
			$option->sel_editor_colorset = $option->colorset ?: $this->default_editor_config['sel_editor_colorset'];
		}
		if (!$option->editor_height)
		{
			$option->editor_height = $option->height ?: $this->default_editor_config['editor_height'];
		}
		if ($option->editor_skin === 'ckeditor' && preg_match('/^(?:white|black)(_text_(?:use|no)html)?$/', $option->sel_editor_colorset))
		{
			$option->sel_editor_colorset = 'moono-lisa';
		}
		Context::set('skin', $option->editor_skin);
		Context::set('editor_path', $this->module_path . 'skins/' . $option->editor_skin . '/');
		Context::set('content_style', $option->content_style);
		Context::set('content_style_path', $this->module_path . 'styles/' . $option->content_style);
		Context::set('colorset', $option->sel_editor_colorset);
		Context::set('editor_height', $option->editor_height);
		Context::set('editor_toolbar', $option->editor_toolbar);
		Context::set('editor_toolbar_hide', toBool($option->editor_toolbar_hide));
		Context::set('module_type', $option->module_type);
		
		// Default font setting
		Context::set('content_font', $option->content_font);
		Context::set('content_font_size', $option->content_font_size);
		Context::set('content_line_height', $option->content_line_height);
		Context::set('content_paragraph_spacing', $option->content_paragraph_spacing);
		Context::set('content_word_break', $option->content_word_break);
		Context::set('editor_autoinsert_image', $option->autoinsert_image);
		Context::set('editor_additional_css', $option->additional_css);
		Context::set('editor_additional_plugins', $option->additional_plugins);
		Context::set('editor_remove_plugins', $option->remove_plugins);
		
		// Set the primary key valueof the document or comments
		Context::set('editor_primary_key_name', $option->primary_key_name);
		
		// Set content column name to sync contents
		Context::set('editor_content_key_name', $option->content_key_name);
		
		// Set autosave (do not use if the post is edited)
		$option->enable_autosave = toBool($option->enable_autosave) && !Context::get($option->primary_key_name);
		if ($option->enable_autosave)
		{
			Context::set('saved_doc', $this->getSavedDoc($upload_target_srl));
		}
		Context::set('enable_autosave', $option->enable_autosave);
		
		// Set allow html and focus
		Context::set('allow_html', ($option->allow_html === false || $option->allow_html === 'N') ? false : true);
		Context::set('editor_focus', toBool($option->editor_focus));
		
		// Load editor components.
		if($option->enable_component)
		{
			if(!Context::get('component_list'))
			{
				$component_list = $this->getComponentList(true);
				Context::set('component_list', $component_list);
			}
		}
		Context::set('enable_component', $option->enable_component ? true : false);
		Context::set('enable_default_component', $option->enable_default_component ? true : false);

		// Set HTML mode.
		Context::set('html_mode', $option->disable_html ? false : true);

		/**
		 * Upload setting by using configuration of the file module internally
		 */
		$files_count = 0;
		if($option->allow_fileupload)
		{
			// Get file upload limits
			$oFileModel = getModel('file');
			$file_config = $oFileModel->getUploadConfig();
			$file_config->allowed_attach_size = $file_config->allowed_attach_size*1024*1024;
			$file_config->allowed_filesize = $file_config->allowed_filesize*1024*1024;
			if (PHP_INT_SIZE < 8)
			{
				$file_config->allowed_filesize = min($file_config->allowed_filesize, 2147483647);
			}
			$file_config->allowed_chunk_size = min(FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')) * 0.95, 64 * 1024 * 1024);
			if ($file_config->allowed_chunk_size > 4 * 1048576)
			{
				$file_config->allowed_chunk_size = floor($file_config->allowed_chunk_size / 1048576) * 1048576;
			}
			else
			{
				$file_config->allowed_chunk_size = floor($file_config->allowed_chunk_size / 65536) * 65536;
			}
			
			// Do not allow chunked uploads in IE < 10, Android browser, and Opera
			$browser = Rhymix\Framework\UA::getBrowserInfo();
			if (($browser->browser === 'IE' && version_compare($browser->version, '10', '<')) || $browser->browser === 'Android' || $browser->browser === 'Opera')
			{
				$file_config->allowed_filesize = min($file_config->allowed_filesize, FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')));
				$file_config->allowed_chunk_size = 0;
			}
			
			// Do not allow chunked uploads in XpressEditor.
			if (starts_with($option->editor_skin, 'xpresseditor'))
			{
				$file_config->allowed_filesize = min($file_config->allowed_filesize, FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')));
				$file_config->allowed_chunk_size = 0;
			}

			Context::set('file_config',$file_config);
			// Configure upload status such as file size
			$upload_status = $oFileModel->getUploadStatus();
			Context::set('upload_status', $upload_status);
			// Upload enabled (internally caching)
			$oFileController = getController('file');
			$oFileController->setUploadInfo($option->editor_sequence, $upload_target_srl);
			// Check if the file already exists
			if($upload_target_srl) $files_count = $oFileModel->getFilesCount($upload_target_srl);
		}
		Context::set('files_count', (int)$files_count);

		// Check an option whether to start the editor manually.
		Context::set('editor_manual_start', $option->manual_start);

		// Compile and return the editor skin template.
		$tpl_path = Context::get('editor_path');
		Context::loadLang($tpl_path.'lang');
		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, 'editor.html');
	}

	/**
	 * @brief Return editor template which contains settings of each module
	 * Result of getModuleEditor() is as same as getEditor(). But getModuleEditor()uses additional settings of each module to generate an editor
	 *
	 * 2 types of editors supported; document and comment.
	 * 2 types of editors can be used on a single module. For instance each for original post and reply port.
	 */
	function getModuleEditor($type = 'document', $module_srl, $upload_target_srl, $primary_key_name, $content_key_name)
	{
		// Get editor settings of the module
		$editor_config = $this->getEditorConfig($module_srl);

		// Check mobile status
		$is_mobile = Mobile::isFromMobilePhone() || \Rhymix\Framework\UA::isMobile();
		
		// Initialize options
		$option = new stdClass();
		$option->module_type = $type;

		// Convert configuration keys according to type (document or comment).
		if($type == 'document')
		{
			foreach (get_object_vars($editor_config) as $key => $val)
			{
				$option->$key = $val;
			}
			if ($is_mobile)
			{
				$option->editor_height = $option->mobile_editor_height;
				$option->editor_toolbar = $option->mobile_editor_toolbar;
				$option->editor_toolbar_hide = $option->mobile_editor_toolbar_hide;
				$option->additional_css = $option->additional_mobile_css;
			}
		}
		else
		{
			foreach (get_object_vars($editor_config) as $key => $val)
			{
				$option->$key = $val;
			}
			$option->editor_skin = $option->comment_editor_skin;
			$option->content_style = $option->comment_content_style;
			$option->sel_editor_colorset = $option->sel_comment_editor_colorset;
			$option->upload_file_grant = $option->comment_upload_file_grant;
			$option->enable_default_component_grant = $option->enable_comment_default_component_grant;
			$option->enable_component_grant = $option->enable_comment_component_grant;
			$option->enable_html_grant = $option->enable_comment_html_grant;
			$option->editor_height = $option->comment_editor_height;
			$option->editor_toolbar = $option->comment_editor_toolbar;
			$option->editor_toolbar_hide = $option->comment_editor_toolbar_hide;
			$option->enable_autosave = 'N';
			if ($is_mobile)
			{
				$option->editor_height = $option->mobile_comment_editor_height;
				$option->editor_toolbar = $option->mobile_comment_editor_toolbar;
				$option->editor_toolbar_hide = $option->mobile_comment_editor_toolbar_hide;
				$option->additional_css = $option->additional_mobile_css;
			}
		}
		
		// Check a group_list of the currently logged-in user for permission check
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$group_list = $logged_info->group_list;
		}
		else
		{
			$group_list = array();
		}
		
		// Permission check for file upload
		if($module_srl)
		{
			if ($logged_info->is_admin === 'Y' || !count($option->upload_file_grant))
			{
				$option->allow_fileupload = true;
			}
			else
			{
				$option->allow_fileupload = false;
				foreach($group_list as $group_srl => $group_info)
				{
					if(in_array($group_srl, $option->upload_file_grant))
					{
						$option->allow_fileupload = true;
						break;
					}
				}
			}
		}
		
		// Permission check for using default components
		if ($logged_info->is_admin === 'Y' || !count($option->enable_default_component_grant))
		{
			$option->enable_default_component = true;
		}
		else
		{
			$option->enable_default_component = false;
			foreach($group_list as $group_srl => $group_info)
			{
				if(in_array($group_srl, $option->enable_default_component_grant))
				{
					$option->enable_default_component = true;
					break;
				}
			}
		}
		
		// Permisshion check for using extended components
		if($logged_info->is_admin === 'Y' || !count($option->enable_component_grant))
		{
			$option->enable_component = true;
		}
		else
		{
			$option->enable_component = false;
			foreach($group_list as $group_srl => $group_info)
			{
				if(in_array($group_srl, $option->enable_component_grant))
				{
					$option->enable_component = true;
					break;
				}
			}
		}
		
		// HTML editing privileges
		if($logged_info->is_admin === 'Y' || !count($option->enable_html_grant))
		{
			$option->disable_html = false;
		}
		else
		{
			$option->disable_html = true;
			foreach($group_list as $group_srl => $group_info)
			{
				if(in_array($group_srl, $option->enable_html_grant))
				{
					$option->disable_html = false;
					break;
				}
			}
		}
		
		// Other settings
		$option->primary_key_name = $primary_key_name;
		$option->content_key_name = $content_key_name;
		return $this->getEditor($upload_target_srl, $option);
	}

	/**
	 * @brief Get information which has been auto-saved
	 */
	function getSavedDoc($upload_target_srl)
	{
		$auto_save_args = new stdClass();
		$auto_save_args->module_srl = Context::get('module_srl');

		// Get the current module if module_srl doesn't exist
		if(!$auto_save_args->module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$auto_save_args->module_srl = $current_module_info->module_srl;
		}

		// Find a document by using member_srl for logged-in user and ipaddress for non-logged user
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$auto_save_args->member_srl = $logged_info->member_srl;
		}
		elseif($_COOKIE['autosave_certify_key_' . $auto_save_args->module_srl])
		{
			$auto_save_args->certify_key = $_COOKIE['autosave_certify_key_' . $auto_save_args->module_srl];
		}
		else
		{
			$auto_save_args->ipaddress = RX_CLIENT_IP;
		}

		// Extract auto-saved data from the DB
		$output = executeQuery('editor.getSavedDocument', $auto_save_args);
		$saved_doc = $output->data;

		// Return null if no result is auto-saved
		if(!$saved_doc) return;
		
		// Return null if certify key does not match
		if($saved_doc->certify_key && !isset($auto_save_args->certify_key))
		{
			return;
		}

		// Check if the auto-saved document already exists
		$oDocumentModel = getModel('document');
		$oSaved = $oDocumentModel->getDocument($saved_doc->document_srl);
		if($oSaved->isExists()) return;

		// Move all the files if the auto-saved data contains document_srl and file
		// Then set document_srl to editor_sequence
		if($saved_doc->document_srl && $upload_target_srl && !Context::get('document_srl'))
		{
			$saved_doc->module_srl = $auto_save_args->module_srl;
			$oFileController = getController('file');
			$oFileController->moveFile($saved_doc->document_srl, $saved_doc->module_srl, $upload_target_srl);
		}
		elseif($upload_target_srl)
		{
			$saved_doc->document_srl = $upload_target_srl;
		}

		// Change auto-saved data
		$saved_doc->certify_key = $auto_save_args->certify_key;
		if(!$saved_doc->certify_key)
		{
			$saved_doc->certify_key = Rhymix\Framework\Security::getRandom(32);
			setcookie('autosave_certify_key_' . $saved_doc->module_srl, $saved_doc->certify_key, time() + 86400, null, null, RX_SSL, true);
		}
		$oEditorController = getController('editor');
		$oEditorController->deleteSavedDoc(false);
		$oEditorController->doSaveDoc($saved_doc);

		setUserSequence($saved_doc->document_srl);

		return $saved_doc;
	}

	/**
	 * @brief create objects of the component
	 */
	function getComponentObject($component, $editor_sequence = 0, $site_srl = 0)
	{
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$component) || !preg_match('/^[0-9]+$/', $editor_sequence . $site_srl)) return;

		if(!$this->loaded_component_list[$component][$editor_sequence])
		{
			// Create an object of the component and execute
			$class_path = sprintf('%scomponents/%s/', $this->module_path, $component);
			$class_file = sprintf('%s%s.class.php', $class_path, $component);
			if(!file_exists($class_file)) return new BaseObject(-1, 'msg_component_is_not_founded', $component);
			// Create an object after loading the class file
			require_once($class_file);
			$oComponent = new $component($editor_sequence, $class_path);
			if(!$oComponent) return new BaseObject(-1, 'msg_component_is_not_founded', $component);
			// Add configuration information
			$component_info = $this->getComponent($component, $site_srl);
			$oComponent->setInfo($component_info);
			$this->loaded_component_list[$component][$editor_sequence] = $oComponent;
		}

		return $this->loaded_component_list[$component][$editor_sequence];
	}

	/**
	 * @brief Return a list of the editor skin
	 */
	function getEditorSkinList()
	{
		return FileHandler::readDir('./modules/editor/skins');
	}

	/**
	 * @brief Return a component list (DB Information included)
	 */
	function getComponentList($filter_enabled = true, $site_srl = 0, $from_db = false)
	{
		$cache_key = 'editor:components:' . ($filter_enabled ? 'enabled' : 'all');
		$component_list = $from_db ? null : Rhymix\Framework\Cache::get($cache_key);
		if (!$component_list)
		{
			$oEditorController = getController('editor');
			$component_list = $oEditorController->makeCache(false);
		}

		$logged_info = Context::get('logged_info');
		if($logged_info && is_array($logged_info->group_list))
		{
			$group_list = array_keys($logged_info->group_list);
		}
		else
		{
			$group_list = array();
		}

		if(countobj($component_list))
		{
			foreach($component_list as $key => $val)
			{
				if(!trim($key)) continue;
				if(!is_dir(\RX_BASEDIR.'modules/editor/components/'.$key))
				{
					return $this->getComponentList($filter_enabled, 0, true);
				}
				if(!$filter_enabled) continue;
				if($val->enabled == "N")
				{
					unset($component_list->{$key});
					continue;
				}
				if($logged_info->is_admin == "Y") continue;
				if($val->target_group)
				{
					if(!Context::get('is_logged'))
					{
						$val->enabled = "N";
					}
					else
					{
						$is_granted = false;
						foreach($group_list as $group_srl)
						{
							if(in_array($group_srl, $val->target_group)) $is_granted = true;
						}
						if(!$is_granted) $val->enabled = "N";
					}
				}
				if($val->enabled != "N" && $val->mid_list)
				{
					$mid = Context::get('mid');
					if(!in_array($mid, $val->mid_list)) $val->enabled = "N";
				}
				if($val->enabled == "N")
				{
					unset($component_list->{$key});
					continue;
				}
			}
		}
		return $component_list;
	}

	/**
	 * @brief Get xml and db information of the component
	 */
	function getComponent($component_name)
	{
		$args = new stdClass();
		$args->component_name = $component_name;
		$output = executeQuery('editor.getComponent', $args);
		$component = $output->data;

		if(!$output->data) return false;

		$component_name = $component->component_name;

		unset($xml_info);
		$xml_info = $this->getComponentXmlInfo($component_name);
		$xml_info->enabled = $component->enabled;

		$xml_info->target_group = array();

		$xml_info->mid_list = array();

		if($component->extra_vars)
		{
			$extra_vars = unserialize($component->extra_vars);

			if($extra_vars->target_group)
			{
				$xml_info->target_group = $extra_vars->target_group;
				unset($extra_vars->target_group);
			}

			if($extra_vars->mid_list)
			{
				$xml_info->mid_list = $extra_vars->mid_list;
				unset($extra_vars->mid_list);
			}

			if($xml_info->extra_vars)
			{
				foreach($xml_info->extra_vars as $key => $val)
				{
					$xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
				}
			}
		}

		return $xml_info;
	}

	/**
	 * @brief Read xml information of the component
	 */
	function getComponentXmlInfo($component)
	{
		$lang_type = Context::getLangType();

		// Get xml file path of the requested components
		$component_path = sprintf('%s/components/%s/', $this->module_path, $component);

		$xml_file = sprintf('%sinfo.xml', $component_path);
		$cache_file = sprintf('./files/cache/editor/%s.%s.php', $component, $lang_type);

		// Include and return xml file information if cached file exists
		if(file_exists($cache_file) && file_exists($xml_file) && filemtime($cache_file) > filemtime($xml_file))
		{
			include($cache_file);

			return $xml_info;
		}

		$oParser = new XmlParser();
		$xml_doc = $oParser->loadXmlFile($xml_file);

		// Component information listed
		$component_info = new stdClass;
		$component_info->author = array();
		$component_info->extra_vars = new stdClass;
		$component_info->component_name = $component;
		$component_info->title = $xml_doc->component->title->body;

		if($xml_doc->component->version)
		{
			$component_info->description = str_replace('\n', "\n", $xml_doc->component->description->body);
			$component_info->version = $xml_doc->component->version->body;
			$component_info->date = $xml_doc->component->date->body;
			$component_info->homepage = $xml_doc->component->link->body;
			$component_info->license = $xml_doc->component->license->body;
			$component_info->license_link = $xml_doc->component->license->attrs->link;
		}
		else
		{
			sscanf($xml_doc->component->author->attrs->date, '%d. %d. %d', $date_obj->y, $date_obj->m, $date_obj->d);
			$date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);

			$component_info->description = str_replace('\n', "\n", $xml_doc->component->author->description->body);
			$component_info->version = $xml_doc->component->attrs->version;
			$component_info->date = $date;

			$component_info->author = array();
			$component_info->author[0]->name = $xml_doc->component->author->name->body;
			$component_info->author[0]->email_address = $xml_doc->component->author->attrs->email_address;
			$component_info->author[0]->homepage = $xml_doc->component->author->attrs->link;
		}

		// Author information
		$author_list = array();
		if(!is_array($xml_doc->component->author)) $author_list[] = $xml_doc->component->author;
		else $author_list = $xml_doc->component->author;

		for($i = 0; $i < count($author_list); $i++)
		{
			$author = new stdClass;
			$author->name = $author_list[$i]->name->body;
			$author->email_address = $author_list[$i]->attrs->email_address;
			$author->homepage = $author_list[$i]->attrs->link;
			$component_info->author[] = $author;
		}

		// List extra variables (text type only for editor component)
		$extra_vars = $xml_doc->component->extra_vars;
		if($extra_vars)
		{
			$extra_var_groups = $extra_vars->group;
			if(!$extra_var_groups)
			{
				$extra_var_groups = $extra_vars;
			}
			if(!is_array($extra_var_groups))
			{
				$extra_var_groups = array($extra_var_groups);
			}

			foreach($extra_var_groups as $group)
			{
				$extra_vars = $group->var;
				if(!is_array($group->var))
				{
					$extra_vars = array($group->var);
				}

				foreach($extra_vars as $key => $val)
				{
					if(!$val)
					{
						continue;
					}

					$obj = new stdClass();
					if(!$val->attrs)
					{
						$val->attrs = new stdClass();
					}
					if(!$val->attrs->type)
					{
						$val->attrs->type = 'text';
					}

					$obj->group = $group->title->body;
					$obj->name = $val->attrs->name;
					$obj->title = $val->title->body;
					$obj->type = $val->attrs->type;
					$obj->description = $val->description->body;
					if($obj->name)
					{
						$obj->value = $extra_vals->{$obj->name};
					}
					if(strpos($obj->value, '|@|') != FALSE)
					{
						$obj->value = explode('|@|', $obj->value);
					}
					if($obj->type == 'mid_list' && !is_array($obj->value))
					{
						$obj->value = array($obj->value);
					}

					// 'Select'type obtained from the option list.
					if($val->options && !is_array($val->options))
					{
						$val->options = array($val->options);
					}

					for($i = 0, $c = count($val->options); $i < $c; $i++)
					{
						$obj->options[$i] = new stdClass();
						$obj->options[$i]->title = $val->options[$i]->title->body;
						$obj->options[$i]->value = $val->options[$i]->attrs->value;
					}

					$component_info->extra_vars->{$obj->name} = $obj;
				}
			}
		}

		$buff = array();
		$buff[] = '<?php if(!defined(\'__XE__\')) exit();';
		$buff[] = '$xml_info = ' . var_export($component_info, TRUE) . ';';
		$buff = str_replace('stdClass::__set_state', '(object)', implode(PHP_EOL, $buff));

		FileHandler::writeFile($cache_file, $buff, 'w');

		return $component_info;
	}
	
	/**
	 * Return converted content
	 * @param object $obj
	 * @return string
	 */
	function converter($obj, $type = null)
	{
		$converter = null;
		$config = $this->getEditorConfig($obj->module_srl);
		
		// Get editor skin
		if (in_array($type, array('document', 'comment')))
		{
			$skin = ($type == 'comment') ? $config->comment_editor_skin : $config->editor_skin;
		}
		else
		{
			$converter = $obj->converter;
			$skin = $obj->editor_skin ?: $config->editor_skin;
		}
		
		// if not inserted converter, Get converter from skin
		if (!$converter)
		{
			$converter = $this->getSkinConfig($skin)->converter;
		}
		
		// if not inserted converter, Check
		if (!$converter)
		{
			if ($config->allow_html === 'N' || $obj->use_html === 'N')
			{
				$converter = 'text';
			}
			elseif (strpos($type == 'comment' ? $config->sel_comment_editor_colorset : $config->sel_editor_colorset, 'nohtml') !== false)
			{
				$converter = 'text';
			}
			elseif ($obj->use_editor === 'N')
			{
				$converter = 'nl2br';
			}
		}
		
		// Convert
		if ($converter)
		{
			if ($converter == 'text')
			{
				// Remove Tag
				$obj->content = strip_tags($obj->content);
				
				// Trim space
				$obj->content = utf8_trim($obj->content);
				
				// Escape
				$obj->content = escape($obj->content, false);
				
				// Insert HTML line
				$obj->content = nl2br($obj->content);
			}
			elseif ($converter == 'text2html')
			{
				$obj->content = Rhymix\Framework\Formatter::text2html($obj->content);
			}
			elseif ($converter == 'markdown2html')
			{
				$obj->content = Rhymix\Framework\Formatter::markdown2html($obj->content);
			}
			elseif ($converter == 'bbcode')
			{
				$obj->content = Rhymix\Framework\Formatter::bbcode($obj->content);
			}
			elseif ($converter == 'nl2br')
			{
				$obj->content = nl2br($obj->content);
			}
		}
		
		return $obj->content;
	}
}
/* End of file editor.model.php */
/* Location: ./modules/editor/editor.model.php */
