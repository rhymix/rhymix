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

		return new Object();
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
		if(!$oDB->isColumnExists("editor_autosave","module_srl")) 
			$oDB->addColumn("editor_autosave","module_srl","number",11);

		// create an index on module_srl
		if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) $oDB->addIndex("editor_autosave","idx_module_srl", "module_srl");

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

		return new Object(0, 'success_updated');
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
