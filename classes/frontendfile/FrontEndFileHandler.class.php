<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Handle front end files
 * @author NAVER (developers@xpressengine.com)
 * */
class FrontEndFileHandler extends Handler
{
	/**
	 * Minification and concatenation configuration.
	 */
	public static $minify = null;
	public static $concat = null;
	
	/**
	 * Directory for minified, compiled, and concatenated CSS/JS assets.
	 */
	public static $assetdir = 'files/cache/assets';

	/**
	 * Map for css
	 * @var array
	 */
	public $cssMap = array();

	/**
	 * Map for Javascript at head
	 * @var array
	 */
	public $jsHeadMap = array();

	/**
	 * Map for Javascript at body
	 * @var array
	 */
	public $jsBodyMap = array();

	/**
	 * Index for css
	 * @var array
	 */
	public $cssMapIndex = array();

	/**
	 * Index for javascript at head
	 * @var array
	 */
	public $jsHeadMapIndex = array();

	/**
	 * Index for javascript at body
	 * @var array
	 */
	public $jsBodyMapIndex = array();

	/**
	 * Check SSL
	 *
	 * @return bool If using ssl returns true, otherwise returns false.
     * @deprecated
	 */
	public function isSsl()
	{
		return \RX_SSL;
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
	 * 		$args[2]: target IE / source type hint
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
		
		// Replace obsolete paths with current paths.
		$args[0] = preg_replace(array_keys(HTMLDisplayHandler::$replacements), array_values(HTMLDisplayHandler::$replacements), $args[0]);
		$isCommon = preg_match(HTMLDisplayHandler::$reservedCSS, $args[0]) || preg_match(HTMLDisplayHandler::$reservedJS, $args[0]);
		
		// Prevent overwriting common scripts.
		if(!isset($args[3]) || intval($args[3]) > -1500000000)
		{
			if($isCommon)
			{
				return;
			}
			foreach(HTMLDisplayHandler::$blockedScripts as $regexp)
			{
				if(preg_match($regexp, $args[0]))
				{
					return;
				}
			}
		}
		
		if (isset($args[2]) && preg_match('/IE/i', $args[2]))
		{
			$source_hint = '';
		}
		elseif (isset($args[2]) && $args[2] !== '')
		{
			$source_hint = $args[2];
			$args[2] = '';
		}
		else
		{
			$source_hint = '';
		}
		
		$file = $this->getFileInfo($args[0], $args[2] ?? '', $args[1] ?? 'all', $args[4] ?? [], $isCommon);
		$file->index = (int)($args[3] ?? 0);

		$availableExtension = array('css' => 1, 'js' => 1, 'less' => 1, 'scss' => 1);
		if(!isset($availableExtension[$file->fileExtension]))
		{
			return;
		}

		if($file->fileExtension == 'css' || $file->fileExtension == 'less' || $file->fileExtension == 'scss')
		{
			$map = &$this->cssMap;
			$mapIndex = &$this->cssMapIndex;

			$this->_arrangeCssIndex($file->fileRealPath, $file, $source_hint);
		}
		else if($file->fileExtension == 'js')
		{
			if(isset($args[1]) && $args[1] == 'body')
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
	protected function getFileInfo($fileName, $targetIe = '', $media = 'all', $vars = array(), $isCommon = false)
	{
		$pathInfo = pathinfo($fileName);
		
		$file = new stdClass();
		$file->fileName = $pathInfo['basename'];
		$file->filePath = $this->_getAbsFileUrl($pathInfo['dirname'] ?? '');
		$file->fileRealPath = FileHandler::getRealPath($pathInfo['dirname'] ?? '');
		$file->fileFullPath = $file->fileRealPath . '/' . ($pathInfo['basename'] ?? '');
		$file->fileExtension = strtolower($pathInfo['extension'] ?? '');
		if (($pos = strpos($file->fileExtension, '?')) !== false)
		{
			$file->fileExtension = substr($file->fileExtension, 0, $pos);
		}
		if (preg_match('/^(.+)\.min$/', $pathInfo['filename'] ?? '', $matches))
		{
			$file->fileNameNoExt = $matches[1];
			$file->isMinified = true;
		}
		else
		{
			$file->fileNameNoExt = $pathInfo['filename'] ?? '';
			$file->isMinified = false;
		}
		$file->isExternalURL = preg_match('@^(https?:)?//@i', $file->filePath) ? true : false;
		if ($file->isExternalURL && !$file->fileExtension)
		{
			$file->fileExtension = preg_match('/[\.\/](css|js)\b/', $fileName, $matches) ? $matches[1] : null;
		}
		$file->isCachedScript = !$file->isExternalURL && strpos($file->filePath, 'files/cache/') !== false;
		$file->isCommon = $isCommon;
		$file->keyName = $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = $this->_normalizeFilePath($pathInfo['dirname'] ?? '');
		$file->vars = (array)$vars;

		// Fix incorrectly minified URL
		if($file->isMinified && !$file->isExternalURL && (!file_exists($file->fileFullPath) || is_link($file->fileFullPath) || filesize($file->fileFullPath) < 40))
		{
			if(file_exists($file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension))
			{
				$file->fileName = $file->fileNameNoExt . '.' . $file->fileExtension;
				$file->isMinified = false;
				$file->fileFullPath = $file->fileRealPath . '/' . $file->fileNameNoExt . '.' . $file->fileExtension;
			}
		}
		
		// Do not minify common JS plugins
		if (strpos($file->filePath, 'common/js/plugins') !== false)
		{
			$file->isMinified = true;
		}
		
		// Process targetIe and media attributes
		$file->targetIe = $targetIe;
		if($file->fileExtension == 'css' || $file->fileExtension == 'less' || $file->fileExtension == 'scss')
		{
			$file->media = $media;
			if(!$file->media)
			{
				$file->media = 'all';
			}
			$file->key = sprintf('%s/%s:%s:%s', $file->filePath, $file->keyName, $file->targetIe, $file->media);
		}
		else if($file->fileExtension == 'js')
		{
			$file->key = sprintf('%s/%s:%s', $file->filePath, $file->keyName, $file->targetIe);
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
		$minifiedFilePath = \RX_BASEDIR . self::$assetdir . '/minified/' . $minifiedFileHash . '.' . $minifiedFileName;
		
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
		$file->filePath = \RX_BASEURL . self::$assetdir . '/minified';
		$file->fileRealPath = \RX_BASEDIR . self::$assetdir . '/minified';
		$file->fileFullPath = $minifiedFilePath;
		$file->keyName = $minifiedFileHash . '.' . $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = './' . self::$assetdir . '/minified';
		$file->isMinified = true;
	}
	
	/**
	 * Process LESS and SCSS file
	 * 
	 * @param object $file
	 * @param bool $minify
	 * @return void
	 */
	protected function proc_LESS_SCSS($file, $minify)
	{
		if (!file_exists($file->fileFullPath))
		{
			return;
		}
		
		$default_font_config = Context::get('default_font_config') ?: EditorModel::$default_font_config;
		$file->vars['enable_xe_btn_styles'] = (defined('DISABLE_XE_BTN_STYLES') && DISABLE_XE_BTN_STYLES) ? 'false' : 'true';
		$file->vars['enable_xe_msg_styles'] = (defined('DISABLE_XE_MSG_STYLES') && DISABLE_XE_MSG_STYLES) ? 'false' : 'true';
		$file->vars = array_merge($file->vars, $default_font_config);
		if ($file->fileExtension === 'less')
		{
			$file->vars = array_map(function($str) {
				return preg_match('/^[0-9a-zA-Z\.%_-]+$/', $str) ? $str : ('~"' . str_replace('"', '\\"', $str) . '"');
			}, $file->vars);
		}
		
		$compiledFileName = $file->fileName . ($minify ? '.min' : '') . '.css';
		$compiledFileHash = sha1($file->fileRealPath . ':' . serialize($file->vars));
		$compiledFilePath = \RX_BASEDIR . self::$assetdir . '/compiled/' . $compiledFileHash . '.' . $compiledFileName;

		$importedFileName = $file->fileName . ($minify ? '.min' : '') . '.imports.php';
		$importedFilePath = \RX_BASEDIR . self::$assetdir . '/compiled/' . $compiledFileHash . '.' . $importedFileName;
		
		if (!file_exists($compiledFilePath))
		{
			$recompile = 1;
		}
		else
		{
			$compiledTime = filemtime($compiledFilePath);
			if ($compiledTime < filemtime($file->fileFullPath))
			{
				$recompile = 2;
			}
			else
			{
				$checklist = Rhymix\Framework\Storage::readPHPData($importedFilePath);
				if (is_array($checklist))
				{
					$recompile = 0;
					foreach ($checklist as $filename)
					{
						if (!file_exists($filename) || filemtime($filename) > $compiledTime)
						{
							$recompile = 3;
							break;
						}
					}
				}
				else
				{
					$recompile = 4;
				}
			}
		}
		
		if ($recompile)
		{
			$method_name = 'compile' . $file->fileExtension;
			Rhymix\Framework\Formatter::$method_name($file->fileFullPath, $compiledFilePath, $file->vars, $minify);
		}
		
		$file->fileName = $compiledFileHash . '.' . $compiledFileName;
		$file->filePath = \RX_BASEURL . self::$assetdir . '/compiled';
		$file->fileRealPath = \RX_BASEDIR . self::$assetdir . '/compiled';
		$file->fileFullPath = $compiledFilePath;
		$file->keyName = $compiledFileHash . '.' . $file->fileNameNoExt . '.' . $file->fileExtension;
		$file->cdnPath = './' . self::$assetdir . '/compiled';
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
	public function unloadAllFiles($type = 'all')
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
	 * @param bool $finalize (optional)
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	public function getCssFileList($finalize = false)
	{
		$map = &$this->cssMap;
		$mapIndex = &$this->cssMapIndex;
		$minify = self::$minify !== null ? self::$minify : (config('view.minify_scripts') ?: 'common');
		$concat = strpos(self::$concat !== null ? self::$concat : config('view.concat_scripts'), 'css') !== false;
		$this->_sortMap($map, $mapIndex);
		
		// Minify all scripts, and compile LESS/SCSS into CSS.
		if ($finalize)
		{
			foreach ($map as $indexedMap)
			{
				foreach ($indexedMap as $file)
				{
					$minify_this_file = !$file->isMinified && !$file->isExternalURL && !$file->isCachedScript && (($file->isCommon && $minify !== 'none') || $minify === 'all');
					if ($file->fileExtension === 'css')
					{
						$this->proc_CSS_JS($file, $minify_this_file);
					}
					else
					{
						$this->proc_LESS_SCSS($file, $minify_this_file);
					}
				}
			}
		}
		
		// Add all files to the final result.
		$result = array();
		if ($concat && $finalize && count($concat_list = $this->_concatMap($map)))
		{
			foreach ($concat_list as $concat_fileset)
			{
				if (count($concat_fileset) === 1)
				{
					$file = reset($concat_fileset);
					$url = $file->filePath . '/' . $file->fileName;
					if (!$file->isExternalURL && is_readable($file->fileFullPath))
					{
						$url .= '?' . date('YmdHis', filemtime($file->fileFullPath));
					}
					$result[] = array('file' => $url, 'media' => $file->media, 'targetie' => $file->targetIe);
				}
				else
				{
					$concat_files = array();
					$concat_max_timestamp = 0;
					foreach ($concat_fileset as $file)
					{
						$concat_files[] = $file->media === 'all' ? $file->fileFullPath : array($file->fileFullPath, $file->media);
						$concat_max_timestamp = max($concat_max_timestamp, filemtime($file->fileFullPath));
					}
					$concat_filename = self::$assetdir . '/combined/' . sha1(serialize($concat_files)) . '.css';
					if (!file_exists(\RX_BASEDIR . $concat_filename) || filemtime(\RX_BASEDIR . $concat_filename) < $concat_max_timestamp)
					{
						Rhymix\Framework\Storage::write(\RX_BASEDIR . $concat_filename, Rhymix\Framework\Formatter::concatCSS($concat_files, $concat_filename));
					}
					$concat_filename .= '?' . date('YmdHis', filemtime(\RX_BASEDIR . $concat_filename));
					$result[] = array('file' => \RX_BASEURL . $concat_filename, 'media' => 'all', 'targetie' => '');
				}
			}
		}
		else
		{
			foreach ($map as $indexedMap)
			{
				foreach ($indexedMap as $file)
				{
					$url = $file->filePath . '/' . $file->fileName;
					if (!$file->isExternalURL && is_readable($file->fileFullPath))
					{
						$url .= '?' . date('YmdHis', filemtime($file->fileFullPath));
					}
					$result[] = array('file' => $url, 'media' => $file->media, 'targetie' => $file->targetIe);
				}
			}
		}
		
		// Enable HTTP/2 server push for CSS resources.
		if ($finalize && $this->_isServerPushEnabled())
		{
			foreach ($result as $resource)
			{
				if ($resource['file'][0] === '/' && $resource['file'][1] !== '/')
				{
					header(sprintf('Link: <%s>; rel=preload; as=style', $resource['file']), false);
				}
			}
		}
		return $result;
	}

	/**
	 * Get javascript file list
	 *
	 * @param string $type Type of javascript. head, body
	 * @param bool $finalize (optional)
	 * @return array Returns javascript file list. Array contains file, targetie.
	 */
	public function getJsFileList($type = 'head', $finalize = false)
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
		
		$minify = self::$minify !== null ? self::$minify : (config('view.minify_scripts') ?: 'common');
		$concat = strpos(self::$concat !== null ? self::$concat : config('view.concat_scripts'), 'js') !== false;
		$this->_sortMap($map, $mapIndex);
		
		// Minify all scripts.
		if ($finalize)
		{
			foreach ($map as $indexedMap)
			{
				foreach ($indexedMap as $file)
				{
					if (!$file->isMinified && !$file->isExternalURL && !$file->isCachedScript && (($file->isCommon && $minify !== 'none') || $minify === 'all'))
					{
						$this->proc_CSS_JS($file, true);
					}
				}
			}
		}
		
		// Add all files to the final result.
		$result = array();
		if ($concat && $finalize && $type === 'head' && count($concat_list = $this->_concatMap($map)))
		{
			foreach ($concat_list as $concat_fileset)
			{
				if (count($concat_fileset) === 1)
				{
					$file = reset($concat_fileset);
					$url = $file->filePath . '/' . $file->fileName;
					if (!$file->isExternalURL && is_readable($file->fileFullPath))
					{
						$url .= '?' . date('YmdHis', filemtime($file->fileFullPath));
					}
					$result[] = array('file' => $url, 'targetie' => $file->targetIe);
				}
				else
				{
					$concat_files = array();
					$concat_max_timestamp = 0;
					foreach ($concat_fileset as $file)
					{
						$concat_files[] = $file->targetIe ? array($file->fileFullPath, $file->targetIe) : $file->fileFullPath;
						$concat_max_timestamp = max($concat_max_timestamp, filemtime($file->fileFullPath));
					}
					$concat_filename = self::$assetdir . '/combined/' . sha1(serialize($concat_files)) . '.js';
					if (!file_exists(\RX_BASEDIR . $concat_filename) || filemtime(\RX_BASEDIR . $concat_filename) < $concat_max_timestamp)
					{
						Rhymix\Framework\Storage::write(\RX_BASEDIR . $concat_filename, Rhymix\Framework\Formatter::concatJS($concat_files, $concat_filename));
					}
					$concat_filename .= '?' . date('YmdHis', filemtime(\RX_BASEDIR . $concat_filename));
					$result[] = array('file' => \RX_BASEURL . $concat_filename, 'targetie' => '');
				}
			}
		}
		else
		{
			foreach ($map as $indexedMap)
			{
				foreach ($indexedMap as $file)
				{
					$url = $file->filePath . '/' . $file->fileName;
					if (!$file->isExternalURL && is_readable($file->fileFullPath))
					{
						$url .= '?' . date('YmdHis', filemtime($file->fileFullPath));
					}
					$result[] = array('file' => $url, 'targetie' => $file->targetIe);
				}
			}
		}
		
		// Enable HTTP/2 server push for JS resources.
		if ($type === 'head' && $finalize && $this->_isServerPushEnabled())
		{
			foreach ($result as $resource)
			{
				if ($resource['file'][0] === '/' && $resource['file'][1] !== '/')
				{
					header(sprintf('Link: <%s>; rel=preload; as=script', $resource['file']), false);
				}
			}
		}
		return $result;
	}
	
	/**
	 * Create a concatenation map, skipping external URLs and unreadable scripts.
	 * 
	 * @param array $map
	 * @return array
	 */
	protected function _concatMap(&$map)
	{
		$concat_list = array();
		$concat_key = 0;
		foreach ($map as $indexedMap)
		{
			foreach ($indexedMap as $file)
			{
				if ($file->isExternalURL || ($file->fileExtension === 'css' && $file->targetIe) || !is_readable($file->fileFullPath))
				{
					$concat_key++;
					$concat_list[$concat_key][] = $file;
					$concat_key++;
				}
				else
				{
					$concat_list[$concat_key][] = $file;
				}
			}
		}
		return $concat_list;
	}
	
	/**
	 * Sort a map
	 *
	 * @param array $map Array to sort
	 * @param array $index Not used
	 * @return void
	 */
	protected function _sortMap(&$map, &$index)
	{
		ksort($map);
	}

	/**
	 * Normalize File path
	 *
	 * @param string $path Path to normalize
	 * @return string Normalized path
	 */
	protected function _normalizeFilePath($path)
	{
		$path = strval($path);
		if(!preg_match('!://!', $path) && !preg_match('!^[/.]!', $path))
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
	protected function _getAbsFileUrl($path)
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
	 * @param string $hint
	 * @return void
	 */
	protected function _arrangeCssIndex($dirname, $file, $hint = '')
	{
		if ($file->index < -100000)
		{
			return;
		}
		
		if ($hint)
		{
			$tmp = $hint;
		}
		else
		{
			$dirname = substr($dirname, strlen(\RX_BASEDIR));
			if (strncmp($dirname, self::$assetdir . '/', strlen(self::$assetdir) + 1) === 0)
			{
				$dirname = substr($dirname, strlen(self::$assetdir) + 1);
			}
			$tmp = array_first(explode('/', strtr($dirname, '\\.', '//')));
		}
		if ($tmp)
		{
			$cssSortList = array('common' => -100000, 'layouts' => -90000, 'm.layouts' => -90000, 'modules' => -80000, 'widgets' => -70000, 'addons' => -60000);
			$file->index += isset($cssSortList[$tmp]) ? $cssSortList[$tmp] : 0;
		}
	}
	
	/**
	 * Check if server push is enabled.
	 * 
	 * @return bool
	 */
	protected function _isServerPushEnabled()
	{
		if (!config('view.server_push'))
		{
			return false;
		}
		elseif (strncmp($_SERVER['SERVER_PROTOCOL'] ?? '', 'HTTP/2', 6) === 0)
		{
			return true;
		}
		elseif (isset($_SERVER['HTTP_CF_VISITOR']) && \RX_SSL)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
/* End of file FrontEndFileHandler.class.php */
/* Location: ./classes/frontendfile/FrontEndFileHandler.class.php */
