<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * importerAdminView class
 * admin view class of the importer module 
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/importer
 * @version 0.1
 */
class importerAdminView extends importer
{
	/**
	 * Initialization
	 * Importer module is divided by general use and administrative use \n
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Display a form to upload the xml file
	 * @return void
	 */
	function dispImporterAdminContent()
	{
		$this->setTemplatePath($this->module_path.'tpl');

		$source_type = Context::get('source_type');
		switch($source_type)
		{
			case 'member' : 
				$template_filename = "member";
				break;
			case 'ttxml' : 
				$oModuleModel = getModel('module');
				//$mid_list = $oModuleModel->getMidList();	//perhaps mid_list variables not use
				//Context::set('mid_list', $mid_list);

				$template_filename = "ttxml";
				break;
			case 'module' : 
				$oModuleModel = getModel('module');
				//$mid_list = $oModuleModel->getMidList();	//perhaps mid_list variables not use
				//Context::set('mid_list', $mid_list);

				$template_filename = "module";
				break;
			case 'message' : 
				$template_filename = "message";
				break;
			case 'sync' : 
				$template_filename = "sync";
				break;
			default : 
				$template_filename = "index";
				break;
		}

		$this->setTemplateFile($template_filename);
	}

	/**
	 * Display a form to upload the xml file
	 * @return void
	 */
	function dispImporterAdminImportForm()
	{
		$oDocumentModel = getModel('document');	//for document lang use in this page

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('index');
	}

}
/* End of file importer.admin.view.php */
/* Location: ./modules/importer/importer.admin.view.php */
