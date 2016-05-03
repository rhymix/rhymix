<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Handle front end files
 * @author NAVER (developers@xpressengine.com)
 * */
class FrontEndFileHandler extends Handler
{

	public static $isSSL = null;
	public static $minify = null;

	/**
	 * Map for css
	 * @var array
	 */
	var $cssMap = array();

	/**
	 * Map for Javascript at head
	 * @var array
	 */
	var $jsHeadMap = array();

	/**
	 * Map for Javascript at body
	 * @var array
	 */
	var $jsBodyMap = array();

	/**
	 * Index for css
	 * @var array
	 */
	var $cssMapIndex = array();

	/**
	 * Index for javascript at head
	 * @var array
	 */
	var $jsHeadMapIndex = array();

	/**
	 * Index for javascript at body
	 * @var array
	 */
	var $jsBodyMapIndex = array();

	/**
	 * Check SSL
	 *
	 * @return bool If using ssl returns true, otherwise returns false.
     * @deprecated
	 */
	function isSsl()
	{
		if(!is_null(self::$isSSL))
		{
			return self::$isSSL;
		}

		$url_info = parse_url(Context::getRequestUrl());
		self::$isSSL = ($url_info['scheme'] == 'https');

		return self::$isSSL;
	}

	/**
	 * Load front end file
	 *
	 * The $args is use as below. File type(js, css) is detected by file extension.
	 *
	 * <pre>
	 * case js
	 * 		$args[0]: file name
	 * 		$args[1]: type (head | body)
	 * 		$args[2]: target IE
	 * 		$args[3]: index
	 * case css
	 * 		$args[0]: file name
	 * 		$args[1]: media
	 * 		$args[2]: target IE
	 * 		$args[3]: index
	 * 		$args[4]: vars for LESS and SCSS
	 * </pre>
	 *
	 * @param array $args Arguments
	 * @return void
	 * */
	public function loadFile($args)
	{
		if(!is_array($args))
		{
			$args = array($args);
		}
		$args[0] = preg_replace(array_keys(HTMLDisplayHandler::$replacements), array_values(HTMLDisplayHandler::$replacements), $args[0]);
		$isCommon = preg_match(HTMLDisplayHandler::$reservedCSS, $args[0]) || preg_match(HTMLDisplayHandler::$reservedJS, $args[0]);
		if($args[3] > -1500000 && $isCommon)
		{
			return;
		}
		$file = $this->getFileInfo($args[0], $args[2], $args[1], $args[4], $isCommon);
		$file->index = (int)$args[3];

		$availableExtension = array('css' => 1, 'js' => 1);
		if(!isset($availableExtension[$file->fileExtension]))
		{
			return;
		}

		if($file->fileExtension == 'css')
		{
			$map = &$this->cssMap;
			$mapIndex = &$this->cssMapIndex;

			$this->_arrangeCssIndex($file->fileRealPath, $file);
		}
		else if($file->fileExtension == 'js')
		{
			if($args[1] == 'body')
			{
				$map = &$this->jsBodyMap;
				$mapIndex = &$this->jsBodyMapIndex;
			}
			else
			{
				$map = &$this->jsHeadMap;
				$mapIndex = &$this->jsHeadMapIndex;
			}
		}

		if(!isset($mapIndex[$file->key]) || $mapIndex[$file->key] > $file->index)
		{
			//$this->unloadFile($args[0], $args[2], $args[1]);
			$map[$file->index][$file->key] = $file;
			$mapIndex[$file->key] = $file->index;
		}
	}

