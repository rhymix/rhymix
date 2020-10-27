<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * documentView class
 * View class of the module document
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class documentView extends document
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Document printing
	 * I make it out to find the geulman;;
	 * @return void|Object
	 */
	function dispDocumentPrint()
	{
		// Bring a list of variables needed to implement
		$document_srl = Context::get('document_srl');

		// Creates an object for displaying the selected document
		$oDocument = DocumentModel::getDocument($document_srl, $this->grant->manager);
		if(!$oDocument->isExists()) throw new Rhymix\Framework\Exceptions\TargetNotFound;
		// Check permissions
		if(!$oDocument->isAccessible()) throw new Rhymix\Framework\Exceptions\NotPermitted;
		// Information setting module
		//Context::set('module_info', $module_info);	//module_info not use in UI
		// Browser title settings
		Context::setBrowserTitle($oDocument->getTitleText());
		Context::set('oDocument', $oDocument);

		Context::set('layout','none');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('print_page');
	}

	/**
	 * Preview
	 * @return void
	 */
	function dispDocumentPreview()
	{
		if(!checkCSRF())
		{
			throw new Rhymix\Framework\Exceptions\SecurityViolation;
		} 
		
		$content = Context::get('content');
		
		if(Context::get('logged_info')->is_admin != 'Y')
		{
			$content = removeHackTag($content);
		}
		
		// Editor converter
		$obj = new stdClass;
		$obj->content = $content;
		$obj->module_srl = ModuleModel::getModuleInfoByMid(Context::get('mid'))->module_srl;
		$content = getModel('editor')->converter($obj, 'document');
		$content = sprintf('<div class="document_0_%d rhymix_content xe_content">%s</div>', Context::get('logged_info')->member_srl, $content);
		Context::set('content', $content);
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('preview_page');
		Context::set('layout', 'none');
	}

	/**
	 * Selected by the administrator for the document management
	 * @return void|Object
	 */
	function dispDocumentManageDocument()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;
		// Taken from a list of selected sessions
		$document_srl_list = array();
		$flag_list = $_SESSION['document_management'];
		if(count($flag_list))
		{
			foreach($flag_list as $key => $val)
			{
				if(!is_bool($val)) continue;
				$document_srl_list[] = $key;
			}
		}

		if(count($document_srl_list))
		{
			$document_list = DocumentModel::getDocuments($document_srl_list, $this->grant->is_admin);
			Context::set('document_list', $document_list);
		}
		else
		{
			Context::set('document_list', array());
		}

		$module_srl = intval(Context::get('module_srl'));
		Context::set('module_srl',$module_srl);
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		Context::set('mid',$module_info->mid);
		Context::set('browser_title',$module_info->browser_title);

		// Select Pop-up layout
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('checked_list');
	}

	/**
	 * Trigger method.
	 * Additional information realte to document setting
	 * @param string $obj
	 * @return Object
	 */
	function triggerDispDocumentAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			// Get information of the current module
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return new BaseObject();
		}

		if($current_module_srl)
		{
			$document_config = ModuleModel::getModulePartConfig('document', $current_module_srl);
		}
		if(!$document_config)
		{
			$document_config = new stdClass();
		}
		if(!isset($document_config->use_history)) $document_config->use_history = 'N';
		Context::set('document_config', $document_config);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'document_module_config');
		$obj = $tpl . $obj;

		return new BaseObject();
	}

	/**
	 * Document temp saved list
	 * @return void
	 */
	function dispTempSavedList()
	{
		$this->setLayoutFile('popup_layout');

		// A message appears if the user is not logged-in
		if(!$this->user->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}
		// Get the saved document (module_srl is set to member_srl instead)
		$logged_info = Context::get('logged_info');
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->statusList = array($this->getConfigStatus('temp'));
		$args->page = (int)Context::get('page');
		$args->list_count = 10;

		$output = DocumentModel::getDocumentList($args, true);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('document_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('saved_list_popup');
	}

	/**
	 * Report an improper post
	 * @return void
	 */
	function dispDocumentDeclare()
	{
		$this->setLayoutFile('popup_layout');
		$document_srl = Context::get('target_srl');

		// A message appears if the user is not logged-in
		if(!$this->user->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Creates an object for displaying the selected document
		$oDocument = DocumentModel::getDocument($document_srl, $this->grant->manager, FALSE);
		if(!$oDocument->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		// Check permissions
		if(!$oDocument->isAccessible())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Browser title settings
		Context::set('target_document', $oDocument);

		Context::set('target_srl', $document_srl);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('declare_document');
	}
}
/* End of file document.view.php */
/* Location: ./modules/document/document.view.php */
