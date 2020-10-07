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
		if(!$cart) throw new Rhymix\Framework\Exception('msg_cart_is_null');
		$document_srl_list= explode('|@|', $cart);
		$document_count = count($document_srl_list);
		if(!$document_count) throw new Rhymix\Framework\Exception('msg_cart_is_null');
		// Delete a doc
		$oDocumentController = getController('document');
		for($i=0;$i<$document_count;$i++)
		{
			$document_srl = trim($document_srl_list[$i]);
			if(!$document_srl) continue;

			$oDocumentController->deleteDocument($document_srl, true);
		}

		$this->setMessage(sprintf(lang('msg_checked_document_is_deleted'), $document_count) );
	}
	
	/**
	 * Save the default settings of the document module
	 * @return object
	 */
	function procDocumentAdminInsertConfig()
	{
		// Get the basic information
		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		$config->view_count_option = Context::get('view_count_option');
		$config->icons = Context::get('icons');
		$config->micons = Context::get('micons');

		// Insert by creating the module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('document',$config);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminConfig');
		$this->setRedirectUrl($returnUrl, $output);
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
			$args = new stdClass();
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
		$temp_cache_dir = './files/thumbnails_' . $_SERVER['REQUEST_TIME'];
		FileHandler::rename('./files/thumbnails', $temp_cache_dir);
		FileHandler::makeDir('./files/thumbnails');

		FileHandler::removeDir($temp_cache_dir);

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

		if(!$module_srl || !$name || !$eid) throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
			throw new Rhymix\Framework\Exception('msg_extra_name_exists');
		}

		// insert or update
		$oDocumentController = getController('document');
		$output = $oDocumentController->insertDocumentExtraKey($module_srl, $var_idx, $name, $type, $is_required, $search, $default, $desc, $eid);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_registed');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminAlias');
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
		if(!$module_srl || !$var_idx) throw new Rhymix\Framework\Exceptions\InvalidRequest;

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

		if(!$type || !$module_srl || !$var_idx) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info->module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oDocumentModel = getModel('document');
		$extra_keys = $oDocumentModel->getExtraKeys($module_srl);
		if(!$extra_keys[$var_idx]) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		if($type == 'up') $new_idx = $var_idx-1;
		else $new_idx = $var_idx+1;
		if($new_idx<1) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->var_idx = $new_idx;
		$output = executeQuery('document.getDocumentExtraKeys', $args);
		if (!$output->toBool()) return $output;
		if (!$output->data) throw new Rhymix\Framework\Exceptions\InvalidRequest;
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

		Rhymix\Framework\Cache::delete("site_and_module:module_document_extra_keys:$module_srl");
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
		$args = new stdClass();
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
		$logged_info = Context::get('logged_info');
		$document_srl = Context::get('document_srl');

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
		if(!$oDocument->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($oDocument->get('member_srl'));
		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_admin_document_no_move_to_trash');
		}

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
	
	/**
	 * Move module of the documents
	 * @param array $document_srl_list
	 * @param int $target_module_srl
	 * @param int $target_category_srl
	 * @return Object
	 */
	function moveDocumentModule($document_srl_list, $target_module_srl, $target_category_srl)
	{
		if(empty($document_srl_list))
		{
			return;
		}
		if(!is_array($document_srl_list))
		{
			$document_srl_list = array_map('trim', explode(',', $document_srl_list));
		}
		$document_srl_list = array_map('intval', $document_srl_list);
		
		$obj = new stdClass;
		$obj->document_srls = $document_srl_list;
		$obj->list_count = count($document_srl_list);
		$obj->document_list = executeQueryArray('document.getDocuments', $obj)->data;
		$obj->module_srl = $target_module_srl;
		$obj->category_srl = $target_category_srl;
		
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('document.moveDocumentModule', 'before', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		$origin_category = array();
		$oDocumentController = getController('document');
		
		foreach($obj->document_list as $document)
		{
			// if the target module is different
			if($document->module_srl != $obj->module_srl)
			{
				$oDocumentController->deleteDocumentAliasByDocument($document->document_srl);
			}
			
			// if the target category is different
			if($document->category_srl != $obj->category_srl && $document->category_srl)
			{
				$origin_category[$document->category_srl] = $document->module_srl;
			}
			
			$oDocumentController->insertDocumentUpdateLog($document);
		}
		
		// update documents
		$output = executeQuery('document.updateDocumentsModule', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// update extra vars
		$output = executeQuery('document.updateDocumentExtraVarsModule', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// call a trigger (after)
		ModuleHandler::triggerCall('document.moveDocumentModule', 'after', $obj);
		
		// update category count
		foreach($origin_category as $category_srl => $module_srl)
		{
			$oDocumentController->updateCategoryCount($module_srl, $category_srl);
		}
		if($obj->category_srl)
		{
			$oDocumentController->updateCategoryCount($obj->module_srl, $obj->category_srl);
		}
		
		$oDB->commit();
		
		// remove from cache
		foreach($obj->document_list as $document)
		{
			Rhymix\Framework\Cache::delete('document_item:'. getNumberingPath($document->document_srl) . $document->document_srl);
			Rhymix\Framework\Cache::delete('site_and_module:document_srl:' . $document->document_srl);
		}
		
		return new BaseObject();
	}

	/**
	 * Copy the documents
	 * @param array $document_srl_list
	 * @param int $target_module_srl
	 * @param int $target_category_srl
	 * @return object
	 */
	function copyDocumentModule($document_srl_list, $target_module_srl, $target_category_srl)
	{
		if(empty($document_srl_list))
		{
			return;
		}
		if(!is_array($document_srl_list))
		{
			$document_srl_list = array_map('trim', explode(',', $document_srl_list));
		}
		$document_srl_list = array_map('intval', $document_srl_list);
		
		$obj = new stdClass;
		$obj->document_srls = $document_srl_list;
		$obj->list_count = count($document_srl_list);
		$obj->document_list = executeQueryArray('document.getDocuments', $obj)->data;
		$obj->module_srl = $target_module_srl;
		$obj->category_srl = $target_category_srl;
		
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('document.copyDocumentModule', 'before', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		$oDocumentController = getController('document');
		$extra_vars_list = getModel('document')->getDocumentExtraVarsFromDB($document_srl_list)->data;
		
		$extra_vars = array();
		foreach($extra_vars_list as $extra)
		{
			if(!isset($extra_vars[$extra->document_srl]))
			{
				$extra_vars[$extra->document_srl] = array();
			}
			
			$extra_vars[$extra->document_srl][] = $extra;
		}
		
		$copied_srls = array();
		foreach($obj->document_list as $document)
		{
			$copy = clone $document;
			$copy->document_srl = getNextSequence();
			$copy->module_srl = $obj->module_srl;
			$copy->category_srl = $obj->category_srl;
			$copy->comment_count = 0;
			$copy->trackback_count = 0;
			$copy->password_is_hashed = true;
			
			// call a trigger (add)
			$args = new stdClass;
			$args->source = $document;
			$args->copied = $copy;
			ModuleHandler::triggerCall('document.copyDocumentModule', 'add', $args);
			
			// insert a copied document
			$output = $oDocumentController->insertDocument($copy, true, true);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
			
			// insert copied extra vars of the document
			if(isset($extra_vars[$document->document_srl]))
			{
				foreach($extra_vars[$document->document_srl] as $extra)
				{
					$oDocumentController->insertDocumentExtraVar($copy->module_srl, $copy->document_srl, $extra->var_idx, $extra->value, $extra->eid, $extra->lang_code);
				}
			}
			
			$copied_srls[$document->document_srl] = $copy->document_srl;
		}
		
		// call a trigger (after)
		$obj->copied_srls = $copied_srls;
		ModuleHandler::triggerCall('document.copyDocumentModule', 'after', $obj);
		
		$oDB->commit();
		
		// return copied document_srls
		$output = new BaseObject();
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
		$args = new stdClass;
		$args->list_count = 0;
		$args->module_srl = intval($module_srl);
		$document_list = executeQueryArray('document.getDocumentList', $args, array('document_srl'))->data;
		
		// delete documents
		$output = executeQuery('document.deleteModuleDocument', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		
		// remove from cache
		foreach ($document_list as $document)
		{
			Rhymix\Framework\Cache::delete('document_item:'. getNumberingPath($document->document_srl) . $document->document_srl);
			Rhymix\Framework\Cache::delete('site_and_module:document_srl:' . $document->document_srl);
		}
		
		return new BaseObject();
	}
	
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
		if(!$output->toBool()) return $output;

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
		ModuleHandler::triggerCall('document.restoreTrash', 'after', $originObject);

		// commit
		$oDB->commit();
		return new BaseObject(0, 'success');
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
