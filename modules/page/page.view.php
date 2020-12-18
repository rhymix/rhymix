<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pageView
 * @author NAVER (developers@xpressengine.com)
 * @brief page view class of the module
 */
class pageView extends page
{
	var $module_srl = 0;
	var $list_count = 20;
	var $page_count = 10;
	var $cache_file;
	var $interval;
	var $path;

	/**
	 * @brief Initialization
	 */
	function init()
	{
		switch($this->module_info->page_type)
		{
			case 'WIDGET' :
				{
					$this->cache_file = sprintf("%sfiles/cache/page/%d.%s.%s.cache.php", RX_BASEDIR, $this->module_info->module_srl, Context::getLangType(), Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval ?? 0);
					break;
				}
			case 'OUTSIDE' :
				{
					$this->cache_file = sprintf("%sfiles/cache/opage/%d.%s.cache.php", RX_BASEDIR, $this->module_info->module_srl, Context::getSslStatus());
					$this->interval = (int)($this->module_info->page_caching_interval ?? 0);
					$this->path = $this->module_info->path;
					break;
				}
		}
	}

	/**
	 * @brief General request output
	 */
	function dispPageIndex()
	{
		// Variables used in the template Context:: set()
		if($this->module_srl) Context::set('module_srl',$this->module_srl);

		$page_type_name = strtolower($this->module_info->page_type);
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		if(method_exists($this, $method))
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
		$this->setTemplateFile('content');
	}

	function _getWidgetContent()
	{
		if($this->interval>0)
		{
			if(!file_exists($this->cache_file)) $mtime = 0;
			else $mtime = filemtime($this->cache_file);

			if($mtime + $this->interval*60 > $_SERVER['REQUEST_TIME'])
			{
				$page_content = FileHandler::readFile($this->cache_file); 
				$page_content = str_replace('<!--#Meta:', '<!--Meta:', $page_content);
			}
			else
			{
				$oWidgetController = getController('widget');
				$page_content = $oWidgetController->transWidgetCode($this->module_info->content);
				FileHandler::writeFile($this->cache_file, $page_content);
			}
		}
		else
		{
			if(file_exists($this->cache_file)) FileHandler::removeFile($this->cache_file);
			$page_content = $this->module_info->content;
		}
		return $page_content;
	}

	function _getArticleContent()
	{
		$oTemplate = &TemplateHandler::getInstance();

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0);

		if($this->module_info->document_srl)
		{
			$document_srl = $this->module_info->document_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		Context::set('oDocument', $oDocument);

		$templatePath = sprintf('%sskins/%s', $this->module_path, $this->module_info->skin ?: 'default');
		$page_content = $oTemplate->compile($templatePath, 'content');

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

	/**
	 * @brief Save the file and return if a file is requested by http
	 */
	function getHtmlPage($path, $caching_interval, $cache_file)
	{
		// Verify cache
		if($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + $caching_interval*60 > $_SERVER['REQUEST_TIME'])
		{
			$content = FileHandler::readFile($cache_file);
		}
		else
		{
			FileHandler::getRemoteFile($path, $cache_file);
			$content = FileHandler::readFile($cache_file);
		}
		// Create opage controller
		$oPageController = getController('page');
		// change url of image, css, javascript and so on if the page is from external server
		$content = $oPageController->replaceSrc($content, $path);

		// Change the document to utf-8 format
		$buff = new stdClass;
		$buff->content = $content;
		$buff = Context::convertEncoding($buff);
		$content = $buff->content;
		// Extract a title
		$title = $oPageController->getTitle($content);
		if($title) Context::setBrowserTitle($title);
		// Extract header script
		$head_script = $oPageController->getHeadScript($content);
		if($head_script) Context::addHtmlHeader($head_script);
		// Extract content from the body
		$body_script = $oPageController->getBodyScript($content);
		if(!$body_script) $body_script = $content;

		return $content;
	}

	/**
	 * @brief Create a cache file in order to include if it is an internal file
	 */
	function executeFile($target_file, $caching_interval, $cache_file)
	{
		// Cancel if the file doesn't exist
		if(!file_exists(FileHandler::getRealPath($target_file))) return;

		// Get a path and filename
		$tmp_path = explode('/',$cache_file);
		$filename = $tmp_path[count($tmp_path)-1];
		$filepath = preg_replace('/'.$filename."$/i","",$cache_file);
		$cache_file = FileHandler::getRealPath($cache_file);

		$level = ob_get_level();
		// Verify cache
		if($caching_interval <1 || !file_exists($cache_file) || filemtime($cache_file) + $caching_interval*60 <= $_SERVER['REQUEST_TIME'] || filemtime($cache_file)<filemtime($target_file))
		{
			if(file_exists($cache_file)) FileHandler::removeFile($cache_file);

			// Read a target file and get content
			ob_start();
			include(FileHandler::getRealPath($target_file));
			$content = ob_get_clean();
			// Replace relative path to the absolute path 
			$this->path = str_replace('\\', '/', realpath(dirname($target_file))) . '/';
			$content = preg_replace_callback('/(target=|src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is',array($this,'_replacePath'),$content);
			$content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is',array($this,'_replacePath'),$content);

			FileHandler::writeFile($cache_file, $content);
			// Include and then Return the result
			if(!file_exists($cache_file)) return;
			// Attempt to compile
			$oTemplate = &TemplateHandler::getInstance();
			$script = $oTemplate->compileDirect($filepath, $filename);

			FileHandler::writeFile($cache_file, $script);
		}

		// Import Context and lang as local variables.
		$__Context = &$GLOBALS['__Context__'];
		$__Context->tpl_path = $filepath;
		global $lang;

		// Start the output buffer.
		$__ob_level_before_fetch = ob_get_level();
		ob_start();
		
		// Include the compiled template.
		include $cache_file;

		// Fetch contents of the output buffer until the buffer level is the same as before.
		$contents = '';
		while (ob_get_level() > $__ob_level_before_fetch)
		{
			$contents .= ob_get_clean();
		}
		
		// Insert template path comment tag.
		if(Rhymix\Framework\Debug::isEnabledForCurrentUser() && Context::getResponseMethod() === 'HTML' && !starts_with('<!DOCTYPE', $contents) && !starts_with('<?xml', $contents))
		{
			$sign = PHP_EOL . '<!-- Template %s : ' . $target_file . ' -->' . PHP_EOL;
			$contents = sprintf($sign, 'start') . $contents . sprintf($sign, 'end');
		}
		
		return $contents;
	}

	function _replacePath($matches)
	{
		$val = trim($matches[3]);
		// Pass if the path is external or starts with /, #, { characters
		// /=absolute path, #=hash in a page, {=Template syntax
		if(strpos($val, '.') === FALSE || preg_match('@^((?:http|https|ftp|telnet|mms)://|(?:mailto|javascript):|[/#{])@i',$val))
		{
				return $matches[0];
			// In case of  .. , get a path
		}
		else if(strncasecmp('..', $val, 2) === 0)
		{
			$p = Context::pathToUrl($this->path);
			return sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);
		}

		if(strncasecmp('..', $val, 2) === 0) $val = substr($val,2);
		$p = Context::pathToUrl($this->path);
		$path = sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);

		return $path;
	}
}
/* End of file page.view.php */
/* Location: ./modules/page/page.view.php */
