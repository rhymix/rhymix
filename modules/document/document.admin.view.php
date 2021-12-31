<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * documentAdminView class
 * Document admin view of the module class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class documentAdminView extends document
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		// check current location in admin menu
		$oModuleModel = getModel('module');
		$info = $oModuleModel->getModuleActionXml('document');
		foreach($info->menu AS $key => $menu)
		{
			if(in_array($this->act, $menu->acts))
			{
				Context::set('currentMenu', $key);
				break;
			}
		}
	}

	/**
	 * Display a list(administrative)
	 * @return void
	 */
	function dispDocumentAdminList()
	{
		// option to get a list
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation
		
		$args->search_target = Context::get('search_target'); // /< search (title, contents ...)
		$args->search_keyword = Context::get('search_keyword'); // /< keyword to search
		if ($args->search_target === 'member_srl')
		{
			$logged_info = Context::get('logged_info');
			if ($logged_info->is_admin === 'Y' || intval($logged_info->member_srl) === intval($args->search_keyword))
			{
				$args->member_srl = array(intval($args->search_keyword), intval($args->search_keyword) * -1);
				unset($args->search_target, $args->search_keyword);
			}
		}

		$args->sort_index = 'list_order'; // /< sorting value
		$args->module_srl = Context::get('module_srl');
		$args->statusList = array($this->getConfigStatus('public'), $this->getConfigStatus('secret'), $this->getConfigStatus('temp'));
		
		// get a list
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'category_srl', 'member_srl', 'title', 'nick_name', 'comment_count', 'trackback_count', 'readed_count', 'voted_count', 'blamed_count', 'regdate', 'ipaddress', 'status');
		$output = $oDocumentModel->getDocumentList($args, false, true, $columnList);

		// get Status name list
		$statusNameList = $oDocumentModel->getStatusNameList();

		// Set values of document_model::getDocumentList() objects for a template
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('status_name_list', $statusNameList);
		Context::set('page_navigation', $output->page_navigation);

		// set a search option used in the template
		$count_search_option = count($this->search_option);
		for($i=0;$i<$count_search_option;$i++)
		{
			$search_option[$this->search_option[$i]] = lang($this->search_option[$i]);
		}
		Context::set('search_option', $search_option);

		// Module List
		$oModuleModel = getModel('module');
		$module_list = array();
		$mod_srls = array();
		foreach($output->data as $oDocument)
		{
			$mod_srls[] = $oDocument->get('module_srl');
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
		foreach($output->data as $oDocument)
		{
			if($oDocument->get('member_srl') < 0)
			{
				$anonymous_member_srls[] = abs($oDocument->get('member_srl'));
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

		// Specify a template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('document_list');
	}

	/**
	 * Set a document module
	 * @return void
	 */
	function dispDocumentAdminConfig()
	{
		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('config',$config);

		$oModuleModel = getModel('module');
		$pcIconSkinList = $oModuleModel->getSkins($this->module_path . 'tpl', 'icons');
		$mobileIconSkinList = $oModuleModel->getSkins($this->module_path . 'tpl', 'micons');

		Context::set('pcIconSkinList', $pcIconSkinList);
		Context::set('mobileIconSkinList', $mobileIconSkinList);

		// Set the template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('document_config');
	}

	/**
	 * Display a report list on the admin page
	 * @return void
	 */
	function dispDocumentAdminDeclared()
	{
		// get Status name list
		$oDocumentModel = getModel('document');
		$statusNameList = $oDocumentModel->getStatusNameList();

		// option for a list
		$args = new stdClass();
		$args->page = intval(Context::get('page')) ?: 1; // /< Page
		$args->list_count = 20; // /< the number of posts to display on a single page
		$args->page_count = 10; // /< the number of pages that appear in the page navigation
		$args->order_type = strtolower(Context::get('order_type')) === 'asc' ? 'asc' : 'desc';
		
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
			$declared_output = executeQueryArray('document.getDeclaredLatest', $args);
			if ($declared_output->data && count($declared_output->data))
			{
				$args->document_srls = array_map(function($item) { return $item->document_srl; }, $declared_output->data);
				unset($args->page);
				$documents = executeQueryArray('document.getDocuments', $args);
				$document_list = array();
				foreach ($declared_output->data as $key => $declared_info)
				{
					foreach ($documents->data as $document)
					{
						if ($document->document_srl == $declared_info->document_srl)
						{
							$document->declared_count = $declared_info->declared_count;
							$document->latest_declared = $declared_info->latest_declared;
							$document_list[$key] = new documentItem();
							$document_list[$key]->setAttribute($document);
							break;
						}
					}
				}
				$declared_output->data = $document_list;
			}
		}
		else
		{
			if ($sort_index === 'declared_count')
			{
				$args->sort_index = 'document_declared.declared_count';
			}
			else
			{
				$args->sort_index = 'documents.regdate';
			}
			$declared_output = executeQueryArray('document.getDeclaredList', $args);
			if ($declared_output->data && count($declared_output->data))
			{
				$args->document_srls = array_map(function($item) { return $item->document_srl; }, $declared_output->data);
				$args->list_count = 0; unset($args->page);
				$declared_latest = executeQueryArray('document.getDeclaredLatest', $args);
				$document_list = array();
				foreach ($declared_output->data as $key => $document)
				{
					foreach ($declared_latest->data as $key => $declared_info)
					{
						if ($document->document_srl == $declared_info->document_srl)
						{
							$document->declared_count = $declared_info->declared_count;
							$document->latest_declared = $declared_info->latest_declared;
							$document_list[$key] = new documentItem();
							$document_list[$key]->setAttribute($document);
						}
					}
				}
				$declared_output->data = $document_list;
			}
		}

		// Set values of document_model::getDocumentList() objects for a template
		Context::set('total_count', $declared_output->total_count);
		Context::set('total_page', $declared_output->total_page);
		Context::set('page', $declared_output->page);
		Context::set('document_list', $declared_output->data);
		Context::set('page_navigation', $declared_output->page_navigation);
		Context::set('status_name_list', $statusNameList);
		// Set the template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('declared_list');
	}

	/**
	 * Display a reported post and log of reporting
	 * @return void
	 */
	function dispDocumentAdminDeclaredLogByDocumentSrl()
	{
		// option for a list
		$args = new stdClass;
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 10; // /< the number of pages that appear in the page navigation
		$args->document_srl = intval(Context::get('target_srl'));


		// get Status name list
		$oDocumentModel = getModel('document');
		$oMemberModel = getModel('member');
		$oDocument = $oDocumentModel->getDocument($args->document_srl);

		$declared_output = executeQuery('document.getDeclaredLogByDocumentSrl', $args);
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
		Context::set('declared_document', $oDocument);
		Context::set('page_navigation', $declared_output->page_navigation);

		// Set the template
		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('declared_log');
	}

	/**
	 * Display a alias list on the admin page
	 * @return void
	 */
	function dispDocumentAdminAlias()
	{
		$args = new stdClass();
		$args->document_srl = Context::get('document_srl');
		if(!$args->document_srl) return $this->dispDocumentAdminList();

		$oModel = getModel('document');
		$oDocument = $oModel->getDocument($args->document_srl);
		if(!$oDocument->isExists()) return $this->dispDocumentAdminList();
		Context::set('oDocument', $oDocument);

		$output = executeQueryArray('document.getAliases', $args);
		if(!$output->data)
		{
			$aliases = array();
		}
		else
		{
			$aliases = $output->data; 
		}

		Context::set('aliases', $aliases);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('document_alias');
	}

	/**
	 * Display a trash list on the admin page
	 * @return void
	 */
	function dispDocumentAdminTrashList()
	{
		// options for a list
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 10; // /< the number of pages that appear in the page navigation

		$args->sort_index = 'list_order'; // /< sorting values
		$args->order_type = 'desc'; // /< sorting values by order

		$args->module_srl = Context::get('module_srl');

		// get a list
		$oDocumentAdminModel = getAdminModel('document');
		$output = $oDocumentAdminModel->getDocumentTrashList($args);

		// Set values of document_admin_model::getDocumentTrashList() objects for a template
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		// set the template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('document_trash_list');
	}
}
/* End of file document.admin.view.php */
/* Location: ./modules/document/document.admin.view.php */
