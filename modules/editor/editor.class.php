<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  editor
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the editor odule 
 */
class editor extends ModuleObject
{
	/**
	 * @brief Default font config
	 */
	public $default_font_config = array(
		'default_font_family' => 'inherit',
		'default_font_size' => '13px',
		'default_line_height' => '160%',
		'default_paragraph_spacing' => '0',
		'default_word_break' => 'normal',
	);
	
	/**
	 * @brief Default editor config
	 */
	public $default_editor_config = array(
		'editor_skin' => 'ckeditor',
		'editor_height' => 300,
		'editor_toolbar' => 'default',
		'editor_toolbar_hide' => 'N',
		'mobile_editor_height' => 200,
		'mobile_editor_toolbar' => 'simple',
		'mobile_editor_toolbar_hide' => 'Y',
		'sel_editor_colorset' => 'moono-lisa',
		'content_style' => 'ckeditor_light',
		'comment_editor_skin' => 'ckeditor',
		'comment_editor_height' => 100,
		'comment_editor_toolbar' => 'simple',
		'comment_editor_toolbar_hide' => 'N',
		'mobile_comment_editor_height' => 100,
		'mobile_comment_editor_toolbar' => 'simple',
		'mobile_comment_editor_toolbar_hide' => 'Y',
		'sel_comment_editor_colorset' => 'moono-lisa',
		'comment_content_style' => 'ckeditor_light',
		'content_font' => '',
		'content_font_size' => '13px',
		'content_line_height' => '160%',
		'content_paragraph_spacing' => '0px',
		'content_word_break' => 'normal',
		'enable_autosave' => 'Y',
		'allow_html' => 'Y',
		'editor_focus' => 'N',
		'autoinsert_image' => 'paragraph',
		'additional_css' => array(),
		'additional_mobile_css' => array(),
		'additional_plugins' => array(),
		'remove_plugins' => array(),
	);
	
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');
		// Add the default editor component
		$oEditorController = getAdminController('editor');
		$oEditorController->insertComponent('colorpicker_text',true);
		$oEditorController->insertComponent('colorpicker_bg',true);
		$oEditorController->insertComponent('emoticon',true);
		$oEditorController->insertComponent('url_link',true);
		$oEditorController->insertComponent('image_link',true);
		$oEditorController->insertComponent('multimedia_link',true);
		$oEditorController->insertComponent('quotation',true);
		$oEditorController->insertComponent('table_maker',true);
		$oEditorController->insertComponent('poll_maker',true);
		$oEditorController->insertComponent('image_gallery',true);
		// Create a directory to use in the editor module
		FileHandler::makeDir('./files/cache/editor');
		// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
		$oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		$oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		// 2007. 10. 23 Add an editor trigger on the module addition setup
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');
		// 2009. 04. 14 Add a trigger from compiled codes of the editor component
		$oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		$oDB = &DB::getInstance();
		// 2009. 06. 15 Save module_srl when auto-saving
		if(!$oDB->isColumnExists("editor_autosave","module_srl")) return true;
		if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) return true;

		// XEVE-17-030
		if(!$oDB->isColumnExists('editor_autosave', 'certify_key')) return true;
		if(!$oDB->isIndexExists('editor_autosave', 'idx_certify_key')) return true;

		// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
		if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
		// 2007. 10. 23 Add an editor trigger on the module addition setup
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) return true;
		// 2009. 04. 14 Add a trigger from compiled codes of the editor component
		if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) return true;
		// 2009. 06. 19 Remove unused trigger
		if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) return true;

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after')) return true;

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();
		
		// Save module_srl when auto-saving 15/06/2009
		if(!$oDB->isColumnExists('editor_autosave', 'module_srl'))
		{
			$oDB->addColumn('editor_autosave', 'module_srl', 'number');
		}
		if(!$oDB->isIndexExists('editor_autosave', 'idx_module_srl'))
		{
			$oDB->addIndex('editor_autosave', 'idx_module_srl', 'module_srl');
		}

		// XEVE-17-030
		if(!$oDB->isColumnExists('editor_autosave', 'certify_key'))
		{
			$oDB->addColumn('editor_autosave', 'certify_key', 'varchar', 32);
		}
		if(!$oDB->isIndexExists('editor_autosave', 'idx_certify_key'))
		{
			$oDB->addIndex('editor_autosave', 'idx_certify_key', 'certify_key');
		}
		
		// 2007. 10. 17 Add a trigger to delete automatically saved document whenever the document(insert or update) is modified
		if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
			$oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
			$oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
		// 2007. 10. Add an editor trigger on the module addition setup
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) 
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');
		// 2009. 04. 14 Add a trigger from compiled codes of the editor component
		if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) 
			$oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');
		// 2009. 06. 19 Remove unused trigger
		if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) 
			$oModuleController->deleteTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before');

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'editor', 'controller', 'triggerCopyModule', 'after');
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file editor.class.php */
/* Location: ./modules/editor/editor.class.php */
