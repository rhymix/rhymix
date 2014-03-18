<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentAdminView class
 * admin view class of the comment module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentAdminView extends comment
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Display the list(for administrators)
	 * @return void
	 */
	function dispCommentAdminList()
	{
		// option to get a list
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // / the number of postings to appear on a single page
		$args->page_count = 5; // / the number of pages to appear on the page navigation

		$args->sort_index = 'list_order'; // /< Sorting values

		$args->module_srl = Context::get('module_srl');
		/*
		  $search_target = Context::get('search_target');
		  $search_keyword = Context::get('search_keyword');
		  if ($search_target == 'is_published' && $search_keyword == 'Y')
		  {
		  $args->status = 1;
		  }
		  if ($search_target == 'is_published' && $search_keyword == 'N')
		  {
		  $args->status = 0;
		  }
		 */

		// get a list by using comment->getCommentList. 
		$oCommentModel = getModel('comment');
		$secretNameList = $oCommentModel->getSecretNameList();
		$columnList = array('comment_srl', 'document_srl','module_srl','is_secret', 'status', 'content', 'comments.member_srl', 'comments.nick_name', 'comments.regdate', 'ipaddress', 'voted_count', 'blamed_count');
		$output = $oCommentModel->getTotalCommentList($args, $columnList);

		// $modules = $oCommentModel->getDistinctModules();
		// $modules_list = $modules;

		// set values in the return object of comment_model:: getTotalCommentList() in order to use a template.
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('comment_list', $output->data);
		// Context::set('modules_list', $modules_list);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('secret_name_list', $secretNameList);

		$oModuleModel = getModel('module');
		$module_list = array();
		$mod_srls = array();
		foreach($output->data as $val)
		{
			$mod_srls[] = $val->module_srl;
		}
		$mod_srls = array_unique($mod_srls);
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
		Context::set('module_list', $module_list);

		// set the template 
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('comment_list');
	}

	/**
	 * Show the blacklist of comments in the admin page
	 * @return void
	 */
	function dispCommentAdminDeclared()
	{
		// option to get a blacklist
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of comment postings to appear on a single page
		$args->page_count = 10; // /< the number of pages to appear on the page navigation

		$args->sort_index = 'comment_declared.declared_count'; // /< sorting values
		$args->order_type = 'desc'; // /< sorted value
		// get a list
		$declared_output = executeQuery('comment.getDeclaredList', $args);
		$oCommentModel = getModel('comment');

		if($declared_output->data && count($declared_output->data))
		{
			$comment_list = array();

			foreach($declared_output->data as $key => $comment)
			{
				$comment_list[$key] = new commentItem();
				$comment_list[$key]->setAttribute($comment);
			}
			$declared_output->data = $comment_list;
		}

		$secretNameList = $oCommentModel->getSecretNameList();

		// set values in the return object of comment_model:: getCommentList() in order to use a template.
		Context::set('total_count', $declared_output->total_count);
		Context::set('total_page', $declared_output->total_page);
		Context::set('page', $declared_output->page);
		Context::set('comment_list', $declared_output->data);
		Context::set('page_navigation', $declared_output->page_navigation);
		Context::set('secret_name_list', $secretNameList);
		// set the template
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('declared_list');
	}

}
/* End of file comment.admin.view.php */
/* Location: ./modules/comment/comment.admin.view.php */
