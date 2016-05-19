<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\Config;
use Rhymix\Framework\Plugin;

/**
 * Config helper class.
 */
class ConfigHelper
{
	/**
	 * Cache plugin configuration during consolidation.
	 */
	protected static $_config_cache = array();
	
	/**
	 * Consolidate configuration from multiple sources.
	 * 
	 * @param array $format
	 * @return array
	 */
	public static function consolidate($format)
	{
		self::$_config_cache = array();
		$result = array();
		
		foreach ($format as $key => $value)
		{
			$result[$key] = self::_parseConfigValue((array)$value);
		}
		
		self::$_config_cache = array();
		return $result;
	}
	
	/**
	 * Parse and get a configuration value.
	 * 
	 * @param array $value
	 * @return mixed
	 */
	protected static function _parseConfigValue(array $value)
	{
		foreach ($value as $option)
		{
			$option = array_map('trim', explode(':', $option, 2));
			if (count($option) === 1)
			{
				$result = Config::get($option[0]);
				if ($result !== null)
				{
					return $result;
				}
			}
			else
			{
				if (!isset(self::$_config_cache[$option[0]]))
				{
					self::$_config_cache[$option[0]] = getModel('module')->getModuleConfig($option[0]) ?: null;
				}
				$options = explode('.', $option[1]);
				$temp = self::$_config_cache[$option[0]];
				foreach ($options as $step)
				{
					if (is_object($temp) && isset($temp->$step))
					{
						$temp = $temp->$step;
					}
					elseif (is_array($temp) && isset($temp[$step]))
					{
						$temp = $temp[$step];
					}
					else
					{
						$temp = null;
					}
				}
				if ($temp !== null)
				{
					return $temp;
				}
			}
		}
		
		return null;
	}
}
