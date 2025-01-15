<?php

namespace Rhymix\Framework;

/**
 * The template class.
 */
class Template
{
	/**
	 * Properties for convenience
	 */
	public $user;
	public $request;

	/**
	 * Properties for configuration
	 */
	public $config;
	public $source_type;
	public $source_name;
	public $parent;
	public $vars;

	/**
	 * Properties for path manipulation
	 */
	public $absolute_dirname;
	public $relative_dirname;
	public $filename;
	public $extension;
	public $exists;
	public $absolute_path;
	public $relative_path;

	/**
	 * Properties for caching
	 */
	public $cache_enabled = true;
	public $cache_path;

	/**
	 * Properties for backward compatibility
	 */
	public $path;
	public $web_path;

	/**
	 * Properties for state management during compilation/execution
	 */
	protected $_ob_level;
	protected $_fragments = [];
	protected static $_loopvars = [];
	protected static $_stacks = [];

	/**
	 * Properties for optimization
	 */
	protected static $_mtime;
	protected static $_delay_compile;
	protected static $_json_options;
	protected static $_json_options2;

	/**
	 * Provided for compatibility with old TemplateHandler.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return new self();
	}

	/**
	 * You can also call the constructor directly.
	 *
	 * @param ?string $dirname
	 * @param ?string $filename
	 * @param ?string $extension
	 * @return void
	 */
	public function __construct(?string $dirname = null, ?string $filename = null, ?string $extension = null)
	{
		// Initialize configuration.
		$this->_initConfig();

		// Set user and current request information.
		$this->user = Session::getMemberInfo() ?: new Helpers\SessionHelper();
		$this->request = \Context::getCurrentRequest();

		// Populate static properties for optimization.
		if (self::$_mtime === null)
		{
			self::$_mtime = filemtime(__FILE__);
		}
		if (self::$_delay_compile === null)
		{
			self::$_delay_compile = config('view.delay_compile') ?? 0;
		}
		if (self::$_json_options === null)
		{
			self::$_json_options = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE;
		}
		if (self::$_json_options2 === null)
		{
			self::$_json_options2 = \JSON_HEX_TAG | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;
		}

		// If paths were provided, initialize immediately.
		if ($dirname !== null && $filename !== null)
		{
			$this->_setSourcePath($dirname, $filename, $extension ?? 'auto');
		}
	}

	/**
	 * Initialize the configuration object.
	 *
	 * @return void
	 */
	protected function _initConfig(): void
	{
		$this->config = new \stdClass;
		$this->config->version = 1;
		$this->config->autoescape = false;
		$this->config->context = 'HTML';
	}

	/**
	 * Initialize and normalize paths.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @param string $extension
	 * @return void
	 */
	protected function _setSourcePath(string $dirname, string $filename, string $extension = 'auto'): void
	{
		// Normalize the template path. Result will look like 'modules/foo/views/'
		$dirname = trim(preg_replace('@^(' . preg_quote(\RX_BASEDIR, '@') . '|\./)@', '', strtr($dirname, ['\\' => '/', '//' => '/'])), '/') . '/';
		$dirname = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $dirname);
		$this->absolute_dirname = \RX_BASEDIR . $dirname;
		$this->relative_dirname = $dirname;

