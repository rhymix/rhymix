<?php
	/**
	 * Handle front end files
	 * @author NHN (developers@xpressengine.com)
	 **/
	class FrontEndFileHandler extends Handler
	{
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
		 */
		function isSsl()
		{
			if ($GLOBAL['__XE_IS_SSL__']) return $GLOBAL['__XE_IS_SSL__'];

			$url_info = parse_url(Context::getRequestUrl());
			if ($url_info['scheme'] == 'https')
				$GLOBAL['__XE_IS_SSL__'] = true;
			else
				$GLOBAL['__XE_IS_SSL__'] = false;

			return $GLOBAL['__XE_IS_SSL__'];
		}

		/**
		 * Load front end file
		 *
		 * The $args is use as below. File type(js, css) is detected by file extension.
		 *
		 * <pre>
		 * case js
		 *		$args[0]: file name
		 *		$args[1]: type (head | body)
		 *		$args[2]: target IE
		 *		$args[3]: index
		 * case css
		 *		$args[0]: file name
		 *		$args[1]: media
		 *		$args[2]: target IE
		 *		$args[3]: index
		 * </pre>
		 *
		 * If $useCdn set true, use CDN instead local file.
		 * CDN path = $cdnPrefix . $cdnVersion . $args[0]<br />
		 *<br />
		 * i.e.<br />
		 * $cdnPrefix = 'http://static.xpressengine.com/core/';<br />
		 * $cdnVersion = 'ardent1';<br />
		 * $args[0] = './common/js/xe.js';<br />
		 * The CDN path is http://static.xprssengine.com/core/ardent1/common/js/xe.js.<br />
		 *
		 * @param array $args Arguments
		 * @param bool $useCdn If set true, use cdn instead local file
		 * @param string $cdnPrefix CDN url prefix. (http://static.xpressengine.com/core/)
		 * @param string $cdnVersion CDN version string (ardent1)
		 * @return void
		 **/
		function loadFile($args, $useCdn = false, $cdnPrefix = '', $cdnVersion = '')
		{
			if (!is_array($args)) $args = array($args);

			$pathInfo = pathinfo($args[0]);
			$file->fileName = $pathInfo['basename'];
			$file->filePath = $this->_getAbsFileUrl($pathInfo['dirname']);
			$file->fileExtension = strtolower($pathInfo['extension']);

			if (strpos($file->filePath, '://') == false)
			{
				$file->useCdn = $useCdn;
				$file->cdnPath = $this->_normalizeFilePath($pathInfo['dirname']);
				$file->cdnPrefix = $cdnPrefix;
				$file->cdnVersion = $cdnVersion;
			}

			$availableExtension = array('css'=>1, 'js'=>1);
			if (!isset($availableExtension[$file->fileExtension])) return;

			$file->targetIe = $args[2];
			$file->index = (int)$args[3];

			if ($file->fileExtension == 'css')
			{
				$file->media = $args[1];
				if (!$file->media) $file->media = 'all';
				$map = &$this->cssMap;
				$mapIndex = &$this->cssMapIndex;
				$key = $file->filePath . $file->fileName . "\t" . $file->targetIe . "\t" . $file->media;

				$this->_arrangeCssIndex($pathInfo['dirname'], $file);
			}
			else if ($file->fileExtension == 'js')
			{
				$type = $args[1];
				if ($type == 'body')
				{
					$map = &$this->jsBodyMap;
					$mapIndex = &$this->jsBodyMapIndex;
				}
				else
				{
					$map = &$this->jsHeadMap;
					$mapIndex = &$this->jsHeadMapIndex;
				}
				$key = $file->filePath . $file->fileName . "\t" . $file->targetIe;
			}

			(is_null($file->index))?$file->index=0:$file->index=$file->index;
			if (!isset($map[$file->index][$key]) || $mapIndex[$key] != $file->index)
			{
				$this->unloadFile($args[0], $args[2], $args[1]);
				$map[$file->index][$key] = $file;
				$mapIndex[$key] = $file->index;
			}
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
			$pathInfo = pathinfo($fileName);
			$fileName = $pathInfo['basename'];
			$filePath = $this->_getAbsFileUrl($pathInfo['dirname']);
			$fileExtension = strtolower($pathInfo['extension']);
			$key = $filePath . $fileName . "\t" . $targetIe;

			if ($fileExtension == 'css')
			{
				if(empty($media))
				{
					$media = 'all';
				}

				$key .= "\t" . $media;
				if (isset($this->cssMapIndex[$key]))
				{
					$index = $this->cssMapIndex[$key];
					unset($this->cssMap[$index][$key]);
					unset($this->cssMapIndex[$key]);
				}
			}
			else
			{
				if (isset($this->jsHeadMapIndex[$key]))
				{
					$index = $this->jsHeadMapIndex[$key];
					unset($this->jsHeadMap[$index][$key]);
					unset($this->jsHeadMapIndex[$key]);
				}
				if (isset($this->jsBodyMapIndex[$key]))
				{
					$index = $this->jsBodyMapIndex[$key];
					unset($this->jsBodyMap[$index][$key]);
					unset($this->jsBodyMapIndex[$key]);
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
			if ($type == 'css' || $type == 'all')
			{
				$this->cssMap = array();
				$this->cssMapIndex = array();
			}

			if ($type == 'js' || $type == 'all')
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

			$dbInfo = Context::getDBInfo();
			$useCdn = $dbInfo->use_cdn;

			$result = array();
			foreach($map as $indexedMap)
			{
				foreach($indexedMap as $file)
				{
					if ($this->isSsl() == false && $useCdn == 'Y' && $file->useCdn && $file->cdnVersion != '%__XE_CDN_VERSION__%')
					{
						$fullFilePath = $file->cdnPrefix . $file->cdnVersion . '/' . substr($file->cdnPath, 2) . '/' . $file->fileName;
					}
					else
					{
						$fullFilePath = $file->filePath . '/' . $file->fileName;
					}
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
			if ($type == 'head')
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

			$dbInfo = Context::getDBInfo();
			$useCdn = $dbInfo->use_cdn;

			$result = array();
			foreach($map as $indexedMap)
			{
				foreach($indexedMap as $file)
				{
					if ($this->isSsl() == false && $useCdn == 'Y' && $file->useCdn && $file->cdnVersion != '%__XE_CDN_VERSION__%')
					{
						$fullFilePath = $file->cdnPrefix . $file->cdnVersion . '/' . substr($file->cdnPath, 2) . '/' . $file->fileName;
					}
					else
					{
						$noneCache = (is_readable($file->cdnPath.'/'.$file->fileName))?'?'.date('YmdHis', filemtime($file->cdnPath.'/'.$file->fileName)):'';
						$fullFilePath = $file->filePath . '/' . $file->fileName.$noneCache;
					}
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
			if (strpos($path, '://') === false && $path{0} != '/' && $path{0} != '.')
			{
				$path = './' . $path;
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
				if (dirname($_SERVER['SCRIPT_NAME']) == '/' || dirname($_SERVER['SCRIPT_NAME']) == '\\')
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
				$path= $this->_normalizeFilePath(dirname($_SERVER['SCRIPT_NAME']) . "/{$path}");
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

			$cssSortList = array('common'=>-100000, 'layouts'=>-90000, 'modules'=>-80000, 'widgets'=>-70000, 'addons'=>-60000);
			$file->index = $cssSortList[$tmp[0]];
		}
	}

/* End of file FrontEndFileHandler.class.php */
/* Location: ./classes/frontendfile/FrontEndFileHandler.class.php */
