<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pointController
 * @author NAVER (developers@xpressengine.com)
 * @brief Controller class of point modules
 */
class pointController extends point
{
	/**
	 * @brief Initialization
	 */
	public function init()
	{
	}

	/**
	 * @brief Membership point application trigger
	 */
	public function triggerInsertMember($obj)
	{
		$member_srl = $obj->member_srl;
		if (!$member_srl)
		{
			return new Object();
		}
		
		$config = $this->getConfig();
		$point = intval($config->signup_point);
		if (!$point)
		{
			return new Object();
		}
		
		$cur_point = getModel('point')->getPoint($member_srl, true);
		$this->setPoint($member_srl, $cur_point + $point, 'signup');
		
		return new Object();
	}

	/**
	 * @brief A trigger to add points to the member for login
	 */
	public function triggerAfterLogin($obj)
	{
		$member_srl = $obj->member_srl;
		if (!$member_srl)
		{
			return new Object();
		}
		
		// Points are given only once a day.
		if (substr($obj->last_login, 0, 8) === date('Ymd'))
		{
			return new Object();
		}
		
		$config = $this->getConfig();
		$point = intval($config->login_point);
		if (!$point)
		{
			return new Object();
		}
		
		$cur_point = getModel('point')->getPoint($member_srl, true);
		$this->setPoint($member_srl, $cur_point + $point);
		
		return new Object();
	}

	/**
	 * @brief Member group deletion trigger
	 */
	public function triggerDeleteGroup($obj)
	{
		$group_srl = $obj->group_srl;
		$config = $this->getConfig();
		
		// Exclude deleted group from point/level/group integration
		if($config->point_group && isset($config->point_group[$group_srl]))
		{
			unset($config->point_group[$group_srl]);
			getController('module')->insertModuleConfig('point', $config);
		}

		return new Object();
	}
	
	/**
	 * @brief A trigger to add points to the member for creating a post
	 */
	public function triggerInsertDocument($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return new Object();
		}
		
		// The fix to disable giving points for saving the document temporarily
		if ($module_srl == $member_srl)
		{
			return new Object();
		}
		if ($obj->status === getModel('document')->getConfigStatus('temp'))
		{
			return new Object();
		}
		
		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Add points for the document.
		$document_point = $this->_getModulePointConfig($module_srl, 'insert_document');
		$cur_point += $document_point;
		
