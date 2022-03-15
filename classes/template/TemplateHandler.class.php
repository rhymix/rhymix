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
	private $path = NULL; ///< target directory
	private $filename = NULL; ///< target filename
	private $file = NULL; ///< target file (fullpath)
	private $web_path = NULL; ///< tpl file web path
	private $compiled_file = NULL; ///< tpl file web path
	private $source_type = NULL;
	private $config = NULL;
	private $skipTags = NULL;
	private $handler_mtime = 0;
	private static $rootTpl = NULL;
	
	/**
	 * Context variables accessible as $this in template files
	 */
	public $user = FALSE;

	/**
	 * constructor
	 * @return void
	 */
	public function __construct()
	{
		ini_set('pcre.jit', false);
		$this->config = new stdClass;
		$this->handler_mtime = filemtime(__FILE__);
		$this->user = Rhymix\Framework\Session::getMemberInfo();
	}

	/**
	 * returns TemplateHandler's singleton object
	 * @return TemplateHandler instance
	 */
	public static function getInstance()
	{
		static $oTemplate = NULL;

		if(!isset($GLOBALS['__TemplateHandlerCalled__']))
		{
			$GLOBALS['__TemplateHandlerCalled__'] = 1;
		}
		else
		{
			$GLOBALS['__TemplateHandlerCalled__']++;
		}

		if(!$oTemplate)
		{
			$oTemplate = new TemplateHandler();
		}

		return $oTemplate;
	}

	/**
	 * Reset all instance properties to the default state.
	 * 
	 * @return void
	 */
	protected function resetState()
	{
		$this->path = null;
		$this->web_path = null;
		$this->filename = null;
		$this->file = null;
		$this->compiled_file = null;
		$this->source_type = null;
		$this->config = new stdClass;
		$this->skipTags = null;
		self::$rootTpl = null;
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
		$tpl_path = trim(preg_replace('@^' . preg_quote(\RX_BASEDIR, '@') . '|\./@', '', str_replace('\\', '/', $tpl_path)), '/') . '/';
		if($tpl_path === '/')
		{
			$tpl_path = '';
		}
		elseif(!is_dir(\RX_BASEDIR . $tpl_path))
		{
			$this->resetState();
			return;
		}

		if(!file_exists(\RX_BASEDIR . $tpl_path . $tpl_filename) && file_exists(\RX_BASEDIR . $tpl_path . $tpl_filename . '.html'))
		{
			$tpl_filename .= '.html';
		}

		// create tpl_file variable
		if($tpl_file)
		{
			$tpl_file = trim(preg_replace('@^' . preg_quote(\RX_BASEDIR, '@') . '|\./@', '', str_replace('\\', '/', $tpl_file)), '/');
		}
		else
		{
			$tpl_file = $tpl_path . $tpl_filename;
		}

		// set template file infos.
		$this->path = \RX_BASEDIR . $tpl_path;
		$this->web_path = \RX_BASEURL . $tpl_path;
		$this->filename = $tpl_filename;
		$this->file = \RX_BASEDIR . $tpl_file;

		// set compiled file name
		$converted_path = ltrim(str_replace(array('\\', '..'), array('/', 'dotdot'), $tpl_file), '/');
		$this->compiled_file = \RX_BASEDIR . 'files/cache/template/' . $converted_path . '.php';
		$this->source_type = preg_match('!^((?:m\.)?[a-z]+)/!', $tpl_path, $matches) ? $matches[1] : null;
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
		// store the starting time for debug information
		$start = microtime(true);

		// initiation
		$this->init($tpl_path, $tpl_filename, $tpl_file);

		// if target file does not exist exit
		if(!$this->file || !file_exists($this->file))
		{
			$tpl_path = rtrim(str_replace('\\', '/', $tpl_path), '/') . '/';
			$error_message = vsprintf('Template not found: %s%s%s', array(
				$tpl_path,
				preg_replace('/\.html$/i', '', $tpl_filename) . '.html',
				$tpl_file ? " (${tpl_file})" : '',
			));
			trigger_error($error_message, \E_USER_WARNING);
			return escape($error_message);
		}

		// for backward compatibility
		if(is_null(self::$rootTpl))
		{
			self::$rootTpl = $this->file;
		}

		$latest_mtime = max(filemtime($this->file), $this->handler_mtime);
		
		// make compiled file
		if(!file_exists($this->compiled_file) || filemtime($this->compiled_file) < $latest_mtime)
		{
			$buff = $this->parse();
			if(Rhymix\Framework\Storage::write($this->compiled_file, $buff) === false)
			{
				$tmpfilename = tempnam(sys_get_temp_dir(), 'rx-compiled');
				if($tmpfilename === false || Rhymix\Framework\Storage::write($tmpfilename, $buff) === false)
				{
					$error_message = 'Template compile failed: Cannot create temporary file. Please check permissions.';
					trigger_error($error_message, \E_USER_WARNING);
					return escape($error_message);
				}
				
				$this->compiled_file = $tmpfilename;
			}
		}
		
		Rhymix\Framework\Debug::addFilenameAlias($this->file, $this->compiled_file);
		$output = $this->_fetch($this->compiled_file);
		
		// delete tmpfile
		if(isset($tmpfilename))
		{
			Rhymix\Framework\Storage::delete($tmpfilename);
		}

		if(isset($__templatehandler_root_tpl) && $__templatehandler_root_tpl == $this->file)
		{
			$__templatehandler_root_tpl = null;
		}

		// store the ending time for debug information
		if (!isset($GLOBALS['__template_elapsed__']))
		{
			$GLOBALS['__template_elapsed__'] = 0;
		}
		$GLOBALS['__template_elapsed__'] += microtime(true) - $start;

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
			$tpl_path = rtrim(str_replace('\\', '/', $tpl_path), '/') . '/';
			$error_message = vsprintf('Template not found: %s%s', array(
				$tpl_path,
				preg_replace('/\.html$/i', '', $tpl_filename) . '.html',
			));
			trigger_error($error_message, \E_USER_WARNING);
			return escape($error_message);
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

		// reset config for this buffer (this step is necessary because we use a singleton for every template)
		$previous_config = clone $this->config;
		$this->config = new stdClass();

		// detect existence of autoescape config
		$this->config->autoescape = (strpos($buff, ' autoescape="') === FALSE) ? NULL : 'off';

		// replace comments
		$buff = preg_replace('@<!--//.*?-->@s', '', $buff);

		// replace value of src in img/input/script tag
		$buff = preg_replace_callback('/<(?:img|input|script)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* src="(?!(?:https?|file):\/\/|[\/\{])([^"]+)"/is', array($this, '_replacePath'), $buff);

		// replace value of srcset in img/source/link tag
		$buff = preg_replace_callback('/<(?:img|source|link)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* srcset="([^"]+)"/is', array($this, '_replaceSrcsetPath'), $buff);

		// replace loop and cond template syntax
		$buff = $this->_parseInline($buff);

		// include, unload/load, import
		$buff = preg_replace_callback('/{(@[\s\S]+?|(?=[\$\\\\]\w+|_{1,2}[A-Z]+|[!\(+-]|\w+(?:\(|::)|\d+|[\'"].*?[\'"]).+?)}|<(!--[#%])?(include|import|(un)?load(?(4)|(?:_js_plugin)?)|config)(?(2)\(["\']([^"\']+)["\'])(.*?)(?(2)\)--|\/)>|<!--(@[a-z@]*)([\s\S]*?)-->(\s*)/', array($this, '_parseResource'), $buff);

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

		// restore config to previous value
		$this->config = $previous_config;

		return $buff;
	}

	/**
	 * preg_replace_callback handler
	 * 1. remove ruleset from form tag
	 * 2. add hidden tag with ruleset value
	 * 3. if empty default hidden tag, generate hidden tag (ex:mid, act...)
	 * 4. generate return url, return url use in server side validator
	 * @param array $matches
	 * @return string
	 */
	private function _compileFormAuthGeneration($matches)
	{
		// check rx-autoform attribute
		if (preg_match('/\srx-autoform="([^">]*?)"/', $matches[1], $m1))
		{
			$autoform = toBool($m1[1]);
			$matches[1] = preg_replace('/\srx-autoform="([^">]*?)"/', '', $matches[1]);
		}
		else
		{
			$autoform = true;
		}
		
		// form ruleset attribute move to hidden tag
		if ($autoform && $matches[1])
		{
			preg_match('/ruleset="([^"]*?)"/is', $matches[1], $m);
			if(isset($m[0]) && $m[0])
			{
				$matches[1] = preg_replace('/' . addcslashes($m[0], '?$') . '/i', '', $matches[1]);

				if(strpos($m[1], '@') !== FALSE)
				{
					$path = str_replace('@', '', $m[1]);
					$path = './files/ruleset/' . $path . '.xml';
					$autoPath = '';
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
					$autoPath = '';
				}

				$matches[2] = '<input type="hidden" name="ruleset" value="' . $m[1] . '" />' . $matches[2];
				//assign to addJsFile method for js dynamic recache
				$matches[1] = '<?php Context::addJsFile("' . $path . '", FALSE, "", 0, "body", TRUE, "' . $autoPath . '") ?' . '>' . $matches[1];
			}
		}

		// if not exists default hidden tag, generate hidden tag
		if ($autoform)
		{
			preg_match_all('/<input[^>]* name="(act|mid)"/is', $matches[2], $m2);
			$missing_inputs = array_diff(['act', 'mid'], $m2[1]);
			if(is_array($missing_inputs))
			{
				$generatedHidden = '';
				foreach($missing_inputs as $key)
				{
					$generatedHidden .= '<input type="hidden" name="' . $key . '" value="<?php echo $__Context->' . $key . ' ?? \'\'; ?>" />';
				}
				$matches[2] = $generatedHidden . $matches[2];
			}
		}

		// return url generate
		if ($autoform)
		{
			if (!preg_match('/no-(?:error-)?return-url="true"/i', $matches[1]))
			{
				preg_match('/<input[^>]*name="error_return_url"[^>]*>/is', $matches[2], $m3);
				if(!isset($m3[0]) || !$m3[0])
				{
					$matches[2] = '<input type="hidden" name="error_return_url" value="<?php echo escape(getRequestUriByServerEnviroment(), false); ?>" />' . $matches[2];
				}
			}
			else
			{
				$matches[1] = preg_replace('/no-(?:error-)?return-url="true"/i', '', $matches[1]);
			}
		}
		
		array_shift($matches);
		return implode('', $matches);
	}

	/**
	 * fetch using ob_* function
	 * @param string $filename compiled template file name
	 * @return string
	 */
	private function _fetch($filename)
	{
		// Import Context and lang as local variables.
		$__Context = Context::getAll();
		$__Context->tpl_path = $this->path;
		global $lang;
		
		// Start the output buffer.
		$__ob_level_before_fetch = ob_get_level();
		ob_start();
		
		// Include the compiled template.
		include $filename;
		
		// Fetch contents of the output buffer until the buffer level is the same as before.
		$contents = '';
		while (ob_get_level() > $__ob_level_before_fetch)
		{
			$contents .= ob_get_clean();
		}
		
		// Insert template path comment tag.
		if(Rhymix\Framework\Debug::isEnabledForCurrentUser() && Context::getResponseMethod() === 'HTML' && !starts_with('<!DOCTYPE', $contents) && !starts_with('<?xml', $contents))
		{
			$sign = "\n" . '<!-- Template %s : ' . $this->web_path . $this->filename . ' -->' . "\n";
			$contents = sprintf($sign, 'start') . $contents . sprintf($sign, 'end');
		}
		
		return $contents;
	}

	/**
	 * preg_replace_callback handler
	 *
	 * replace image path
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replacePath($match)
	{
		$src = $this->_replaceRelativePath($match);
		return substr($match[0], 0, -strlen($match[1]) - 6) . "src=\"{$src}\"";
	}

	/**
	 * replace relative path
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replaceRelativePath($match)
	{
		//return origin code when src value started '${'.
		if(preg_match('@^\${@', $match[1]))
		{
			return $match[1];
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

		return $src;
	}

	/**
	 * preg_replace_callback handler
	 *
	 * replace srcset string with multiple paths
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replaceSrcsetPath($match)
	{
		// explode urls by comma
		$url_list = explode(",", $match[1]);

		foreach ($url_list as &$url) {
			// replace if url is not starting with the pattern
			$url = preg_replace_callback(
				'/^(?!(?:https?|file):\/\/|[\/\{])(\S+)/i',
				array($this, '_replaceRelativePath'),
				trim($url)
			);
		}
		$srcset = implode(", ", $url_list);

		return substr($match[0], 0, -strlen($match[1]) - 9) . "srcset=\"{$srcset}\"";
	}

	/**
	 * replace loop and cond template syntax
	 * @param string $buff
	 * @return string changed result
	 */
	private function _parseInline($buff)
	{
		// list of self closing tags
		$self_closing = array('area' => 1, 'base' => 1, 'basefont' => 1, 'br' => 1, 'hr' => 1, 'input' => 1, 'img' => 1, 'link' => 1, 'meta' => 1, 'param' => 1, 'frame' => 1, 'col' => 1);
		
		$skip = $this->skipTags ? sprintf('(?!%s)', implode('|', $this->skipTags)) : '';
		$split_regex = "@(</?{$skip}[a-zA-Z](?>[^<>{}\"]+|<!--.*?-->.*?<!--.*?end-->|{[^}]*}|\"(?>'.*?'|.)*?\"|.)*?>)@s";
		$nodes = preg_split($split_regex, $buff, -1, PREG_SPLIT_DELIM_CAPTURE);
		
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
					$expr = self::_replaceVar($expr);
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
								if(isset($expr_m[3]) && $expr_m[3])
								{
									$expr_m[2] .= '=>' . trim($expr_m[3]);
								}
								$nodes[$idx - 1] .= sprintf('<?php $__loop_tmp=%1$s;if($__loop_tmp)foreach($__loop_tmp as %2$s){ ?>', $expr_m[1], $expr_m[2]);
							}
							elseif(isset($expr_m[4]) && $expr_m[4])
							{
								$nodes[$idx - 1] .= "<?php for({$expr_m[4]}){ ?>";
							}
							elseif(isset($expr_m[5]) && $expr_m[5])
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
				if($node[1] == '!' || substr($node, -2, 1) == '/' || isset($self_closing[$tag]))
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
				$node = self::_replaceVar($node);
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
	 * preg_replace_callback handler
	 * replace php code.
	 * @param array $m
	 * @return string changed result
	 */
	private function _parseResource($m)
	{
		// {@ ... } or {$var} or {func(...)}
		if($m[1])
		{
			if(preg_match('@^(\w+)\(@', $m[1], $mm) && (!function_exists($mm[1]) && !in_array($mm[1], ['isset', 'unset', 'empty'])))
			{
				return $m[0];
			}
			
			if($m[1][0] == '@')
			{
				$m[1] = self::_replaceVar(substr($m[1], 1));
				return "<?php {$m[1]} ?>";
			}
			else
			{
				// Get escape options.
				if($m[1] === '$content' && preg_match('@/layouts/.+/layout\.html$@', $this->file))
				{
					$escape_option = 'noescape';
				}
				elseif(preg_match('/^\$(?:user_)?lang->[a-zA-Z0-9\_]+$/', $m[1]))
				{
					$escape_option = 'noescape';
				}
				elseif(preg_match('/^lang\(.+\)$/', $m[1]))
				{
					$escape_option = 'noescape';
				}
				else
				{
					$escape_option = $this->config->autoescape !== null ? 'auto' : 'noescape';
				}
				
				// Separate filters from variable.
				if (preg_match('@^(.+?)(?<![|\s])((?:\|[a-z]{2}[a-z0-9_]+(?::.+)?)+)$@', $m[1], $mm))
				{
					$m[1] = $mm[1];
					$filters = array_map('trim', explode_with_escape('|', substr($mm[2], 1)));
				}
				else
				{
					$filters = array();
				}
				
				// Process the variable.
				$var = self::_replaceVar($m[1]);
				
				// Apply filters.
				foreach ($filters as $filter)
				{
					// Separate filter option from the filter name.
					if (preg_match('/^([a-z0-9_-]+):(.+)$/', $filter, $matches))
					{
						$filter = $matches[1];
						$filter_option = $matches[2];
						if (!self::_isVar($filter_option) && !preg_match("/^'.*'$/", $filter_option) && !preg_match('/^".*"$/', $filter_option))
						{
							$filter_option = "'" . escape_sqstr($filter_option) . "'";
						}
						else
						{
							$filter_option = self::_replaceVar($filter_option);
						}
					}
					else
					{
						$filter_option = null;
					}
					
					// Apply each filter.
					switch ($filter)
					{
						case 'auto':
						case 'autoescape':
						case 'autolang':
						case 'escape':
						case 'noescape':
							$escape_option = $filter;
							break;
							
						case 'escapejs':
							$var = "escape_js({$var})";
							break;
							
						case 'json':
							$var = "json_encode({$var})";
							break;
							
						case 'strip':
						case 'strip_tags':
							$var = $filter_option ? "strip_tags({$var}, {$filter_option})" : "strip_tags({$var})";
							break;
							
						case 'trim':
							$var = "trim({$var})";
							break;
							
						case 'urlencode':
							$var = "rawurlencode({$var})";
							break;
							
						case 'lower':
							$var = "strtolower({$var})";
							break;
							
						case 'upper':
							$var = "strtoupper({$var})";
							break;
							
						case 'nl2br':
							$var = $this->_applyEscapeOption($var, $escape_option);
							$var = "nl2br({$var})";
							$escape_option = 'noescape';
							break;
							
						case 'join':
							$var = $filter_option ? "implode({$filter_option}, {$var})" : "implode(', ', {$var})";
							break;
							
						case 'date':
							$var = $filter_option ? "getDisplayDateTime(ztime({$var}), {$filter_option})" : "getDisplayDateTime(ztime({$var}), 'Y-m-d H:i:s')";
							break;
							
						case 'format':
						case 'number_format':
							$var = $filter_option ? "number_format({$var}, {$filter_option})" : "number_format({$var})";
							break;
						
						case 'shorten':						
						case 'number_shorten':
							$var = $filter_option ? "number_shorten({$var}, {$filter_option})" : "number_shorten({$var})";
							break;
							
						case 'link':
							$var = $this->_applyEscapeOption($var, $escape_option);
							if ($filter_option)
							{
								$filter_option = $this->_applyEscapeOption($filter_option, $escape_option);
								$var = "'<a href=\"' . {$filter_option} . '\">' . {$var} . '</a>'";
							}
							else
							{
								$var = "'<a href=\"' . {$var} . '\">' . {$var} . '</a>'";
							}
							$escape_option = 'noescape';
							break;
							
						default:
							$filter = escape_sqstr($filter);
							$var = "'INVALID FILTER ({$filter})'";
					}
				}
				
				// Apply the escape option and return.
				return '<?php echo ' . $this->_applyEscapeOption($var, $escape_option) . ' ?>';
			}
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
					$plugin = self::_replaceVar($m[5]);
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
					$metavars = '';
					$replacements = HTMLDisplayHandler::$replacements;
					$attr['target'] = preg_replace(array_keys($replacements), array_values($replacements), $attr['target']);
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
								$result = vsprintf("Context::unloadFile('%s', '%s');", [$attr['target'] ?? '', $attr['targetie'] ?? '']);
							}
							else
							{
								$metafile = isset($attr['target']) ? $attr['target'] : '';
								$result = vsprintf("Context::loadFile(['%s', '%s', '%s', '%s']);", [
									$attr['target'] ?? '', $attr['type'] ?? '', $attr['targetie'] ?? ($isRemote ? $this->source_type : ''), $attr['index'] ?? '',
								]);
							}
							break;
						case 'css':
						case 'less':
						case 'scss':
							if($doUnload)
							{
								$result = vsprintf("Context::unloadFile('%s', '%s', '%s');", [
									$attr['target'] ?? '', $attr['targetie'] ?? '', $attr['media'] ?? '',
								]);
							}
							else
							{
								$metafile = isset($attr['target']) ? $attr['target'] : '';
								$metavars = isset($attr['vars']) ? ($attr['vars'] ? self::_replaceVar($attr['vars']) : '') : '';
								$result = vsprintf("Context::loadFile(['%s', '%s', '%s', '%s', %s]);", [
									$attr['target'] ?? '', $attr['media'] ?? '', $attr['targetie'] ?? ($isRemote ? $this->source_type : ''), $attr['index'] ?? '',
									isset($attr['vars']) ? ($attr['vars'] ? self::_replaceVar($attr['vars']) : '[]') : '[]',
								]);
							}
							break;
					}

					$result = "<?php {$result} ?>";
					if($metafile)
					{
						if(!$metavars)
						{
							$result = "<!--#Meta:{$metafile}-->" . $result;
						}
						else
						{
							// LESS or SCSS needs the variables to be substituted.
							$result = "<!--#Meta:{$metafile}?{$metavars}-->" . $result;
						}
					}

					return $result;
				// <config ...>
				case 'config':
					$result = '';
					if(preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $config_matches, PREG_SET_ORDER))
					{
						foreach($config_matches as $config_match)
						{
							$result .= "\$this->config->{$config_match[1]} = '" . trim(strtolower($config_match[2])) . "';";
						}
					}
					return "<?php {$result} ?>";
			}
		}

		// <!--@..--> such as <!--@if($cond)-->, <!--@else-->, <!--@end-->
		if($m[7])
		{
			$m[7] = substr($m[7], 1);
			if(!$m[7])
			{
				return '<?php ' . self::_replaceVar($m[8]) . '{ ?>' . $m[9];
			}
			if(!preg_match('/^(?:((?:end)?(?:if|switch|for(?:each)?|while)|end)|(else(?:if)?)|(break@)?(case|default)|(break))$/', $m[7], $mm))
			{
				return '';
			}
			if($mm[1])
			{
				if($mm[1][0] == 'e')
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
					$precheck = "if({$var})";
				}
				return '<?php ' . self::_replaceVar($precheck . $m[7] . $m[8]) . '{ ?>' . $m[9];
			}
			if($mm[2])
			{
				return "<?php }{$m[7]}" . self::_replaceVar($m[8]) . "{ ?>" . $m[9];
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
	 * Apply escape option to an expression.
	 */
	private function _applyEscapeOption($str, $escape_option)
	{
		switch($escape_option)
		{
			case 'escape':
				return "htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', true)";
			case 'noescape':
				return "{$str}";
			case 'autoescape':
				return "htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false)";
			case 'autolang':
				return "(preg_match('/^\\$(?:user_)?lang->[a-zA-Z0-9\_]+$/', {$str}) ? ({$str}) : htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false))";
			case 'auto':
			default:
				return "(\$this->config->autoescape === 'on' ? htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false) : ({$str}))";
		}
	}

	/**
	 * change relative path
	 * @param string $path
	 * @return string
	 */
	private function _getRelativeDir($path)
	{
		$_path = $path;

		$fileDir = strtr(realpath($this->path), '\\', '/');
		if($path[0] != '/')
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

		$path = preg_replace('/^' . preg_quote(\RX_BASEDIR, '/') . '/', '', $path);

		return $path;
	}
	
	/**
	 * Check if a string seems to contain a variable.
	 * 
	 * @param string $str
	 * @return bool
	 */
	private static function _isVar($str)
	{
		return preg_match('@(?<!::|\\\\|(?<!eval\()\')\$([a-z_][a-z0-9_]*)@i', $str) ? true : false;
	}

	/**
	 * Replace PHP variables of $ character
	 * 
	 * @param string $php
	 * @return string $__Context->varname
	 */
	private static function _replaceVar($php)
	{
		if(!strlen($php))
		{
			return '';
		}
		
		return preg_replace_callback('@(?<!::|\\\\|(?<!eval\()\')\$([a-z_][a-z0-9_]*)@i', function($matches) {
			if (preg_match('/^(?:GLOBALS|_SERVER|_COOKIE|_GET|_POST|_REQUEST|_SESSION|__Context|this|lang)$/', $matches[1]))
			{
				return '$' . $matches[1];
			}
			else
			{
				return '$__Context->' . $matches[1];
			}
		}, $php);
	}

}
/* End of File: TemplateHandler.class.php */
/* Location: ./classes/template/TemplateHandler.class.php */
