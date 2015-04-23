<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Handle front end files
 * @author NAVER (developers@xpressengine.com)
 * */
class FrontEndFileHandler extends Handler
{

	static $isSSL = null;

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
		$file = $this->getFileInfo($args[0], $args[2], $args[1]);

		$availableExtension = array('css' => 1, 'js' => 1);
		if(!isset($availableExtension[$file->fileExtension]))
		{
			return;
		}

		$file->index = (int) $args[3];

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

		(is_null($file->index)) ? $file->index = 0 : $file->index = $file->index;
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
	 * @return stdClass The file information
	 */
	private function getFileInfo($fileName, $targetIe = '', $media = 'all')
	{
		static $existsInfo = array();

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
		$file->fileNameNoExt = preg_replace('/\.min$/', '', $pathInfo['filename']);
		$file->keyName = implode('.', array($file->fileNameNoExt, $file->fileExtension));
		$file->cdnPath = $this->_normalizeFilePath($pathInfo['dirname']);

		if(strpos($file->filePath, '://') === FALSE)
		{
			if(!__DEBUG__ && __XE_VERSION_STABLE__)
			{
				// if no debug mode, load minifed file
				$minifiedFileName = implode('.', array($file->fileNameNoExt, 'min', $file->fileExtension));
				$minifiedRealPath = implode('/', array($file->fileRealPath, $minifiedFileName));
				if(file_exists($minifiedRealPath))
				{
					$file->fileName = $minifiedFileName;
				}
			}
			else
			{
				// Remove .min
				if(file_exists(implode('/', array($file->fileRealPath, $file->keyName))))
				{
					$file->fileName = $file->keyName;
				}
			}
		}

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
			return $path;
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