	/**
	 * Get file information
	 *
	 * @param string $fileName The file name
	 * @param string $targetIe Target IE of file
	 * @param string $media Media of file
	 * @param array $vars Variables for LESS and SCSS
	 * @param bool $forceMinify Whether this file should be minified
	 * @return stdClass The file information
	 */
	protected function getFileInfo($fileName, $targetIe = '', $media = 'all', $vars = array(), $forceMinify = false)
	{
		static $existsInfo = array();

		if(self::$minify === null)
		{
			self::$minify = config('view.minify_scripts') ?: 'common';
		}

		if(isset($existsInfo[$existsKey]))
		{
			return $existsInfo[$existsKey];
		}
		
		$pathInfo = pathinfo($fileName);
		$file = new stdClass();
		$file->fileName = $pathInfo['basename'];
		$file->filePath = $this->_getAbsFileUrl($pathInfo['dirname']);
		$file->fileRealPath = FileHandler::getRealPath($pathInfo['dirname']);
		$file->fileFullPath = $file->fileRealPath . '/' . $pathInfo['basename'];
		$file->fileExtension = strtolower($pathInfo['extension']);
		if(preg_match('/^(.+)\.min$/', $pathInfo['filename'], $matches))
		{
			$file->fileNameNoExt = $matches[1];
			$file->isMinified = true;
		}
		else
		{
			$file->fileNameNoExt = $pathInfo['filename'];
			$file->isMinified = false;
		}
		$file->isExternalURL = preg_match('@^(https?:)?//@i', $file->filePath) ? true : false;
		$file->isCachedScript = !$file->isExternalURL && strpos($file->filePath, 'files/cache/') !== false;
		$file->keyName = $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = $this->_normalizeFilePath($pathInfo['dirname']);

		// Fix incorrectly minified URL
		if($file->isMinified && !$file->isExternalURL && (!file_exists($file->fileFullPath) || is_link($file->fileFullPath) ||
			(filesize($file->fileFullPath) < 32 && trim(file_get_contents($file->fileFullPath)) === $file->keyName)))
		{
			if(file_exists($file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension))
			{
				$file->fileName = $file->fileNameNoExt . '.' . $file->fileExtension;
				$file->isMinified = false;
				$file->fileFullPath = $file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension;
			}
		}

		// Decide whether to minify this file
		if ($file->isMinified || $file->isExternalURL || $file->isCachedScript || strpos($file->filePath, 'common/js/plugins') !== false || self::$minify === 'none')
		{
			$minify = false;
		}
		elseif (self::$minify === 'all')
		{
			$minify = true;
		}
		else
		{
			$minify = $forceMinify;
		}
		
		// Process according to file type
		switch ($file->fileExtension)
		{
			case 'css':
			case 'js':
				$this->proc_CSS_JS($file, $minify);
				break;
			case 'less':
			case 'scss':
				$this->proc_LESS_SCSS($file, $minify, (array)$vars);
				break;
			default:
				break;
		}

		// Process targetIe and media attributes
		$file->targetIe = $targetIe;
		if($file->fileExtension == 'css')
		{
			$file->media = $media;
			if(!$file->media)
			{
				$file->media = 'all';
			}
			$file->key = $file->filePath . $file->keyName . "\t" . $file->targetIe . "\t" . $file->media;
		}
		else if($file->fileExtension == 'js')
		{
			$file->key = $file->filePath . $file->keyName . "\t" . $file->targetIe;
		}

		return $file;
	}
	
	/**
	 * Process CSS and JS file
	 * 
	 * @param object $file
	 * @param bool $minify
	 * @return void
	 */
	protected function proc_CSS_JS($file, $minify)
	{
		if (!$minify || !file_exists($file->fileFullPath))
		{
			return;
		}
		
		$minifiedFileName = $file->fileNameNoExt . '.min.' . $file->fileExtension;
		$minifiedFileHash = ltrim(str_replace(array('/', '\\'), '.', substr($file->fileRealPath, strlen(\RX_BASEDIR))), '.');
		$minifiedFilePath = \RX_BASEDIR . 'files/cache/minify/' . $minifiedFileHash . '.' . $minifiedFileName;
		
		if (!file_exists($minifiedFilePath) || filemtime($minifiedFilePath) < filemtime($file->fileFullPath))
		{
			$method_name = 'minify' . $file->fileExtension;
			$success = Rhymix\Framework\Formatter::$method_name($file->fileFullPath, $minifiedFilePath);
			if ($success === false)
			{
				return;
			}
		}
		
		$file->fileName = $minifiedFileHash . '.' . $minifiedFileName;
		$file->filePath = \RX_BASEURL . 'files/cache/minify';
		$file->fileRealPath = \RX_BASEDIR . 'files/cache/minify';
		$file->fileFullPath = $minifiedFilePath;
		$file->keyName = $minifiedFileHash . '.' . $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = './files/cache/minify';
		$file->isMinified = true;
	}
	
