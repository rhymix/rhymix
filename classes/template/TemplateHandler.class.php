<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class TemplateHandler
 * @author NAVER (developers@xpressengine.com)
 * template compiler
 * @version 0.1
 * @remarks It compiles template file by using regular expression into php
 *          code, and XE caches compiled code for further uses
 */
class TemplateHandler
{

	private $compiled_path = 'files/cache/template_compiled/'; ///< path of compiled caches files
	private $path = NULL; ///< target directory
	private $filename = NULL; ///< target filename
	private $file = NULL; ///< target file (fullpath)
	private $xe_path = NULL;  ///< XpressEngine base path
	private $web_path = NULL; ///< tpl file web path
	private $compiled_file = NULL; ///< tpl file web path
	private $skipTags = NULL;
	private $handler_mtime = 0;
	static private $rootTpl = NULL;

	/**
	 * constructor
	 * @return void
	 */
	public function __construct()
	{
		$this->xe_path = rtrim(preg_replace('/([^\.^\/]+)\.php$/i', '', $_SERVER['SCRIPT_NAME']), '/');
		$this->compiled_path = _XE_PATH_ . $this->compiled_path;
	}

	/**
	 * returns TemplateHandler's singleton object
	 * @return TemplateHandler instance
	 */
	static public function &getInstance()
	{
		static $oTemplate = NULL;

		if(__DEBUG__ == 3)
		{
			if(!isset($GLOBALS['__TemplateHandlerCalled__']))
			{
				$GLOBALS['__TemplateHandlerCalled__'] = 1;
			}
			else
			{
				$GLOBALS['__TemplateHandlerCalled__']++;
			}
		}

		if(!$oTemplate)
		{
			$oTemplate = new TemplateHandler();
		}

		return $oTemplate;
	}

	/**
	 * set variables for template compile
	 * @param string $tpl_path
	 * @param string $tpl_filename
	 * @param string $tpl_file
	 * @return void
	 */
	protected function init($tpl_path, $tpl_filename, $tpl_file = '')
	{
		// verify arguments
		if(substr($tpl_path, -1) != '/')
		{
			$tpl_path .= '/';
		}
		if(!is_dir($tpl_path))
		{
			return;
		}
		if(!file_exists($tpl_path . $tpl_filename) && file_exists($tpl_path . $tpl_filename . '.html'))
		{
			$tpl_filename .= '.html';
		}

		// create tpl_file variable
		if(!$tpl_file)
		{
			$tpl_file = $tpl_path . $tpl_filename;
		}

		// set template file infos.
		$this->path = $tpl_path;
		$this->filename = $tpl_filename;
		$this->file = $tpl_file;

		$this->web_path = $this->xe_path . '/' . ltrim(preg_replace('@^' . preg_quote(_XE_PATH_, '@') . '|\./@', '', $this->path), '/');

		// get compiled file name
		$hash = md5($this->file . __XE_VERSION__);
		$this->compiled_file = "{$this->compiled_path}{$hash}.compiled.php";

		// compare various file's modified time for check changed
		$this->handler_mtime = filemtime(__FILE__);

		$skip = array('');
	}

	/**
	 * compiles specified tpl file and execution result in Context into resultant content
	 * @param string $tpl_path path of the directory containing target template file
	 * @param string $tpl_filename target template file's name
	 * @param string $tpl_file if specified use it as template file's full path
	 * @return string Returns compiled result in case of success, NULL otherwise
	 */
	public function compile($tpl_path, $tpl_filename, $tpl_file = '')
	{
		$buff = false;

		// store the starting time for debug information
		if(__DEBUG__ == 3)
		{
			$start = getMicroTime();
		}

		// initiation
		$this->init($tpl_path, $tpl_filename, $tpl_file);

		// if target file does not exist exit
		if(!$this->file || !file_exists($this->file))
		{
			return "Err : '{$this->file}' template file does not exists.";
		}

		// for backward compatibility
		if(is_null(self::$rootTpl))
		{
			self::$rootTpl = $this->file;
		}

		$source_template_mtime = filemtime($this->file);
		$latest_mtime = $source_template_mtime > $this->handler_mtime ? $source_template_mtime : $this->handler_mtime;

		// cache control
		$oCacheHandler = CacheHandler::getInstance('template');

		// get cached buff
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'template:' . $this->file;
			$buff = $oCacheHandler->get($cache_key, $latest_mtime);
		}
		else
		{
			if(is_readable($this->compiled_file) && filemtime($this->compiled_file) > $latest_mtime && filesize($this->compiled_file))
			{
				$buff = 'file://' . $this->compiled_file;
			}
		}

