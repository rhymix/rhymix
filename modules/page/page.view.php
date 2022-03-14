<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pageView
 * @author NAVER (developers@xpressengine.com)
 * @brief page view class of the module
 */
class pageView extends page
{
	const COMPILE_TEMPLATE = false;
	
	public $module_srl = 0;
	public $list_count = 20;
	public $page_count = 10;
	public $cache_file = null;
	public $interval = 0;
	public $path = '';

	/**
	 * @brief Initialization
	 */
	function init()
	{
		if ($this->module_info->page_type === 'WIDGET')
		{
			$this->interval = (int)($this->module_info->page_caching_interval ?? 0);
			$this->cache_file = vsprintf('%sfiles/cache/page/%d.%s.%s.%s.cache.php', [
				\RX_BASEDIR,
				$this->module_info->module_srl,
				Context::getLangType(),
				Context::getSslStatus(),
				$this instanceof pageMobile ? 'm' : 'pc',
			]);
		}
		
		if ($this->module_info->page_type === 'OUTSIDE')
		{
			$this->interval = (int)($this->module_info->page_caching_interval ?? 0);
			$this->path = $this->module_info->path ?? '';
			$this->cache_file = vsprintf('%sfiles/cache/opage/%d.%s.%s.%s.cache.php', [
				\RX_BASEDIR,
				$this->module_info->module_srl,
				Context::getSslStatus(),
				self::COMPILE_TEMPLATE ? 'ct' : 'nt',
				$this instanceof pageMobile ? 'm' : 'pc',
			]);
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
		// Stop if the path is not set.
		if (!$this->path)
		{
			return;
		}
		
		// External URL
		if (preg_match('!^[a-z]+://!i', $this->path))
		{
			return $this->getHtmlPage($this->path, $this->interval, $this->cache_file);
		}
		
		// Internal PHP document
		else
		{
			return $this->executeFile($this->path, $this->interval, $this->cache_file);
		}
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
		$real_target_file = FileHandler::getRealPath($target_file);
		if (!file_exists($real_target_file))
		{
			return;
		}

		// Get a path and filename
		$tmp_path = explode('/',$cache_file);
		$filename = $tmp_path[count($tmp_path)-1];
		$filepath = preg_replace('/'.$filename."$/i","",$cache_file);
		$cache_file = FileHandler::getRealPath($cache_file);

		// Verify cache
		if ($caching_interval < 1 || !file_exists($cache_file) || filemtime($cache_file) + ($caching_interval * 60) <= \RX_TIME || filemtime($cache_file) < filemtime($real_target_file))
		{
			// Read a target file and get content
			ob_start();
			include $real_target_file;
			$content = ob_get_clean();
			
			// Replace relative path to the absolute path 
			$this->path = str_replace('\\', '/', realpath(dirname($target_file))) . '/';
			$content = preg_replace_callback('/(target=|src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is',array($this,'_replacePath'),$content);
			$content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is',array($this,'_replacePath'),$content);

			FileHandler::writeFile($cache_file, $content);
			if (!file_exists($cache_file))
			{
				return '';
			}
			
			// Attempt to compile
			if (self::COMPILE_TEMPLATE)
			{
				$oTemplate = TemplateHandler::getInstance();
				$script = $oTemplate->compileDirect($filepath, $filename);
				FileHandler::writeFile($cache_file, $script);
			}
			else
			{
				return $content;
			}
		}

		// Return content if not compiling as template.
		if (!self::COMPILE_TEMPLATE)
		{
			return file_get_contents($cache_file);
		}
		
		// Import Context and lang as local variables.
		$__Context = Context::getAll();
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
		}
		// In case of  .. , get a path
		elseif(strncasecmp('..', $val, 2) === 0)
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