		// Normalize the filename. Result will look like 'bar/example.html'
		$filename = trim(strtr($filename, ['\\' => '/', '//' => '/']), '/');
		$filename = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $filename);

		// If the filename doesn't have a typical extension and doesn't exist, try adding common extensions.
		if (!preg_match('/\.(?:html?|php)$/', $filename) && !Storage::isFile($this->absolute_dirname . $filename))
		{
			if ($extension !== 'auto')
			{
				$filename .= '.' . $extension;
				$this->extension = $extension;
			}
			elseif (Storage::isFile($this->absolute_dirname . $filename . '.html'))
			{
				$filename .= '.html';
				$this->extension = 'html';
				$this->exists = true;
			}
			elseif (Storage::isFile($this->absolute_dirname . $filename . '.blade.php'))
			{
				$filename .= '.blade.php';
				$this->extension = 'blade.php';
				$this->exists = true;
			}
			else
			{
				$filename .= '.html';
				$this->extension = 'html';
			}
		}

		// Set the remainder of properties.
		$this->filename = $filename;
		$this->absolute_path = $this->absolute_dirname . $filename;
		$this->relative_path = $this->relative_dirname . $filename;
		if ($this->extension === null)
		{
			$this->extension = preg_match('/\.(blade\.php|[a-z]+)$/i', $filename, $m) ? $m[1] : '';
		}
		if ($this->exists === null)
		{
			$this->exists = Storage::isFile($this->absolute_path);
		}
		if ($this->exists && $this->extension === 'blade.php')
		{
			$this->config->version = 2;
			$this->config->autoescape = true;
		}
		if (preg_match('!^(addons|common|(?:m\.)?layouts|modules|plugins|themes|widgets|widgetstyles)/(\w+)!', $this->relative_dirname, $match))
		{
			$this->source_type = $match[1];
			$this->source_name = $match[2];
		}
		$this->path = $this->absolute_dirname;
		$this->web_path = \RX_BASEURL . $this->relative_dirname;
		$this->setCachePath();
	}

	/**
	 * Set the path for the cache file.
	 *
	 * @param ?string $cache_path
	 * @return void
	 */
	public function setCachePath(?string $cache_path = null)
	{
		$clean_path = str_replace('../', '__parentdir/', $this->relative_path);
		$this->cache_path = $cache_path ?? (\RX_BASEDIR . 'files/cache/template/' . $clean_path . '.compiled.php');
		if ($this->exists)
		{
			Debug::addFilenameAlias($this->absolute_path, $this->cache_path);
		}
	}

	/**
	 * Disable caching.
	 *
	 * @return void
	 */
	public function disableCache(): void
	{
		$this->cache_enabled = false;
	}

	/**
	 * Check if the template file exists.
	 *
	 * @return bool
	 */
	public function exists(): bool
	{
		return $this->exists ? true : false;
	}

	/**
	 * Get the parent template.
	 *
	 * @return ?self
	 */
	public function getParent(): ?self
	{
		return $this->parent;
	}

	/**
	 * Set the parent template.
	 *
	 * @param ?self $parent
	 * @return void
	 */
	public function setParent(self $parent): void
	{
		$this->parent = $parent;
	}

	/**
	 * Get vars.
	 *
	 * @return ?object
	 */
	public function getVars(): ?object
	{
		return $this->vars;
	}

	/**
	 * Set vars.
	 *
	 * @param array|object $vars
	 * @return void
	 */
	public function setVars($vars): void
	{
		if (is_array($vars))
		{
			$this->vars = (object)$vars;
		}
		elseif (is_object($vars))
		{
			$this->vars = clone $vars;
		}
		else
		{
			throw new Exception('Template vars must be an array or object');
		}
	}

	/**
	 * Add vars.
	 *
	 * @param array|object $vars
	 * @return void
	 */
	public function addVars($vars): void
	{
		if (!isset($this->vars))
		{
			$this->vars = new \stdClass;
		}

		foreach (is_object($vars) ? get_object_vars($vars) : $vars as $key => $val)
		{
			$this->vars->$key = $val;
		}
	}

	/**
	 * Compile and execute a template file.
	 *
	 * You don't need to pass any paths if you have already supplied them
	 * through the constructor. They exist for backward compatibility.
	 *
	 * $override_filename should be considered deprecated, as it is only
	 * used in faceOff (layout source editor).
	 *
	 * @param ?string $dirname
	 * @param ?string $filename
	 * @param ?string $override_filename
	 * @return string
	 */
	public function compile(?string $dirname = null, ?string $filename = null, ?string $override_filename = null)
	{
		// If paths are given, initialize now.
		if ($dirname && $filename)
		{
			$this->_initConfig();
			$this->_setSourcePath($dirname, $filename);
		}
		if ($override_filename)
		{
			$override_filename = trim(preg_replace('@^' . preg_quote(\RX_BASEDIR, '@') . '|\./@', '', strtr($override_filename, ['\\' => '/', '//' => '/'])), '/');
			$override_filename = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $override_filename);
			$this->absolute_path = \RX_BASEDIR . $override_filename;
			$this->relative_path = $override_filename;
			$this->exists = Storage::exists($this->absolute_path);
			$this->setCachePath();
		}

		// Return error if the source file does not exist.
		if (!$this->exists)
		{
			$error_message = sprintf('Template not found: %s', $this->relative_path);
			trigger_error($error_message, \E_USER_WARNING);
			return escape($error_message);
		}

		// Record the starting time.
		$start = microtime(true);

		// Find the latest mtime of the source template and the template parser.
		$filemtime = filemtime($this->absolute_path);
		if ($filemtime > time() - self::$_delay_compile)
		{
			$latest_mtime = self::$_mtime;
		}
		else
		{
			$latest_mtime = max($filemtime, self::$_mtime);
		}

		// If a cached result does not exist, or if it is stale, compile again.
		if (!Storage::exists($this->cache_path) || filemtime($this->cache_path) < $latest_mtime || !$this->cache_enabled)
		{
			$content = $this->parse();
			if (!Storage::write($this->cache_path, $content))
			{
				throw new Exception('Cannot write template cache file: ' . $this->cache_path);
			}
		}

		$output = $this->execute();

		// Record the time elapsed.
		$elapsed_time = microtime(true) - $start;
		if (!isset($GLOBALS['__template_elapsed__']))
		{
			$GLOBALS['__template_elapsed__'] = 0;
		}
		$GLOBALS['__template_elapsed__'] += $elapsed_time;

		return $output;
	}

	/**
	 * Compile a template and return the PHP code.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return string
	 */
	public function compileDirect(string $dirname, string $filename): string
	{
		// Initialize paths. Return error if file does not exist.
		$this->_initConfig();
		$this->_setSourcePath($dirname, $filename);
		if (!$this->exists)
		{
			$error_message = sprintf('Template not found: %s', $this->relative_path);
			trigger_error($error_message, \E_USER_WARNING);
			return escape($error_message);
		}

		// Parse the template, but don't actually execute it.
		return $this->parse();
	}

	/**
	 * Convert template code to PHP using a version-specific parser.
	 *
	 * Directly passing $content as a string is not available as an
	 * official API. It only exists for unit testing.
	 *
	 * @return string
	 */
	public function parse(?string $content = null): string
	{
		// Read the source, or use the provided content.
		if ($content === null && $this->exists)
		{
			$content = Storage::read($this->absolute_path);
			$content = trim($content) . PHP_EOL;
		}
		if ($content === null || $content === '' || $content === PHP_EOL)
		{
			return '';
		}

		// Remove UTF-8 BOM and convert CRLF to LF.
		$content = preg_replace(['/^\xEF\xBB\xBF/', '/\r\n/'], ['', "\n"], $content);

		// Check the config tag: <config version="2" /> or <config autoescape="on" />
		$content = preg_replace_callback('!(?<=^|\n)<config\s+(\w+)="([^"]+)"\s*/?>!', function($match) {
			$this->config->{$match[1]} = ($match[1] === 'version' ? intval($match[2]) : toBool($match[2]));
			return sprintf('<?php $this->config->%s = %s; ?>', $match[1], var_export($this->config->{$match[1]}, true));
		}, $content);

		// Check the alternative version directive: @version(2)
		$content = preg_replace_callback('!(?<=^|\n)@version\s?\(([0-9]+)\)!', function($match) {
			$this->config->version = intval($match[1]);
			return sprintf('<?php $this->config->version = %s; ?>', var_export($this->config->version, true));
		}, $content);

		// Call a version-specific parser to convert template code into PHP.
		$class_name = '\Rhymix\Framework\Parsers\Template\TemplateParser_v' . $this->config->version;
		$parser = new $class_name;
		$content = $parser->convert($content, $this);

		return $content;
	}

	/**
	 * Execute the converted template and return the output.
	 *
	 * @return string
	 */
	public function execute(): string
	{
		// Import Context and lang as local variables.
		$__Context = $this->vars ?: \Context::getAll();
		$__Context->tpl_path = './' . $this->relative_dirname;

		// Start the output buffer.
		$this->_ob_level = ob_get_level();
		ob_start();

		// Include the compiled template.
		include $this->cache_path;

		// Fetch the content of the output buffer until the buffer level is the same as before.
		$content = '';
		while (ob_get_level() > $this->_ob_level)
		{
			$content .= ob_get_clean();
		}

		// Insert comments for debugging.
		if(Debug::isEnabledForCurrentUser() && \Context::getResponseMethod() === 'HTML' && !preg_match('/^<(?:\!DOCTYPE|\?xml)/', $content))
		{
			$meta = '<!--Template%s:' . $this->relative_path . '-->' . "\n";
			$content = sprintf($meta, 'Start') . $content . sprintf($meta, 'End');
		}

		return $content;
	}

	/**
	 * Get a fragment of the executed output.
	 *
	 * @param string $name
	 * @return ?string
	 */
	public function getFragment(string $name): ?string
	{
		if (isset($this->_fragments[$name]))
		{
			return $this->_fragments[$name];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the contents of a stack.
	 *
	 * @param string $name
	 * @return ?array
	 */
	public function getStack(string $name): ?array
	{
		if (isset(self::$_stacks[$name]))
		{
			return self::$_stacks[$name];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Check if a path should be treated as relative to the path of the current template.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isRelativePath(string $path): bool
	{
		return !preg_match('#^((?:https?|file|data):|[\/\{<])#i', $path);
	}

	/**
	 * Convert a relative path using the given basepath.
	 *
	 * @param string $path
	 * @param ?string $basepath
	 * @return string
	 */
	public function convertPath(string $path, ?string $basepath = null): string
	{
		// If basepath is not provided, use the relative dir of the current instance.
		if ($basepath === null)
		{
			$basepath = $this->relative_dirname;
		}

		// Path relative to the Rhymix installation directory?
		if (preg_match('#^\^/?(\w.+)$#s', $path, $match))
		{
			$path = \RX_BASEURL . $match[1];
		}

		// Other paths will be relative to the given basepath.
		else
		{
			$path = preg_replace('#/\./#', '/', $basepath . $path);
		}

		// Normalize and return the path.
		return $this->normalizePath($path);
	}

	/**
	 * Normalize a path by removing extra slashes and parent directory references.
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalizePath(string $path): string
	{
		$path = preg_replace('#[\\\\/]+#', '/', $path);
		$path = preg_replace('#/\./#', '/', $path);
		while (($tmp = preg_replace('#(/|^)(?!\.\./)[^/]+/\.\.(/|$)#', '$1', $path)) !== $path)
		{
			$path = $tmp;
		}
		return $path;
	}

	/**
	 * =================== HELPER FUNCTIONS FOR TEMPLATE v2 ===================
	 */

	/**
	 * Include another template from v2 @include directive.
	 *
	 * Blade has several variations of the @include directive, and we need
	 * access to the actual PHP args in order to process them accurately.
	 * So we do this in the Template class, not in the converter.
	 *
	 * @param ...$args
	 * @return string
	 */
	protected function _v2_include(...$args): string
	{
		// Set some basic information.
		$directive = $args[0];
		$extension = $this->extension === 'blade.php' ? 'blade.php' : null;
		$isConditional = in_array($directive, ['includeWhen', 'includeUnless']);
		$basedir = $this->relative_dirname;
		$cond = $isConditional ? $args[1] : null;
		$path = $isConditional ? $args[2] : $args[1];
		$vars = $isConditional ? ($args[3] ?? null) : ($args[2] ?? null);

		// If the conditions are not met, return.
		if ($isConditional && $directive === 'includeWhen' && !$cond)
		{
			return '';
		}
		if ($isConditional && $directive === 'includeUnless' && $cond)
		{
			return '';
		}

		// Handle paths relative to the Rhymix installation directory.
		if (preg_match('#^\^/?(\w.+)$#s', $path, $match))
		{
			$basedir = str_contains($match[1], '/') ? dirname($match[1]) : \RX_BASEDIR;
			$path = basename($match[1]);
		}

		// Convert relative paths embedded in the filename.
		if (preg_match('#^(.+)/([^/]+)$#', $path, $match))
		{
			$basedir = $this->normalizePath($basedir . $match[1] . '/');
			$path = $match[2];
		}

		// Create a new instance of TemplateHandler.
		$template = new self($basedir, $path, $extension);

		// If the directive is @includeIf and the template file does not exist, return.
		if ($directive === 'includeIf' && !$template->exists())
		{
			return '';
		}

		// Set variables.
		$template->setParent($this);
		if ($this->vars)
		{
			$template->setVars($this->vars);
		}
		if ($vars !== null)
		{
			$template->addVars($vars);
		}

		// Compile and return.
		return $template->compile();
	}

	/**
	 * Load a resource from v2 @load directive.
	 *
	 * The Blade-style syntax does not have named arguments, so we must rely
	 * on the position and format of each argument to guess what it is for.
	 * Fortunately, there are only a handful of valid options for the type,
	 * media, and index attributes.
	 *
	 * @param string $path
	 * @param string $media_type
	 * @param int $index
	 * @param array|object $vars
	 * @return void
	 */
	protected function _v2_loadResource(string $path, $media_type = null, $index = null, $vars = null): void
	{
		// Assign the path.
		if (empty($path))
		{
			trigger_error('Resource loading directive used with no path', \E_USER_WARNING);
			return;
		}

		// Check whether the path is an internal or external link.
		$external = false;
		if (preg_match('#^\^#', $path))
		{
			$path = './' . ltrim($path, '^/');
		}
		elseif ($this->isRelativePath($path))
		{
			$path = $this->convertPath($path, './' . $this->relative_dirname);
		}
		else
		{
			$external = true;
		}

		// If any of the variables seems to be an array or object, it's $vars.
		if (!is_scalar($media_type ?? ''))
		{
			$vars = $media_type;
			$media_type = null;
		}
		if (!is_scalar($index ?? ''))
		{
			$vars = $index;
			$index = null;
		}
		if (ctype_digit($media_type ?? ''))
		{
			$index = $media_type;
			$media_type = null;
		}

		// Split the media type if it has a colon in it.
		if (preg_match('#^(css|js):(.+)$#s', $media_type ?? '', $match))
		{
			$media_type = trim($match[2]);
			$type = $match[1];
		}

		// Determine the type of resource.
		elseif (!$external && str_starts_with($path, './common/js/plugins/'))
		{
			$type = 'jsplugin';
		}
		elseif (!$external && preg_match('#/lang(\.xml)?$#', $path))
		{
			$type = 'lang';
		}
		elseif (preg_match('#\.(css|js|scss|less)($|\?|/)#', $path, $match))
		{
			$type = $match[1];
		}
		elseif (preg_match('#/css\d?\?.+#', $path))
		{
			$type = 'css';
		}
		else
		{
			$type = 'unknown';
		}

		// Load the resource.
		if ($type === 'jsplugin')
		{
			if (preg_match('#/common/js/plugins/([^/]+)#', $path, $match))
			{
				$plugin_name = $match[1];
				\Context::loadJavascriptPlugin($plugin_name);
			}
			else
			{
				trigger_error("Unable to find JS plugin at $path", \E_USER_WARNING);
			}
		}
		elseif ($type === 'lang')
		{
			$lang_dir = preg_replace('#/lang\.xml$#', '', $path);
			\Context::loadLang($lang_dir);
		}
		elseif ($type === 'js')
		{
			\Context::loadFile([
				$path,
				$media_type ?? '',
				$external ? $this->source_type : '',
				$index ? intval($index) : '',
			]);
		}
		elseif ($type === 'css' || $type === 'scss' || $type === 'less')
		{
			\Context::loadFile([
				$path,
				$media_type ?? '',
				$external ? $this->source_type : '',
				$index ? intval($index) : '',
				$vars ?? [],
			]);
		}
		else
		{
			trigger_error("Unable to determine type of resource at $path", \E_USER_WARNING);
		}
	}

	/**
	 * Initialize v2 loop variable.
	 *
	 * @param string $stack_id
	 * @param array|Traversable &$array
	 * @return object
	 */
	protected function _v2_initLoopVar(string $stack_id, &$array): object
	{
		// Create the data structure.
		$loop = new \stdClass;
		$loop->index = 0;
		$loop->iteration = 1;
		$loop->count = is_countable($array) ? count($array) : countobj($array);
		$loop->remaining = $loop->count - 1;
		$loop->first = true;
		$loop->last = ($loop->count === 1);
		$loop->even = false;
		$loop->odd = true;
		$loop->depth = count(self::$_loopvars) + 1;
		$loop->parent = count(self::$_loopvars) ? end(self::$_loopvars) : null;

		// Append to stack and return.
		return self::$_loopvars[$stack_id] = $loop;
	}

	/**
	 * Increment v2 loop variable.
	 *
	 * @param object $loopvar
	 * @return void
	 */
	protected function _v2_incrLoopVar(object $loop): void
	{
		// Update properties.
		$loop->index++;
		$loop->iteration++;
		$loop->remaining--;
		$loop->first = ($loop->count === 1);
		$loop->last = ($loop->iteration === $loop->count);
		$loop->even = ($loop->iteration % 2 === 0);
		$loop->odd = !$loop->even;
	}

	/**
	 * Remove v2 loop variable.
	 *
	 * @param object $loopvar
	 * @return void
	 */
	protected function _v2_removeLoopVar(object $loop): void
	{
		// Remove from stack.
		if ($loop === end(self::$_loopvars))
		{
			array_pop(self::$_loopvars);
		}
	}

	/**
	 * Attribute builder for v2.
	 *
	 * @param string $attribute
	 * @param array $definition
	 * @return string
	 */
	protected function _v2_buildAttribute(string $attribute, array $definition = []): string
	{
		$delimiters = [
			'class' => ' ',
			'style' => '; ',
		];

		$values = [];
		foreach ($definition as $key => $val)
		{
			if (is_int($key) && !empty($val))
			{
				$values[] = $val;
			}
			elseif ($val)
			{
				$values[] = $key;
			}
		}

		return sprintf(' %s="%s"', $attribute, escape(implode($delimiters[$attribute], $values), false));
	}

	/**
	 * Auth checker for v2.
	 *
	 * @param string $type
	 * @return bool
	 */
	protected function _v2_checkAuth(string $type = 'member'): bool
	{
		$grant = \Context::get('grant');
		switch ($type)
		{
			case 'admin': return $this->user->isAdmin();
			case 'manager': return $grant->manager ?? false;
			case 'member': return $this->user->isMember();
			default: false;
		}
	}

	/**
	 * Capability checker for v2.
	 *
	 * @param int $check_type
	 * @param string|array $capability
	 * @return bool
	 */
	protected function _v2_checkCapability(int $check_type, $capability): bool
	{
		$grant = \Context::get('grant');
		if (!($grant instanceof \Rhymix\Modules\Module\Models\Permission))
		{
			return false;
		}
		elseif ($check_type === 1)
		{
			return $grant->can($capability);
		}
		elseif ($check_type === 2)
		{
			return !$grant->can($capability);
		}
		elseif (is_array($capability))
		{
			foreach ($capability as $cap)
			{
				if ($grant->can($cap))
				{
					return true;
				}
			}
			return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check if a validation error exists for v2.
	 *
	 * @param ...$args
	 * @return bool
	 */
	protected function _v2_errorExists(...$args): bool
	{
		$validator_id = \Context::get('XE_VALIDATOR_ID');
		$validator_message = \Context::get('XE_VALIDATOR_MESSAGE');
		if (empty($validator_id) || empty($validator_message))
		{
			return false;
		}
		return count($args) ? in_array((string)$validator_id, $args, true) : true;
	}

	/**
	 * Lang shortcut for v2.
	 *
	 * @param ...$args
	 * @return string
	 */
	protected function _v2_lang(...$args): string
	{
		if (!isset($GLOBALS['lang']) || !$GLOBALS['lang'] instanceof Lang)
		{
			$GLOBALS['lang'] = Lang::getInstance(\Context::getLangType());
			$GLOBALS['lang']->loadDirectory(\RX_BASEDIR . 'common/lang', 'common');
		}

		if (isset($args[0]) && !strncmp($args[0], 'this.', 5))
		{
			$args[0] = $this->source_name . '.' . substr($args[0], 5);
		}

		return $GLOBALS['lang']->get(...$args);
	}
}
