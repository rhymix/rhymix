<?php
	/**
	 * @class FrontEndFileHandler
	 * @author NHN (developers@xpressengine.com)
	 **/

	class FrontEndFileHandler extends Handler
	{
		var $cssMap = array();
		var $jsHeadMap = array();
		var $jsBodyMap = array();
		var $cssMapIndex = array();
		var $jsHeadMapIndex = array();
		var $jsBodyMapIndex = array();

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
		 * @brief load front end file
		 * @params $args array
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

			$availableExtension = array('css', 'js');
			if (!in_array($file->fileExtension, $availableExtension)) return;

			$file->targetIe = $args[2];
			$file->index = (int)$args[3];

			if ($file->fileExtension == 'css')
			{
				$file->media = $args[1];
				if (!$file->media) $file->media = 'all';
				$map = &$this->cssMap;
				$mapIndex = &$this->cssMapIndex;
				$key = $file->filePath . $file->fileName . "\t" . $file->targetIe . "\t" . $file->media;
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

			if (!isset($map[$key]) || $mapIndex[$key] != $file->index)
			{
				$this->unloadFile($args[0], $args[2], $args[1]);
				$map[$file->index][$key] = $file;
				$mapIndex[$key] = $file->index;
			}
		}

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

		function unloadAllFiles($type = 'all')
		{
			if ($type == 'css' || $type == 'all')
			{
				$cssMap = array();
				$cssMapIndex = array();
			}

			if ($type == 'js' || $type == 'all')
			{
				$jsHeadMap = array();
				$jsBodyMap = array();
				$jsHeadMapIndex = array();
				$jsBodyMapIndex = array();
			}
		}

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

		function _sortMap(&$map, &$index)
		{
			ksort($map);
		}

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
	}