	/**
	 * Process LESS and SCSS file
	 * 
	 * @param object $file
	 * @param bool $minify
	 * @param array $vars
	 * @return void
	 */
	protected function proc_LESS_SCSS($file, $minify, $vars = array())
	{
		if (!file_exists($file->fileFullPath))
		{
			return;
		}
		
		$compiledFileName = $file->fileName . ($minify ? '.min' : '') . '.css';
		$compiledFileHash = ltrim(str_replace(array('/', '\\'), '.', substr($file->fileRealPath, strlen(\RX_BASEDIR))), '.');
		$compiledFilePath = \RX_BASEDIR . 'files/cache/minify/' . $compiledFileHash . '.' . $compiledFileName;
		
		if (!file_exists($compiledFilePath) || filemtime($compiledFilePath) < filemtime($file->fileFullPath))
		{
			$method_name = 'compile' . $file->fileExtension;
			$success = Rhymix\Framework\Formatter::$method_name($file->fileFullPath, $compiledFilePath, $vars, $minify);
			if ($success === false)
			{
				return;
			}
		}
		
		$file->fileName = $compiledFileHash . '.' . $compiledFileName;
		$file->filePath = \RX_BASEURL . 'files/cache/minify';
		$file->fileRealPath = \RX_BASEDIR . 'files/cache/minify';
		$file->fileFullPath = $compiledFilePath;
		$file->keyName = $compiledFileHash . '.' . $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = './files/cache/minify';
		$file->isMinified = true;
		$file->fileExtension = 'css';
	}

	/**
	 * Unload front end file
	 *
	 * @param string $fileName The file name to unload
	 * @param string $targetIe Target IE of file to unload
	 * @param string $media Media of file to unload. Only use when file is css.
	 * @return void
	 */
	public function unloadFile($fileName, $targetIe = '', $media = 'all')
	{
		$file = $this->getFileInfo($fileName, $targetIe, $media);

		if($file->fileExtension == 'css')
		{
			if(isset($this->cssMapIndex[$file->key]))
			{
				$index = $this->cssMapIndex[$file->key];
				unset($this->cssMap[$index][$file->key], $this->cssMapIndex[$file->key]);
			}
		}
		else
		{
			if(isset($this->jsHeadMapIndex[$file->key]))
			{
				$index = $this->jsHeadMapIndex[$file->key];
				unset($this->jsHeadMap[$index][$file->key], $this->jsHeadMapIndex[$file->key]);
			}
			if(isset($this->jsBodyMapIndex[$file->key]))
			{
				$index = $this->jsBodyMapIndex[$file->key];
				unset($this->jsBodyMap[$index][$file->key], $this->jsBodyMapIndex[$file->key]);
			}
		}
	}

	/**
	 * Unload all front end file
	 *
	 * @param string $type Type to unload. all, css, js
	 * @return void
	 */
	function unloadAllFiles($type = 'all')
	{
		if($type == 'css' || $type == 'all')
		{
			$this->cssMap = array();
			$this->cssMapIndex = array();
		}

		if($type == 'js' || $type == 'all')
		{
			$this->jsHeadMap = array();
			$this->jsBodyMap = array();
			$this->jsHeadMapIndex = array();
			$this->jsBodyMapIndex = array();
		}
	}

