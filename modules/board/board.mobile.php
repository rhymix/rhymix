<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once(_XE_PATH_.'modules/board/board.view.php');

class boardMobile extends boardView
{
	function init()
	{
		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_srl', 'comment_srl', 'vid', 'mid', 'page', 'category', 'search_target', 'search_keyword', 'sort_index', 'order_type', 'trackback_srl');

		if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
		if($this->module_info->mobile_list_count) $this->list_count = $this->module_info->mobile_list_count;
		if($this->module_info->search_list_count) $this->search_list_count = $this->module_info->search_list_count;
		if($this->module_info->mobile_search_list_count) $this->list_count = $this->module_info->mobile_search_list_count;
		if($this->module_info->page_count) $this->page_count = $this->module_info->page_count;
		if($this->module_info->mobile_page_count) $this->page_count = $this->module_info->mobile_page_count;
		$this->except_notice = $this->module_info->except_notice == 'N' ? false : true;

		// $this->_getStatusNameListecret option backward compatibility
		$oDocumentModel = getModel('document');

		$statusList = $this->_getStatusNameList($oDocumentModel);
		if(isset($statusList['SECRET']))
		{
			$this->module_info->secret = 'Y';
		}

		// use_category <=1.5.x, hide_category >=1.7.x
		$count_category = count($oDocumentModel->getCategoryList($this->module_info->module_srl));
		if($count_category)
		{
			if($this->module_info->hide_category)
			{
				$this->module_info->use_category = ($this->module_info->hide_category == 'Y') ? 'N' : 'Y';
			}
			else if($this->module_info->use_category)
			{
				$this->module_info->hide_category = ($this->module_info->use_category == 'Y') ? 'N' : 'Y';
			}
			else
			{
				$this->module_info->hide_category = 'N';
				$this->module_info->use_category = 'Y';
			}
		}
		else
		{
			$this->module_info->hide_category = 'Y';
			$this->module_info->use_category = 'N';
		}

		/**
		 * check the consultation function, if the user is admin then swich off consultation function
		 * if the user is not logged, then disppear write document/write comment./ view document
		 **/
		if($this->module_info->consultation == 'Y' && !$this->grant->manager)
		{
			$this->consultation = true;
			if(!Context::get('is_logged')) $this->grant->list = $this->grant->write_document = $this->grant->write_comment = $this->grant->view = false;
		} else {
			$this->consultation = false;
		}

		$oDocumentModel = getModel('document');
		$extra_keys = $oDocumentModel->getExtraKeys($this->module_info->module_srl);
		Context::set('extra_keys', $extra_keys);

		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin)
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
		Context::addJsFilter($this->module_path.'tpl/filter', 'input_password.xml');
	}

	function dispBoardCategory()
	{
		$this->dispBoardCategoryList();
		$category_list = Context::get('category_list');
		$this->setTemplateFile('category.html');
	}

	function getBoardCommentPage()
	{
		$document_srl = Context::get('document_srl');
		$oDocumentModel =& getModel('document');
		if(!$document_srl)
		{
			return new Object(-1, "msg_invalid_request");
		}
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists())
		{
			return new Object(-1, "msg_invalid_request");
		}
		Context::set('oDocument', $oDocument);
		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->getTemplatePath(), "comment.html");
		$this->add("html", $html);
	}

	function dispBoardMessage($msg_code)
	{
		$msg = Context::getLang($msg_code);
		$oMessageObject = &ModuleHandler::getModuleInstance('message','mobile');
		$oMessageObject->setError(-1);
		$oMessageObject->setMessage($msg);
		$oMessageObject->dispMessage();

		$this->setTemplatePath($oMessageObject->getTemplatePath());
		$this->setTemplateFile($oMessageObject->getTemplateFile());
	}
}
