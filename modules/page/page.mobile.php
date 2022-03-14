<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class pageMobile extends pageView
{
	function dispPageIndex()
	{
		// Variables used in the template Context:: set()
		if($this->module_srl) Context::set('module_srl',$this->module_srl);

		$page_type_name = strtolower($this->module_info->page_type);
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		if (method_exists($this, $method))
		{
			$page_content = $this->{$method}();
		}
		else
		{
			throw new Rhymix\Framework\Exception(sprintf('%s method is not exists', $method));
		}

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);
		
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('mobile');
	}

	function _getWidgetContent()
	{
		// Arrange a widget ryeolro
		if($this->module_info->mcontent)
		{
			if($this->interval>0)
			{
				if(!file_exists($this->cache_file) || filesize($this->cache_file) < 1)
				{
					$mtime = 0;
				}
				else
				{
					$mtime = filemtime($this->cache_file);
				}

				if($mtime + $this->interval*60 > $_SERVER['REQUEST_TIME']) 
				{
					$page_content = FileHandler::readFile($this->cache_file); 
					$page_content = str_replace('<!--#Meta:', '<!--Meta:', $page_content);
				} 
				else 
				{
					$oWidgetController = getController('widget');
					$page_content = $oWidgetController->transWidgetCode($this->module_info->mcontent);
					FileHandler::writeFile($this->cache_file, $page_content);
				}
			} 
			else 
			{
				if(file_exists($this->cache_file))
				{
					FileHandler::removeFile($this->cache_file);
				}
				$page_content = $this->module_info->mcontent;
			}
		}
		else
		{
			$page_content = $this->module_info->content;
		}

		return $page_content;
	}

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
