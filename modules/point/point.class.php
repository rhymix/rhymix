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
	 * Cache for the point module's own configuration.
	 */
	protected static $_config = null;
	
	/**
	 * Cache for other modules' point configuration.
	 */
	protected static $_module_config_cache = array();
	
	/**
	 * Cache for member points.
	 */
	protected static $_member_point_cache = array();
	
	/**
	 * Triggers to insert.
	 */
	protected static $_insert_triggers = array(
		array('member.insertMember', 'after', 'controller', 'triggerInsertMember'),
		array('member.doLogin', 'after', 'controller', 'triggerAfterLogin'),
		array('member.deleteGroup', 'after', 'controller', 'triggerDeleteGroup'),
		array('document.insertDocument', 'after', 'controller', 'triggerInsertDocument'),
		array('document.updateDocument', 'before', 'controller', 'triggerBeforeUpdateDocument'),
		array('document.updateDocument', 'after', 'controller', 'triggerAfterUpdateDocument'),
		array('document.deleteDocument', 'after', 'controller', 'triggerDeleteDocument'),
		array('document.moveDocumentToTrash', 'after', 'controller', 'triggerTrashDocument'),
		array('comment.insertComment', 'after', 'controller', 'triggerInsertComment'),
		array('comment.updateComment', 'after', 'controller', 'triggerUpdateComment'),
		array('comment.deleteComment', 'after', 'controller', 'triggerDeleteComment'),
		array('comment.moveCommentToTrash', 'after', 'controller', 'triggerTrashComment'),
		array('file.deleteFile', 'after', 'controller', 'triggerDeleteFile'),
		array('file.downloadFile', 'before', 'controller', 'triggerBeforeDownloadFile'),
		array('file.downloadFile', 'after', 'controller', 'triggerDownloadFile'),
		array('document.updateReadedCount', 'after', 'controller', 'triggerUpdateReadedCount'),
		array('document.updateVotedCount', 'after', 'controller', 'triggerUpdateVotedCount'),
		array('document.updateVotedCountCancel', 'after', 'controller', 'triggerUpdateVotedCount'),
		array('comment.updateVotedCount', 'after', 'controller', 'triggerUpdateVotedCount'),
		array('comment.updateVotedCountCancel', 'after', 'controller', 'triggerUpdateVotedCount'),
		array('module.procModuleAdminCopyModule', 'after', 'controller', 'triggerCopyModule'),
		array('module.dispAdditionSetup', 'after', 'view', 'triggerDispPointAdditionSetup'),
	);
	
	/**
	 * Triggers to delete.
	 */
	protected static $_delete_triggers = array(
		array('document.updateDocument', 'before', 'controller', 'triggerUpdateDocument'),
		array('document.deleteDocument', 'before', 'controller', 'triggerBeforeDeleteDocument'),
		array('file.insertFile', 'after', 'controller', 'triggerInsertFile'),
	);
	
	/**
	 * @brief Shortcut to getting module configuration
	 */
	public function getConfig()
	{
		if (self::$_config === null)
		{
			self::$_config = getModel('module')->getModuleConfig('point');
		}
		return self::$_config;
	}
	
	/**
	 * Check triggers.
	 * 
	 * @return bool
	 */
	public function checkTriggers()
	{
		$oModuleModel = getModel('module');
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if ($oModuleModel->getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Register triggers.
	 * 
	 * @return object
	 */
	public function registerTriggers()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->insertTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if ($oModuleModel->getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->deleteTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
	}
	
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Define default configuration.
		$config = new stdClass;
		$config->able_module = 'N';
		$config->point_name = 'point';
		$config->level_icon = 'default';
		$config->disable_read_document = 'N';
		$config->disable_download = 'N';
		$config->group_reset = 'Y';
		$config->group_ratchet = 'N';
		$config->max_level = 30;
		for ($i = 1; $i <= 30; $i++)
		{
			$config->level_step[$i] = pow($i, 2) * 90;
		}
		
		// Define default points.
		$config->signup_point = 10;
		$config->login_point = 5;
		$config->insert_document = 10;
		$config->insert_comment = 5;
		$config->upload_file = 5;
		$config->download_file = -5;
		$config->read_document = 0;
		$config->voted = 0;
		$config->blamed = 0;
		$config->voted_comment = 0;
		$config->blamed_comment = 0;
		
		// Save module config.
		getController('module')->insertModuleConfig('point', $config);
		
		// Create a directory to store points information.
		FileHandler::makeDir('./files/member_extra_info/point');
		
		// Register triggers.
		$this->registerTriggers();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$config = $this->getConfig();
		if ($config->able_module === 'Y')
		{
			return $this->checkTriggers();
		}
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		return $this->registerTriggers();
	}

	/**
	 * @brief Re-create the cache file
	 */
	function recompileCache()
	{
		
	}
}
/* End of file point.class.php */
/* Location: ./modules/point/point.class.php */
