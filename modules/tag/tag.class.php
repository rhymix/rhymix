<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tag
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the tag module
 */
class tag extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();

		$oDB->addIndex("tags","idx_tag", array("document_srl","tag"));
		// 2007. 10. 17 document.insertDocument, updateDocument, deleteDocument trigger property for
		$oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');
		$oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after');
		$oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');
		$oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after');
		$oModuleController->insertTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after');
		// 2007. 10. 17 modules are deleted when you delete all registered triggers that add tag
		$oModuleController->insertTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after');

		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();
		// 2007. 10. 17 trigger registration, if registered upset
		if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) return true;
		if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after')) return true;
		// 2007. 10. 17 modules are deleted when you delete all registered triggers that add tag
		if(!$oModuleModel->getTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after')) return true;
		// tag in the index column of the table tag
		if(!$oDB->isIndexExists("tags","idx_tag")) return true;

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
		// 2007. 10. 17 document.insertDocument, updateDocument, deleteDocument trigger property for
		if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) 
			$oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');

		if(!$oModuleModel->getTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) 
			$oModuleController->insertTrigger('document.insertDocument', 'tag', 'controller', 'triggerInsertTag', 'after');

		if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before')) 
			$oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerArrangeTag', 'before');

		if(!$oModuleModel->getTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after')) 
			$oModuleController->insertTrigger('document.updateDocument', 'tag', 'controller', 'triggerInsertTag', 'after');

		if(!$oModuleModel->getTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after')) 
			$oModuleController->insertTrigger('document.deleteDocument', 'tag', 'controller', 'triggerDeleteTag', 'after');
		// 2007. 10. 17 modules are deleted when you delete all registered triggers that add tag
		if(!$oModuleModel->getTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after'))
			$oModuleController->insertTrigger('module.deleteModule', 'tag', 'controller', 'triggerDeleteModuleTags', 'after');
		// tag in the index column of the table tag
		if(!$oDB->isIndexExists("tags","idx_tag")) 
			$oDB->addIndex("tags","idx_tag", array("document_srl","tag"));

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file tag.class.php */
/* Location: ./modules/tag/tag.class.php */
