<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class PageMobile extends PageView
{
	function _getArticleContent()
	{
		$oDocument = DocumentModel::getDocument(0);
		if($this->module_info->mdocument_srl ?? 0)
		{
			$document_srl = $this->module_info->mdocument_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		if(!$oDocument->isExists())
		{
			$document_srl = $this->module_info->document_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		Context::set('oDocument', $oDocument);

		$oTemplate = Rhymix\Framework\Template::getInstance();
		$template_path = $this->getTemplatePath();
		if (preg_match('!/skins/!', $template_path))
		{
			$page_content = $oTemplate->compile($this->getTemplatePath(), 'content');
		}
		else
		{
			$page_content = $oTemplate->compile($this->getTemplatePath(), 'mobile');
		}

		return $page_content;
	}
}
/* End of file page.mobile.php */
/* Location: ./modules/page/page.mobile.php */
