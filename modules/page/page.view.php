<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pageView
 * @author NAVER (developers@xpressengine.com)
 * @brief page view class of the module
 */
class pageView extends page
{
	public $module_srl = 0;
	public $list_count = 20;
	public $page_count = 10;
	public $cache_file = null;
	public $interval = 0;
	public $path = '';
	public $proc_php = false;
	public $proc_tpl = false;

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
			if ($this instanceof pageMobile)
			{
				$this->path = ($this->module_info->mpath ?? '') ?: ($this->module_info->path ?? '');
			}
			else
			{
				$this->path = ($this->module_info->path ?? '');
			}
			$this->proc_php = (isset($this->module_info->opage_proc_php) && $this->module_info->opage_proc_php === 'N') ? false : true;
			$this->proc_tpl = (isset($this->module_info->opage_proc_tpl) && $this->module_info->opage_proc_tpl === 'Y') ? true : false;
			$this->cache_file = vsprintf('%sfiles/cache/opage/%d.%s.%s.%s.%s.cache.php', [
				\RX_BASEDIR,
				$this->module_info->module_srl,
				Context::getSslStatus(),
				$this->proc_php ? 'php' : 'nophp',
				$this->proc_tpl ? 'tpl' : 'notpl',
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
		if ($this->module_srl)
		{
			Context::set('module_srl', $this->module_srl);
		}
		
		// Kick out anyone who tries to exploit RVE-2022-2.
		foreach (Context::getRequestVars() as $key => $val)
		{
			if (preg_match('/[\{\}\(\)<>\$\'"]/', $key) || preg_match('/[\{\}\(\)<>\$\'"]/', $val))
			{
				throw new Rhymix\Framework\Exceptions\SecurityViolation();
			}
		}

		// Get page content according to page type.
		$page_type_name = strtolower($this->module_info->page_type);
		if (!in_array($page_type_name, ['widget', 'article', 'outside']))
		{
			$page_type_name = 'widget';
		}
		$method = '_get' . ucfirst($page_type_name) . 'Content';
		$page_content = $this->{$method}();

		Context::set('module_info', $this->module_info);
		Context::set('page_content', $page_content);
		
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile($this instanceof pageMobile ? 'mobile' : 'content');
	}

	function _getWidgetContent()
	{
		if ($this instanceof pageMobile)
		{
			$page_content = $this->module_info->mcontent ?: $this->module_info->content;
		}
		else
		{
			$page_content = $this->module_info->content;
		}
		
		if ($this->interval > 0)
		{
			if (!file_exists($this->cache_file) || !filesize($this->cache_file))
			{
				$mtime = 0;
			}
			else
			{
				$mtime = filemtime($this->cache_file);
			}

			if($mtime && $mtime + ($this->interval * 60) > \RX_TIME)
			{
				$page_content = FileHandler::readFile($this->cache_file); 
				$page_content = str_replace('<!--#Meta:', '<!--Meta:', $page_content);
			}
			else
			{
				$oWidgetController = WidgetController::getInstance();
				$page_content = $oWidgetController->transWidgetCode($page_content);
				FileHandler::writeFile($this->cache_file, $page_content);
			}
		}
		else
		{
			if (file_exists($this->cache_file))
			{
				FileHandler::removeFile($this->cache_file);
			}
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

		// Return content from cache if available.
		if ($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + ($caching_interval * 60) > \RX_TIME && filemtime($cache_file) > filemtime($real_target_file))
		{
			return file_get_contents($cache_file);
		}
		
		// Parse as template if enabled.
		if ($this->proc_tpl)
		{
			// Store compiled template in a temporary file.
			$oTemplate = TemplateHandler::getInstance();
			$real_target_dir = dirname($real_target_file);
			$tmp_cache_file = preg_replace('/\.cache\.php$/', '.compiled.php', $cache_file);
			$content = $oTemplate->compileDirect($real_target_dir . '/', basename($real_target_file));
			$success = Rhymix\Framework\Storage::write($tmp_cache_file, $content);
			if (!$success)
			{
				return '';
			}
			
			// Include template file in an isolated scope.
			$content = '';
			$include_path = get_include_path();
			$ob_level = ob_get_level();
			ob_start();
			set_include_path($real_target_dir . PATH_SEPARATOR . $include_path);
			call_user_func(function() use($real_target_dir, $tmp_cache_file) {
				$__Context = Context::getAll();
				$__Context->tpl_path = $real_target_dir;
				global $lang;
				include $tmp_cache_file;
			});
			set_include_path($include_path);
			while (ob_get_level() > $ob_level)
			{
				$content .= ob_get_clean();
			}
			
			// Insert comments for debugging.
			if(Rhymix\Framework\Debug::isEnabledForCurrentUser() && Context::getResponseMethod() === 'HTML' && !starts_with('<!DOCTYPE', $content) && !starts_with('<?xml', $content))
			{
				$sign = PHP_EOL . '<!-- Template %s : ' . $target_file . ' -->' . PHP_EOL;
				$content = sprintf($sign, 'start') . $content . sprintf($sign, 'end');
			}
		}
		
		// Parse as PHP if enabled.
		elseif ($this->proc_php)
		{
			ob_start();
			call_user_func(function() use($real_target_file) {
				include $real_target_file;
			});
			$content = ob_get_clean();
		}
		
		// Otherwise, get the raw content of the file.
		else
		{
			$content = file_get_contents($real_target_file);
		}
		
		// Convert relative paths to absolute paths.
		$this->path = str_replace('\\', '/', dirname($real_target_file)) . '/';
		$content = preg_replace_callback('/\b(target=|src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is', array($this, '_replacePath'), $content);
		$content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is', array($this, '_replacePath'), $content);

		// Write cache file.
		$success = Rhymix\Framework\Storage::write($cache_file, $content);
		return $content;
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
