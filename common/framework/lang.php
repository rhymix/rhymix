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
		$this->_language = preg_replace('/[^a-z0-9_-]/i', '', $language);
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
			$compiled_filename = self::compileXMLtoPHP($filename, $this->_language === 'ja' ? 'jp' : $this->_language);
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
	 * Convert a directory of old language files to the RhymiX format.
	 * 
	 * @param string $dir
	 * @param array $xml_langs When converting XML to PHP, only convert these languages. (Optional)
	 * @return void
	 */
	public static function convertDirectory($dir, $xml_langs = array())
	{
		if (file_exists("$dir/lang.xml"))
		{
			$langs = count($xml_langs) ? $xml_langs : array_keys(self::getSupportedList());
			foreach ($langs as $lang)
			{
				self::compileXMLtoPHP("$dir/lang.xml", $lang === 'ja' ? 'jp' : $lang, "$dir/$lang.php");
			}
		}
		else
		{
			$files = glob($dir . '/*.lang.php');
			foreach ($files as $filename)
			{
				$new_filename = preg_replace('/\.lang\.php$/', '.php', str_replace('jp.lang', 'ja.lang', $filename));
				\FileHandler::rename($filename, $new_filename);
			}
		}
	}
	
	/**
	 * Compile XE-compatible XML lang files into PHP.
	 * 
	 * @param string $filename
	 * @param string $language
	 * @return string|false
	 */
	public static function compileXMLtoPHP($filename, $language, $output_filename = null)
	{
		// Check if the cache file already exists.
		if ($output_filename === null)
		{
			$output_filename = RX_BASEDIR . 'files/cache/lang/' . md5($filename) . '.' . $language . '.php';
			if (file_exists($output_filename) && filemtime($output_filename) > filemtime($filename))
			{
				return $output_filename;
			}
		}
		
		// Load the XML lang file.
		$xml = @simplexml_load_file($filename);
		if ($xml === false)
		{
			\FileHandler::writeFile($output_filename, '');
			return false;
		}
		
		// Convert XML to a PHP array.
		$lang = array();
		foreach ($xml->item as $item)
		{
			$name = strval($item['name']);
			if (count($item->item))
			{
				$lang[$name] = array();
				foreach ($item->item as $subitem)
				{
					$subname = strval($subitem['name']);
					foreach ($subitem->value as $value)
					{
						$attribs = $value->attributes('xml', true);
						if (strval($attribs['lang']) === $language)
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
					if (strval($attribs['lang']) === $language)
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
			if (is_array($value))
			{
				foreach ($value as $subkey => $subvalue)
				{
					$buff .= '$lang->' . $key . "['" . $subkey . "']" . ' = ' . var_export($subvalue, true) . ";\n";
				}
			}
			else
			{
				$buff .= '$lang->' . $key . ' = ' . var_export($value, true) . ";\n";
			}
		}
		\FileHandler::writeFile($output_filename, $buff);
		return $output_filename;
	}
	
	/**
	 * Get the list of supported languages.
	 * 
	 * @return array
	 */
	public static function getSupportedList()
	{
		return (include RX_BASEDIR . 'common/defaults/lang.php');
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
