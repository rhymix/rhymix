<?php

namespace Rhymix\Framework;

/**
 * The template class.
 */
class Template
{
	/**
	 * Properties for user
	 */
	public $user;

	/**
	 * Properties for internal use
	 */
	public $config;
	public $absolute_dirname;
	public $relative_dirname;
	public $filename;
	public $extension;
	public $exists;
	public $absolute_path;
	public $relative_path;
	public $cache_path;
	public $cache_enabled = true;
	public $ob_level = 0;
	public $vars;

	/**
	 * Static properties
	 */
	protected static $_mtime;
	protected static $_delay_compile;

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
		// Set instance configuration to default values.
		$this->config = new \stdClass;
		$this->config->version = 1;
		$this->config->autoescape = false;
		$this->config->context = 'HTML';

		// Set user information.
		$this->user = Session::getMemberInfo() ?: new Helpers\SessionHelper();

		// Cache commonly used configurations as static properties.
		if (self::$_mtime === null)
		{
			self::$_mtime = filemtime(__FILE__);
		}
		if (self::$_delay_compile === null)
		{
			self::$_delay_compile = config('view.delay_compile') ?? 0;
		}

		// If paths were provided, initialize immediately.
		if ($dirname && $filename)
		{
			$this->_setSourcePath($dirname, $filename, $extension ?? 'auto');
		}
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
		$dirname = trim(preg_replace('@^' . preg_quote(\RX_BASEDIR, '@') . '|\./@', '', strtr($dirname, ['\\' => '/', '//' => '/'])), '/') . '/';
		$dirname = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $dirname);
		$this->absolute_dirname = \RX_BASEDIR . $dirname;
		$this->relative_dirname = $dirname;

		// Normalize the filename. Result will look like 'bar/example.html'
		$filename = trim(strtr($filename, ['\\' => '/', '//' => '/']), '/');
		$filename = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $filename);

		// If the filename doesn't have a typical extension and doesn't exist, try adding common extensions.
		if (!preg_match('/\.(?:html?|php)$/', $filename) && !Storage::exists($this->absolute_dirname . $filename))
		{
			if ($extension !== 'auto')
			{
				$filename .= '.' . $extension;
				$this->extension = $extension;
			}
			elseif (Storage::exists($this->absolute_dirname . $filename . '.html'))
			{
				$filename .= '.html';
				$this->extension = 'html';
				$this->exists = true;
			}
			elseif (Storage::exists($this->absolute_dirname . $filename . '.blade.php'))
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
			$this->exists = Storage::exists($this->absolute_path);
		}
		if ($this->exists && $this->extension === 'blade.php')
		{
			$this->config->version = 2;
			$this->config->autoescape = true;
		}
		$this->_setCachePath();
	}

	/**
	 * Set the path for the cache file.
	 *
	 * @return void
	 */
	protected function _setCachePath()
	{
		$this->cache_path = \RX_BASEDIR . 'files/cache/template/' . $this->relative_path . '.compiled.php';
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
			$this->vars = $vars;
		}
		else
		{
			throw new Exception('Template vars must be an array or object');
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
			$this->_setSourcePath($dirname, $filename);
		}
		if ($override_filename)
		{
			$override_filename = trim(preg_replace('@^' . preg_quote(\RX_BASEDIR, '@') . '|\./@', '', strtr($override_filename, ['\\' => '/', '//' => '/'])), '/') . '/';
			$override_filename = preg_replace('/[\{\}\(\)\[\]<>\$\'"]/', '', $override_filename);
			$this->absolute_path = \RX_BASEDIR . $override_filename;
			$this->relative_path = $override_filename;
			$this->exists = Storage::exists($this->absolute_path);
			$this->_setCachePath();
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

		// Start the output buffer.
		$this->ob_level = ob_get_level();
		ob_start();

		// Include the compiled template.
		include $this->cache_path;

		// Fetch the content of the output buffer until the buffer level is the same as before.
		$content = '';
		while (ob_get_level() > $this->ob_level)
		{
			$content .= ob_get_clean();
		}

		// Insert comments for debugging.
		if(Debug::isEnabledForCurrentUser() && \Context::getResponseMethod() === 'HTML' && !preg_match('/^<(?:\!DOCTYPE|\?xml)/', $content))
		{
			$meta = '<!--#Template%s:' . $this->relative_path . '-->' . "\n";
			$content = sprintf($meta, 'Start') . $content . sprintf($meta, 'End');
		}

		return $content;
	}
}
