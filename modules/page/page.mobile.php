<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
require_once(_XE_PATH_.'modules/page/page.view.php');

class pageMobile extends pageView
{
	function init()
	{
		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');

		switch($this->module_info->page_type)
		{
			case 'WIDGET' :
				{
					$this->cache_file = sprintf("%sfiles/cache/page/%d.%s.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType(), Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval);
					break;
				}
			case 'OUTSIDE' :
				{
					$this->cache_file = sprintf("./files/cache/opage/%d.%s.m.cache.php", $this->module_info->module_srl, Context::getSslStatus()); 
					$this->interval = (int)($this->module_info->page_caching_interval);
					$this->path = $this->module_info->mpath;
					break;
				}
		}
	}

	function dispPageIndex()
	{
		// Variables used in the template Context:: set()
		if($this->module_srl) Context::set('module_srl',$this->module_srl);

		$page_type_name = strtolower($this->module_info->page_type);
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		if (method_exists($this, $method)) $page_content = $this->{$method}();
		else return new Object(-1, sprintf('%s method is not exists', $method));

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);

		$this->setTemplateFile('mobile');
	}

	function _getWidgetContent()
	{
		// Arrange a widget ryeolro
		if($this->module_info->mcontent)
		{
			$cache_file = sprintf("%sfiles/cache/page/%d.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
			$interval = (int)($this->module_info->page_caching_interval);
			if($interval>0)
			{
				if(!file_exists($cache_file) || filesize($cache_file) < 1)
				{
					$mtime = 0;
				}
				else
				{
					$mtime = filemtime($cache_file);
				}

				if($mtime + $interval*60 > $_SERVER['REQUEST_TIME']) 
				{
					$page_content = FileHandler::readFile($cache_file); 
					$page_content = preg_replace('@<\!--#Meta:@', '<!--Meta:', $page_content);
				} 
				else 
				{
					$oWidgetController = getController('widget');
					$page_content = $oWidgetController->transWidgetCode($this->module_info->mcontent);
					FileHandler::writeFile($cache_file, $page_content);
				}
			} 
			else 
			{
				if(file_exists($cache_file))
				{
					FileHandler::removeFile($cache_file);
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
		$oDocument = $oDocumentModel->getDocument(0, true);

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

		if($this->module_info->mskin)
		{
			$templatePath = (sprintf($this->module_path.'m.skins/%s', $this->module_info->mskin));
		}
		else
		{
			$templatePath = ($this->module_path.'m.skins/default');
		}

		$page_content = $oTemplate->compile($templatePath, 'mobile');

		return $page_content;
	}

	function _getOutsideContent()
	{
		// check if it is http or internal file
		if($this->path)
		{
			if(preg_match("/^([a-z]+):\/\//i",$this->path)) $content = $this->getHtmlPage($this->path, $this->interval, $this->cache_file);
			else $content = $this->executeFile($this->path, $this->interval, $this->cache_file);
		}

		return $content;
	}
}
/* End of file page.mobile.php */
/* Location: ./modules/page/page.mobile.php */
