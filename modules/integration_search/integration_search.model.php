<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The model class of integration module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_searchModel extends module
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Search documents
	 *
	 * @param object $search_args
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output document list
	 */
	function searchDocuments($search_args, $list_count = 20)
	{
		$module_srls_list = $search_args->module_srls_list;
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srls_list ? explode(',', $module_srls_list) : array();
		}
		$module_srls_list = array_map('intval', $module_srls_list);
		$accessible_modules = getModel('module')->getAccessibleModuleList();

		$search_modules = $search_args->search_modules ?? array();
		$args = new stdClass();
		if ($search_args->target == 'exclude')
		{
			$args->module_srl = count($search_modules) === 0 ?
				array_keys($accessible_modules) :
				array_intersect(array_keys($accessible_modules), $search_modules);
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = count($search_modules) === 0 ?
				array_intersect($module_srls_list, array_keys($accessible_modules)) :
				array_intersect($module_srls_list, array_keys($accessible_modules), $search_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;
		$args->page = $search_args->page ?? 1;
		$args->list_count = $list_count;
		$args->page_count = Mobile::isFromMobilePhone() ? 5 : 10;
		$args->search_target = $search_args->search_target;
		$args->search_keyword = $search_args->search_keyword;
		$args->start_regdate = $search_args->start_regdate ?? null;
		$args->end_regdate = $search_args->end_regdate ?? null;
		$args->sort_index = 'list_order';
		$args->order_type = 'asc';
		$args->statusList = array('PUBLIC');
		if(!$args->module_srl) unset($args->module_srl);
		if(!$args->exclude_module_srl) unset($args->exclude_module_srl);

		// Get a list of documents
		$document_list = DocumentModel::getDocumentList($args);

		// Replace title with module title if it belongs to a page
		foreach ($document_list->data as $document)
		{
			if (isset($accessible_modules[$document->get('module_srl')]) && $accessible_modules[$document->get('module_srl')]->module === 'page')
			{
				$document->add('title', $accessible_modules[$document->get('module_srl')]->browser_title);
			}
		}
		return $document_list;
	}

	/**
	 * Search comment
	 *
	 * @param object $search_args Keyword
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output comment list
	 */
	function searchComments($search_args, $list_count = 20)
	{
		$module_srls_list = $search_args->module_srls_list;
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srls_list ? explode(',', $module_srls_list) : array();
		}
		$module_srls_list = array_map('intval', $module_srls_list);
		$accessible_modules = array_keys(getModel('module')->getAccessibleModuleList());

		$search_modules = $search_args->search_modules ?? array();
		$args = new stdClass();
		if ($search_args->target == 'exclude')
		{
			$args->module_srl = count($search_modules) === 0 ?
				$accessible_modules : array_intersect($accessible_modules, $search_modules);
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = count($search_modules) === 0 ?
				array_intersect($module_srls_list, $accessible_modules) :
				array_intersect($module_srls_list, $accessible_modules, $search_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;

		$args->page = $search_args->page ?? 1;
		$args->list_count = $list_count;
		$args->page_count = Mobile::isFromMobilePhone() ? 5 : 10;
		$args->search_target = 'content';
		$args->search_keyword = $search_args->search_keyword;
		$args->start_regdate = $search_args->start_regdate ?? null;
		$args->end_regdate = $search_args->end_regdate ?? null;
		$args->is_secret = 'N';
		$args->sort_index = 'list_order';
		$args->order_type = 'asc';
		$args->statusList = array(1);
		$args->document_statusList = array('PUBLIC');
		if(!$args->module_srl) unset($args->module_srl);
		if(!$args->exclude_module_srl) unset($args->exclude_module_srl);

		// Get a list of comments
		$oCommentModel = getModel('comment');
		return $oCommentModel->getTotalCommentList($args);
	}

	/**
	 * Search file
	 *
	 * @param object $search_args
	 * @param integer $list_count list count of page navigation
	 * @param string $direct_download Y or N
	 *
	 * @return Object output file list
	 */
	function _searchFiles($search_args, $list_count = 20, $direct_download = 'Y')
	{
		$module_srls_list = $search_args->module_srls_list;
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srls_list ? explode(',', $module_srls_list) : array();
		}
		$accessible_modules = array_keys(getModel('module')->getAccessibleModuleList());

		$search_modules = $search_args->search_modules ?? array();
		$args = new stdClass();
		if ($search_args->target == 'exclude')
		{
			$args->module_srl = count($search_modules) === 0 ?
				$accessible_modules : array_intersect($accessible_modules, $search_modules);
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = count($search_modules) === 0 ?
				array_intersect($module_srls_list, $accessible_modules) :
				array_intersect($module_srls_list, $accessible_modules, $search_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;

		$args->page = $search_args->page ?? 1;
		$args->list_count = $list_count;
		$args->page_count = Mobile::isFromMobilePhone() ? 5 : 10;
		$args->search_target = 'filename';
		$args->search_keyword = $search_args->search_keyword;
		$args->start_regdate = $search_args->start_regdate ?? null;
		$args->end_regdate = $search_args->end_regdate ?? null;
		$args->sort_index = 'files.file_srl';
		$args->order_type = 'desc';
		$args->isvalid = 'Y';
		$args->direct_download = $direct_download=='Y'?'Y':'N';
		$args->exclude_secret = 'Y';

		// Get a list of files
		$oFileAdminModel = FileAdminModel::getInstance();
		$output = $oFileAdminModel->getFileList($args);
		if(!$output->toBool() || !$output->data) return $output;

		$list = array();
		foreach($output->data as $key => $val)
		{
			$obj = new \Rhymix\Modules\Integration_Search\Models\FileSearchResult;
			$obj->file_srl = $val->file_srl;
			$obj->filename = $val->source_filename;
			$obj->uploaded_filename = $val->uploaded_filename;
			$obj->download_count = $val->download_count;
			$obj->download_url = \RX_BASEURL . preg_replace('!^\.\/!', '', $val->download_url);
			$obj->target_srl = $val->upload_target_srl;
			$obj->file_size = $val->file_size;

			// Images
			if(preg_match('/\.(jpe?g|gif|png|bmp|webp)$/i', $val->source_filename))
			{
				$obj->type = 'image';
			}
			elseif(Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($val->source_filename))
			{
				$obj->type = 'multimedia';
				if ($val->thumbnail_filename)
				{
					$obj->video_thumbnail_url = \RX_BASEURL . preg_replace('!^\.\/!', '', $val->thumbnail_filename);
				}
			}
			else
			{
				$obj->type = 'binary';
			}

			$list[] = $obj;
			$target_list[] = $val->upload_target_srl;
		}
		$output->data = $list;

		$oDocumentModel = getModel('document');
		$document_list = $oDocumentModel->getDocuments($target_list);
		if($document_list) foreach($document_list as $key => $val)
		{
			foreach($output->data as $k => $v)
			{
				if($v->target_srl== $val->document_srl)
				{
					$output->data[$k]->url = $val->getPermanentUrl();
					$output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
					$output->data[$k]->nick_name = $val->getNickName();
				}
			}
		}

		$oCommentModel = getModel('comment');
		$comment_list = $oCommentModel->getComments($target_list);
		if($comment_list) foreach($comment_list as $key => $val)
		{
			foreach($output->data as $k => $v)
			{
				if($v->target_srl== $val->comment_srl)
				{
					$output->data[$k]->url = $val->getPermanentUrl();
					$output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
					$output->data[$k]->nick_name = $val->getNickName();
				}
			}
		}

		return $output;
	}

	/**
	 * Search Multimedia. call function _getFiles().
	 *
	 * @param object $search_args Keyword
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function searchImages($search_args, $list_count = 20)
	{
		return $this->_searchFiles($search_args, $list_count);
	}

	/**
	 * Search for attachments. call function _getFiles().
	 *
	 * @param object $search_args Keyword
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function searchFiles($search_args, $list_count = 20)
	{
		return $this->_searchFiles($search_args, $list_count, 'N');
	}

	/**
	 * Search trackbacks
	 *
	 * @deprecated
	 * @return BaseObject
	 */
	function getTrackbacks()
	{
		return new BaseObject();
	}

	/**
     * Legacy function to maintain backward compatibility with existing code.
     * This function wraps the new searchDocuments function.
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_target Target
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output document list
	 */
	function getDocuments($target, $module_srls_list, $search_target, $search_keyword, $page=1, $list_count=20)
	{
		$args = new stdClass();
		$args->target = $target;
		$args->module_srls_list = $module_srls_list;
		$args->search_target = $search_target;
		$args->search_keyword = $search_keyword;
		$args->page = $page;
		return $this->searchDocuments($args, $list_count);
	}

	/**
     * Legacy function to maintain backward compatibility with existing code.
     * This function wraps the new searchComments function.	
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output comment list
	 */
	function getComments($target, $module_srls_list, $search_keyword, $page=1, $list_count=20)
	{
		$args = new stdClass();
		$args->target = $target;
		$args->module_srls_list = $module_srls_list;
		$args->search_keyword = $search_keyword;
		$args->page = $page;
		return $this->searchComments($args, $list_count);
	}

	/**
     * Legacy function to maintain backward compatibility with existing code.
     * This function wraps the new searchImages function.
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function getImages($target, $module_srls_list, $search_keyword, $page=1, $list_count=20)
	{
		$args = new stdClass();
		$args->target = $target;
		$args->module_srls_list = $module_srls_list;
		$args->search_keyword = $search_keyword;
		$args->page = $page;
		return $this->searchImages($args, $list_count);
	}

	/**
     * Legacy function to maintain backward compatibility with existing code.
     * This function wraps the new searchFiles function.
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function getFiles($target, $module_srls_list, $search_keyword, $page=1, $list_count=20)
	{
		$args = new stdClass();
		$args->target = $target;
		$args->module_srls_list = $module_srls_list;
		$args->search_keyword = $search_keyword;
		$args->page = $page;
		return $this->searchFiles($args, $list_count);
	}

}
/* End of file integration_search.model.php */
/* Location: ./modules/integration_search/integration_search.model.php */
