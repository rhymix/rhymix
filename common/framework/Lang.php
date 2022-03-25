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
	 * Return language type.
	 * 
	 * @return string
	 */
	public function langType()
	{
		return $this->_language;
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
			$this->loadDirectory(\RX_BASEDIR . 'common/lang', 'common');
		}
		elseif (file_exists(\RX_BASEDIR . "modules/$name/lang"))
		{
			$this->loadDirectory(\RX_BASEDIR . "modules/$name/lang", $name);
		}
		elseif (file_exists(\RX_BASEDIR . "plugins/$name/lang"))
		{
			$this->loadDirectory(\RX_BASEDIR . "plugins/$name/lang", $name);
		}
		elseif (file_exists(\RX_BASEDIR . "addons/$name/lang"))
		{
			$this->loadDirectory(\RX_BASEDIR . "addons/$name/lang", $name);
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
		
		// Initialize variables.
		$filename = null;
		$lang = new \stdClass;
		$result = true;
		
		// Find a suitable language file in the given directory.
		if (file_exists($dir . '/' . $this->_language . '.php'))
		{
			$filename = $dir . '/' . $this->_language . '.php';
		}
		elseif (($hyphen = strpos($this->_language, '-')) !== false && file_exists($dir . '/' . substr($this->_language, 0, $hyphen) . '.php'))
		{
			$filename = $dir . '/' . substr($this->_language, 0, $hyphen) . '.php';
		}
		elseif (file_exists("$dir/lang.xml"))
		{
			$filename = Parsers\LangParser::compileXMLtoPHP("$dir/lang.xml", $this->_language === 'ja' ? 'jp' : $this->_language);
		}
		elseif (file_exists($dir . '/' . ($this->_language === 'ja' ? 'jp' : $this->_language) . '.lang.php'))
		{
			$filename = $dir . '/' . ($this->_language === 'ja' ? 'jp' : $this->_language) . '.lang.php';
		}
		
		// Load the language file.
		if ($filename)
		{
			include $filename;
			array_unshift($this->_search_priority, $plugin_name);
			$result = true;
		}
		else
		{
			$result = false;
		}
		
		// Mark this directory and plugin as loaded.
		$this->_loaded_directories[$dir] = true;
		$this->_loaded_plugins[$plugin_name] = $lang;
		
		// Load the same directory in the default language, too.
		if ($this->_language !== 'en')
		{
			self::getInstance('en')->loadDirectory($dir, $plugin_name);
		}
		
		return $result;
	}
	
	/**
	 * Get the list of supported languages.
	 * 
	 * @return array
	 */
	public static function getSupportedList()
	{
		static $list = null;
		if ($list === null)
		{
			$list = (include \RX_BASEDIR . 'common/defaults/locales.php');
		}
		return $list;
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
		if (count($args) === 1 && is_array($args[0]))
		{
			$args = $args[0];
		}
		
		// Get the translation.
		$translation = $this->__get($key);
		
		// If there are no arguments, return the translation.
		if (!count($args)) return $translation;
		
		// If there are arguments, interpolate them into the translation and return the result.
		return vsprintf($translation, $args);
	}
	
	/**
	 * Generic setter.
	 * 
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->__set($key, $value);
	}
	
	/**
	 * Fallback method for getting the default translation.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getFromDefaultLang($key)
	{
		if ($this->_language === 'en')
		{
			return $key;
		}
		else
		{
			return self::getInstance('en')->__get($key);
		}
	}
	
	/**
	 * Magic method for translations without arguments.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function __get($key)
	{
		// Load a dot-separated key (prefixed by plugin name).
		if (preg_match('/^[a-z0-9_.-]+$/i', $key) && ($keys = explode('.', $key)) && count($keys) >= 2)
		{
			// Attempt to load the plugin.
			$plugin_name = array_shift($keys);
			if (!isset($this->_loaded_plugins[$plugin_name]))
			{
				$this->loadPlugin($plugin_name);
			}
			if (!isset($this->_loaded_plugins[$plugin_name]))
			{
				return $this->getFromDefaultLang($key);
			}
			
			// Find the given key.
			$lang = $this->_loaded_plugins[$plugin_name];
			foreach ($keys as $subkey)
			{
				if (is_object($lang) && isset($lang->{$subkey}))
				{
					$lang = $lang->{$subkey};
				}
				elseif (is_array($lang) && isset($lang[$subkey]))
				{
					$lang = $lang[$subkey];
				}
				else
				{
					return $this->getFromDefaultLang($key);
				}
			}
			return is_array($lang) ? new \ArrayObject($lang, 3) : $lang;
		}
		
		// Search custom translations first.
		if (isset($this->_loaded_plugins['_custom_']->{$key}))
		{
			$lang = $this->_loaded_plugins['_custom_']->{$key};
			return is_array($lang) ? new \ArrayObject($lang, 3) : $lang;
		}
		
		// Search other plugins.
		foreach ($this->_search_priority as $plugin_name)
		{
			if (isset($this->_loaded_plugins[$plugin_name]->{$key}))
			{
				$lang = $this->_loaded_plugins[$plugin_name]->{$key};
				return is_array($lang) ? new \ArrayObject($lang, 3) : $lang;
			}
		}
		
		// If no translation is found, return the default language.
		return $this->getFromDefaultLang($key);
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
		// Set a dot-separated key (prefixed by plugin name).
		if (preg_match('/^[a-z0-9_.-]+$/i', $key) && ($keys = explode('.', $key)) && count($keys) >= 2)
		{
			// Attempt to load the plugin.
			$plugin_name = array_shift($keys);
			if (!isset($this->_loaded_plugins[$plugin_name]))
			{
				$this->loadPlugin($plugin_name);
			}
			if (!isset($this->_loaded_plugins[$plugin_name]))
			{
				return false;
			}
			
			// Set the given key.
			$count = count($keys);
			$lang = $this->_loaded_plugins[$plugin_name];
			foreach ($keys as $i => $subkey)
			{
				if (is_object($lang) && isset($lang->{$subkey}))
				{
					if ($i === $count - 1)
					{
						$lang->{$subkey} = $value;
						break;
					}
					elseif (is_array($lang->{$subkey}))
					{
						$lang = &$lang->{$subkey};
					}
					else
					{
						return false;
					}
				}
				elseif (is_array($lang) && isset($lang[$subkey]))
				{
					if ($i === $count - 1)
					{
						$lang[$subkey] = $value;
						break;
					}
					elseif (is_array($lang[$subkey]))
					{
						$lang = &$lang[$subkey];
					}
					else
					{
						return false;
					}
				}
				else
				{
					if (is_object($lang))
					{
						$lang->{$subkey} = $value;
					}
					else
					{
						$lang[$subkey] = $value;
					}
					break;
				}
			}
		}
		
		// Set a regular key.
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
		$this->set($key, null);
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
		return $this->get($key, $args);
	}
}
