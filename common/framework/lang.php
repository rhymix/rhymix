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
	protected $_loaded_plugins = array();
	protected $_search_priority = array();
	
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
		$this->_loaded_plugins['_custom_'] = new \stdClass();
	}
	
	/**
	 * Load translations from a plugin (module, addon).
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function loadPlugin($name)
	{
		if (isset($this->_loaded_plugins[$name]))
		{
			return true;
		}
		
		if ($name === 'common')
		{
			$this->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		}
		elseif (file_exists(RX_BASEDIR . "plugins/$name/lang"))
		{
			$this->loadDirectory(RX_BASEDIR . "plugins/$name/lang", $name);
		}
		elseif (file_exists(RX_BASEDIR . "modules/$name/lang"))
		{
			$this->loadDirectory(RX_BASEDIR . "modules/$name/lang", $name);
		}
		elseif (file_exists(RX_BASEDIR . "addons/$name/lang"))
		{
			$this->loadDirectory(RX_BASEDIR . "addons/$name/lang", $name);
		}
	}
	
	/**
	 * Load translations from a directory.
	 * 
	 * @param string $dir
	 * @param string $plugin_name
	 * @return bool
	 */
	public function loadDirectory($dir, $plugin_name = null)
	{
		// Do not load the same directory twice.
		$dir = rtrim($dir, '/');
		$plugin_name = $plugin_name ?: $dir;
		if (isset($this->_loaded_directories[$dir]) || isset($this->_loaded_plugins[$plugin_name]))
		{
			return true;
		}
		
		// Load the language file.
		$lang = $this->getPluginLang($dir);
		
		// Load the default language file.
		if ($this->_language !== 'en')
		{
			self::getInstance('en')->loadDirectory($dir, $plugin_name);
		}
		
		if (!empty($lang))
		{
			$this->_loaded_directories[$dir] = true;
			$this->_loaded_plugins[$plugin_name] = $lang;
			array_unshift($this->_search_priority, $plugin_name);
			return true;
		}
		else
		{
			$this->_loaded_directories[$dir] = true;
			$this->_loaded_plugins[$plugin_name] = new \stdClass;
			return false;
		}
	}
	
	/**
	 * Get the language file from plugin.
	 * 
	 * @param string $dir
	 * @param string $language
	 * @return object
	 */
	public function getPluginLang($dir, $language = null)
	{
		if (!$language)
		{
			$language = $this->_language;
		}
		
		if (file_exists($dir . '/' . $language . '.php'))
		{
			$filename = $dir . '/' . $language . '.php';
		}
		elseif (($hyphen = strpos($language, '-')) !== false && file_exists($dir . '/' . substr($language, 0, $hyphen) . '.php'))
		{
			$filename = $dir . '/' . substr($language, 0, $hyphen) . '.php';
		}
		elseif (file_exists("$dir/lang.xml"))
		{
			$filename = Compat\LangParser::compileXMLtoPHP("$dir/lang.xml", $language === 'ja' ? 'jp' : $language);
		}
		elseif (file_exists($dir . '/' . ($language === 'ja' ? 'jp' : $language) . '.lang.php'))
		{
			$filename = $dir . '/' . ($language === 'ja' ? 'jp' : $language) . '.lang.php';
		}
		
		if (!$filename)
		{
			return new \stdClass;
		}
		
		$lang = new \stdClass;
		include $filename;
		
		return $lang;
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
	 * Generic getter.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function get($key)
	{
		$args = func_get_args();
		array_shift($args);
		return $this->__call($key, $args);
	}
	
	/**
	 * Magic method for translations without arguments.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function __get($key)
	{
		// Get default language
		if ($this->_language !== 'en')
		{
			$lang_en = self::getInstance('en')->{$key};
		}
		
		// Separate the plugin name from the key.
		if (preg_match('/^[a-z0-9_.-]+$/i', $key) && ($keys = explode('.', $key, 2)) && count($keys) === 2)
		{
			list($plugin_name, $lang_key) = $keys;
			if (!isset($this->_loaded_plugins[$plugin_name]))
			{
				$this->loadPlugin($plugin_name);
			}
			
			if (isset($this->_loaded_plugins[$plugin_name]->{$lang_key}))
			{
				$lang = $this->_loaded_plugins[$plugin_name]->{$lang_key};
				if (is_array($lang) && isset($lang_en) && count($lang_en, COUNT_RECURSIVE) > count($lang, COUNT_RECURSIVE))
				{
					return $lang_en;
				}
				
				return $lang;
			}
		}
		else
		{
			// Search custom translations first.
			if (isset($this->_loaded_plugins['_custom_']->{$key}))
			{
				$lang = $this->_loaded_plugins['_custom_']->{$key};
				if (is_array($lang))
				{
					if (isset($lang_en) && count($lang_en, COUNT_RECURSIVE) > count($lang, COUNT_RECURSIVE))
					{
						return new \ArrayObject($lang_en, 3);
					}
					
					return new \ArrayObject($lang, 3);
				}
				else
				{
					return $lang;
				}
			}
			
			// Search other plugins.
			foreach ($this->_search_priority as $plugin_name)
			{
				if (isset($this->_loaded_plugins[$plugin_name]->{$key}))
				{
					$lang = $this->_loaded_plugins[$plugin_name]->{$key};
					if (is_array($lang))
					{
						if (isset($lang_en) && count($lang_en, COUNT_RECURSIVE) > count($lang, COUNT_RECURSIVE))
						{
							return new \ArrayObject($lang_en, 3);
						}
						
						return new \ArrayObject($lang, 3);
					}
					else
					{
						return $lang;
					}
				}
			}
		}
		
		// Search other language.
		if (isset($lang_en))
		{
			return $lang_en;
		}
		
		// If no translation is found, return the key.
		return $key;
	}
	
	/**
	 * Magic method for setting a new custom translation.
	 * 
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->_loaded_plugins['_custom_']->{$key} = $value;
	}
	
	/**
	 * Magic method for checking whether a translation exists.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function __isset($key)
	{
		foreach ($this->_loaded_plugins as $plugin_name => $translations)
		{
			if (isset($translations->{$key}))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Magic method for unsetting a translation.
	 * 
	 * @param string $key
	 * @return void
	 */
	public function __unset($key)
	{
		foreach ($this->_loaded_plugins as $plugin_name => $translations)
		{
			if (isset($translations->{$key}))
			{
				unset($translations->{$key});
			}
		}
	}
	
	/**
	 * Magic method for translations with arguments.
	 * 
	 * @param string $key
	 * @param mixed $args
	 * @return string|null
	 */
	public function __call($key, $args = array())
	{
		// Remove a colon from the beginning of the string.
		if ($key !== '' && $key[0] === ':') $key = substr($key, 1);
		
		// Find the translation.
		$translation = $this->__get($key);
		
		// If there are no arguments, return the translation.
		if (!count($args)) return $translation;
		
		// If there are arguments, interpolate them into the translation and return the result.
		return vsprintf($translation, $args);
	}
}
