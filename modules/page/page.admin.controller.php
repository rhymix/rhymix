<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pageAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief page of the module admin controller class
 */
class PageAdminController extends Page
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Add a Page
	 */
	function procPageAdminInsert()
	{
		// Set board module
		$args = Context::getRequestVars();
		$args->module = 'page';
		$args->mid = $args->page_name;	//because if mid is empty in context, set start page mid
		$args->path = (!$args->path) ? '' : $args->path;
		$args->mpath = (!$args->mpath) ? '' : $args->mpath;
		if (preg_match('!\bfiles/cache/!i', $args->path))
		{
			$this->setError(-1);
			$this->setMessage('msg_invalid_opage_pc_path');
			$this->setRedirectUrl(Context::get('success_return_url'));
			return;
		}
		if (preg_match('!\bfiles/cache/!i', $args->mpath))
		{
			$this->setError(-1);
			$this->setMessage('msg_invalid_opage_mobile_path');
			$this->setRedirectUrl(Context::get('success_return_url'));
			return;
		}
		$args->opage_proc_php = $args->opage_proc_php ?? 'N';
		$args->opage_proc_tpl = $args->opage_proc_tpl ?? 'N';
		if ($args->opage_proc_tpl === 'Y')
		{
			$args->opage_proc_php = 'Y';
		}
		unset($args->page_name);

		if($args->use_mobile != 'Y') $args->use_mobile = '';
		$args->browser_title = trim(utf8_normalize_spaces($args->browser_title));
		$args->robots_tag = ($args->robots_tag === 'noindex') ? 'noindex' : 'all';
		$args->meta_keywords = $args->meta_keywords ? implode(', ', array_map('trim', explode(',', $args->meta_keywords))) : '';
		$args->meta_description = trim(utf8_normalize_spaces($args->meta_description));

		// Check if an original module exists by using module_srl
		if($args->module_srl)
		{
			$columnList = array('module_srl');
			$module_info = ModuleModel::getModuleInfoByModuleSrl($args->module_srl, $columnList);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
			else
			{
				foreach($args as $key=>$val)
				{
					$module_info->{$key} = $val;
				}
				$args = $module_info;
			}
		}

		switch ($args->page_type)
		{
			case 'WIDGET' :
				{
					unset($args->skin);
					unset($args->mskin);
					unset($args->path);
					unset($args->mpath);
					break;
				}
			case 'ARTICLE' :
				{
					unset($args->page_caching_interval);
					unset($args->path);
					unset($args->mpath);
					break;
				}
			case 'OUTSIDE' :
				{
					unset($args->skin);
					unset($args->mskin);
					break;
				}
		}
		// Insert/update depending on module_srl
		$oModuleController = ModuleController::getInstance();
		if(!$args->module_srl)
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		$this->add("page", Context::get('page'));
		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispPageAdminInfo');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Page Modify
	 */
	function procPageAdminUpdate()
	{
		$this->procPageAdminInsert();
	}

	function putDocumentsInPageToArray($target, &$array)
	{
		if(!$target) return;
		preg_match_all('!<img hasContent="true" ([^>]+)!is', $target, $matches);
		$pattern = '!document_srl="(\d+)"!';
		foreach($matches[1] as $match)
		{
			$match2 = null;
			preg_match($pattern, $match, $match2);
			if(count($match2))
			{
				$array[(int)$match2[1]] = 1;
			}
		}
	}

	/**
	 * @brief Save page edits
	 */
	function procPageAdminInsertContent()
	{
		$module_srl = Context::get('module_srl');
		$content = Context::get('content');
		if(!$module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
		$mcontent = Context::get('mcontent');
		$type = Context::get('type');
		// Guhaeom won information page
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if($type == "mobile")
		{
			if(!$mcontent) $mcontent = '';
			$module_info->mcontent = $mcontent;
		}
		else
		{
			if(!isset($content)) $content ='';
			$module_info->content = $content;
		}

		$document_srls = array();
		$this->putDocumentsInPageToArray($module_info->content, $document_srls);
		$this->putDocumentsInPageToArray($module_info->mcontent, $document_srls);

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		$obj = new stdClass();
		$obj->module_srl = $module_srl;
		$obj->list_count = 99999999;
		$output = $oDocumentModel->getDocumentList($obj);
		if(count($output->data))
		{
			foreach($output->data as $document)
			{
				if($document_srls[$document->document_srl]) continue;
				$oDocumentController->deleteDocument($document->document_srl, true);
			}
		}
		// Creates an object of the controller module module
		$oModuleController = getController('module');
		// Save
		$output = $oModuleController->updateModule($module_info);
		if(!$output->toBool()) return $output;
		// On the page, change the validity status of the attached file
		$oFileController = getController('file');
		$oFileController->setFilesValid($module_info->module_srl, 'mod');

		$this->add("module_srl", $module_info->module_srl);
		$this->add("page", Context::get('page'));
		$this->add("mid", $module_info->mid);

		// Create cache file
		$this->procPageAdminRemoveWidgetCache();
	}

	/**
	 * @brief Delete page
	 */
	function procPageAdminDelete()
	{
		$module_srl = Context::get('module_srl');
		// Get an original
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','page');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPageAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Additional pages of basic information
	 */
	function procPageAdminInsertConfig()
	{
		// Get the basic information
		$args = Context::getRequestVars();
		// Insert by creating the module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('page',$args);
		return $output;
	}

	/**
	 * @brief Upload attachments
	 */
	function procUploadFile()
	{
		// Basic variables setting
		$upload_target_srl = Context::get('upload_target_srl');
		$module_srl = Context::get('module_srl');
		// Create the controller object file class
		$oFileController = getController('file');
		$output = $oFileController->insertFile($module_srl, $upload_target_srl);
		// Attachment to the output of the list, java script
		$oFileController->printUploadedFileList($upload_target_srl);
	}

	/**
	 * @brief Delete the attachment
	 * Delete individual files in the editor using
	 */
	function procDeleteFile()
	{
		// Basic variable setting(upload_target_srl and module_srl set)
		$upload_target_srl = Context::get('upload_target_srl');
		$module_srl = Context::get('module_srl');
		$file_srl = Context::get('file_srl');
		// Create the controller object file class
		$oFileController = getController('file');
		if($file_srl) $output = $oFileController->deleteFile($file_srl, $this->grant->manager);
		// Attachment to the output of the list, java script
		$oFileController->printUploadedFileList($upload_target_srl);
	}

	/**
	 * @brief Clear widget cache files of the specified page
	 */
	function procPageAdminRemoveWidgetCache()
	{
		$module_srl = Context::get('module_srl');

		if(!$module_srl) return;

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'content');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);

		$content = $module_info->content;

		// widget controller re-run of the cache files
		$oWidgetController = getController('widget');
		$oWidgetController->recompileWidget($content);

		if($module_info->page_type == 'WIDGET')
		{
			$path = RX_BASEDIR . 'files/cache/page/';
			$cache_files = FileHandler::readDir($path, '/^' . $module_info->module_srl . '\./');
			foreach($cache_files as $file_name)
			{
				FileHandler::removeFile($path . $module_info->module_srl . '.' . $file_name);
			}
		}
		else if($module_info->page_type == 'OUTSIDE')
		{
			$path = RX_BASEDIR . 'files/cache/page/';
			$cache_files = FileHandler::readDir($path, '/^' . $module_info->module_srl . './');
			foreach($cache_files as $file_name)
			{
				FileHandler::removeFile($path . $module_info->module_srl . '.' . $file_name);
			}
		}

		Rhymix\Framework\Cache::delete('site_and_module:mid_info:' . $module_info->module_srl);
	}

	function procPageAdminArticleDocumentInsert()
	{
		// Check privileges
		$logged_info = Context::get('logged_info');
		$grant = ModuleModel::getGrant($this->module_info, $logged_info);
		if(!$grant->manager && !$grant->modify)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_info->module_srl;
		$obj->is_notice = 'N';

		// If the tile is empty, extract string from the contents.
		$obj->title = escape($obj->title, false);
		if ($obj->title === '')
		{
			$obj->title = escape(cut_str(trim(utf8_normalize_spaces(strip_tags($obj->content))), 20, '...'), false);
		}
		if ($obj->title === '')
		{
			$obj->title = 'Untitled';
		}

		// Check original document
		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if ($oDocument->isExists() && $oDocument->get('module_srl') != $this->module_info->module_srl)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Insert or update document
		$oDocumentController = DocumentController::getInstance();
		if ($oDocument->isExists())
		{
			$output = $oDocumentController->updateDocument($oDocument, $obj);
			if (!$output->toBool())
			{
				return $output;
			}
			$msg_code = 'success_updated';
			$document_srl = $obj->document_srl;
		}
		else
		{
			$output = $oDocumentController->insertDocument($obj);
			if (!$output->toBool())
			{
				return $output;
			}
			$msg_code = 'success_registed';
			$document_srl = $output->get('document_srl');
		}

		// Update module info
		$target = ($obj->isMobile == 'Y') ? 'mdocument_srl' : 'document_srl';
		if(!isset($this->module_info->{$target}) || (isset($this->module_info->{$target}) && $this->module_info->{$target} !== $document_srl))
		{
			$this->module_info->{$target} = $document_srl;
			$oModuleController = ModuleController::getInstance();
			$output = $oModuleController->updateModule($this->module_info);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		// 결과를 리턴
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $output->get('document_srl'));
		$this->add('is_mobile', $obj->isMobile);

		// 성공 메세지 등록
		$this->setMessage($msg_code);
	}
}
/* End of file page.admin.controller.php */
/* Location: ./modules/page/page.admin.controller.php */
