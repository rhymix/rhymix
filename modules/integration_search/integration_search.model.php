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
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_target Target
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output document list
	 */
	function getDocuments($target, $module_srls_list, $search_target, $search_keyword, $page=1, $list_count = 20)
	{
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srls_list ? explode(',', $module_srls_list) : array();
		}
		$module_srls_list = array_map('intval', $module_srls_list);
		$accessible_modules = getModel('module')->getAccessibleModuleList();

		$args = new stdClass();
		if($target == 'exclude')
		{
			$args->module_srl = array_keys($accessible_modules);
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = array_intersect($module_srls_list, array_keys($accessible_modules));
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;
		
		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = $search_target;
		$args->search_keyword = $search_keyword;
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
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output comment list
	 */
	function getComments($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20)
	{
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srl_list ? explode(',', $module_srls_list) : array();
		}
		$module_srls_list = array_map('intval', $module_srls_list);
		$accessible_modules = array_keys(getModel('module')->getAccessibleModuleList());

		$args = new stdClass();
		if($target == 'exclude')
		{
			$args->module_srl = $accessible_modules;
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = array_intersect($module_srls_list, $accessible_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;

		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = 'content';
		$args->search_keyword = $search_keyword;
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
	 * Search trackbacks
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_target Target
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object output trackback list
	 */
	function getTrackbacks($target, $module_srls_list, $search_target = "title", $search_keyword, $page=1, $list_count = 20)
	{
		$oTrackbackModel = getAdminModel('trackback');
		if(!$oTrackbackModel) return new BaseObject();

		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srl_list ? explode(',', $module_srls_list) : array();
		}
		$module_srls_list = array_map('intval', $module_srls_list);
		$accessible_modules = array_keys(getModel('module')->getAccessibleModuleList());

		$args = new stdClass();
		if($target == 'exclude')
		{
			$args->module_srl = $accessible_modules;
			$args->exclude_module_srl = $module_srls_list;
		}
		else
		{
			$args->module_srl = array_intersect($module_srls_list, $accessible_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;
		
		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = $search_target;
		$args->search_keyword = $search_keyword;
		$args->sort_index = 'list_order';
		$args->order_type = 'asc';
		// Get a list of documents
		$output = $oTrackbackModel->getTotalTrackbackList($args);
		if(!$output->toBool()|| !$output->data) return $output;
		return $output;
	}

	/**
	 * Search file
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 * @param string $direct_download Y or N
	 *
	 * @return Object output file list
	 */
	function _getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, $direct_download = 'Y')
	{
		if(!is_array($module_srls_list))
		{
			$module_srls_list = $module_srl_list ? explode(',', $module_srls_list) : array();
		}
		$accessible_modules = array_keys(getModel('module')->getAccessibleModuleList());

		$args = new stdClass();
		if($target == 'exclude')
		{
			$args->module_srl = $accessible_modules;
			$args->exclude_module_srl = array_diff($module_srls_list, $accessible_modules);
		}
		else
		{
			$args->module_srl = array_intersect($module_srls_list, $accessible_modules);
			$args->exclude_module_srl = array(0); // exclude 'trash'
		}
		$args->module_srl[] = 0;
		
		$args->page = $page;
		$args->list_count = $list_count;
		$args->page_count = 10;
		$args->search_target = 'filename';
		$args->search_keyword = $search_keyword;
		$args->sort_index = 'files.file_srl';
		$args->order_type = 'desc';
		$args->isvalid = 'Y';
		$args->direct_download = $direct_download=='Y'?'Y':'N';
		$args->exclude_secret = 'Y';
		// Get a list of documents
		$oFileAdminModel = getAdminModel('file');
		$output = $oFileAdminModel->getFileList($args);
		if(!$output->toBool() || !$output->data) return $output;

		$list = array();
		foreach($output->data as $key => $val)
		{
			$obj = new stdClass;
			$obj->filename = $val->source_filename;
			$obj->download_count = $val->download_count;
			if(substr($val->download_url,0,2)=='./') $val->download_url = substr($val->download_url,2);
			$obj->download_url = Context::getRequestUri().$val->download_url;
			$obj->target_srl = $val->upload_target_srl;
			$obj->file_size = $val->file_size;
			// Images
			if(preg_match('/\.(jpg|jpeg|gif|png)$/i', $val->source_filename))
			{
				$obj->type = 'image';

				$thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($val->file_srl, 3));
				if(!is_dir($thumbnail_path)) FileHandler::makeDir($thumbnail_path);
				$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, 120, 120, 'crop');
				$thumbnail_url  = Context::getRequestUri().$thumbnail_file;
				if(!file_exists($thumbnail_file)) FileHandler::createImageFile($val->uploaded_filename, $thumbnail_file, 120, 120, 'jpg', 'crop');
				$obj->src = sprintf('<img src="%s" alt="%s" width="%d" height="%d" class="thumb" />', $thumbnail_url, htmlspecialchars($obj->filename, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), 120, 120);
				// Videos
			}
			elseif(Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($val->source_filename))
			{
				$obj->type = 'multimedia';
				$obj->src = sprintf('<script>displayMultimedia("%s",80,80);</script>', $val->uploaded_filename);
				// Others
			}
			else
			{
				$obj->type = 'binary';
				$obj->src = '';
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
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function getImages($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20)
	{
		return $this->_getFiles($target, $module_srls_list, $search_keyword, $page, $list_count);
	}

	/**
	 * Search for attachments. call function _getFiles().
	 *
	 * @param string $target choose target. exclude or include for $module_srls_list
	 * @param string $module_srls_list module_srl list to string type. ef - 102842,59392,102038
	 * @param string $search_keyword Keyword
	 * @param integer $page page of page navigation
	 * @param integer $list_count list count of page navigation
	 *
	 * @return Object
	 */
	function getFiles($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20)
	{
		return $this->_getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, 'N');
	}
}
/* End of file integration_search.model.php */
/* Location: ./modules/integration_search/integration_search.model.php */