		if($buff === FALSE)
		{
			$buff = $this->parse();
			if($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($cache_key, $buff);
			}
			else
			{
				FileHandler::writeFile($this->compiled_file, $buff);
			}
		}

		$output = $this->_fetch($buff);

		if($__templatehandler_root_tpl == $this->file)
		{
			$__templatehandler_root_tpl = null;
		}

		// store the ending time for debug information
		if(__DEBUG__ == 3)
		{
			$GLOBALS['__template_elapsed__'] += getMicroTime() - $start;
		}

		return $output;
	}

	/**
	 * compile specified file and immediately return
	 * @param string $tpl_path path of the directory containing target template file
	 * @param string $tpl_filename target template file's name
	 * @return string Returns compiled content in case of success or NULL in case of failure
	 */
	public function compileDirect($tpl_path, $tpl_filename)
	{
		$this->init($tpl_path, $tpl_filename, null);

		// if target file does not exist exit
		if(!$this->file || !file_exists($this->file))
		{
			Context::close();
			exit("Cannot find the template file: '{$this->file}'");
		}

		return $this->parse();
	}

	/**
	 * parse syntax.
	 * @param string $buff template file
	 * @return string compiled result in case of success or NULL in case of error
	 */
	protected function parse($buff = null)
	{
		if(is_null($buff))
		{
			if(!is_readable($this->file))
			{
				return;
			}

			// read tpl file
			$buff = FileHandler::readFile($this->file);
		}

		// HTML tags to skip
		if(is_null($this->skipTags))
		{
			$this->skipTags = array('marquee');
		}

		// replace comments
		$buff = preg_replace('@<!--//.*?-->@s', '', $buff);

		// replace value of src in img/input/script tag
		$buff = preg_replace_callback('/<(?:img|input|script)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* src="(?!(?:https?|file):\/\/|[\/\{])([^"]+)"/is', array($this, '_replacePath'), $buff);

		// replace loop and cond template syntax
		$buff = $this->_parseInline($buff);

		// include, unload/load, import
		$buff = preg_replace_callback('/{(@[\s\S]+?|(?=\$\w+|_{1,2}[A-Z]+|[!\(+-]|\w+(?:\(|::)|\d+|[\'"].*?[\'"]).+?)}|<(!--[#%])?(include|import|(un)?load(?(4)|(?:_js_plugin)?))(?(2)\(["\']([^"\']+)["\'])(.*?)(?(2)\)--|\/)>|<!--(@[a-z@]*)([\s\S]*?)-->(\s*)/', array($this, '_parseResource'), $buff);

		// remove block which is a virtual tag
		$buff = preg_replace('@</?block\s*>@is', '', $buff);

		// form auto generation
		$temp = preg_replace_callback('/(<form(?:<\?php.+?\?>|[^<>]+)*?>)(.*?)(<\/form>)/is', array($this, '_compileFormAuthGeneration'), $buff);
		if($temp)
		{
			$buff = $temp;
		}

		// prevent from calling directly before writing into file
		$buff = '<?php if(!defined("__XE__"))exit;?>' . $buff;

		// remove php script reopening
		$buff = preg_replace(array('/(\n|\r\n)+/', '/(;)?( )*\?\>\<\?php([\n\t ]+)?/'), array("\n", ";\n"), $buff);

		return $buff;
	}

	/**
	 * preg_replace_callback handler
	 * 1. remove ruleset from form tag
	 * 2. add hidden tag with ruleset value
	 * 3. if empty default hidden tag, generate hidden tag (ex:mid, vid, act...)
	 * 4. generate return url, return url use in server side validator
	 * @param array $matches
	 * @return string
	 */
	private function _compileFormAuthGeneration($matches)
	{
		// form ruleset attribute move to hidden tag
		if($matches[1])
		{
			preg_match('/ruleset="([^"]*?)"/is', $matches[1], $m);
			if($m[0])
			{
				$matches[1] = preg_replace('/' . addcslashes($m[0], '?$') . '/i', '', $matches[1]);

				if(strpos($m[1], '@') !== FALSE)
				{
					$path = str_replace('@', '', $m[1]);
					$path = './files/ruleset/' . $path . '.xml';
				}
				else if(strpos($m[1], '#') !== FALSE)
				{
					$fileName = str_replace('#', '', $m[1]);
					$fileName = str_replace('<?php echo ', '', $fileName);
					$fileName = str_replace(' ?>', '', $fileName);
					$path = '#./files/ruleset/' . $fileName . '.xml';

					preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->path, $mm);
					$module_path = $mm[1];
					list($rulsetFile) = explode('.', $fileName);
					$autoPath = $module_path . '/ruleset/' . $rulsetFile . '.xml';
					$m[1] = $rulsetFile;
				}
				else if(preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->path, $mm))
				{
					$module_path = $mm[1];
					$path = $module_path . '/ruleset/' . $m[1] . '.xml';
				}

				$matches[2] = '<input type="hidden" name="ruleset" value="' . $m[1] . '" />' . $matches[2];
				//assign to addJsFile method for js dynamic recache
				$matches[1] = '<?php Context::addJsFile("' . $path . '", FALSE, "", 0, "body", TRUE, "' . $autoPath . '") ?' . '>' . $matches[1];
			}
		}

		// if not exists default hidden tag, generate hidden tag
		preg_match_all('/<input[^>]* name="(act|mid|vid)"/is', $matches[2], $m2);
		$checkVar = array('act', 'mid', 'vid');
		$resultArray = array_diff($checkVar, $m2[1]);
		if(is_array($resultArray))
		{
			$generatedHidden = '';
			foreach($resultArray AS $key => $value)
			{
				$generatedHidden .= '<input type="hidden" name="' . $value . '" value="<?php echo $__Context->' . $value . ' ?>" />';
			}
			$matches[2] = $generatedHidden . $matches[2];
		}

		// return url generate
		if(!preg_match('/no-error-return-url="true"/i', $matches[1]))
		{
			preg_match('/<input[^>]*name="error_return_url"[^>]*>/is', $matches[2], $m3);
			if(!$m3[0])
				$matches[2] = '<input type="hidden" name="error_return_url" value="<?php echo htmlspecialchars(getRequestUriByServerEnviroment(), ENT_COMPAT | ENT_HTML401, \'UTF-8\', false) ?>" />' . $matches[2];
		}
		else
		{
			$matches[1] = preg_replace('/no-error-return-url="true"/i', '', $matches[1]);
		}

		$matches[0] = '';
		return implode($matches);
	}

	/**
	 * fetch using ob_* function
	 * @param string $buff if buff is not null, eval it instead of including compiled template file
	 * @return string
	 */
	private function _fetch($buff)
	{
		if(!$buff)
		{
			return;
		}

		$__Context = &$GLOBALS['__Context__'];
		$__Context->tpl_path = $this->path;

		if($_SESSION['is_logged'])
		{
			$__Context->logged_info = Context::get('logged_info');
		}

		$level = ob_get_level();
		ob_start();
		if(substr($buff, 0, 7) == 'file://')
		{
			if(__DEBUG__)
			{
				//load cache file from disk
				$eval_str = FileHandler::readFile(substr($buff, 7));
				$eval_str_buffed = "?>" . $eval_str;
				@eval($eval_str_buffed);
				$error_info = error_get_last();
				//parse error
				if ($error_info['type'] == 4)
				{
				    throw new Exception("Error Parsing Template - {$error_info['message']} in template file {$this->file}");
				}
			}
			else
			{
				include(substr($buff, 7));
			}
		}
		else
		{
			$eval_str = "?>" . $buff;
			@eval($eval_str);
			$error_info = error_get_last();
			//parse error
			if ($error_info['type'] == 4)
			{
			    throw new Exception("Error Parsing Template - {$error_info['message']} in template file {$this->file}");
			}
		}

		$contents = '';
		while (ob_get_level() - $level > 0) {
			$contents .= ob_get_contents();
			ob_end_clean();
		}
		return $contents;
	}

	/**
	 * preg_replace_callback hanlder
	 *
	 * replace image path
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replacePath($match)
	{
		//return origin conde when src value started '${'.
		if(preg_match('@^\${@', $match[1]))
		{
			return $match[0];
		}

		//return origin code when src value include variable.
		if(preg_match('@^[\'|"]\s*\.\s*\$@', $match[1]))
		{
			return $match[0];
		}

		$src = preg_replace('@^(\./)+@', '', trim($match[1]));

		$src = $this->web_path . $src;
		$src = str_replace('/./', '/', $src);

		// for backward compatibility
		$src = preg_replace('@/((?:[\w-]+/)+)\1@', '/\1', $src);

		while(($tmp = preg_replace('@[^/]+/\.\./@', '', $src, 1)) !== $src)
		{
			$src = $tmp;
		}

		return substr($match[0], 0, -strlen($match[1]) - 6) . "src=\"{$src}\"";
	}

	/**
	 * replace loop and cond template syntax
	 * @param string $buff
	 * @return string changed result
	 */
	private function _parseInline($buff)
	{
		if(preg_match_all('/<([a-zA-Z]+\d?)(?>(?!<[a-z]+\d?[\s>]).)*?(?:[ \|]cond| loop)="/s', $buff, $match) === false)
		{
			return $buff;
		}

		$tags = array_diff(array_unique($match[1]), $this->skipTags);

		if(!count($tags))
		{
			return $buff;
		}

		$tags = '(?:' . implode('|', $tags) . ')';
		$split_regex = "@(<(?>/?{$tags})(?>[^<>\{\}\"']+|<!--.*?-->|{[^}]+}|\".*?\"|'.*?'|.)*?>)@s";

		$nodes = preg_split($split_regex, $buff, -1, PREG_SPLIT_DELIM_CAPTURE);

		// list of self closing tags
		$self_closing = array('area' => 1, 'base' => 1, 'basefont' => 1, 'br' => 1, 'hr' => 1, 'input' => 1, 'img' => 1, 'link' => 1, 'meta' => 1, 'param' => 1, 'frame' => 1, 'col' => 1);

		for($idx = 1, $node_len = count($nodes); $idx < $node_len; $idx+=2)
		{
			if(!($node = $nodes[$idx]))
			{
				continue;
			}

			if(preg_match_all('@\s(loop|cond)="([^"]+)"@', $node, $matches))
			{
				// this tag
				$tag = substr($node, 1, strpos($node, ' ') - 1);

				// if the vale of $closing is 0, it means 'skipping'
				$closing = 0;

				// process opening tag
				foreach($matches[1] as $n => $stmt)
				{
					$expr = $matches[2][$n];
					$expr = $this->_replaceVar($expr);
					$closing++;

					switch($stmt)
					{
						case 'cond':
							$nodes[$idx - 1] .= "<?php if({$expr}){ ?>";
							break;
						case 'loop':
							if(!preg_match('@^(?:(.+?)=>(.+?)(?:,(.+?))?|(.*?;.*?;.*?)|(.+?)\s*=\s*(.+?))$@', $expr, $expr_m))
							{
								break;
							}
							if($expr_m[1])
							{
								$expr_m[1] = trim($expr_m[1]);
								$expr_m[2] = trim($expr_m[2]);
								if($expr_m[3])
								{
									$expr_m[2] .= '=>' . trim($expr_m[3]);
								}
								$nodes[$idx - 1] .= "<?php if({$expr_m[1]}&&count({$expr_m[1]}))foreach({$expr_m[1]} as {$expr_m[2]}){ ?>";
							}
							elseif($expr_m[4])
							{
								$nodes[$idx - 1] .= "<?php for({$expr_m[4]}){ ?>";
							}
							elseif($expr_m[5])
							{
								$nodes[$idx - 1] .= "<?php while({$expr_m[5]}={$expr_m[6]}){ ?>";
							}
							break;
					}
				}
				$node = preg_replace('@\s(loop|cond)="([^"]+)"@', '', $node);

				// find closing tag
				$close_php = '<?php ' . str_repeat('}', $closing) . ' ?>';
				//  self closing tag
				if($node{1} == '!' || substr($node, -2, 1) == '/' || isset($self_closing[$tag]))
				{
					$nodes[$idx + 1] = $close_php . $nodes[$idx + 1];
				}
				else
				{
					$depth = 1;
					for($i = $idx + 2; $i < $node_len; $i+=2)
					{
						$nd = $nodes[$i];
						if(strpos($nd, $tag) === 1)
						{
							$depth++;
						}
						elseif(strpos($nd, '/' . $tag) === 1)
						{
							$depth--;
							if(!$depth)
							{
								$nodes[$i - 1] .= $nodes[$i] . $close_php;
								$nodes[$i] = '';
								break;
							}
						}
					}
				}
			}

			if(strpos($node, '|cond="') !== false)
			{
				$node = preg_replace('@(\s[-\w:]+(?:="[^"]+?")?)\|cond="(.+?)"@s', '<?php if($2){ ?>$1<?php } ?>', $node);
				$node = $this->_replaceVar($node);
			}

			if($nodes[$idx] != $node)
			{
				$nodes[$idx] = $node;
			}
		}

		$buff = implode('', $nodes);

		return $buff;
	}

	/**
	 * preg_replace_callback hanlder
	 * replace php code.
	 * @param array $m
	 * @return string changed result
	 */
	private function _parseResource($m)
	{
		// {@ ... } or {$var} or {func(...)}
		if($m[1])
		{
			if(preg_match('@^(\w+)\(@', $m[1], $mm) && !function_exists($mm[1]))
			{
				return $m[0];
			}

			$echo = 'echo ';
			if($m[1]{0} == '@')
			{
				$echo = '';
				$m[1] = substr($m[1], 1);
			}
			return '<?php ' . $echo . $this->_replaceVar($m[1]) . ' ?>';
		}

		if($m[3])
		{
			$attr = array();
			if($m[5])
			{
				if(preg_match_all('@,(\w+)="([^"]+)"@', $m[6], $mm))
				{
					foreach($mm[1] as $idx => $name)
					{
						$attr[$name] = $mm[2][$idx];
					}
				}
				$attr['target'] = $m[5];
			}
			else
			{
				if(!preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $mm))
				{
					return $m[0];
				}
				foreach($mm[1] as $idx => $name)
				{
					$attr[$name] = $mm[2][$idx];
				}
			}

			switch($m[3])
			{
				// <!--#include--> or <include ..>
				case 'include':
					if(!$this->file || !$attr['target'])
					{
						return '';
					}

					$pathinfo = pathinfo($attr['target']);
					$fileDir = $this->_getRelativeDir($pathinfo['dirname']);

					if(!$fileDir)
					{
						return '';
					}

					return "<?php \$__tpl=TemplateHandler::getInstance();echo \$__tpl->compile('{$fileDir}','{$pathinfo['basename']}') ?>";
				// <!--%load_js_plugin-->
				case 'load_js_plugin':
					$plugin = $this->_replaceVar($m[5]);
					$s = "<!--#JSPLUGIN:{$plugin}-->";
					if(strpos($plugin, '$__Context') === false)
					{
						$plugin = "'{$plugin}'";
					}

					$s .= "<?php Context::loadJavascriptPlugin({$plugin}); ?>";
					return $s;
				// <load ...> or <unload ...> or <!--%import ...--> or <!--%unload ...-->
				case 'import':
				case 'load':
				case 'unload':
					$metafile = '';
					$pathinfo = pathinfo($attr['target']);
					$doUnload = ($m[3] === 'unload');
					$isRemote = !!preg_match('@^(https?:)?//@i', $attr['target']);

					if(!$isRemote)
					{
						if(!preg_match('@^\.?/@', $attr['target']))
						{
							$attr['target'] = './' . $attr['target'];
						}
						if(substr($attr['target'], -5) == '/lang')
						{
							$pathinfo['dirname'] .= '/lang';
							$pathinfo['basename'] = '';
							$pathinfo['extension'] = 'xml';
						}

						$relativeDir = $this->_getRelativeDir($pathinfo['dirname']);

						$attr['target'] = $relativeDir . '/' . $pathinfo['basename'];
					}

					switch($pathinfo['extension'])
					{
						case 'xml':
							if($isRemote || $doUnload)
							{
								return '';
							}
							// language file?
							if($pathinfo['basename'] == 'lang.xml' || substr($pathinfo['dirname'], -5) == '/lang')
							{
								$result = "Context::loadLang('{$relativeDir}');";
							}
							else
							{
								$result = "require_once('./classes/xml/XmlJsFilter.class.php');\$__xmlFilter=new XmlJsFilter('{$relativeDir}','{$pathinfo['basename']}');\$__xmlFilter->compile();";
							}
							break;
						case 'js':
							if($doUnload)
							{
								$result = "Context::unloadFile('{$attr['target']}','{$attr['targetie']}');";
							}
							else
							{
								$metafile = $attr['target'];
								$result = "\$__tmp=array('{$attr['target']}','{$attr['type']}','{$attr['targetie']}','{$attr['index']}');Context::loadFile(\$__tmp);unset(\$__tmp);";
							}
							break;
						case 'css':
							if($doUnload)
							{
								$result = "Context::unloadFile('{$attr['target']}','{$attr['targetie']}','{$attr['media']}');";
							}
							else
							{
								$metafile = $attr['target'];
								$result = "\$__tmp=array('{$attr['target']}','{$attr['media']}','{$attr['targetie']}','{$attr['index']}');Context::loadFile(\$__tmp);unset(\$__tmp);";
							}
							break;
					}

					$result = "<?php {$result} ?>";
					if($metafile)
					{
						$result = "<!--#Meta:{$metafile}-->" . $result;
					}

					return $result;
			}
		}

		// <!--@..--> such as <!--@if($cond)-->, <!--@else-->, <!--@end-->
		if($m[7])
		{
			$m[7] = substr($m[7], 1);
			if(!$m[7])
			{
				return '<?php ' . $this->_replaceVar($m[8]) . '{ ?>' . $m[9];
			}
			if(!preg_match('/^(?:((?:end)?(?:if|switch|for(?:each)?|while)|end)|(else(?:if)?)|(break@)?(case|default)|(break))$/', $m[7], $mm))
			{
				return '';
			}
			if($mm[1])
			{
				if($mm[1]{0} == 'e')
				{
					return '<?php } ?>' . $m[9];
				}

				$precheck = '';
				if($mm[1] == 'switch')
				{
					$m[9] = '';
				}
				elseif($mm[1] == 'foreach')
				{
					$var = preg_replace('/^\s*\(\s*(.+?) .*$/', '$1', $m[8]);
					$precheck = "if({$var}&&count({$var}))";
				}
				return '<?php ' . $this->_replaceVar($precheck . $m[7] . $m[8]) . '{ ?>' . $m[9];
			}
			if($mm[2])
			{
				return "<?php }{$m[7]}" . $this->_replaceVar($m[8]) . "{ ?>" . $m[9];
			}
			if($mm[4])
			{
				return "<?php " . ($mm[3] ? 'break;' : '') . "{$m[7]} " . trim($m[8], '()') . ": ?>" . $m[9];
			}
			if($mm[5])
			{
				return "<?php break; ?>";
			}
			return '';
		}
		return $m[0];
	}

	/**
	 * change relative path
	 * @param string $path
	 * @return string
	 */
	function _getRelativeDir($path)
	{
		$_path = $path;

		$fileDir = strtr(realpath($this->path), '\\', '/');
		if($path{0} != '/')
		{
			$path = strtr(realpath($fileDir . '/' . $path), '\\', '/');
		}

		// for backward compatibility
		if(!$path)
		{
			$dirs = explode('/', $fileDir);
			$paths = explode('/', $_path);
			$idx = array_search($paths[0], $dirs);

			if($idx !== false)
			{
				while($dirs[$idx] && $dirs[$idx] === $paths[0])
				{
					array_splice($dirs, $idx, 1);
					array_shift($paths);
				}
				$path = strtr(realpath($fileDir . '/' . implode('/', $paths)), '\\', '/');
			}
		}

		$path = preg_replace('/^' . preg_quote(_XE_PATH_, '/') . '/', '', $path);

		return $path;
	}

	/**
	 * replace PHP variables of $ character
	 * @param string $php
	 * @return string $__Context->varname
	 */
	function _replaceVar($php)
	{
		if(!strlen($php))
		{
			return '';
		}
		return preg_replace('@(?<!::|\\\\|(?<!eval\()\')\$([a-z]|_[a-z0-9])@i', '\$__Context->$1', $php);
	}

}
/* End of File: TemplateHandler.class.php */
/* Location: ./classes/template/TemplateHandler.class.php */
