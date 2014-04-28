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

		$args->sort_index = 'list_order'; // /< sorting value

		$args->module_srl = Context::get('module_srl');

		// get a list
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'title', 'member_srl', 'nick_name', 'readed_count', 'voted_count', 'blamed_count', 'regdate', 'ipaddress', 'status');
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
			$search_option[$this->search_option[$i]] = Context::getLang($this->search_option[$i]);
		}
		Context::set('search_option', $search_option);

		$oModuleModel = getModel('module');
		$module_list = array();
		$mod_srls = array();
		foreach($output->data as $oDocument)
		{
			$mod_srls[] = $oDocument->get('module_srl');
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
		// option for a list
		$args =new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 10; // /< the number of pages that appear in the page navigation

		$args->sort_index = 'document_declared.declared_count'; // /< sorting values
		$args->order_type = 'desc'; // /< sorting values by order

		// get Status name list
		$oDocumentModel = getModel('document');
		$statusNameList = $oDocumentModel->getStatusNameList();

		// get a list
		$declared_output = executeQuery('document.getDeclaredList', $args);
		if($declared_output->data && count($declared_output->data))
		{
			$document_list = array();

			foreach($declared_output->data as $key => $document)
			{
				$document_list[$key] = new documentItem();
				$document_list[$key]->setAttribute($document);
			}
			$declared_output->data = $document_list;
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
	 * Display a alias list on the admin page
	 * @return void
	 */
	function dispDocumentAdminAlias()
	{
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