	/**
	 * Get css file list
	 *
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	function getCssFileList()
	{
		$map = &$this->cssMap;
		$mapIndex = &$this->cssMapIndex;

		$this->_sortMap($map, $mapIndex);

		$result = array();
		foreach($map as $indexedMap)
		{
			foreach($indexedMap as $file)
			{
				$fullFilePath = $file->filePath . '/' . $file->fileName . '?' . date('YmdHis', filemtime($file->fileFullPath));
				$result[] = array('file' => $fullFilePath, 'media' => $file->media, 'targetie' => $file->targetIe);
			}
		}

		return $result;
	}

	/**
	 * Get javascript file list
	 *
	 * @param string $type Type of javascript. head, body
	 * @return array Returns javascript file list. Array contains file, targetie.
	 */
	function getJsFileList($type = 'head')
	{
		if($type == 'head')
		{
			$map = &$this->jsHeadMap;
			$mapIndex = &$this->jsHeadMapIndex;
		}
		else
		{
			$map = &$this->jsBodyMap;
			$mapIndex = &$this->jsBodyMapIndex;
		}

		$this->_sortMap($map, $mapIndex);

		$result = array();
		foreach($map as $indexedMap)
		{
			foreach($indexedMap as $file)
			{
				$fullFilePath = $file->filePath . '/' . $file->fileName . '?' . date('YmdHis', filemtime($file->fileFullPath));
				$result[] = array('file' => $fullFilePath, 'targetie' => $file->targetIe);
			}
		}

		return $result;
	}

	/**
	 * Sort a map
	 *
	 * @param array $map Array to sort
	 * @param array $index Not used
	 * @return void
	 */
	function _sortMap(&$map, &$index)
	{
		ksort($map);
	}

	/**
	 * Normalize File path
	 *
	 * @param string $path Path to normalize
	 * @return string Normalized path
	 */
	function _normalizeFilePath($path)
	{
		if(strpos($path, '://') === FALSE && $path{0} != '/' && $path{0} != '.')
		{
			$path = './' . $path;
		}
		elseif(!strncmp($path, '//', 2))
		{
			return preg_replace('#^//+#', '//', $path);
		}

		$path = preg_replace('@/\./|(?<!:)\/\/@', '/', $path);

		while(strpos($path, '/../'))
		{
			$path = preg_replace('/\/([^\/]+)\/\.\.\//s', '/', $path, 1);
		}

		return $path;
	}

	/**
	 * Get absolute file url
	 *
	 * @param string $path Path to get absolute url
	 * @return string Absolute url
	 */
	function _getAbsFileUrl($path)
	{
		$path = Rhymix\Framework\Filters\FilenameFilter::cleanPath($path);
		if (!strncmp($path, \RX_BASEDIR, strlen(\RX_BASEDIR)))
		{
			$path = \RX_BASEURL . substr($path, strlen(\RX_BASEDIR));
		}
		return $path;
	}

	/**
	 * Arrage css index
	 *
	 * @param string $dirname
	 * @param object $file
	 * @return void
	 */
	function _arrangeCssIndex($dirname, $file)
	{
		if ($file->index !== 0)
		{
			return;
		}
		
		$dirname = substr($dirname, strlen(\RX_BASEDIR));
		if (strncmp($dirname, 'files/cache/minify/', 19) === 0)
		{
			$dirname = substr($dirname, 19);
		}
		$tmp = array_first(explode('/', strtr($dirname, '\\.', '//')));

		$cssSortList = array('common' => -100000, 'layouts' => -90000, 'modules' => -80000, 'widgets' => -70000, 'addons' => -60000);
		$file->index = $cssSortList[$tmp[0]];
	}

}
/* End of file FrontEndFileHandler.class.php */
/* Location: ./classes/frontendfile/FrontEndFileHandler.class.php */
