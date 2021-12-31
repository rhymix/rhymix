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
		$output = $oCommentModel->getTotalCommentList($args);

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

		// Module List
		$oModuleModel = getModel('module');
		$module_list = array();
		$mod_srls = array();
		foreach($output->data as $val)
		{
			$mod_srls[] = $val->module_srl;
		}
		$mod_srls = array_unique($mod_srls);
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
		
		// Get anonymous nicknames
		$anonymous_member_srls = array();
		foreach($output->data as $val)
		{
			if($val->get('member_srl') < 0)
			{
				$anonymous_member_srls[] = abs($val->get('member_srl'));
			}
		}
		if($anonymous_member_srls)
		{
			$member_args = new stdClass();
			$member_args->member_srl = $anonymous_member_srls;
			$member_output = executeQueryArray('member.getMembers', $member_args);
			if($member_output)
			{
				$member_nick_neme = array();
				foreach($member_output->data as $member)
				{
					$member_nick_neme[$member->member_srl] = $member->nick_name;
				}
			}
		}
		Context::set('member_nick_name', $member_nick_neme);
		
		$security = new Security();
		$security->encodeHTML('search_target', 'search_keyword');

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
		$args->order_type = 'desc'; // /< sorted value
		
		// select sort method
		$sort_index = Context::get('sort_index');
		if (!in_array($sort_index, array('declared_latest', 'declared_count', 'regdate')))
		{
			$sort_index = 'declared_latest';
		}
		Context::set('sort_index', $sort_index);
		
		// get latest declared list
		if ($sort_index === 'declared_latest')
		{
			$declared_output = executeQueryArray('comment.getDeclaredLatest', $args);
			if ($declared_output->data && count($declared_output->data))
			{
				$args->comment_srls = array_map(function($item) { return $item->comment_srl; }, $declared_output->data);
				$comments = executeQueryArray('comment.getComments', $args);
				$comment_list = array();
				foreach ($declared_output->data as $key => $declared_info)
				{
					foreach ($comments->data as $comment)
					{
						if ($comment->comment_srl == $declared_info->comment_srl)
						{
							$comment->declared_count = $declared_info->declared_count;
							$comment->latest_declared = $declared_info->latest_declared;
							$comment_list[$key] = new commentItem();
							$comment_list[$key]->setAttribute($comment);
							break;
						}
					}
				}
				$declared_output->data = $comment_list;
			}
		}
		else
		{
			if ($sort_index === 'declared_count')
			{
				$args->sort_index = 'comment_declared.declared_count';
			}
			else
			{
				$args->sort_index = 'comments.regdate';
			}
			$declared_output = executeQueryArray('comment.getDeclaredList', $args);
			if ($declared_output->data && count($declared_output->data))
			{
				$args->comment_srls = array_map(function($item) { return $item->comment_srl; }, $declared_output->data);
				$args->list_count = 0; unset($args->page);
				$declared_latest = executeQueryArray('comment.getDeclaredLatest', $args);
				$comment_list = array();
				foreach ($declared_output->data as $key => $comment)
				{
					foreach ($declared_latest->data as $key => $declared_info)
					{
						if ($comment->comment_srl == $declared_info->comment_srl)
						{
							$comment->declared_count = $declared_info->declared_count;
							$comment->latest_declared = $declared_info->latest_declared;
							$comment_list[$key] = new commentItem();
							$comment_list[$key]->setAttribute($comment);
						}
					}
				}
				$declared_output->data = $comment_list;
			}
		}

		$oCommentModel = getModel('comment');
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

	/**
	 * Display a reported comment and log of reporting
	 * @return void
	 */
	function dispCommentAdminDeclaredLogByCommentSrl()
	{
		// option for a list
		$args = new stdClass;
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 10; // /< the number of pages that appear in the page navigation
		$args->comment_srl = intval(Context::get('target_srl'));


		// get Status name list
		$oCommentModel = getModel('comment');
		$oMemberModel = getModel('member');
		$oComment = $oCommentModel->getComment($args->comment_srl);

		$declared_output = executeQuery('comment.getDeclaredLogByCommentSrl', $args);
		if($declared_output->data && count($declared_output->data))
		{
			$reporter_list = array();

			foreach($declared_output->data as $key => $log)
			{
				$reporter_list[$log->member_srl] = $oMemberModel->getMemberInfoByMemberSrl($log->member_srl);
			}
		}

		// Set values of document_model::getDocumentList() objects for a template
		Context::set('total_count', $declared_output->total_count);
		Context::set('total_page', $declared_output->total_page);
		Context::set('page', $declared_output->page);
		Context::set('declare_log', $declared_output->data);
		Context::set('reporter_list', $reporter_list);
		Context::set('declared_comment', $oComment);
		Context::set('page_navigation', $declared_output->page_navigation);

		// Set the template
		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('declared_log');
	}
}
/* End of file comment.admin.view.php */
/* Location: ./modules/comment/comment.admin.view.php */