		// Add points for attached files.
		if ($obj->uploaded_count > 0)
		{
			$attached_files_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$cur_point += $attached_files_point * $obj->uploaded_count;
		}
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief The trigger to give points for normal saving the temporarily saved document
	 * Temporary storage at the point in 1.2.3 changed to avoid payment
	 */
	public function triggerUpdateDocument($obj)
	{
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		
		$module_srl = $oDocument->get('module_srl');
		$member_srl = abs($oDocument->get('member_srl'));
		if (!$module_srl || !$member_srl)
		{
			return new Object();
		}
		
		// Only give points if the document is being updated from TEMP to another status such as PUBLIC.
		if ($obj->status === $oDocumentModel->getConfigStatus('temp') || $oDocument->get('status') !== $oDocumentModel->getConfigStatus('temp'))
		{
			if ($obj->uploaded_count > $oDocument->get('uploaded_count'))
			{
				$cur_point = getModel('point')->getPoint($member_srl, true);
				$attached_files_point = $this->_getModulePointConfig($module_srl, 'upload_file');
				$cur_point += $attached_files_point * ($obj->uploaded_count - $oDocument->get('uploaded_count'));
				$this->setPoint($member_srl, $cur_point);
			}
			return new Object();
		}

		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Add points for the document.
		$document_point = $this->_getModulePointConfig($module_srl, 'insert_document');
		$cur_point += $document_point;
		
		// Add points for attached files.
		if ($obj->uploaded_count > 0)
		{
			$attached_files_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$cur_point += $attached_files_point * $obj->uploaded_count;
		}
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief The trigger which deducts the points related to post comments before deleting the post itself
	 */
	public function triggerBeforeDeleteDocument($obj)
	{
		return new Object();
	}

	/**
	 * @brief A trigger to give points for deleting the post
	 */
	public function triggerDeleteDocument($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return new Object();
		}
		
		// The fix to disable giving points for saving the document temporarily
		if ($module_srl == $member_srl)
		{
			return new Object();
		}
		if ($obj->status === getModel('document')->getConfigStatus('temp'))
		{
			return new Object();
		}
		
		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Subtract points for the document.
		$document_point = $this->_getModulePointConfig($module_srl, 'insert_document');
		if ($document_point > 0)
		{
			$cur_point -= $document_point;
		}
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief A trigger which gives points for entering a comment
	 */
	public function triggerInsertComment($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return new Object();
		}
		
		// Abort if the comment and the document have the same author.
		$oDocument = getModel('document')->getDocument($obj->document_srl);
		if (!$oDocument->isExists() || abs($oDocument->get('member_srl')) == $member_srl)
		{
			return new Object();
		}
		
		// Abort if the document is older than a configured limit.
		$config = $this->getConfig();
		if ($config->no_point_date > 0 && ztime($oDocument->get('regdate')) < time() - ($config->no_point_date * 86400))
		{
			return new Object();
		}
		
		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Add points for the comment.
		$comment_point = $this->_getModulePointConfig($module_srl, 'insert_comment');
		$cur_point += $comment_point;
		
		// Add points for attached files.
		if ($obj->uploaded_count > 0)
		{
			$attached_files_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$cur_point += $attached_files_point * $obj->uploaded_count;
		}
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief A trigger which gives points for uploaded file changes to a comment
	 */
	public function triggerUpdateComment($obj)
	{
		return new Object();
	}
	
	/**
	 * @brief A trigger which gives points for deleting a comment
	 */
	public function triggerDeleteComment($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return new Object();
		}
		
		// Abort if the comment and the document have the same author.
		$oDocument = getModel('document')->getDocument($obj->document_srl);
		if (!$oDocument->isExists() || abs($oDocument->get('member_srl')) == $member_srl)
		{
			return new Object();
		}
		
		// Abort if the document is older than a configured limit.
		$config = $this->getConfig();
		if ($config->no_point_date > 0 && ztime($oDocument->get('regdate')) < ztime($obj->regdate) - ($config->no_point_date * 86400))
		{
			return new Object();
		}
		
		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Add points for the comment.
		$comment_point = $this->_getModulePointConfig($module_srl, 'insert_comment');
		$cur_point -= $comment_point;
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief Add the file registration trigger
	 * To prevent taking points for invalid file registration this method wlil return a null object
	 */
	public function triggerInsertFile($obj)
	{
		return new Object();
	}

	/**
	 * @brief A trigger to give points for deleting a file
	 * Remove points only in case an invalid file is being deleted
	 */
	public function triggerDeleteFile($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl || $obj->isvalid !== 'Y')
		{
			return new Object();
		}
		
		// Get the points of the member
		$cur_point = getModel('point')->getPoint($member_srl, true);

		// Subtract points for the file.
		$file_point = $this->_getModulePointConfig($module_srl, 'upload_file');
		$cur_point -= $file_point;
		
		// Update the point.
		$this->setPoint($member_srl, $cur_point);
		return new Object();
	}

	/**
	 * @brief The trigger called before a file is downloaded
	 */
	public function triggerBeforeDownloadFile($obj)
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$module_srl = $obj->module_srl;
		
		if ($member_srl && abs($obj->member_srl) == $member_srl)
		{
			return new Object();
		}
		
		$point = $this->_getModulePointConfig($module_srl, 'download_file');
		if (!$point)
		{
			return new Object();
		}
		
		// Get current points.
		$cur_point = $member_srl ? getModel('point')->getPoint($member_srl, true) : 0;
		
		// If the user (member or guest) does not have enough points, deny access.
		$config = $this->getConfig();
		if ($config->disable_download == 'Y' && $cur_point + $point < 0)
		{
			return new Object(-1, 'msg_cannot_download');
		}
		
