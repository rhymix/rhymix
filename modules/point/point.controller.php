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
	 * Variables for internal reference.
	 */
	protected $_original;
	
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
			return;
		}
		
		$config = $this->getConfig();
		$point = intval($config->signup_point);
		if (!$point)
		{
			return;
		}
		
		$cur_point = PointModel::getPoint($member_srl);
		$this->setPoint($member_srl, $cur_point + $point, 'signup');
	}

	/**
	 * @brief A trigger to add points to the member for login
	 */
	public function triggerAfterLogin($obj)
	{
		$member_srl = $obj->member_srl;
		if (!$member_srl)
		{
			return;
		}
		
		// Points are given only once a day.
		if (substr($obj->last_login, 0, 8) === date('Ymd'))
		{
			return;
		}
		
		$config = $this->getConfig();
		$point = intval($config->login_point);
		if (!$point)
		{
			return;
		}
		
		$cur_point = PointModel::getPoint($member_srl);
		$this->setPoint($member_srl, $cur_point + $point);
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
			return;
		}
		
		// The fix to disable giving points for saving the document temporarily
		if ($module_srl == $member_srl)
		{
			return;
		}
		if ($obj->status === DocumentModel::getConfigStatus('temp'))
		{
			return;
		}
		
		$diff = 0;

		// Add points for the document.
		$diff += $this->_getModulePointConfig($module_srl, 'insert_document');
		
		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}
		
		// Increase the point.
		$cur_point = PointModel::getPoint($member_srl);
		$this->setPoint($member_srl, $cur_point + $diff);
	}

	/**
	 * Save information about the original document before updating.
	 */
	public function triggerBeforeUpdateDocument($obj)
	{
		if ($obj->document_srl)
		{
			$this->_original = DocumentModel::getDocument($obj->document_srl);
			if (!$this->_original->isExists())
			{
				$this->_original = null;
			}
		}
	}
	
	/**
	 * @brief The trigger to give points for normal saving the temporarily saved document
	 * Temporary storage at the point in 1.2.3 changed to avoid payment
	 */
	public function triggerAfterUpdateDocument($obj)
	{
		if ($this->_original)
		{
			$module_srl = $this->_original->get('module_srl');
			$member_srl = abs($this->_original->get('member_srl'));
		}
		else
		{
			$module_srl = $obj->module_srl;
			$member_srl = $obj->member_srl;
		}
		
		if (!$module_srl || !$member_srl)
		{
			$this->_original = null;
			return;
		}
		
		$diff = 0;
		
		// Add points for the document, only if the document is being updated from TEMP to another status such as PUBLIC.
		if ($obj->status !== DocumentModel::getConfigStatus('temp') && $this->_original->get('status') === DocumentModel::getConfigStatus('temp'))
		{
			$diff += $this->_getModulePointConfig($module_srl, 'insert_document');
		}

		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}
		
		// Increase the point.
		$cur_point = PointModel::getPoint($member_srl);
		$this->setPoint($member_srl, $cur_point + $diff);
		
		// Remove the reference to the original document.
		$this->_original = null;
	}

	/**
	 * @brief The trigger which deducts the points related to post comments before deleting the post itself
	 */
	public function triggerBeforeDeleteDocument($obj)
	{
		
	}

	/**
	 * @brief A trigger to deduct points for deleting the post
	 */
	public function triggerDeleteDocument($obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return;
		}
		if ($obj->isEmptyTrash)
		{
			return;
		}
		
		// Return if disabled
		$config = $this->getConfig();
		if ($config->insert_document_revert_on_delete === false)
		{
			return;
		}
		
		// The fix to disable giving points for saving the document temporarily
		if ($module_srl == $member_srl)
		{
			return;
		}
		if ($obj->status === DocumentModel::getConfigStatus('temp'))
		{
			return;
		}
		
		// Get the points of the member
		$cur_point = PointModel::getPoint($member_srl);

		// Subtract points for the document.
		$document_point = $this->_getModulePointConfig($module_srl, 'insert_document');
		if ($document_point > 0)
		{
			$cur_point -= $document_point;
		}
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
	}

	/**
	 * @brief A trigger to deduct points when a document is moved to Trash
	 */
	public function triggerTrashDocument($obj)
	{
		return $this->triggerDeleteDocument($obj);
	}

	/**
	 * @brief A trigger which gives points for entering a comment
	 */
	public function triggerInsertComment($obj, $mode = 'insert')
	{
		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		if (!$module_srl || !$member_srl)
		{
			return;
		}
		
		// Abort if the comment and the document have the same author.
		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if (!$oDocument->isExists() || abs($oDocument->get('member_srl')) == $member_srl)
		{
			return;
		}
		
		// Abort if the document is older than a configured limit.
		$config = $this->getConfig();
		$time_limit = $config->insert_comment_limit ?: $config->no_point_date;
		if ($time_limit > 0 && ztime($oDocument->get('regdate')) < RX_TIME - ($time_limit * 86400))
		{
			return;
		}
		
		$diff = 0;
		
		// Add points for the comment.
		if ($mode === 'insert')
		{
			$diff += $this->_getModulePointConfig($module_srl, 'insert_comment');
		}
		
		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = $this->_getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}
		
		// Increase the point.
		$cur_point = PointModel::getPoint($member_srl);
		$this->setPoint($member_srl, $cur_point + $diff);
	}

	/**
	 * @brief A trigger which gives points for uploaded file changes to a comment
	 */
	public function triggerUpdateComment($obj)
	{
		$this->triggerInsertComment($obj, 'update');
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
			return;
		}
		if ($obj->isMoveToTrash)
		{
			return;
		}
		
		// Return if disabled
		$config = $this->getConfig();
		if ($config->insert_comment_revert_on_delete === false)
		{
			return;
		}
		
		// Abort if the comment and the document have the same author.
		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if (!$oDocument->isExists() || abs($oDocument->get('member_srl')) == $member_srl)
		{
			return;
		}
		
		// Abort if the document is older than a configured limit.
		$time_limit = $config->insert_comment_limit ?: $config->no_point_date;
		if ($time_limit > 0 && ztime($oDocument->get('regdate')) < RX_TIME - ($time_limit * 86400))
		{
			return;
		}
		
		// Get the module_srl of the document to which this comment belongs
		$module_srl = $oDocument->get('module_srl');
		
		// Get the points of the member
		$cur_point = PointModel::getPoint($member_srl);

		// Add points for the comment.
		$comment_point = $this->_getModulePointConfig($module_srl, 'insert_comment');
		$cur_point -= $comment_point;
		
		// Increase the point.
		$this->setPoint($member_srl, $cur_point);
	}

	/**
	 * @brief A trigger to deduct points when a comment is moved to Trash
	 */
	public function triggerTrashComment($obj)
	{
		return $this->triggerDeleteComment($obj);
	}

	/**
	 * @brief Add the file registration trigger
	 * To prevent taking points for invalid file registration this method wlil return a null object
	 */
	public function triggerInsertFile($obj)
	{
		
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
			return;
		}
		
		// Return if disabled
		$config = $this->getConfig();
		if ($config->upload_file_revert_on_delete === false)
		{
			return;
		}
		
		// Get the points of the member
		$cur_point = PointModel::getPoint($member_srl);

		// Subtract points for the file.
		$file_point = $this->_getModulePointConfig($module_srl, 'upload_file');
		$cur_point -= $file_point;
		
		// Update the point.
		$this->setPoint($member_srl, $cur_point);
	}

	/**
	 * @brief The trigger called before a file is downloaded
	 */
	public function triggerBeforeDownloadFile($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info->member_srl;
		$author_member_srl = abs($obj->member_srl);
		$module_srl = $obj->module_srl;
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}
		
		$point = $this->_getModulePointConfig($module_srl, 'download_file');
		if (!$point)
		{
			return;
		}
		
		// Get current points.
		$cur_point = $logged_member_srl ? PointModel::getPoint($logged_member_srl) : 0;
		
		// If the user (member or guest) does not have enough points, deny access.
		$config = $this->getConfig();
		if ($config->disable_download == 'Y' && $cur_point + $point < 0)
		{
			return new BaseObject(-1, 'msg_cannot_download');
		}
		
		// Points will be adjusted after downloading (triggerDownloadFile).
	}

	/**
	 * @brief The trigger to give or take points for downloading the file
	 */
	public function triggerDownloadFile($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info->member_srl;
		$author_member_srl = abs($obj->member_srl);
		$module_srl = $obj->module_srl;
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}
		
		// Adjust points of the downloader.
		if ($logged_member_srl)
		{
			$point = $this->_getModulePointConfig($module_srl, 'download_file');
			if ($point)
			{
				$cur_point = PointModel::getPoint($logged_member_srl);
				$this->setPoint($logged_member_srl, $cur_point + $point);
			}
		}
		
		// Adjust points of the uploader.
		if ($author_member_srl)
		{
			$point = $this->_getModulePointConfig($module_srl, 'download_file_author');
			if ($point)
			{
				$cur_point = PointModel::getPoint($author_member_srl);
				$this->setPoint($author_member_srl, $cur_point + $point);
			}
		}
	}

	/**
	 * @brief Give points for hits increase
	 * Run it even if there are no points
	 */
	public function triggerUpdateReadedCount($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info->member_srl;
		$author_member_srl = abs($obj->get('member_srl'));
		$module_srl = $obj->get('module_srl');
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}
		
		// Load configuration for reader and author points.
		$reader_point = $this->_getModulePointConfig($module_srl, 'read_document');
		$author_point = $this->_getModulePointConfig($module_srl, 'read_document_author');
		if (!$reader_point && !$author_point)
		{
			return;
		}
		
		// If the reader has already read this document, do not adjust points again.
		if ($logged_member_srl)
		{
			$args = new stdClass;
			$args->member_srl = $logged_member_srl;
			$args->document_srl = $obj->document_srl;
			$output = executeQuery('document.getDocumentReadedLogInfo', $args);
			if ($output->data->count)
			{
				return;
			}
		}
		
		// Give no points if the document is older than a configured limit.
		$regdate = ztime($obj->get('regdate'));
		$config = $this->getConfig();
		if ($config->read_document_limit > 0 && $regdate < RX_TIME - ($config->read_document_limit * 86400))
		{
			$reader_point = 0;
		}
		if ($config->read_document_author_limit > 0 && $regdate < RX_TIME - ($config->read_document_author_limit * 86400))
		{
			$author_point = 0;
		}
		
		// Give no points if the document is a notice and an exception has been configured.
		if ($obj->get('is_notice') === 'Y')
		{
			if ($config->read_document_except_notice)
			{
				$reader_point = 0;
			}
			if ($config->read_document_author_except_notice)
			{
				$author_point = 0;
			}
		}
		
		// Adjust points of the reader.
		if ($reader_point)
		{
			// Get current points.
			$cur_point = $logged_member_srl ? PointModel::getPoint($logged_member_srl) : 0;
			
			// If the reader does not have enough points, deny access.
			if ($cur_point + $reader_point < 0 && $config->disable_read_document == 'Y')
			{
				if (!$logged_member_srl && $config->disable_read_document_except_robots == 'Y' && isCrawler())
				{
					$_SESSION['banned_document'][$obj->document_srl] = false;
				}
				else
				{
					$message = sprintf(lang('msg_disallow_by_point'), abs($reader_point), $cur_point);
					$obj->add('content', $message);
					$GLOBALS['XE_EXTRA_VARS'][$obj->document_srl] = array();
					$_SESSION['banned_document'][$obj->document_srl] = true;
					return new BaseObject(-1, $message);
				}
			}
			else
			{
				$_SESSION['banned_document'][$obj->document_srl] = false;
			}
			
			// Record the fact that this member has already read this document.
			if ($logged_member_srl)
			{
				$args = new stdClass();
				$args->member_srl = $logged_member_srl;
				$args->document_srl = $obj->document_srl;
				$output = executeQuery('document.insertDocumentReadedLog', $args);
				$this->setPoint($logged_member_srl, $cur_point + $reader_point);
			}
		}
		
		// Adjust points of the person who wrote the document.
		if ($author_point && $author_member_srl)
		{
			$cur_point = PointModel::getPoint($author_member_srl);
			$this->setPoint($author_member_srl, $cur_point + $author_point);
		}
	}

	/**
	 * @brief Points for voting up or down
	 */
	public function triggerUpdateVotedCount($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info->member_srl;
		$target_member_srl = abs($obj->member_srl);
		if ($logged_member_srl && $logged_member_srl == $target_member_srl)
		{
			return;
		}
		
		// Document or comment?
		$is_comment = isset($obj->comment_srl) && $obj->comment_srl;
		
		// Give no points if the document or comment is older than a configured limit.
		$config = $this->getConfig();
		if ($is_comment)
		{
			$regdate = ztime(CommentModel::getComment($obj->comment_srl)->get('regdate'));
			$logged_config_key = ($obj->point > 0) ? 'voter_comment_limit' : 'blamer_comment_limit';
			$target_config_key = ($obj->point > 0) ? 'voted_comment_limit' : 'blamed_comment_limit';
		}
		else
		{
			$regdate = ztime(DocumentModel::getDocument($obj->document_srl)->get('regdate'));
			$logged_config_key = ($obj->point > 0) ? 'voter_limit' : 'blamer_limit';
			$target_config_key = ($obj->point > 0) ? 'voted_limit' : 'blamed_limit';
		}
		$logged_enabled = !($config->$logged_config_key > 0 && $regdate < RX_TIME - ($config->$logged_config_key * 86400));
		$target_enabled = !($config->$target_config_key > 0 && $regdate < RX_TIME - ($config->$target_config_key * 86400));
		
		// Adjust points of the voter.
		if ($logged_member_srl && $logged_enabled)
		{
			$config_key = ($obj->point > 0) ? ($is_comment ? 'voter_comment' : 'voter') : ($is_comment ? 'blamer_comment' : 'blamer');
			$point = $this->_getModulePointConfig($obj->module_srl, $config_key);
			if ($point)
			{
				if (isset($obj->cancel) && $obj->cancel)
				{
					$point = -1 * $point;
				}
				$cur_point = PointModel::getPoint($logged_member_srl);
				$this->setPoint($logged_member_srl, $cur_point + $point);
			}
		}
		
		// Adjust points of the person who wrote the document or comment.
		if ($target_member_srl && $target_enabled)
		{
			$config_key = ($obj->point > 0) ? ($is_comment ? 'voted_comment' : 'voted') : ($is_comment ? 'blamed_comment' : 'blamed');
			$point = $this->_getModulePointConfig($obj->module_srl, $config_key);
			if ($point)
			{
				if (isset($obj->cancel) && $obj->cancel)
				{
					$point = -1 * $point;
				}
				$cur_point = PointModel::getPoint($target_member_srl);
				$this->setPoint($target_member_srl, $cur_point + $point);
			}
		}
	}

	/**
	 * @brief Copy point settings when copying module
	 */
	public function triggerCopyModule($obj)
	{
		$pointConfig = ModuleModel::getModulePartConfig('point', $obj->originModuleSrl);
		if (is_object($pointConfig))
		{
			$pointConfig = get_object_vars($pointConfig);
		}

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
		$config = ModuleModel::getModuleConfig('point');

		// Get the default configuration information
		$current_point = PointModel::getPoint($member_srl, false, $exists);
		$current_level = PointModel::getLevel($current_point, $config->level_step);

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
		if ($exists)
		{
			$output = executeQuery("point.updatePoint", $args);
		}
		else
		{
			$output = executeQuery("point.insertPoint", $args);
			// 많은 동접시 포인트를 넣는 과정에서 미리 들어간 포인트가 있을 수 있는 문제가 있어 이를 확실하게 처리하도록 수정요청을 한 번 더 실행.
			if(!$output->toBool())
			{
				$output = executeQuery("point.updatePoint", $args);
			}
		}

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Get a new level
		$level = PointModel::getLevel($point, $config->level_step);

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
				$default_group = MemberModel::getDefaultGroup();
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
					executeQuery('point.deleteMemberGroup', $del_group_args);
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
		$cache_key = sprintf('member:point:%d', $member_srl);
		$cache_path = sprintf(RX_BASEDIR . 'files/member_extra_info/point/%s', getNumberingPath($member_srl));
		$cache_filename = sprintf('%s/%d.cache.txt', $cache_path, $member_srl);
		if (Rhymix\Framework\Cache::getDriverName() !== 'dummy')
		{
			Rhymix\Framework\Cache::set($cache_key, $point);
			Rhymix\Framework\Storage::delete($cache_filename);
		}
		else
		{
			Rhymix\Framework\Storage::write($cache_filename, $point);
		}

		MemberController::clearMemberCache($member_srl);
		unset(parent::$_member_point_cache[$member_srl]);

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
		
		if ($module_srl)
		{
			if (!isset(self::$_module_config_cache[$module_srl]))
			{
				self::$_module_config_cache[$module_srl] = ModuleModel::getModulePartConfig('point', $module_srl);
				if (is_object(self::$_module_config_cache[$module_srl]))
				{
					self::$_module_config_cache[$module_srl] = get_object_vars(self::$_module_config_cache[$module_srl]);
				}
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
