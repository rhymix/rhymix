<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * documentAdminController class
 * Document the module's admin controller class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class documentAdminController extends document
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Remove the selected docs from admin page
	 * @return void
	 */
	function procDocumentAdminDeleteChecked()
	{
		// error appears if no doc is selected
		$cart = Context::get('cart');
		if(!$cart) return $this->stop('msg_cart_is_null');
		$document_srl_list= explode('|@|', $cart);
		$document_count = count($document_srl_list);
		if(!$document_count) return $this->stop('msg_cart_is_null');
		// Delete a doc
		$oDocumentController = getController('document');
		for($i=0;$i<$document_count;$i++)
		{
			$document_srl = trim($document_srl_list[$i]);
			if(!$document_srl) continue;

			$oDocumentController->deleteDocument($document_srl, true);
		}

		$this->setMessage(sprintf(Context::getLang('msg_checked_document_is_deleted'), $document_count) );
	}

	/**
	 * Change the module to move a specific article
	 * @param array $document_srl_list
	 * @param int $module_srl
	 * @param int $category_srl
	 * @return Object
	 */
	function moveDocumentModule($document_srl_list, $module_srl, $category_srl)
	{
		if(!count($document_srl_list)) return;

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$triggerObj = new stdClass();
		$triggerObj->document_srls = implode(',',$document_srl_list);
		$triggerObj->module_srl = $module_srl;
		$triggerObj->category_srl = $category_srl;
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('document.moveDocumentModule', 'before', $triggerObj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		for($i=count($document_srl_list)-1;$i>=0;$i--)
		{
			$document_srl = $document_srl_list[$i];
			$oDocument = $oDocumentModel->getDocument($document_srl);
			if(!$oDocument->isExists()) continue;

			$source_category_srl = $oDocument->get('category_srl');

			unset($obj);
			$obj = $oDocument->getObjectVars();

			// ISSUE https://github.com/xpressengine/xe-core/issues/32
			$args_doc_origin->document_srl = $document_srl;
			$output_ori = executeQuery('document.getDocument', $args_doc_origin, array('content'));              
			$obj->content = $output_ori->data->content;

			// Move the attached file if the target module is different
			if($module_srl != $obj->module_srl && $oDocument->hasUploadedFiles())
			{
				$oFileController = getController('file');

				$files = $oDocument->getUploadedFiles();
				$delete_file_srls = array();
				if(is_array($files))
				{
					foreach($files as $val)
					{
						$file_info = array();
						$file_info['tmp_name'] = $val->uploaded_filename;
						$file_info['name'] = $val->source_filename;
						$inserted_file = $oFileController->insertFile($file_info, $module_srl, $obj->document_srl, $val->download_count, true);
						if($inserted_file && $inserted_file->toBool())
						{
							// for image/video files
							if($val->direct_download == 'Y')
							{
								$source_filename = substr($val->uploaded_filename,2);
								$target_filename = substr($inserted_file->get('uploaded_filename'),2);
								$obj->content = str_replace($source_filename, $target_filename, $obj->content);
								// For binary files
							}
							else
							{
								$obj->content = str_replace('file_srl='.$val->file_srl, 'file_srl='.$inserted_file->get('file_srl'), $obj->content);
								$obj->content = str_replace('sid='.$val->sid, 'sid='.$inserted_file->get('sid'), $obj->content);
							}
						}
						$delete_file_srls[] = $val->file_srl;
					}
					// Delete an existing file
					$oFileController->deleteFile($delete_file_srls);
				}
				// Set the all files to be valid
				$oFileController->setFilesValid($obj->document_srl);
			}

			if($module_srl != $obj->module_srl)
			{
				$oDocumentController->deleteDocumentAliasByDocument($obj->document_srl);
			}
			// Move a module of the article
			$obj->module_srl = $module_srl;
			$obj->category_srl = $category_srl;
			$output = executeQuery('document.updateDocumentModule', $obj);
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			//Move a module of the extra vars
			$output = executeQuery('document.moveDocumentExtraVars', $obj);
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}
			// Set 0 if a new category doesn't exist after catergory change
			if($source_category_srl != $category_srl)
			{
				if($source_category_srl) $oDocumentController->updateCategoryCount($oDocument->get('module_srl'), $source_category_srl);
				if($category_srl) $oDocumentController->updateCategoryCount($module_srl, $category_srl);
			}
		}

		$args = new stdClass();
		$args->document_srls = implode(',',$document_srl_list);
		$args->module_srl = $module_srl;
		// move the comment
		$output = executeQuery('comment.updateCommentModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = executeQuery('comment.updateCommentListModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// move the trackback
		if(getClass('trackback'))
		{
			$output = executeQuery('trackback.updateTrackbackModule', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// Tags
		$output = executeQuery('tag.updateTagModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('document.moveDocumentModule', 'after', $triggerObj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();
		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object');
		if($oCacheHandler->isSupport())
		{
			foreach($document_srl_list as $document_srl)
			{
				$cache_key_item = 'document_item:'. getNumberingPath($document_srl) . $document_srl;
				$oCacheHandler->delete($cache_key_item);
			}
		}
		return new Object();
	}

	/**
	 * Copy the post
	 * @param array $document_srl_list
	 * @param int $module_srl
	 * @param int $category_srl
	 * @return object
	 */
	function copyDocumentModule($document_srl_list, $module_srl, $category_srl)
	{
		if(count($document_srl_list) < 1) return;

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');

		$oFileModel = getModel('file');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$triggerObj = new stdClass();
		$triggerObj->document_srls = implode(',',$document_srl_list);
		$triggerObj->module_srl = $module_srl;
		$triggerObj->category_srl = $category_srl;
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('document.copyDocumentModule', 'before', $triggerObj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$extraVarsList = $oDocumentModel->getDocumentExtraVarsFromDB($document_srl_list);
		$extraVarsListByDocumentSrl = array();
		if(is_array($extraVarsList->data))
		{
			foreach($extraVarsList->data as $value)
			{
				if(!isset($extraVarsListByDocumentSrl[$value->document_srl]))
				{
					$extraVarsListByDocumentSrl[$value->document_srl] = array();
				}

				$extraVarsListByDocumentSrl[$value->document_srl][] = $value;
			}
		}

		for($i=count($document_srl_list)-1;$i>=0;$i--)
		{
			$document_srl = $document_srl_list[$i];
			$oDocument = $oDocumentModel->getDocument($document_srl);
			if(!$oDocument->isExists()) continue;

			$obj = $oDocument->getObjectVars();

			$extraVars = $extraVarsListByDocumentSrl[$document_srl];
			if($module_srl == $obj->module_srl)
			{
				if(is_array($extraVars))
				{
					foreach($extraVars as $extraItem)
					{
						if($extraItem->var_idx >= 0) $obj->{'extra_vars'.$extraItem->var_idx} = $extraItem->value;
					}
				}
			}
			$obj->module_srl = $module_srl;
			$obj->document_srl = getNextSequence();
			$obj->category_srl = $category_srl;
			$obj->password_is_hashed = true;
			$obj->comment_count = 0;
			$obj->trackback_count = 0;

			// Pre-register the attachment
			if($oDocument->hasUploadedFiles())
			{
				$files = $oDocument->getUploadedFiles();
				foreach($files as $val)
				{
					$file_info = array();
					$file_info['tmp_name'] = $val->uploaded_filename;
					$file_info['name'] = $val->source_filename;
					$oFileController = getController('file');
					$inserted_file = $oFileController->insertFile($file_info, $module_srl, $obj->document_srl, 0, true);
					// if image/video files
					if($val->direct_download == 'Y')
					{
						$source_filename = substr($val->uploaded_filename,2);
						$target_filename = substr($inserted_file->get('uploaded_filename'),2);
						$obj->content = str_replace($source_filename, $target_filename, $obj->content);
						// If binary file
					}
					else
					{
						$obj->content = str_replace('file_srl='.$val->file_srl, 'file_srl='.$inserted_file->get('file_srl'), $obj->content);
						$obj->content = str_replace('sid='.$val->sid, 'sid='.$inserted_file->get('sid'), $obj->content);
					}
				}
			}

			// Write a post
			$output = $oDocumentController->insertDocument($obj, true, true);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// copy multi language contents
			if(is_array($extraVars))
			{
				foreach($extraVars as $value)
				{
					if($value->idx >= 0 && $value->lang_code == Context::getLangType())
					{
						continue;
					}

					if( $value->var_idx < 0 || ($module_srl == $value->module_srl && $value->var_idx >= 0) )
					{
						$oDocumentController->insertDocumentExtraVar($value->module_srl, $obj->document_srl, $value->var_idx, $value->value, $value->eid, $value->lang_code);
					}
				}
			}

			// Move the comments
			if($oDocument->getCommentCount())
			{
				$oCommentModel = getModel('comment');
				$comment_output = $oCommentModel->getCommentList($document_srl, 0, true, 99999999);
				$comments = $comment_output->data;
				if(count($comments) > 0)
				{
					$oCommentController = getController('comment');
					$success_count = 0;
					$p_comment_srl = array();
					foreach($comments as $comment_obj)
					{
						$comment_srl = getNextSequence();
						$p_comment_srl[$comment_obj->comment_srl] = $comment_srl;

						// Pre-register the attachment
						if($comment_obj->uploaded_count)
						{
							$files = $oFileModel->getFiles($comment_obj->comment_srl, true);
							foreach($files as $val)
							{
								$file_info = array();
								$file_info['tmp_name'] = $val->uploaded_filename;
								$file_info['name'] = $val->source_filename;
								$oFileController = getController('file');
								$inserted_file = $oFileController->insertFile($file_info, $module_srl, $comment_srl, 0, true);
								// if image/video files
								if($val->direct_download == 'Y')
								{
									$source_filename = substr($val->uploaded_filename,2);
									$target_filename = substr($inserted_file->get('uploaded_filename'),2);
									$comment_obj->content = str_replace($source_filename, $target_filename, $comment_obj->content);
									// If binary file
								}
								else
								{
									$comment_obj->content = str_replace('file_srl='.$val->file_srl, 'file_srl='.$inserted_file->get('file_srl'), $comment_obj->content);
									$comment_obj->content = str_replace('sid='.$val->sid, 'sid='.$inserted_file->get('sid'), $comment_obj->content);
								}
							}
						}

						$comment_obj->module_srl = $obj->module_srl;
						$comment_obj->document_srl = $obj->document_srl;
						$comment_obj->comment_srl = $comment_srl;

						if($comment_obj->parent_srl) $comment_obj->parent_srl = $p_comment_srl[$comment_obj->parent_srl];

						$output = $oCommentController->insertComment($comment_obj, true);
						if($output->toBool()) $success_count ++;
					}
					$oDocumentController->updateCommentCount($obj->document_srl, $success_count, $comment_obj->nick_name, true);
				}
			}

			// Move the trackbacks
			$oTrackbackModel = getModel('trackback');
			if($oTrackbackModel && $oDocument->getTrackbackCount())
			{
				$trackbacks = $oTrackbackModel->getTrackbackList($oDocument->document_srl);
				if(count($trackbacks))
				{
					$success_count = 0;
					foreach($trackbacks as $trackback_obj)
					{
						$trackback_obj->trackback_srl = getNextSequence();
						$trackback_obj->module_srl = $obj->module_srl;
						$trackback_obj->document_srl = $obj->document_srl;
						$output = executeQuery('trackback.insertTrackback', $trackback_obj);
						if($output->toBool()) $success_count++;
					}
					// Update the number of trackbacks
					$oDocumentController->updateTrackbackCount($obj->document_srl, $success_count);
				}
			}

			$copied_srls[$document_srl] = $obj->document_srl;
		}

		// Call a trigger (before)
		$triggerObj->copied_srls = $copied_srls;
		$output = ModuleHandler::triggerCall('document.copyDocumentModule', 'after', $triggerObj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		$output = new Object();
		$output->add('copied_srls', $copied_srls);
		return $output;
	}

	/**
	 * Delete all documents of the module
	 * @param int $module_srl
	 * @return object
	 */
	function deleteModuleDocument($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$oDocumentModel = getModel('document');
		$args->module_srl = $module_srl;
		$document_list = $oDocumentModel->getDocumentList($args);
		$documents = $document_list->data;
		$output = executeQuery('document.deleteModuleDocument', $args);
		if(is_array($documents))
		{
			foreach ($documents as $oDocument)
			{
				$document_srl_list[] = $oDocument->document_srl;
			}
		}
		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object');
		if($oCacheHandler->isSupport())
		{
			if(is_array($document_srl_list))
			{
				foreach($document_srl_list as $document_srl)
				{
					$cache_key_item = 'document_item:'. getNumberingPath($document_srl) . $document_srl;
					$oCacheHandler->delete($cache_key_item);
				}
			}
		}
		return $output;
	}

	/**
	 * Save the default settings of the document module
	 * @return object
	 */
	function procDocumentAdminInsertConfig()
	{
		// Get the basic information
		$config = Context::gets('thumbnail_type');
		// Insert by creating the module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('document',$config);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminConfig');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Revoke declaration of the blacklisted posts
	 * @return object
	 */
	function procDocumentAdminCancelDeclare()
	{
		$document_srl = trim(Context::get('document_srl'));

		if($document_srl)
		{
			$args->document_srl = $document_srl;
			$output = executeQuery('document.deleteDeclaredDocuments', $args);
			if(!$output->toBool()) return $output;
		}
	}

	/**
	 * Delete all thumbnails
	 * @return void
	 */
	function procDocumentAdminDeleteAllThumbnail()
	{
		// delete all of thumbnail_ *. jpg files from files/attaches/images/ directory (prior versions to 1.0.4)
		$this->deleteThumbnailFile('./files/attach/images');
		// delete a directory itself, files/thumbnails (thumbnail policies have changed since version 1.0.5)
		FileHandler::removeFilesInDir('./files/thumbnails');

		$this->setMessage('success_deleted');
	}

	/**
	 * Delete thumbnails with subdirectory
	 * @return void
	 */
	function deleteThumbnailFile($path)
	{
		$directory = dir($path);
		while($entry = $directory->read()) {
			if ($entry != "." && $entry != "..") {
				if (is_dir($path."/".$entry)) {
					$this->deleteThumbnailFile($path."/".$entry);
				} else {
					if(!preg_match('/^thumbnail_([^\.]*)\.jpg$/i',$entry)) continue;
					FileHandler::removeFile($path.'/'.$entry);
				}
			}
		}
		$directory->close();
	}

	/**
	 * Add or modify extra variables of the module
	 * @return void|object
	 */
	function procDocumentAdminInsertExtraVar()
	{
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');
		$name = Context::get('name');
		$type = Context::get('type');
		$is_required = Context::get('is_required');
		$default = Context::get('default');
		$desc = Context::get('desc') ? Context::get('desc') : '';
		$search = Context::get('search');
		$eid = Context::get('eid');
		$obj = new stdClass();

		if(!$module_srl || !$name || !$eid) return new Object(-1,'msg_invalid_request');
		// set the max value if idx is not specified
		if(!$var_idx)
		{
			$obj->module_srl = $module_srl;
			$output = executeQuery('document.getDocumentMaxExtraKeyIdx', $obj);
			$var_idx = $output->data->var_idx+1;
		}

		// Check if the module name already exists
		$obj->module_srl = $module_srl;
		$obj->var_idx = $var_idx;
		$obj->eid = $eid;
		$output = executeQuery('document.isExistsExtraKey', $obj);
		if(!$output->toBool() || $output->data->count)
		{
			return new Object(-1, 'msg_extra_name_exists');
		}

		// insert or update
		$oDocumentController = getController('document');
		$output = $oDocumentController->insertDocumentExtraKey($module_srl, $var_idx, $name, $type, $is_required, $search, $default, $desc, $eid);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_registed');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminAlias', 'document_srl', $args->document_srl);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete extra variables of the module
	 * @return void|object
	 */
	function procDocumentAdminDeleteExtraVar()
	{
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');
		if(!$module_srl || !$var_idx) return new Object(-1,'msg_invalid_request');

		$oDocumentController = getController('document');
		$output = $oDocumentController->deleteDocumentExtraKeys($module_srl, $var_idx);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_deleted');
	}

	/**
	 * Control the order of extra variables
	 * @return void|object
	 */
	function procDocumentAdminMoveExtraVar()
	{
		$type = Context::get('type');
		$module_srl = Context::get('module_srl');
		$var_idx = Context::get('var_idx');

		if(!$type || !$module_srl || !$var_idx) return new Object(-1,'msg_invalid_request');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info->module_srl) return new Object(-1,'msg_invalid_request');

		$oDocumentModel = getModel('document');
		$extra_keys = $oDocumentModel->getExtraKeys($module_srl);
		if(!$extra_keys[$var_idx]) return new Object(-1,'msg_invalid_request');

		if($type == 'up') $new_idx = $var_idx-1;
		else $new_idx = $var_idx+1;
		if($new_idx<1) return new Object(-1,'msg_invalid_request');

		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->var_idx = $new_idx;
		$output = executeQuery('document.getDocumentExtraKeys', $args);
		if (!$output->toBool()) return $output;
		if (!$output->data) return new Object(-1, 'msg_invalid_request');
		unset($args);

		// update immediately if there is no idx to change
		if(!$extra_keys[$new_idx])
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->var_idx = $var_idx;
			$args->new_idx = $new_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;
			// replace if exists
		}
		else
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->var_idx = $new_idx;
			$args->new_idx = -10000;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;

			$args->var_idx = $var_idx;
			$args->new_idx = $new_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;

			$args->var_idx = -10000;
			$args->new_idx = $var_idx;
			$output = executeQuery('document.updateDocumentExtraKeyIdx', $args);
			if(!$output->toBool()) return $output;
			$output = executeQuery('document.updateDocumentExtraVarIdx', $args);
			if(!$output->toBool()) return $output;
		}

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_document_extra_keys:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}
	}

	/**
	 * Insert alias for document
	 * @return void|object
	 */
	function procDocumentAdminInsertAlias()
	{
		$args = Context::gets('module_srl','document_srl', 'alias_title');
		$alias_srl = Context::get('alias_srl');
		if(!$alias_srl)
		{
			$args->alias_srl = getNextSequence();
			$query = "document.insertAlias";
		}
		else
		{
			$args->alias_srl = $alias_srl;
			$query = "document.updateAlias";
		}
		$output = executeQuery($query, $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminAlias', 'document_srl', $args->document_srl);
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Delete alias for document
	 * @return void|object
	 */
	function procDocumentAdminDeleteAlias()
	{
		$document_srl = Context::get('document_srl');
		$alias_srl = Context::get('target_srl');
		$args->alias_srl = $alias_srl;
		$output = executeQuery("document.deleteAlias", $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminAlias', 'document_srl', $document_srl);
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	  * @fn procDocumentAdminMoveToTrash
	  * @brief move a document to trash.
	  * @see documentModel::getDocumentMenu
	  */
	function procDocumentAdminMoveToTrash()
	{
		$document_srl = Context::get('document_srl');

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
		if(!$oDocument->isGranted()) return $this->stop('msg_not_permitted');
	
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

		$args = new stdClass();
		$args->description = $message_content;
		$args->document_srl = $document_srl;

		$oDocumentController->moveDocumentToTrash($args);

		$returnUrl = Context::get('success_return_url');
		if(!$returnUrl)	
		{
			$arrUrl = parse_url(Context::get('cur_url'));
			$query = "";

			if($arrUrl['query'])
			{
				parse_str($arrUrl['query'], $arrQuery);

				// set query
				if(isset($arrQuery['document_srl']))
					unset($arrQuery['document_srl']);

				$searchArgs = new stdClass;
				foreach($arrQuery as $key=>$val)
				{
					$searchArgs->{$key} = $val;
				}

				if(!isset($searchArgs->sort_index))
					$searchArgs->sort_index = $module_info->order_target;

				foreach($module_info as $key=>$val)
				{
					if(!isset($searchArgs->{$key}))
						$searchArgs->{$key} = $val;
				}

				$oDocumentModel = getModel('document');
				$output = $oDocumentModel->getDocumentList($searchArgs, $module_info->except_notice, TRUE, array('document_srl'));

				$cur_page = 1;
				if(isset($arrQuery['page'])) {
					$cur_page = (int)$arrQuery['page'];
				}


				if($cur_page>1 && count($output->data) == 0)
					$arrQuery['page'] = $cur_page - 1;

				$query = "?";
				foreach($arrQuery as $key=>$val)
					$query .= sprintf("%s=%s&", $key, $val);
				$query = substr($query, 0, -1);
			}
			$returnUrl = $arrUrl['path'] . $query;
		}

		$this->add('redirect_url', $returnUrl);
	}

	/**
	 * Restor document from trash
	 * @return void|object
	 */
	function procDocumentAdminRestoreTrash()
	{
		$trash_srl = Context::get('trash_srl');
		$this->restoreTrash($trash_srl);
	}

	/*function restoreTrash($trash_srl){
	  $oDB = &DB::getInstance();
	  $oDocumentModel = getModel('document');

	  $trash_args->trash_srl = $trash_srl;

	  $output = executeQuery('document.getTrash', $trash_args);
	  if (!$output->toBool()) {
	  return $output;
	  }

	  $document_args->document_srl = $output->data->document_srl;
	  $document_args->module_srl = $output->data->module_srl;
	  $document_args->member_srl = $output->data->member_srl;
	  $document_args->ipaddress = $output->data->ipaddress;
	  $document_args->update_order = $output->data->update_order;

	  $oDocument = $oDocumentModel->getDocument($document_args->document_srl);

	// begin transaction
	$oDB->begin();

	$output = executeQuery('document.updateDocument', $document_args);
	if (!$output->toBool()) {
	$oDB->rollback();
	return $output;
	}

	$output = executeQuery('document.deleteTrash', $trash_args);
	if (!$output->toBool()) {
	$oDB->rollback();
	return $output;
	}
	// If the post was not temorarily saved, set the attachment's status to be valid
	if($oDocument->hasUploadedFiles() && $document_args->member_srl != $document_args->module_srl) {
	$args->upload_target_srl = $oDocument->document_srl;
	$args->isvalid = 'Y';
	executeQuery('file.updateFileValid', $args);
	}
	// call a trigger (after)
	if($output->toBool()) {
	$trigger_output = ModuleHandler::triggerCall('document.restoreTrash', 'after', $document_args);
	if(!$trigger_output->toBool()) {
	$oDB->rollback();
	return $trigger_output;
	}
	}

	// commit
	$oDB->commit();
	return $output;
	}*/

	/**
	 * Restore document from trash module, called by trash module
	 * This method is passived
	 * @param object|array $originObject
	 * @return object
	 */
	function restoreTrash($originObject)
	{
		if(is_array($originObject)) $originObject = (object)$originObject;

		$oDocumentController = getController('document');
		$oDocumentModel = getModel('document');

		$oDB = &DB::getInstance();
		$oDB->begin();

		//DB restore
		$output = $oDocumentController->insertDocument($originObject, false, true, false);
		if(!$output->toBool()) return new Object(-1, $output->getMessage());

		//FILE restore
		$oDocument = $oDocumentModel->getDocument($originObject->document_srl);
		// If the post was not temorarily saved, set the attachment's status to be valid
		if($oDocument->hasUploadedFiles() && $originObject->member_srl != $originObject->module_srl)
		{
			$args = new stdClass();
			$args->upload_target_srl = $oDocument->document_srl;
			$args->isvalid = 'Y';
			$output = executeQuery('file.updateFileValid', $args);
		}

		// call a trigger (after)
		if($output->toBool())
		{
			$trigger_output = ModuleHandler::triggerCall('document.restoreTrash', 'after', $originObject);
			if(!$trigger_output->toBool())
			{
				$oDB->rollback();
				return $trigger_output;
			}
		}

		// commit
		$oDB->commit();
		return new Object(0, 'success');
	}

	/**
	 * Empty document in trash, called by trash module
	 * This method is passived
	 * @param string $originObject string is serialized object
	 * @return object
	 */
	function emptyTrash($originObject)
	{
		$originObject = unserialize($originObject);
		if(is_array($originObject)) $originObject = (object) $originObject;

		$oDocument = new documentItem();
		$oDocument->setAttribute($originObject);

		$oDocumentController = getController('document');
		$output = $oDocumentController->deleteDocument($oDocument->get('document_srl'), true, true, $oDocument);
		return $output;
	}
}
/* End of file document.admin.controller.php */
/* Location: ./modules/document/document.admin.controller.php */