		// Points will be adjusted after downloading (triggerDownloadFile).
		return new Object();
	}

	/**
	 * @brief The trigger to give or take points for downloading the file
	 */
	public function triggerDownloadFile($obj)
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$module_srl = $obj->module_srl;
		
		if (!$member_srl || abs($obj->member_srl) == $member_srl)
		{
			return new Object();
		}
		
		$point = $this->_getModulePointConfig($module_srl, 'download_file');
		if (!$point)
		{
			return new Object();
		}
		
		$cur_point = getModel('point')->getPoint($member_srl, true);
		$this->setPoint($member_srl, $cur_point + $point);
		
		return new Object();
	}

	/**
	 * @brief Give points for hits increase
	 * Run it even if there are no points
	 */
	public function triggerUpdateReadedCount($obj)
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$module_srl = $obj->get('module_srl');
		$target_member_srl = abs($obj->get('member_srl'));
		if ($member_srl && $target_member_srl == $member_srl)
		{
			return new Object();
		}
		
		$point = $this->_getModulePointConfig($module_srl, 'read_document');
		if (!$point)
		{
			return new Object();
		}
		
		// If the current member has already read this document, do not adjust points again.
		if ($member_srl)
		{
			$args = new stdClass();
			$args->member_srl = $member_srl;
			$args->document_srl = $obj->document_srl;
			$output = executeQuery('document.getDocumentReadedLogInfo', $args);
			if ($output->data->count)
			{
				return new Object();
			}
		}
		
		// Get current points.
		$cur_point = $member_srl ? getModel('point')->getPoint($member_srl, true) : 0;
		
		// If the user (member or guest) does not have enough points, deny access.
		$config = $this->getConfig();
		if($config->disable_read_document == 'Y' && $cur_point + $point < 0)
		{
			$message = sprintf(lang('msg_disallow_by_point'), abs($point), $cur_point);
			$obj->add('content', $message);
			$_SESSION['banned_document'][$obj->document_srl] = true;
			return new Object(-1, $message);
		}
		else
		{
			$_SESSION['banned_document'][$obj->document_srl] = false;
		}
		
		// Adjust points for member.
		if ($member_srl)
		{
			$args = new stdClass();
			$args->member_srl = $member_srl;
			$args->document_srl = $obj->document_srl;
			$output = executeQuery('document.insertDocumentReadedLog', $args);
			$this->setPoint($member_srl, $cur_point + $point);
		}
		
		return new Object();
	}

	/**
	 * @brief Points for voting up or down
	 */
	public function triggerUpdateVotedCount($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info->member_srl;
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if ($logged_member_srl && $logged_member_srl == $member_srl)
		{
			return new Object();
		}
		elseif (!$member_srl)
		{
			return new Object();
		}
		
		// Get current points.
		$cur_point = getModel('point')->getPoint($member_srl, true);
		
		// Get adjustment amount.
		if ($obj->point > 0)
		{
			$config_key = (isset($obj->comment_srl) && $obj->comment_srl) ? 'voted_comment' : 'voted';
		}
		else
		{
			$config_key = (isset($obj->comment_srl) && $obj->comment_srl) ? 'blamed_comment' : 'blamed';
		}
		
		$point = $this->_getModulePointConfig($module_srl, $config_key);
		if (!$point)
		{
			return new Object();
		}
		
		if (isset($obj->cancel) && $obj->cancel)
		{
			$point = -1 * $point;
		}
		
		$this->setPoint($member_srl, $cur_point + $point);
		
		return new Object();
	}

	/**
	 * @brief Copy point settings when copying module
	 */
	public function triggerCopyModule($obj)
	{
		$oModuleModel = getModel('module');
		$pointConfig = $oModuleModel->getModulePartConfig('point', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList AS $key=>$moduleSrl)
			{
				$oModuleController->insertModulePartConfig('point', $moduleSrl, $pointConfig);
			}
		}
	}

	/**
	 * @brief Set points
	 */
	public function setPoint($member_srl, $point, $mode = null)
	{
		$member_srl = abs($member_srl);
		$mode_arr = array('add', 'minus', 'update', 'signup');
		if(!$mode || !in_array($mode,$mode_arr)) $mode = 'update';

		// Get configuration information
		$oMemberModel = getModel('member');
		$oModuleModel = getModel('module');
		$oPointModel = getModel('point');
		$config = $oModuleModel->getModuleConfig('point');

		// Get the default configuration information
		$current_point = $oPointModel->getPoint($member_srl, true);
		$current_level = $oPointModel->getLevel($current_point, $config->level_step);

		// Change points
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->point = $current_point;

		switch($mode)
		{
			case 'add' :
				$args->point += $point;
				break;
			case 'minus' :
				$args->point -= $point;
				break;
			case 'update' :
			case 'signup' :
				$args->point = $point;
				break;
		}
		if($args->point < 0) $args->point = 0;
		$point = $args->point;

		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->member_srl = $args->member_srl;
		$trigger_obj->mode = $mode;
		$trigger_obj->current_point = $current_point;
		$trigger_obj->current_level = $current_level;
		$trigger_obj->set_point = $point;
		$trigger_output = ModuleHandler::triggerCall('point.setPoint', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		// If there are points, update, if no, insert
		$oPointModel = getModel('point');
		if($oPointModel->isExistsPoint($member_srl)) executeQuery("point.updatePoint", $args);
		else executeQuery("point.insertPoint", $args);

		// Get a new level
		$level = $oPointModel->getLevel($point, $config->level_step);

		// If existing level and a new one are different attempt to set a point group
		$new_group_list = array();
		$del_group_list = array();
		if ($config->group_ratchet === 'Y')
		{
			$change_group = ($level > $current_level);
		}
		else
		{
			$change_group = ($level != $current_level);
		}
		
		if ($change_group)
		{
			// Check if the level, for which the current points are prepared, is calculate and set the correct group
			$point_group = $config->point_group;
			// If the point group exists
			if($point_group && is_array($point_group) && count($point_group) )
			{
				// Get the default group
				$default_group = $oMemberModel->getDefaultGroup();
				asort($point_group);
				
				// Reset group after initialization
				if($config->group_reset != 'N')
				{
					// If the new level is in the right group
					if(in_array($level, $point_group))
					{
						// Delete all groups except the one which the current level belongs to
						foreach($point_group as $group_srl => $target_level)
						{
							$del_group_list[] = $group_srl;
							if($target_level == $level) $new_group_list[] = $group_srl;
						}
					}
					// Otherwise, in case the level is reduced, add the recent group
					else
					{
						$i = $level;
						while($i > 0)
						{
							if(in_array($i, $point_group))
							{
								foreach($point_group as $group_srl => $target_level)
								{
									if($target_level == $i)
									{
										$new_group_list[] = $group_srl;
									}
								}
								$i = 0;
							}
							$i--;
						}
					}
					// Delete the group of a level which is higher than the current level
					foreach($point_group as $group_srl => $target_level)
					{
						if($target_level > $level) $del_group_list[] = $group_srl;
					}
					$del_group_list[] = $default_group->group_srl;
				}
				// Grant a new group
				else
				{
					// Check until the current level by rotating setting the configurations of the point groups
					foreach($point_group as $group_srl => $target_level)
					{
						$del_group_list[] = $group_srl;
						if($target_level <= $level) $new_group_list[] = $group_srl;
					}
				}
				// If there is no a new group, granted the default group
				if(!$new_group_list[0]) $new_group_list[0] = $default_group->group_srl;
				// Remove linkage group
				if($del_group_list && count($del_group_list))
				{
					$del_group_args = new stdClass;
					$del_group_args->member_srl = $member_srl;
					$del_group_args->group_srl = implode(',', $del_group_list);
					$del_group_output = executeQuery('point.deleteMemberGroup', $del_group_args);
				}
				// Grant a new group
				foreach($new_group_list as $group_srl)
				{
					$new_group_args = new stdClass;
					$new_group_args->member_srl = $member_srl;
					$new_group_args->group_srl = $group_srl;
					executeQuery('member.addMemberToGroup', $new_group_args);
				}
			}
		}

		// Call a trigger (after)
		$trigger_obj->new_group_list = $new_group_list;
		$trigger_obj->del_group_list = $del_group_list;
		$trigger_obj->new_level = $level;
		ModuleHandler::triggerCall('point.setPoint', 'after', $trigger_obj);

		$oDB->commit();

		// Cache Settings
		$cache_path = sprintf('./files/member_extra_info/point/%s/', getNumberingPath($member_srl));
		FileHandler::makedir($cache_path);

		$cache_filename = sprintf('%s%d.cache.txt', $cache_path, $member_srl);
		FileHandler::writeFile($cache_filename, $point);

		getController('member')->_clearMemberCache($member_srl);
		unset(self::$_member_point_cache[$member_srl]);

		return $output;
	}
	
	/**
	 * Get point configuration for module, falling back to defaults if not set.
	 * 
	 * @param int $module_srl
	 * @param string $config_key
	 * @return int
	 */
	protected function _getModulePointConfig($module_srl, $config_key)
	{
		$module_srl = intval($module_srl);
		$config_key = strval($config_key);
		if (!$config_key)
		{
			return 0;
		}
		
		$oModuleModel = getModel('module');
		
		if ($module_srl)
		{
			if (!isset(self::$_module_config_cache[$module_srl]))
			{
				self::$_module_config_cache[$module_srl] = $oModuleModel->getModulePartConfig('point', $module_srl);
			}
			$module_config = self::$_module_config_cache[$module_srl];
		}
		else
		{
			$module_config = array();
		}
		
		if (isset($module_config[$config_key]) && $module_config[$config_key] !== '')
		{
			$point = $module_config[$config_key];
		}
		else
		{
			$default_config = $this->getConfig();
			$point = $default_config->{$config_key};
		}
		
		return intval($point);
	}
}
/* End of file point.controller.php */
/* Location: ./modules/point/point.controller.php */
