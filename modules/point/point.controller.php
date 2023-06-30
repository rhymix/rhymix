<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pointController
 * @author NAVER (developers@xpressengine.com)
 * @brief Controller class of point modules
 */
class PointController extends Point
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
		$diff = intval($config->signup_point);
		if ($diff != 0)
		{
			// New member is not supposed to have points,
			// but we check just in case another module has intervened.
			$cur_point = PointModel::getPoint($member_srl);
			$this->setPoint($member_srl, $cur_point + $diff, 'signup');
		}
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
		$diff = intval($config->login_point);
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'plus');
		}
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
		$diff += PointModel::getModulePointConfig($module_srl, 'insert_document');

		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = PointModel::getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}

		// Increase the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'plus');
		}
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
			$diff += PointModel::getModulePointConfig($module_srl, 'insert_document');
		}

		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = PointModel::getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}

		// Increase the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'plus');
		}

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

		// Subtract points for the document.
		$diff = PointModel::getModulePointConfig($module_srl, 'insert_document');

		// Decrease the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'minus');
		}
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
			$diff += PointModel::getModulePointConfig($module_srl, 'insert_comment');
		}

		// Add points for attached files.
		if ($obj->updated_file_count > 0)
		{
			$upload_point = PointModel::getModulePointConfig($module_srl, 'upload_file');
			$diff += $upload_point * $obj->updated_file_count;
		}

		// Increase the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'plus');
		}
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

		// Calculate points based on the module_srl of the document to which this comment belongs
		$module_srl = $oDocument->get('module_srl');
		$diff = PointModel::getModulePointConfig($module_srl, 'insert_comment');

		// Decrease the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'minus');
		}
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

		// Subtract points for the file.
		$diff = PointModel::getModulePointConfig($module_srl, 'upload_file');

		// Update the point.
		if ($diff != 0)
		{
			$this->setPoint($member_srl, $diff, 'minus');
		}
	}

	/**
	 * @brief The trigger called before a file is downloaded
	 */
	public function triggerBeforeDownloadFile($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info ? $logged_info->member_srl : 0;
		$author_member_srl = abs($obj->member_srl);
		$module_srl = $obj->module_srl;
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}

		$point = PointModel::getModulePointConfig($module_srl, 'download_file');
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
		$logged_member_srl = $logged_info ? $logged_info->member_srl : 0;
		$author_member_srl = abs($obj->member_srl);
		$module_srl = $obj->module_srl;
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}

		// Adjust points of the downloader.
		if ($logged_member_srl)
		{
			$point = PointModel::getModulePointConfig($module_srl, 'download_file');
			if ($point)
			{
				$this->setPoint($logged_member_srl, $point, 'plus');
			}
		}

		// Adjust points of the uploader.
		if ($author_member_srl)
		{
			$point = PointModel::getModulePointConfig($module_srl, 'download_file_author');
			if ($point)
			{
				$this->setPoint($author_member_srl, $point, 'plus');
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
		$logged_member_srl = $logged_info ? $logged_info->member_srl : 0;
		$author_member_srl = abs($obj->get('member_srl'));
		$module_srl = $obj->get('module_srl');
		if ($logged_member_srl && $logged_member_srl == $author_member_srl)
		{
			return;
		}

		// Load configuration for reader and author points.
		$reader_point = PointModel::getModulePointConfig($module_srl, 'read_document');
		$author_point = PointModel::getModulePointConfig($module_srl, 'read_document_author');
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
					// pass
				}
				else
				{
					$message = sprintf(lang('msg_disallow_by_point'), abs($reader_point), $cur_point);
					$obj->add('content', $message);
					$GLOBALS['XE_EXTRA_VARS'][$obj->document_srl] = array();
					return new BaseObject(-1, $message);
				}
			}

			// Record the fact that this member has already read this document.
			if ($logged_member_srl)
			{
				$args = new stdClass();
				$args->member_srl = $logged_member_srl;
				$args->document_srl = $obj->document_srl;
				$output = executeQuery('document.insertDocumentReadedLog', $args);
				$this->setPoint($logged_member_srl, $reader_point, 'plus');
			}
		}

		// Adjust points of the person who wrote the document.
		if ($author_point && $author_member_srl)
		{
			$this->setPoint($author_member_srl, $author_point, 'plus');
		}
	}

	/**
	 * @brief Points for voting up or down
	 */
	public function triggerUpdateVotedCount($obj)
	{
		$logged_info = Context::get('logged_info');
		$logged_member_srl = $logged_info ? $logged_info->member_srl : 0;
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
			$point = PointModel::getModulePointConfig($obj->module_srl, $config_key);
			if ($point)
			{
				if (isset($obj->cancel) && $obj->cancel)
				{
					$point = -1 * $point;
				}
				$this->setPoint($logged_member_srl, $point, 'plus');
			}
		}

		// Adjust points of the person who wrote the document or comment.
		if ($target_member_srl && $target_enabled)
		{
			$config_key = ($obj->point > 0) ? ($is_comment ? 'voted_comment' : 'voted') : ($is_comment ? 'blamed_comment' : 'blamed');
			$point = PointModel::getModulePointConfig($obj->module_srl, $config_key);
			if ($point)
			{
				if (isset($obj->cancel) && $obj->cancel)
				{
					$point = -1 * $point;
				}
				$this->setPoint($target_member_srl, $point, 'plus');
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
	public static function setPoint($member_srl, $point, $mode = 'update')
	{
		// Normalize parameters
		$member_srl = abs($member_srl);
		$point = intval($point);
		if(!in_array($mode, ['add', 'plus', 'minus', 'update', 'signup']))
		{
			$mode = 'update';
		}

		// Get configuration information
		$config = ModuleModel::getModuleConfig('point');

		// Get current points for the member
		$current_point = PointModel::getPoint($member_srl, false, $exists);
		$current_level = PointModel::getLevel($current_point, $config->level_step);
		$new_point = $current_point;
		$new_level = $current_level;

		// Initialize query arguments
		$args = new stdClass();
		$args->member_srl = $member_srl;

		// Set or update depending on mode
		switch($mode)
		{
			case 'add':
			case 'plus':
				$new_point += $point;
				$args->diff = $point;
				if ($point < 0)
				{
					$mode = 'minus';
					$point = $point * -1;
				}
				break;
			case 'minus':
				$new_point -= $point;
				$args->diff = $point * -1;
				if ($point < 0)
				{
					$mode = 'plus';
					$point = $point * -1;
				}
				break;
			case 'update':
			case 'signup':
				$new_point = $point;
				$args->point = $point;
				break;
		}

		// Prevent negative points. This may not be 100% reliable if using diff.
		if (isset($args->diff) && $current_point + $args->diff < 0)
		{
			$args->diff = $current_point * -1;
		}
		if (isset($args->point) && $args->point < 0)
		{
			$new_point = 0;
			$args->point = 0;
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->member_srl = $args->member_srl;
		$trigger_obj->mode = $mode === 'plus' ? 'add' : $mode;
		$trigger_obj->current_point = $current_point;
		$trigger_obj->current_level = $current_level;
		$trigger_obj->set_point = $new_point;
		$trigger_obj->new_point = $new_point;
		$trigger_output = ModuleHandler::triggerCall('point.setPoint', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// If there are points, update, if no, insert
		if ($exists)
		{
			$output = executeQuery("point.updatePoint", $args);
		}
		else
		{
			// This will fail if someone else has inserted points for this member.
			$args->point = $new_point;
			$output = executeQuery("point.insertPoint", $args);

			// Handle race condition by re-trying update if insert fails.
			if(!$output->toBool())
			{
				if(isset($args->diff)) unset($args->point);
				$output = executeQuery("point.updatePoint", $args);
			}
		}

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Get a new level
		$new_level = PointModel::getLevel($new_point, $config->level_step);

		// If existing level and a new one are different attempt to set a point group
		$new_group_list = array();
		$del_group_list = array();
		if ($config->group_ratchet === 'Y')
		{
			$change_group = ($new_level > $current_level);
		}
		else
		{
			$change_group = ($new_level != $current_level);
		}

		if ($change_group)
		{
			// Check if the level, for which the current points are prepared, is calculate and set the correct group
			$point_group = $config->point_group;
			// If the point group exists
			if($point_group && is_array($point_group) && count($point_group))
			{
				// Get the default group
				$default_group = MemberModel::getDefaultGroup();
				asort($point_group);

				// Reset group after initialization
				if($config->group_reset != 'N')
				{
					// If the new level is in the right group
					if(in_array($new_level, $point_group))
					{
						// Delete all groups except the one which the current level belongs to
						foreach($point_group as $group_srl => $target_level)
						{
							$del_group_list[] = $group_srl;
							if($target_level == $new_level) $new_group_list[] = $group_srl;
						}
					}
					// Otherwise, in case the level is reduced, add the recent group
					else
					{
						$i = $new_level;
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
						if($target_level > $new_level) $del_group_list[] = $group_srl;
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
						if($target_level <= $new_level) $new_group_list[] = $group_srl;
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
		$trigger_obj->new_level = $new_level;
		$trigger_obj->new_group_list = $new_group_list;
		$trigger_obj->del_group_list = $del_group_list;
		ModuleHandler::triggerCall('point.setPoint', 'after', $trigger_obj);

		$oDB->commit();

		// Refresh cache
		PointModel::getPoint($member_srl, true);
		MemberController::clearMemberCache($member_srl);

		return $output;
	}
}
/* End of file point.controller.php */
/* Location: ./modules/point/point.controller.php */
