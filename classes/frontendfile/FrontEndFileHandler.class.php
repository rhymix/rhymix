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
	 * </pre>
	 *
	 * @param array $args Arguments
	 * @return void
	 * */
	function loadFile($args)
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
		$file = $this->getFileInfo($args[0], $args[2], $args[1], $isCommon);
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

			$this->_arrangeCssIndex($pathInfo['dirname'], $file);
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
			$this->unloadFile($args[0], $args[2], $args[1]);
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
	 * @param bool $forceMinify Whether this file should be minified
	 * @return stdClass The file information
	 */
	private function getFileInfo($fileName, $targetIe = '', $media = 'all', $forceMinify = false)
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
		$originalFilePath = $file->fileRealPath . '/' . $pathInfo['basename'];

		// Fix incorrectly minified URL
		if($file->isMinified && !$file->isExternalURL && (!file_exists($originalFilePath) || is_link($originalFilePath) ||
			(filesize($originalFilePath) < 32 && trim(file_get_contents($originalFilePath)) === $file->keyName)))
		{
			if(file_exists($file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension))
			{
				$file->fileName = $file->fileNameNoExt . '.' . $file->fileExtension;
				$file->isMinified = false;
				$originalFilePath = $file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension;
			}
		}

		// Decide whether to minify this file
		if(self::$minify === 'all')
		{
			$minify_enabled = true;
		}
		elseif(self::$minify === 'none')
		{
			$minify_enabled = false;
		}
		else
		{
			$minify_enabled = $forceMinify;
		}
		
		// Minify file
		if($minify_enabled && !$file->isMinified && !$file->isExternalURL && !$file->isCachedScript && strpos($file->filePath, 'common/js/plugins') === false)
		{
			if(($file->fileExtension === 'css' || $file->fileExtension === 'js') && file_exists($originalFilePath))
			{
				$minifiedFileName = $file->fileNameNoExt . '.min.' . $file->fileExtension;
				$minifiedFileHash = ltrim(str_replace(array('/', '\\'), '.', $pathInfo['dirname']), '.');
				$minifiedFilePath = _XE_PATH_ . 'files/cache/minify/' . $minifiedFileHash . '.' . $minifiedFileName;
			
				if(!file_exists($minifiedFilePath) || filemtime($minifiedFilePath) < filemtime($originalFilePath))
				{
					if($file->fileExtension === 'css')
					{
						$minifier = new MatthiasMullie\Minify\CSS($originalFilePath);
						$content = $minifier->execute($minifiedFilePath);
					}
					else
					{
						$minifier = new MatthiasMullie\Minify\JS($originalFilePath);
						$content = $minifier->execute($minifiedFilePath);
					}
					FileHandler::writeFile($minifiedFilePath, $content);
				}
				
				$file->fileName = $minifiedFileHash . '.' . $minifiedFileName;
				$file->filePath = $this->_getAbsFileUrl('./files/cache/minify');
				$file->fileRealPath = _XE_PATH_ . 'files/cache/minify';
				$file->keyName = $minifiedFileHash . '.' . $file->fileNameNoExt . '.' . $file->fileExtension;
				$file->cdnPath = $this->_normalizeFilePath('./files/cache/minify');
				$file->isMinified = true;
			}
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
	 * Unload front end file
	 *
	 * @param string $fileName The file name to unload
	 * @param string $targetIe Target IE of file to unload
	 * @param string $media Media of file to unload. Only use when file is css.
	 * @return void
	 */
	function unloadFile($fileName, $targetIe = '', $media = 'all')
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
				$noneCache = (is_readable($file->cdnPath . '/' . $file->fileName)) ? '?' . date('YmdHis', filemtime($file->cdnPath . '/' . $file->fileName)) : '';
				$fullFilePath = $file->filePath . '/' . $file->fileName . $noneCache;
				
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
				$noneCache = (is_readable($file->cdnPath . '/' . $file->fileName)) ? '?' . date('YmdHis', filemtime($file->cdnPath . '/' . $file->fileName)) : '';
				$fullFilePath = $file->filePath . '/' . $file->fileName . $noneCache;
				
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
		$path = $this->_normalizeFilePath($path);

		if(strpos($path, './') === 0)
		{
			if(dirname($_SERVER['SCRIPT_NAME']) == '/' || dirname($_SERVER['SCRIPT_NAME']) == '\\')
			{
				$path = '/' . substr($path, 2);
			}
			else
			{
				$path = dirname($_SERVER['SCRIPT_NAME']) . '/' . substr($path, 2);
			}
		}
		else if(strpos($file, '../') === 0)
		{
			$path = $this->_normalizeFilePath(dirname($_SERVER['SCRIPT_NAME']) . "/{$path}");
		}

		return $path;
	}

	/**
	 * Arrage css index
	 *
	 * @param string $dirName First directory  name of css path
	 * @param array $file file info.
	 * @return void
	 */
	function _arrangeCssIndex($dirName, &$file)
	{
		if($file->index !== 0)
		{
			return;
		}

		$dirName = str_replace('./', '', $dirName);
		$tmp = explode('/', $dirName);

		$cssSortList = array('common' => -100000, 'layouts' => -90000, 'modules' => -80000, 'widgets' => -70000, 'addons' => -60000);
		$file->index = $cssSortList[$tmp[0]];
	}

}
/* End of file FrontEndFileHandler.class.php */
/* Location: ./classes/frontendfile/FrontEndFileHandler.class.php */
