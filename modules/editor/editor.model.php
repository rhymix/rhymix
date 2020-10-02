<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editorModel
 * @author NAVER (developers@xpressengine.com)
 * @brief model class of the editor odule
 */
class editorModel extends editor
{
	/**
	 * Cache
	 */
	protected static $_module_config = array();
	protected static $_loaded_component_list = array();
	
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
	public static function getEditorConfig($module_srl = null)
	{
		// Load editor config for current module.
		if ($module_srl)
		{
			if (!self::$_module_config[$module_srl])
			{
				self::$_module_config[$module_srl] = ModuleModel::getModulePartConfig('editor', $module_srl);
				if (!is_object(self::$_module_config[$module_srl]))
				{
					self::$_module_config[$module_srl] = new stdClass;
				}
			}
			$editor_config = self::$_module_config[$module_srl];
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
		$editor_default_config = ModuleModel::getModuleConfig('editor') ?: new stdClass;
		
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
		foreach (self::$default_editor_config as $key => $val)
		{
			if ($editor_config->default_editor_settings === 'Y' || !isset($editor_config->$key))
			{
				$editor_config->$key = isset($editor_default_config->$key) ? $editor_default_config->$key : $val;
			}
		}
		
		return $editor_config;
	}

	/**
	 * @brief Return skin config
	 */
	public static function getSkinConfig($skin_name)
	{
		$skin_config = new stdClass;
		
		if($skin_info = ModuleModel::loadSkinInfo('./modules/editor', $skin_name))
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
	public static function getEditor($upload_target_srl = 0, $option = null)
	{
		// Load language files.
		Context::loadLang('./modules/editor/lang');
		
		// Initialize options.
		if (!is_object($option))
		{
			$option = new stdClass;
			
		}
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
		
		// Check that the skin exist.
		if (!$option->editor_skin)
		{
			$option->editor_skin = $option->skin;
		}
		if (!$option->editor_skin || !file_exists('./modules/editor/skins/' . $option->editor_skin . '/editor.html') || starts_with('xpresseditor', $option->editor_skin) || starts_with('dreditor', $option->editor_skin))
		{
			$option->editor_skin = self::$default_editor_config['editor_skin'];
		}
		if (!$option->editor_colorset)
		{
			$option->editor_colorset = $option->colorset ?: ($option->sel_editor_colorset ?: self::$default_editor_config['editor_colorset']);
		}
		if (!$option->editor_height)
		{
			$option->editor_height = $option->height ?: self::$default_editor_config['editor_height'];
		}
		if ($option->editor_skin === 'ckeditor' && !in_array($option->editor_colorset, array('moono', 'moono-dark', 'moono-lisa')))
		{
			$option->editor_colorset = 'moono-lisa';
		}
		if ($option->editor_skin === 'simpleeditor' && !in_array($option->editor_colorset, array('light', 'dark')))
		{
			$option->editor_colorset = 'light';
		}
		Context::set('skin', $option->editor_skin);
		Context::set('editor_path', './modules/editor/skins/' . $option->editor_skin . '/');
		Context::set('colorset', $option->editor_colorset);
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
		Context::set('editor_autoinsert_types', $option->autoinsert_types ?? ($option->autoinsert_image !== 'none' ? self::$default_editor_config['autoinsert_types'] : []));
		Context::set('editor_autoinsert_position', $option->autoinsert_position ?? $option->autoinsert_image);
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
			Context::set('saved_doc', self::getSavedDoc($upload_target_srl));
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
				$component_list = self::getComponentList(true);
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
			$file_config = FileModel::getUploadConfig();
			$file_config->allowed_attach_size = $file_config->allowed_attach_size*1024*1024;
			$file_config->allowed_filesize = $file_config->allowed_filesize*1024*1024;

			// Calculate the appropriate chunk size.
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

			Context::set('file_config',$file_config);
			// Configure upload status such as file size
			$upload_status = FileModel::getUploadStatus();
			Context::set('upload_status', $upload_status);
			// Upload enabled (internally caching)
			$oFileController = getController('file');
			$oFileController->setUploadInfo($option->editor_sequence, $upload_target_srl);
			// Check if the file already exists
			if($upload_target_srl) $files_count = FileModel::getFilesCount($upload_target_srl);
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
	public static function getModuleEditor($type = 'document', $module_srl, $upload_target_srl, $primary_key_name, $content_key_name)
	{
		// Get editor settings of the module
		$editor_config = self::getEditorConfig($module_srl);

		// Check mobile status
		$is_mobile = Mobile::isFromMobilePhone() || \Rhymix\Framework\UA::isMobile();
		
		// Initialize options
		$option = new stdClass();
		$option->module_type = $type;

		// Convert configuration keys according to type (document or comment).
		if($type == 'document')
		{
			foreach ((array)$editor_config as $key => $val)
			{
				$option->$key = $val;
			}
			if ($is_mobile)
			{
				$option->editor_skin = $option->mobile_editor_skin ?: $option->editor_skin;
				$option->editor_colorset = $option->mobile_editor_colorset ?: ($option->editor_colorset ?: $option->sel_editor_colorset);
				$option->editor_height = $option->mobile_editor_height;
				$option->editor_toolbar = $option->mobile_editor_toolbar;
				$option->editor_toolbar_hide = $option->mobile_editor_toolbar_hide;
				$option->additional_css = $option->additional_mobile_css;
			}
		}
		else
		{
			foreach ((array)$editor_config as $key => $val)
			{
				$option->$key = $val;
			}
			$option->editor_skin = $option->comment_editor_skin ?: $option->editor_skin;
			$option->editor_colorset = $option->comment_editor_colorset ?: ($option->editor_colorset ?: $option->sel_editor_colorset);
			$option->editor_height = $option->comment_editor_height;
			$option->editor_toolbar = $option->comment_editor_toolbar;
			$option->editor_toolbar_hide = $option->comment_editor_toolbar_hide;
			$option->enable_autosave = 'N';
			$option->upload_file_grant = $option->comment_upload_file_grant;
			$option->enable_default_component_grant = $option->enable_comment_default_component_grant;
			$option->enable_component_grant = $option->enable_comment_component_grant;
			$option->enable_html_grant = $option->enable_comment_html_grant;
			if ($is_mobile)
			{
				$option->editor_skin = $option->mobile_comment_editor_skin ?: ($option->comment_editor_skin ?: $option->editor_skin);
				$option->editor_colorset = $option->mobile_comment_editor_colorset ?: ($option->comment_editor_colorset ?: ($option->editor_colorset ?: $option->sel_editor_colorset));
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
		return self::getEditor($upload_target_srl, $option);
	}

	/**
	 * @brief Get information which has been auto-saved
	 */
	public static function getSavedDoc($upload_target_srl)
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
		$oSaved = DocumentModel::getDocument($saved_doc->document_srl);
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
		if(!$saved_doc->certify_key && !Context::get('is_logged'))
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
	public static function getComponentObject($component, $editor_sequence = 0, $site_srl = 0)
	{
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$component) || !preg_match('/^[0-9]+$/', $editor_sequence . $site_srl))
		{
			return new BaseObject(-1, 'msg_component_is_not_founded', $component);
		}

		if(!self::$_loaded_component_list[$component][$editor_sequence])
		{
			// Create an object of the component and execute
			$class_path = sprintf('./modules/editor/components/%s/', $component);
			$class_file = sprintf('%s%s.class.php', $class_path, $component);
			if(!file_exists($class_file)) return new BaseObject(-1, 'msg_component_is_not_founded', $component);

			// Create an object after loading the class file
			require_once($class_file);
			$oComponent = new $component($editor_sequence, $class_path);
			if(!$oComponent) return new BaseObject(-1, 'msg_component_is_not_founded', $component);

			// Add configuration information
			$component_info = self::getComponent($component, $site_srl);
			$oComponent->setInfo($component_info);
			self::$_loaded_component_list[$component][$editor_sequence] = $oComponent;
		}

		return self::$_loaded_component_list[$component][$editor_sequence];
	}

	/**
	 * @brief Return a list of the editor skin
	 */
	public static function getEditorSkinList()
	{
		return FileHandler::readDir('./modules/editor/skins');
	}

	/**
	 * @brief Return a component list (DB Information included)
	 */
	public static function getComponentList($filter_enabled = true, $site_srl = 0, $from_db = false)
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
					return self::getComponentList($filter_enabled, 0, true);
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
	public static function getComponent($component_name)
	{
		$args = new stdClass();
		$args->component_name = $component_name;
		$output = executeQuery('editor.getComponent', $args);
		$component = $output->data;

		if(!$output->data) return false;

		$component_name = $component->component_name;

		unset($xml_info);
		$xml_info = self::getComponentXmlInfo($component_name);
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
	public static function getComponentXmlInfo($component)
	{
		// Get xml file path of the requested components
		$component = preg_replace('/[^a-zA-Z0-9-_]/', '', $component);
		$component_path = sprintf('%s/components/%s/', './modules/editor', $component);

		$xml_file = sprintf('%sinfo.xml', $component_path);
		$xml_mtime = filemtime($xml_file);
		$lang_type = Context::getLangType();
		
		// Get from cache
		$cache_key = sprintf('editor:component:%s:%s:%d', $component, $lang_type, $xml_mtime);
		$info = Rhymix\Framework\Cache::get($cache_key);
		if ($info !== null && FALSE)
		{
			return $info;
		}
		
		// Parse XML file
		$info = Rhymix\Framework\Parsers\EditorComponentParser::loadXML($xml_file, $component, $lang_type);
		
		// Set to cache and return
		Rhymix\Framework\Cache::set($cache_key, $info, 0, true);
		return $info;
	}
	
	/**
	 * Return converted content
	 * @param object $obj
	 * @return string
	 */
	public static function converter($obj, $type = null)
	{
		$converter = null;
		$config = self::getEditorConfig($obj->module_srl);
		
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
			$converter = self::getSkinConfig($skin)->converter;
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
