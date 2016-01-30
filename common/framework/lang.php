<?php

namespace Rhymix\Framework;

/**
 * The language class.
 */
class Lang
{
	/**
	 * Instances are stored here.
	 */
	protected static $_instances = array();
	
	/**
	 * Configuration.
	 */
	protected $_language;
	protected $_loaded_directories = array();
	protected $_loaded_files = array();
	
	/**
	 * This method returns the cached instance of a language.
	 * 
	 * @param string $language
	 * @return object
	 */
	public static function getInstance($language)
	{
		if ($language === 'jp')
		{
			$language = 'ja';
		}
		if (!isset(self::$_instances[$language]))
		{
			self::$_instances[$language] = new self($language);
		}
		return self::$_instances[$language];
	}
	
	/**
	 * The constructor should not be called from outside.
	 * 
	 * @param string $language
	 */
	protected function __construct($language)
	{
		$this->_language = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $language));
	}
	
	/**
	 * Add a directory to load translations from.
	 * 
	 * @param string $dir
	 * @return bool
	 */
	public function addDirectory($dir)
	{
		// Do not load the same directory twice.
		$dir = rtrim($dir, '/');
		if (in_array($dir, $this->_loaded_directories))
		{
			return true;
		}
		
		// Alias $this to $lang.
		$lang = $this;
		
		// Check if there is a PHP lang file.
		if (file_exists($filename = $dir . '/' . $this->_language . '.php'))
		{
			$this->_loaded_directories[] = $dir;
			$this->_loaded_files[] = $filename;
			include $filename;
			return true;
		}
		elseif (file_exists($filename = $dir . '/' . ($this->_language === 'ja' ? 'jp' : $this->_language) . '.lang.php'))
		{
			$this->_loaded_directories[] = $dir;
			$this->_loaded_files[] = $filename;
			include $filename;
			return true;
		}
		elseif (($hyphen = strpos($this->_language, '-')) !== false)
		{
			if (file_exists($filename = $dir . '/' . substr($this->_language, 0, $hyphen) . '.php'))
			{
				$this->_loaded_directories[] = $dir;
				$this->_loaded_files[] = $filename;
				include $filename;
				return true;
			}
			elseif (file_exists($filename = $dir . '/' . substr($this->_language, 0, $hyphen) . '.lang.php'))
			{
				$this->_loaded_directories[] = $dir;
				$this->_loaded_files[] = $filename;
				include $filename;
				return true;
			}
		}
		
		// Check if there is a XML lang file.
		if (file_exists($filename = $dir . '/lang.xml'))
		{
			$this->_loaded_directories[] = $dir;
			$this->_loaded_files[] = $filename;
			$compiled_filename = $this->compileXMLtoPHP($filename, $this->_language === 'ja' ? 'jp' : $this->_language);
			if ($compiled_filename !== false)
			{
				include $compiled_filename;
				return true;
			}
		}
		
		// Return false if no suitable lang file is found.
		return false;
	}
	
	/**
	 * Compile XE-compatible XML lang files into PHP.
	 * 
	 * @param string $filename
	 * @param string $language
	 * @return string|false
	 */
	public function compileXMLtoPHP($filename, $language)
	{
		// Check if the cache file already exists.
		$cache_filename = RX_BASEDIR . 'files/cache/lang/' . md5($filename) . '.' . $language . '.php';
		if (file_exists($cache_filename) && filemtime($cache_filename) > filemtime($filename))
		{
			return $cache_filename;
		}
		
		// Load the XML lang file.
		$xml = @simplexml_load_file($filename);
		if ($xml === false)
		{
			\FileHandler::writeFile($cache_filename, '');
			return false;
		}
		
		// Convert XML to a PHP array.
		$lang = array();
		foreach ($xml->item as $item)
		{
			$name = strval($item['name']);
			if (strval($item['type']) === 'array')
			{
				$lang[$name] = array();
				foreach ($item->item as $subitem)
				{
					$subname = strval($subitem['name']);
					foreach ($subitem->value as $value)
					{
						$attribs = $value->attributes('xml', true);
						if (strtolower($attribs['lang']) === $language)
						{
							$lang[$name][$subname] = strval($value);
							break;
						}
					}
				}
			}
			else
			{
				foreach ($item->value as $value)
				{
					$attribs = $value->attributes('xml', true);
					if (strtolower($attribs['lang']) === $language)
					{
						$lang[$name] = strval($value);
						break;
					}
				}
			}
		}
		unset($xml);
		
		// Save the array as a cache file.
		$buff = "<?php\n";
		foreach ($lang as $key => $value)
		{
			$buff .= '$lang->' . $key . ' = ' . var_export($value, true) . ";\n";
		}
		\FileHandler::writeFile($cache_filename, $buff);
		return $cache_filename;
	}
	
	/**
	 * Magic method for translations with arguments.
	 * 
	 * @param string $key
	 * @param mixed $args
	 * @return string|null
	 */
	public function __call($key, $args)
	{
		// Remove a colon from the beginning of the string.
		if ($key !== '' && $key[0] === ':') $key = substr($key, 1);
		
		// If the string does not have a translation, return it verbatim.
		if (!isset($this->{$key})) return $key;
		
		// If there are no arguments, return the translation.
		if (!count($args)) return $this->{$key};
		
		// If there are arguments, interpolate them into the translation and return the result.
		return vsprintf($this->{$key}, $args);
	}
}
