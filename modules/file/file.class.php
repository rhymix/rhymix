<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * High class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class file extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');
		
		// Save the default settings for attachments
		$config = new stdClass;
		$config->allowed_filesize = '2';
		$config->allowed_attach_size = '2';
		$config->allowed_filetypes = '*.*';
		$oModuleController->insertModuleConfig('file', $config);
		// Generate a directory for the file module
		FileHandler::makeDir('./files/attach/images');
		FileHandler::makeDir('./files/attach/binaries');
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		$oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
		$oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
		$oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
		$oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
		$oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');
		$oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
		$oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
		$oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
		$oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
		$oModuleController->insertTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after');
		// 2009. 6. 9 Delete all the attachements when auto-saved document is deleted
		$oModuleController->insertTrigger('editor.deleteSavedDoc', 'file', 'controller', 'triggerDeleteAttached', 'after');
		// 2007. 10. 17 Create a trigger to delete all the attachements when the module is deleted
		$oModuleController->insertTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after');
		// 2007. 10. 19 Call a trigger to set up the file permissions before displaying
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');

		return new Object();
	}

	/**
	 * A method to check if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
		if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
		if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
		if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
		if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
		if(!$oModuleModel->getTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after')) return true;
		// 2009. 6. 9 Delete all the attachements when auto-saved document is deleted
		if(!$oModuleModel->getTrigger('editor.deleteSavedDoc', 'file', 'controller', 'triggerDeleteAttached', 'after')) return true;
		// 2007. 10. 17 Create a trigger to delete all the attachements when the module is deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after')) return true;
		// 2007. 10. 19 Call a trigger to set up the file permissions before displaying
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before')) return true;
		// A column to determine a target type
		if(!$oDB->isColumnExists('files', 'upload_target_type')) return true;

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'file', 'controller', 'triggerCopyModule', 'after')) return true;

		if(!$oDB->isColumnExists('files', 'cover_image')) return true;

		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before'))
			$oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before');

		if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after'))
			$oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after');

		if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before'))
			$oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before');

		if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after'))
			$oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after');

		if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after'))
			$oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');

		if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before'))
			$oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');

		if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after'))
			$oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');

		if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before'))
			$oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');

		if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after'))
			$oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');

		if(!$oModuleModel->getTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after'))
			$oModuleController->insertTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after');
		// 2009. 6. 9 Delete all the attachements when auto-saved document is deleted
		if(!$oModuleModel->getTrigger('editor.deleteSavedDoc', 'file', 'controller', 'triggerDeleteAttached', 'after'))
			$oModuleController->insertTrigger('editor.deleteSavedDoc', 'file', 'controller', 'triggerDeleteAttached', 'after');
		// 2007. 10. 17 Create a trigger to delete all the attachements when the module is deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after'))
			$oModuleController->insertTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after');
		// 2007. 10. 19 Call a trigger to set up the file permissions before displaying
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before'))
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');
		// A column to determine a target type
		if(!$oDB->isColumnExists('files', 'upload_target_type')) $oDB->addColumn('files', 'upload_target_type', 'char', '3');

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'file', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'file', 'controller', 'triggerCopyModule', 'after');
		}

		if(!$oDB->isColumnExists('files', 'cover_image')) $oDB->addColumn('files', 'cover_image', 'char', '1', 'N');

		return new Object(0, 'success_updated');
	}

	/**
	 * Re-generate the cache file
	 *
	 * @return Object
	 */
	function recompileCache()
	{
	}
}
/* End of file file.class.php */
/* Location: ./modules/file/file.class.php */
