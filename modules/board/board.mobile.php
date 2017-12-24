<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class boardMobile extends boardView
{
	function init()
	{
		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_srl', 'comment_srl', 'vid', 'mid', 'page', 'category', 'search_target', 'search_keyword', 'sort_index', 'order_type', 'trackback_srl');

		if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
		if($this->module_info->mobile_list_count) $this->list_count = $this->module_info->mobile_list_count;
		if($this->module_info->search_list_count) $this->search_list_count = $this->module_info->search_list_count;
		if($this->module_info->mobile_search_list_count) $this->search_list_count = $this->module_info->mobile_search_list_count;
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
		if($this->module_info->consultation == 'Y' && !$this->grant->manager && !$this->grant->consultation_read)
		{
			$this->consultation = true;
			if(!Context::get('is_logged')) $this->grant->list = $this->grant->write_document = $this->grant->write_comment = $this->grant->view = false;
		} else {
			$this->consultation = false;
		}

		$oDocumentModel = getModel('document');
		$extra_keys = $oDocumentModel->getExtraKeys($this->module_info->module_srl);
		Context::set('extra_keys', $extra_keys);

		if($this->module_info->mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
			if(!is_dir($template_path)||!$this->module_info->skin)
			{
				$template_path = sprintf("%sskins/%s/",$this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
			if(!is_dir($template_path)||!$this->module_info->mskin)
			{
				$template_path = sprintf("%sm.skins/%s/",$this->module_path, 'default');
			}
		}
		$this->setTemplatePath($template_path);
		Context::addJsFilter($this->module_path.'tpl/filter', 'input_password.xml');
	}

	function getBoardCommentPage()
	{
		$this->dispBoardCommentPage();
		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->getTemplatePath(), 'comment.html');
		$this->add('html', $html);
	}

	function dispBoardMessage($msg_code)
	{
		$msg = lang($msg_code);
		$oMessageObject = &ModuleHandler::getModuleInstance('message','mobile');
		$oMessageObject->setError(-1);
		$oMessageObject->setMessage($msg);
		$oMessageObject->dispMessage();

		$this->setTemplatePath($oMessageObject->getTemplatePath());
		$this->setTemplateFile($oMessageObject->getTemplateFile());
	}
}
