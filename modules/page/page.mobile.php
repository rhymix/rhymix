<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class pageMobile extends pageView
{
	function _getArticleContent()
	{
		$oTemplate = &TemplateHandler::getInstance();

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0);

		if($this->module_info->mdocument_srl)
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

		if($this->module_info->mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
			if(!is_dir($template_path)||!$this->module_info->skin)
			{
				$template_path = sprintf("%sskins/%s/",$this->module_path, 'default');
			}
			$page_content = $oTemplate->compile($template_path, 'content');
		}
		else
		{
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
			if(!is_dir($template_path)||!$this->module_info->mskin)
			{
				$template_path = sprintf("%sm.skins/%s/",$this->module_path, 'default');
			}
			$page_content = $oTemplate->compile($template_path, 'mobile');
		}

		return $page_content;
	}
}
/* End of file page.mobile.php */
/* Location: ./modules/page/page.mobile.php */
