<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  point
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the point module
 */
class point extends ModuleObject
{
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Registration in action forward (for using in the administrator mode)
		$oModuleController = getController('module');
		// Create a directory to store points information
		FileHandler::makeDir('./files/member_extra_info/point');

		$oModuleController = getController('module');
		// The highest level
		$config = new stdClass;
		// default, point module is OFF
		$config->able_module = 'N';
		$config->max_level = 30;
		// Per-level score
		for($i=1;$i<=30;$i++)
		{
			$config->level_step[$i] = pow($i,2)*90;
		}
		// Points for registration
		$config->signup_point = 10;
		// Login points
		$config->login_point = 5;
		// Point's name
		$config->point_name = 'point';
		// Level icon directory
		$config->level_icon = "default";
		// Prevent downloads if there are no scores
		$config->disable_download = false;

		/**
		 * Define the default points per module as well as all actions (as we do not know if it is forum or blogs, specify "act")
		 */
		// Insert document
		$config->insert_document = 10;

		$config->insert_document_act = 'procBoardInsertDocument';
		$config->delete_document_act = 'procBoardDeleteDocument';
		// Insert comment
		$config->insert_comment = 5;

		$config->insert_comment_act = 'procBoardInsertComment,procBlogInsertComment';
		$config->delete_comment_act = 'procBoardDeleteComment,procBlogDeleteComment';
		// Upload
		$config->upload_file = 5;

		$config->upload_file_act = 'procFileUpload';
		$config->delete_file_act = 'procFileDelete';
		// Download
		$config->download_file = -5;
		$config->download_file_act = 'procFileDownload';
		// View
		$config->read_document = 0;
		// Vote up / Vote down
		$config->voted = 0;
		$config->blamed = 0;
		// Save configurations
		$oModuleController->insertModuleConfig('point', $config);
		// Cash act list for faster execution
		$oPointController = getAdminController('point');
		$oPointController->cacheActList();

		return new Object();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		// Get the information of the point module
		$oModuleModel = getModel('module');

		$config = $oModuleModel->getModuleConfig('point');
		// check if module is abled
		if($config->able_module != 'N')
		{
			// Add a trigger for registration/insert document/insert comment/upload a file/download
			if(!$oModuleModel->getTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerBeforeDeleteDocument', 'before')) return true;
			if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after')) return true;
			if(!$oModuleModel->getTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after')) return true;
			if(!$oModuleModel->getTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after')) return true;
			if(!$oModuleModel->getTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after')) return true;
			if(!$oModuleModel->getTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after')) return true;
			if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before')) return true;
			if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after')) return true;
			if(!$oModuleModel->getTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after')) return true;
			if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.updateReadedCount', 'point', 'controller', 'triggerUpdateReadedCount', 'after')) return true;
			// Add a trigger for voting up and down 2008.05.13 haneul
			if(!$oModuleModel->getTrigger('document.updateVotedCount', 'point', 'controller', 'triggerUpdateVotedCount', 'after')) return true;
			// Add a trigger for using points for permanent saving of a temporarily saved document 2009.05.19 zero
			if(!$oModuleModel->getTrigger('document.updateDocument', 'point', 'controller', 'triggerUpdateDocument', 'before')) return true;

			// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
			if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'point', 'controller', 'triggerCopyModule', 'after')) return true;
		}

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		// Get the information of the point module
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// Add a trigger for registration/insert document/insert comment/upload a file/download
		if(!$oModuleModel->getTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after')) 
			$oModuleController->insertTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after');
		if(!$oModuleModel->getTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after')) 
			$oModuleController->insertTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after');
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerBeforeDeleteDocument', 'before')) 
			$oModuleController->insertTrigger('document.deleteDocument', 'point', 'controller', 'triggerBeforeDeleteDocument', 'before');
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after')) 
			$oModuleController->insertTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after');
		if(!$oModuleModel->getTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after')) 
			$oModuleController->insertTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after');
		if(!$oModuleModel->getTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after')) 
			$oModuleController->insertTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after');
		if(!$oModuleModel->getTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after')) 
			$oModuleController->insertTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after');
		if(!$oModuleModel->getTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after')) 
			$oModuleController->insertTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after');
		if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before')) 
			$oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before');
		if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after')) 
			$oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after');
		if(!$oModuleModel->getTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after'))
			$oModuleController->insertTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after');
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after')) 
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after');
		if(!$oModuleModel->getTrigger('document.updateReadedCount', 'point', 'controller', 'triggerUpdateReadedCount', 'after')) 
			$oModuleController->insertTrigger('document.updateReadedCount', 'point', 'controller', 'triggerUpdateReadedCount', 'after');
		// Add a trigger for voting up and down 2008.05.13 haneul
		if(!$oModuleModel->getTrigger('document.updateVotedCount', 'point', 'controller', 'triggerUpdateVotedCount', 'after'))
			$oModuleController->insertTrigger('document.updateVotedCount', 'point', 'controller', 'triggerUpdateVotedCount', 'after');
		// Add a trigger for using points for permanent saving of a temporarily saved document 2009.05.19 zero
		if(!$oModuleModel->getTrigger('document.updateDocument', 'point', 'controller', 'triggerUpdateDocument', 'before')) 
			$oModuleController->insertTrigger('document.updateDocument', 'point', 'controller', 'triggerUpdateDocument', 'before');
		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'point', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'point', 'controller', 'triggerCopyModule', 'after');
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief Re-create the cache file
	 */
	function recompileCache()
	{
		// redefine point action file
		$oPointAdminController = getAdminController('point');
		$oPointAdminController->cacheActList();
	}
}
/* End of file point.class.php */
/* Location: ./modules/point/point.class.php */
