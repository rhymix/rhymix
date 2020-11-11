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
		
		// Generate a directory for the file module
		FileHandler::makeDir('./files/attach/images');
		FileHandler::makeDir('./files/attach/binaries');
		
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		$oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');
		$oModuleController->insertTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after');
		// 2009. 6. 9 Delete all the attachements when auto-saved document is deleted
		$oModuleController->insertTrigger('editor.deleteSavedDoc', 'file', 'controller', 'triggerDeleteAttached', 'after');
		// 2007. 10. 17 Create a trigger to delete all the attachements when the module is deleted
		$oModuleController->insertTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after');
		// 2007. 10. 19 Call a trigger to set up the file permissions before displaying
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');
	}

	/**
	 * A method to check if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

		if($oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
		if($oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
		if($oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
		if($oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
		
		if($oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
		if($oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
		if($oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
		if($oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
		
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after')) return true;
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
		
		if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'file', 'controller', 'triggerMoveDocument', 'after'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('document.copyDocumentModule', 'file', 'controller', 'triggerAddCopyDocument', 'add'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('comment.copyCommentByDocument', 'file', 'controller', 'triggerAddCopyCommentByDocument', 'add'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'thumbnail_filename'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'mime_type') || !$oDB->isIndexExists('files', 'idx_mime_type'))
		{
			return true;
		}
		if($oDB->getColumnInfo('files', 'mime_type')->size < 100)
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'original_type'))
		{
			return true;
		}
		if($oDB->getColumnInfo('files', 'original_type')->size < 100)
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'width'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'height'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'duration'))
		{
			return true;
		}
		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');
		/** @var moduleController $oModuleController */
		$oModuleController = getController('module');

		if($oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before'))
		{
			$oModuleController->deleteTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
		}

		if($oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after'))
		{
			$oModuleController->deleteTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
		}

		if($oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before'))
		{
			$oModuleController->deleteTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
		}

		if($oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after'))
		{
			$oModuleController->deleteTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
		}

		if($oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before'))
		{
			$oModuleController->deleteTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
		}

		if($oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after'))
		{
			$oModuleController->deleteTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
		}

		if($oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before'))
		{
			$oModuleController->deleteTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
		}

		if($oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after'))
		{
			$oModuleController->deleteTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
		}
		
		// 2007. 10. 17 Create a trigger to insert, update, delete documents and comments
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after'))
			$oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');

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
		
		if(!$oModuleModel->getTrigger('document.moveDocumentModule', 'file', 'controller', 'triggerMoveDocument', 'after'))
		{
			$oModuleController->insertTrigger('document.moveDocumentModule', 'file', 'controller', 'triggerMoveDocument', 'after');
		}
		if(!$oModuleModel->getTrigger('document.copyDocumentModule', 'file', 'controller', 'triggerAddCopyDocument', 'add'))
		{
			$oModuleController->insertTrigger('document.copyDocumentModule', 'file', 'controller', 'triggerAddCopyDocument', 'add');
		}
		if(!$oModuleModel->getTrigger('comment.copyCommentByDocument', 'file', 'controller', 'triggerAddCopyCommentByDocument', 'add'))
		{
			$oModuleController->insertTrigger('comment.copyCommentByDocument', 'file', 'controller', 'triggerAddCopyCommentByDocument', 'add');
		}
		if(!$oDB->isColumnExists('files', 'thumbnail_filename'))
		{
			$oDB->addColumn('files', 'thumbnail_filename', 'varchar', '250', null, false, 'uploaded_filename');
		}
		if(!$oDB->isColumnExists('files', 'mime_type'))
		{
			$oDB->addColumn('files', 'mime_type', 'varchar', '100', '', true, 'file_size');
		}
		if($oDB->getColumnInfo('files', 'mime_type')->size < 100)
		{
			$oDB->modifyColumn('files', 'mime_type', 'varchar', 100, '', true);
		}
		if(!$oDB->isIndexExists('files', 'idx_mime_type'))
		{
			$oDB->addIndex('files', 'idx_mime_type', 'mime_type');
		}
		if(!$oDB->isColumnExists('files', 'original_type'))
		{
			$oDB->addColumn('files', 'original_type', 'varchar', '100', null, false, 'mime_type');
		}
		if($oDB->getColumnInfo('files', 'original_type')->size < 100)
		{
			$oDB->modifyColumn('files', 'original_type', 'varchar', 100, '', false);
		}
		if(!$oDB->isColumnExists('files', 'width'))
		{
			$oDB->addColumn('files', 'width', 'number', '11', null, false, 'original_type');
		}
		if(!$oDB->isColumnExists('files', 'height'))
		{
			$oDB->addColumn('files', 'height', 'number', '11', null, false, 'width');
		}
		if(!$oDB->isColumnExists('files', 'duration'))
		{
			$oDB->addColumn('files', 'duration', 'number', '11', null, false, 'height');
		}
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
