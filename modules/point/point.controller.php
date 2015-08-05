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
	function init()
	{
	}

	/**
	 * @brief Membership point application trigger
	 */
	function triggerInsertMember(&$obj)
	{
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		// Get the member_srl of the newly registered member
		$member_srl = $obj->member_srl;
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);

		$point = $config->signup_point;
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point, 'signup');

		return new Object();
	}

	/**
	 * @brief A trigger to add points to the member for login
	 */
	function triggerAfterLogin(&$obj)
	{
		$member_srl = $obj->member_srl;
		if(!$member_srl) return new Object();
		// If the last login is not today, give the points
		if(substr($obj->last_login,0,8)==date("Ymd")) return new Object();
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);

		$point = $config->login_point;
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief A trigger to add points to the member for creating a post
	 */
	function triggerInsertDocument(&$obj)
	{
		$oDocumentModel = getModel('document');
		if($obj->status != $oDocumentModel->getConfigStatus('temp'))
		{
			$module_srl = $obj->module_srl;
			$member_srl = $obj->member_srl;
			if(!$module_srl || !$member_srl) return new Object();
			// The fix to disable giving points for saving the document temporarily
			if($module_srl == $member_srl) return new Object();
			// Get the point module information
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('point');
			$module_config = $oModuleModel->getModulePartConfig('point',$module_srl);
			// Get the points of the member
			$oPointModel = getModel('point');
			$cur_point = $oPointModel->getPoint($member_srl, true);

			$point = $module_config['insert_document'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->insert_document;
			$cur_point += $point;
			// Add points for attaching a file
			$point = $module_config['upload_file'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->upload_file;
			if($obj->uploaded_count) $cur_point += $point * $obj->uploaded_count;
			// Increase the point
			$this->setPoint($member_srl,$cur_point);
		}

		return new Object();
	}

	/**
	 * @brief The trigger to give points for normal saving the temporarily saved document
	 * Temporary storage at the point in 1.2.3 changed to avoid payment
	 */
	function triggerUpdateDocument(&$obj)
	{
		$oDocumentModel = getModel('document');
		$document_srl = $obj->document_srl;
		$oDocument = $oDocumentModel->getDocument($document_srl);

		// if status is TEMP or PUBLIC... give not point, only status is empty
		if($oDocument->get('status') == $oDocumentModel->getConfigStatus('temp') && $obj->status != $oDocumentModel->getConfigStatus('temp'))
		{
			$oModuleModel = getModel('module');

			// Get the point module information
			$config = $oModuleModel->getModuleConfig('point');
			$module_config = $oModuleModel->getModulePartConfig('point',$obj->module_srl);
			// Get the points of the member
			$oPointModel = getModel('point');
			$cur_point = $oPointModel->getPoint($oDocument->get('member_srl'), true);

			$point = $module_config['insert_document'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->insert_document;
			$cur_point += $point;
			// Add points for attaching a file
			$point = $module_config['upload_file'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->upload_file;
			if($obj->uploaded_count) $cur_point += $point * $obj->uploaded_count;
			// Increase the point
			$this->setPoint($oDocument->get('member_srl'), $cur_point);
		}

		return new Object();
	}

	/**
	 * @brief The trigger which deducts the points related to post comments before deleting the post itself
	 */
	function triggerBeforeDeleteDocument(&$obj)
	{
		$document_srl = $obj->document_srl;
		$member_srl = $obj->member_srl;

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists()) return new Object();
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point',$oDocument->get('module_srl'));
		// The process related to clearing the post comments
		$comment_point = $module_config['insert_comment'];
		if(strlen($comment_point) == 0 && !is_int($comment_point)) $comment_point = $config->insert_comment;
		// If there are comment points, attempt to deduct
		if($comment_point>0) return new Object();
		// Get all the comments related to this post
		$cp_args = new stdClass();
		$cp_args->document_srl = $document_srl;
		$output = executeQueryArray('point.getCommentUsers', $cp_args);
		// Return if there is no object
		if(!$output->data) return new Object();
		// Organize the member number
		$member_srls = array();
		$cnt = count($output->data);
		for($i=0;$i<$cnt;$i++)
		{
			if($output->data[$i]->member_srl<1) continue;
			$member_srls[abs($output->data[$i]->member_srl)] = $output->data[$i]->count;
		}
		// Remove the member number who has written the original post
		if($member_srl) unset($member_srls[abs($member_srl)]);
		if(!count($member_srls)) return new Object();
		// Remove all the points for each member
		$oPointModel = getModel('point');
		// Get the points
		$point = $module_config['download_file'];
		foreach($member_srls as $member_srl => $cnt)
		{
			$cur_point = $oPointModel->getPoint($member_srl, true);
			$cur_point -= $cnt * $comment_point;
			$this->setPoint($member_srl,$cur_point);
		}

		return new Object();
	}

	/**
	 * @brief A trigger to give points for deleting the post
	 */
	function triggerDeleteDocument(&$obj)
	{
		$oDocumentModel = getModel('document');
		
		if($obj->status != $oDocumentModel->getConfigStatus('temp'))
		{
			$module_srl = $obj->module_srl;
			$member_srl = $obj->member_srl;
			// The process related to clearing the post object
			if(!$module_srl || !$member_srl) return new Object();
			// Run only when logged in
			$logged_info = Context::get('logged_info');
			if(!$logged_info->member_srl) return new Object();
			// Get the points of the member
			$oPointModel = getModel('point');
			$cur_point = $oPointModel->getPoint($member_srl, true);
			// Get the point module information
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('point');
			$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
	
			$point = $module_config['insert_document'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->insert_document;
			// if the point is set to decrease when writing a document, make sure it does not increase the points when deleting an article
			if($point < 0) return new Object();
			$cur_point -= $point;
			// Add points related to deleting an attachment
			$point = $module_config['upload_file'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->upload_file;
			if($obj->uploaded_count) $cur_point -= $point * $obj->uploaded_count;
			// Increase the point
			$this->setPoint($member_srl,$cur_point);
		}

		return new Object();
	}

	/**
	 * @brief A trigger which gives points for entering a comment
	 */
	function triggerInsertComment(&$obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = $obj->member_srl;
		if(!$module_srl || !$member_srl) return new Object();
		// Do not increase the points if the member is the author of the post
		$document_srl = $obj->document_srl;
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists() || abs($oDocument->get('member_srl'))==abs($member_srl)) return new Object();
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);

		$point = $module_config['insert_comment'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->insert_comment;
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief A trigger which gives points for deleting a comment
	 */
	function triggerDeleteComment(&$obj)
	{
		$oModuleModel = getModel('module');
		$oPointModel = getModel('point');
		$oDocumentModel = getModel('document');

		$module_srl = $obj->module_srl;
		$member_srl = abs($obj->member_srl);
		$document_srl = $obj->document_srl;
		if(!$module_srl || !$member_srl) return new Object();
		// Get the original article (if the original article is missing or if the member is its author, do not apply the points)
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists()) return new Object();
		if($oDocument->get('member_srl')==$member_srl) return new Object();
		// Get the point module information
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
		// Get the points of the member
		$cur_point = $oPointModel->getPoint($member_srl, true);

		$point = $module_config['insert_comment'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->insert_comment;
		// if the point is set to decrease when writing a comment, make sure it does not increase the points when deleting a comment
		if($point < 0) return new Object();
		// Increase the point
		$cur_point -= $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief Add the file registration trigger
	 * To prevent taking points for invalid file registration this method wlil return a null object
	 */
	function triggerInsertFile(&$obj)
	{
		return new Object();
	}

	/**
	 * @brief A trigger to give points for deleting a file
	 * Remove points only in case an invalid file is being deleted
	 */
	function triggerDeleteFile(&$obj)
	{
		if($obj->isvalid != 'Y') return new Object();

		$module_srl = $obj->module_srl;
		$member_srl = $obj->member_srl;
		if(!$module_srl || !$member_srl) return new Object();
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);

		$point = $module_config['upload_file'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->upload_file;
		// Increase the point
		$cur_point -= $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief The trigger called before a file is downloaded
	 */
	function triggerBeforeDownloadFile(&$obj)
	{
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$module_srl = $obj->module_srl;
		if(!$module_srl) return new Object();
		// Pass if it is your file
		if(abs($obj->member_srl) == abs($member_srl)) return new Object();

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
		// If it is set not to allow downloading for non-logged in users, do not permit
		if(!Context::get('is_logged'))
		{
			if($config->disable_download == 'Y' && strlen($module_config['download_file']) == 0 && !is_int($module_config['download_file'])) return new Object(-1,'msg_not_permitted_download');
			else return new Object();
		}
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);
		// Get the points
		$point = $module_config['download_file'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->download_file;
		// If points are less than 0, and if downloading a file is not allowed in this case, give an errors
		if($cur_point + $point < 0 && $config->disable_download == 'Y') return new Object(-1,'msg_cannot_download');

		return new Object();
	}

	/**
	 * @brief The trigger to give points for downloading the file
	 */
	function triggerDownloadFile(&$obj)
	{
		// Run only when logged in
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl) return new Object();
		$module_srl = $obj->module_srl;
		$member_srl = $logged_info->member_srl;
		if(!$module_srl) return new Object();
		// Pass if it is your file
		if(abs($obj->member_srl) == abs($member_srl)) return new Object();
		// Get the point module information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);
		// Get the points of the member
		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);
		// Get the points
		$point = $module_config['download_file'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->download_file;
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief Give points for hits increase
	 * Run it even if there are no points
	 */
	function triggerUpdateReadedCount(&$obj)
	{
		$oModuleModel = getModel('module');
		$oPointModel = getModel('point');
		// Get visitor information
		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		// Get the original author number
		$target_member_srl = abs($obj->get('member_srl'));
		// Pass without increasing the hits if the viewer is the same as the author
		if($target_member_srl == $member_srl) return new Object();
		// Get the point information for each module
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $obj->get('module_srl'));
		// Get hits points
		$point = $module_config['read_document'];
		if(strlen($point) == 0 && !is_int($point)) $point = $config->read_document;
		// Pass if there are no requested points
		if(!$point) return new Object();
		// In case of a registered member, if it is read but cannot just pass, then get the current points
		if($member_srl)
		{
			$args->member_srl = $member_srl;
			$args->document_srl = $obj->document_srl;
			$output = executeQuery('document.getDocumentReadedLogInfo', $args);
			if($output->data->count) return new Object();
			$cur_point = $oPointModel->getPoint($member_srl, true);
		}
		else
		{
			$cur_point = 0;
		}
		// Get the defaul configurations of the Point Module
		$config = $oModuleModel->getModuleConfig('point');
		// When the requested points are negative, compared it with the current point
		$_SESSION['banned_document'][$obj->document_srl] = false;
		if($config->disable_read_document == 'Y' && $point < 0 && abs($point)>$cur_point)
		{
			$message = sprintf(Context::getLang('msg_disallow_by_point'), abs($point), $cur_point);
			$obj->add('content', $message);
			$_SESSION['banned_document'][$obj->document_srl] = true;
			return new Object(-1, $message);
		}
		// If not logged in, pass
		if(!$logged_info->member_srl) return new Object();
		// Pass, if there are no requested points
		if(!$point) return new Object();
		// If the read record is missing, leave it
		$output = executeQuery('document.insertDocumentReadedLog', $args);
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief Points for voting up or down
	 */
	function triggerUpdateVotedCount(&$obj)
	{
		$module_srl = $obj->module_srl;
		$member_srl = $obj->member_srl;
		if(!$module_srl || !$member_srl) return new Object();

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		$module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

		$oPointModel = getModel('point');
		$cur_point = $oPointModel->getPoint($member_srl, true);

		if( $obj->point > 0 )
		{
			$point = $module_config['voted'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->voted;
		}
		else
		{
			$point = $module_config['blamed'];
			if(strlen($point) == 0 && !is_int($point)) $point = $config->blamed;
		}

		if(!$point) return new Object();
		// Increase the point
		$cur_point += $point;
		$this->setPoint($member_srl,$cur_point);

		return new Object();
	}

	/**
	 * @brief Set points
	 */
	function setPoint($member_srl, $point, $mode = null)
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
		if($level != $current_level)
		{
			// Check if the level, for which the current points are prepared, is calculate and set the correct group
			$point_group = $config->point_group;
			// If the point group exists
			if($point_group && is_array($point_group) && count($point_group) )
			{
				// Get the default group
				$default_group = $oMemberModel->getDefaultGroup();
				// Get the removed group and the newly granted group
				$del_group_list = array();
				$new_group_list = array();

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
		$trigger_output = ModuleHandler::triggerCall('point.setPoint', 'after', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			$oDB->rollback();
			return $trigger_output;
		}

		$oDB->commit();

		// Cache Settings
		$cache_path = sprintf('./files/member_extra_info/point/%s/', getNumberingPath($member_srl));
		FileHandler::makedir($cache_path);

		$cache_filename = sprintf('%s%d.cache.txt', $cache_path, $member_srl);
		FileHandler::writeFile($cache_filename, $point);

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($new_group_list && $del_group_list && $oCacheHandler->isSupport())
		{
			$object_key = 'member_groups:' . getNumberingPath($member_srl) . $member_srl . '_0';
			$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
			$oCacheHandler->delete($cache_key);
		}

		$oCacheHandler = CacheHandler::getInstance('object');
		if($new_group_list && $del_group_list && $oCacheHandler->isSupport())
		{
			$object_key = 'member_info:' . getNumberingPath($member_srl) . $member_srl;
			$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
			$oCacheHandler->delete($cache_key);
		}

		return $output;
	}

	function triggerCopyModule(&$obj)
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
}
/* End of file point.controller.php */
/* Location: ./modules/point/point.controller.php */
