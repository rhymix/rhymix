<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * Admin view of the module class file
 * @author NAVER (developers@xpressengine.com)
 */
class fileAdminView extends file
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Display output list (for administrator)
	 *
	 * @return Object
	 */
	function dispFileAdminList()
	{
		// Options to get a list
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< Number of documents that appear on a single page
		$args->page_count = 10; // /< Number of pages that appear in the page navigation

		$args->sort_index = 'file_srl'; // /< Sorting values
		$args->isvalid = Context::get('isvalid');
		$args->module_srl = Context::get('module_srl');
		// Get a list
		$oFileAdminModel = getAdminModel('file');
		$output = $oFileAdminModel->getFileList($args);
		
		// Get the document for looping a list
		if($output->data)
		{
			$oCommentModel = getModel('comment');
			$oDocumentModel = getModel('document');
			$oModuleModel = getModel('module');

			$file_list = array();
			$document_list = array();
			$comment_list = array();
			$module_list= array();

			$doc_srls = array();
			$com_srls = array();
			$mod_srls = array();

			foreach($output->data as $file)
			{
				$file_srl = $file->file_srl;
				$target_srl = $file->upload_target_srl;
				$file_update_args = new stdClass();
				$file_update_args->file_srl = $file_srl;
				// Find and update if upload_target_type doesn't exist
				if(!$file->upload_target_type)
				{
					// Pass if upload_target_type is already found 
					if(isset($document_list[$target_srl]))
					{
						$file->upload_target_type = 'doc';
					}
					elseif(isset($comment_list[$target_srl]))
					{
						$file->upload_target_type = 'com';
					}
					elseif(isset($module_list[$target_srl]))
					{
						$file->upload_target_type = 'mod';
					}
					else
					{
						// document
						$document = $oDocumentModel->getDocument($target_srl);
						if($document->isExists())
						{
							$file->upload_target_type = 'doc';
							$file_update_args->upload_target_type = $file->upload_target_type;
							$document_list[$target_srl] = $document;
						}
						// comment
						if(!$file->upload_target_type)
						{
							$comment = $oCommentModel->getComment($target_srl);
							if($comment->isExists())
							{
								$file->upload_target_type = 'com';
								$file->target_document_srl = $comment->document_srl;
								$file_update_args->upload_target_type = $file->upload_target_type;
								$comment_list[$target_srl] = $comment;
								$doc_srls[] = $comment->document_srl;
							}
						}
						// module (for a page)
						if(!$file->upload_target_type)
						{
							$module = $oModuleModel->getModulesInfo($target_srl);
							if($module)
							{
								$file->upload_target_type = 'mod';
								$file_update_args->upload_target_type = $file->upload_target_type;
								$module_list[$module->comment_srl] = $module;
							}
						}
						if(isset($file_update_args->upload_target_type) && $file_update_args->upload_target_type)
						{
							executeQuery('file.updateFileTargetType', $file_update_args);
						}
					}
					// Check if data is already obtained
					foreach($com_srls as $i => $com_srl)
					{
						if(isset($comment_list[$com_srl])) unset($com_srls[$i]);
					}
					foreach($doc_srls as $i => $doc_srl)
					{
						if(isset($document_list[$doc_srl])) unset($doc_srls[$i]);
					}
					foreach($mod_srls as $i => $mod_srl)
					{
						if(isset($module_list[$mod_srl])) unset($mod_srls[$i]);
					}
				}

				if($file->upload_target_type && isset(${$file->upload_target_type.'_srls'}) && is_array(${$file->upload_target_type.'_srls'}))
				{
					if(!in_array($file->upload_target_srl, ${$file->upload_target_type.'_srls'}))
					{
						${$file->upload_target_type.'_srls'}[] = $target_srl;
					}
				}

				$file_list[$file_srl] = $file;
				$mod_srls[] = $file->module_srl;
			}
			// Remove duplication
			$doc_srls = array_unique($doc_srls);
			$com_srls = array_unique($com_srls);
			$mod_srls = array_unique($mod_srls);
			// Comment list
			$com_srls_count = count($com_srls);
			if($com_srls_count)
			{
				$comment_output = $oCommentModel->getComments($com_srls);
				foreach($comment_output as $comment)
				{
					$comment_list[$comment->comment_srl] = $comment;
					$doc_srls[] = $comment->document_srl;
				}
			}
			// Document list
			$doc_srls_count = count($doc_srls);
			if($doc_srls_count)
			{
				$document_output = $oDocumentModel->getDocuments($doc_srls);
				if(is_array($document_output))
				{
					foreach($document_output as $document)
					{
						$document_list[$document->document_srl] = $document;
					}
				}
			}
			// Module List
			$mod_srls_count = count($mod_srls);
			if($mod_srls_count)
			{
				$columnList = array('module_srl', 'mid', 'browser_title');
				$module_output = $oModuleModel->getModulesInfo($mod_srls, $columnList);
				if($module_output && is_array($module_output))
				{
					foreach($module_output as $module)
					{
						$module_list[$module->module_srl] = $module;
					}
				}
			}

			foreach($file_list as $srl => $file)
			{
				if($file->upload_target_type == 'com')
				{
					$file_list[$srl]->target_document_srl = $comment_list[$file->upload_target_srl]->document_srl;
				}
			}
		}

		Context::set('file_list', $file_list);
		Context::set('document_list', $document_list);
		Context::set('comment_list', $comment_list);
		Context::set('module_list', $module_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		// Set a template
		$security = new Security();
		$security->encodeHTML('file_list..');
		$security->encodeHTML('module_list..');
		$security->encodeHTML('search_target', 'search_keyword');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('file_list');
	}

	/**
	 * Upload config screen
	 *
	 * @return Object
	 */
	function dispFileAdminUploadConfig()
	{
		$oFileModel = getModel('file');
		$config = $oFileModel->getFileConfig();
		Context::set('config', $config);
		Context::set('is_ffmpeg', function_exists('exec') && Rhymix\Framework\Storage::isExecutable($config->ffmpeg_command) && Rhymix\Framework\Storage::isExecutable($config->ffprobe_command));
		
		// Set a template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('upload_config');
	}

	/**
	 * Download config screen
	 *
	 * @return Object
	 */
	function dispFileAdminDownloadConfig()
	{
		$oFileModel = getModel('file');
		$config = $oFileModel->getFileConfig();
		Context::set('config',$config);
		
		// Set a template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('download_config');
	}

	/**
	 * Other config screen
	 *
	 * @return Object
	 */
	function dispFileAdminOtherConfig()
	{
		$oFileModel = getModel('file');
		$config = $oFileModel->getFileConfig();
		Context::set('config',$config);
		
		// Set a template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('other_config');
	}
}
/* End of file file.admin.view.php */
/* Location: ./modules/file/file.admin.view.php */
