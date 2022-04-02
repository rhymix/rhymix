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
	public $source_filename;
	public $source_dir;
	public $relative_filename;
	public $relative_dir;
	public $target_filename;
	public $target_dir;
	
	/**
	 * Static properties
	 */
	protected static $_mtime = 0;
	protected static $_ob_level = 0;
	 
	/**
	 * Constructor
	 * 
	 * @param string $filename
	 */
	public function __construct(string $filename)
	{
		// Convert the template path.
		$filename = strtr($filename, ['\\' => '/', '//' => '/']);
		if (preg_match('!^(?:' . preg_quote(\RX_BASEDIR, '!') . '|\./)((?:[^/]+/)*)([^/]+?)(\.html)?$!', $filename, $matches))
		{
			$this->source_filename = \RX_BASEDIR . $matches[1] . $matches[2] . '.html';
			if (!file_exists($this->source_filename) || !is_readable($this->source_filename))
			{
				throw new Exception('Template not found: ' . $matches[1] . $matches[2]);
			}
			$this->source_dir = \RX_BASEDIR . $matches[1];
			$this->relative_filename = $matches[1] . $matches[2] . '.html';
			$this->relative_dir = $matches[1];
			$this->target_filename = \RX_BASEDIR . 'files/cache/template/' . strtr($matches[1] . $matches[2], ['..' => 'dotdot']) . '.html.php';
			$this->target_dir = dirname($this->target_filename) . '/';
		}
		else
		{
			$this->source_filename = realpath($filename);
			if (!file_exists($this->source_filename) || !is_readable($this->source_filename))
			{
				throw new Exception('Template not found: ' . $this->source_filename);
			}
			$this->source_dir = dirname($this->source_filename) . '/';
			$this->relative_filename = $this->source_filename;
			$this->relative_dir = $this->source_dir;
			$this->target_filename = \RX_BASEDIR . 'files/cache/template/common/hash/' . sha1($this->source_filename) . '.php';
			$this->target_dir = \RX_BASEDIR . 'files/cache/template/common/hash/';
		}
		
		// Initialize template configuration.
		$this->config = new \stdClass;
		$this->config->version = 1;
		$this->config->autoescape = false;
		
		// Set user information.
		$this->user = Session::getMemberInfo() ?: new Helpers\SessionHelper();
		
		// Cache the last modified time of the template parser itself.
		if (self::$_mtime === 0)
		{
			self::$_mtime = filemtime(__FILE__);
		}
	}
	
	/**
	 * Compile and execute the template file.
	 * 
	 * @param bool $force
	 * @return string
	 */
	public function compile(bool $force = false): string
	{
		// Record the starting time.
		$start = microtime(true);
		
		// Find the latest mtime of the source template and the template parser.
		$latest_mtime = max(filemtime($this->source_filename), self::$_mtime);
		
		// If a cached result does not exist, or if it is stale, compile again.
		if ($force || !file_exists($this->target_filename) || filemtime($this->target_filename) < $latest_mtime)
		{
			$content = $this->parse();
			if (!Storage::write($this->target_filename, $content))
			{
				throw new Exception('Cannot write cache file for template');
			}
		}
		
		// Add an alias for debugging.
		Debug::addFilenameAlias($this->source_filename, $this->target_filename);
		$output = $this->execute();
		
		// Record the time elapsed.
		if (!isset($GLOBALS['__template_elapsed__']))
		{
			$GLOBALS['__template_elapsed__'] = 0;
		}
		$GLOBALS['__template_elapsed__'] += microtime(true) - $start;
		
		return $output;
	}
	
	/**
	 * Parse the template and return the result.
	 * 
	 * @return string
	 */
	public function parse(): string
	{
		// Read the original template.
		$content = Storage::read($this->source_filename);
		
		// Remove UTF-8 BOM.
		$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
		
		// Check the config tag.
		$content = preg_replace_callback('!^<config\s+(\w+)="([^"]+)"\s*/?>!', function($match) {
			$this->config->{$match[1]} = ($match[1] === 'version' ? intval($match[2]) : toBool($match[2]));
			return sprintf('<?php // config %s="%s" ?>', $match[1], var_export($this->config->{$match[1]}, true));
		}, $content);
		
		// Turn autoescape on if the version is 2 or greater.
		if ($this->config->version >= 2)
		{
			$config['autoescape'] = true;
		}
		
		// Call the version-appropriate parser to convert template code into PHP.
		$class_name = '\Rhymix\Framework\Parsers\TemplateParser_v' . $this->config->version;
		$parser = new $class_name;
		$content = $parser->convert($content, $this);
		
		return $content;
	}
	
	/**
	 * Execute the compiled template.
	 * 
	 * @return string
	 */
	public function execute(): string
	{
		// Import Context and lang as local variables.
		$__Context = \Context::getAll();
		global $lang;
		
		// Start the output buffer.
		self::$_ob_level = ob_get_level();
		ob_start();
		
		// Include the compiled template.
		include $this->target_filename;
		
		// Fetch the content of the output buffer until the buffer level is the same as before.
		$content = '';
		while (ob_get_level() > self::$_ob_level)
		{
			$content .= ob_get_clean();
		}
		
		// Insert comments for debugging.
		if (Debug::isEnabledForCurrentUser() && \Context::getResponseMethod() === 'HTML' && !preg_match('/^<(!DOCTYPE|?xml|html)\b/', $content))
		{
			$sign = '<!-- Template %s: ' . $this->relative_filename . ' -->' . "\n";
			$content = sprintf($sign, 'Start') . $content . "\n" . sprintf($sign, 'End');
		}
		
		return $content;
	}
}
