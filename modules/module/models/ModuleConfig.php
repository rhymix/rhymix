<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Parsers\DBQuery\NullValue;

class ModuleConfig
{
	/**
	 * Get global config for a module.
	 *
	 * @param string $module
	 * @return ?object
	 */
	public static function getModuleConfig(string $module): ?object
	{
		if (!$module)
		{
			return null;
		}

		if (!isset($GLOBALS['__ModuleConfig__'][$module]))
		{
			$cache_key = "site_and_module:module_config:$module";
			$config = Cache::get($cache_key);
			if ($config === null)
			{
				$output = executeQuery('module.getModuleConfig', ['module' => $module]);
				if (isset($output->data->config) && $output->data->config)
				{
					$config = self::_normalizeConfig(unserialize($output->data->config));
				}
				else
				{
					$config = -1;  // Use -1 as a temporary value because null cannot be cached
				}

				if ($output->toBool())
				{
					Cache::set($cache_key, $config, 0, true);
				}
			}
			$GLOBALS['__ModuleConfig__'][$module] = $config;
		}

		$config = $GLOBALS['__ModuleConfig__'][$module];
		return $config === -1 ? null : $config;
	}

	/**
	 * Get an independent section of module config.
	 *
	 * @param string $module
	 * @param string $section
	 * @return ?object
	 */
	public static function getModuleSectionConfig(string $module, string $section): ?object
	{
		return self::getModuleConfig("$module:$section");
	}

	/**
	 * Get config for a specific pair of module and module_srl.
	 *
	 * @param string module
	 * @param int $module_srl
	 * @return ?object
	 */
	public static function getModulePartConfig(string $module, int $module_srl): ?object
	{
		if (!$module || !$module_srl)
		{
			return null;
		}

		if (!isset($GLOBALS['__ModulePartConfig__'][$module][$module_srl]))
		{
			$cache_key = 'site_and_module:module_part_config:' . $module . '_' . $module_srl;
			$config = Cache::get($cache_key);
			if (!is_object($config))
			{
				$output = executeQuery('module.getModulePartConfig', [
					'module' => $module,
					'module_srl' => $module_srl,
				]);
				if (isset($output->data->config) && $output->data->config)
				{
					$config = self::_normalizeConfig(unserialize($output->data->config));
				}
				else
				{
					$config = -1;  // Use -1 as a temporary value because null cannot be cached
				}

				// Set cache
				if ($output->toBool())
				{
					Cache::set($cache_key, $config, 0, true);
				}
			}
			$GLOBALS['__ModulePartConfig__'][$module][$module_srl] = $config;
		}

		$config = $GLOBALS['__ModulePartConfig__'][$module][$module_srl];
		return $config === -1 ? null : $config;
	}

	/**
	 * Get all module part configs for a module.
	 *
	 * @param string $module
	 * @return array
	 */
	public static function getModulePartConfigs(string $module)
	{
		$output = executeQueryArray('module.getModulePartConfigs', ['module' => $module]);
		$result = array();
		foreach($output->data ?? [] as $val)
		{
			$result[$val->module_srl] = self::_normalizeConfig(unserialize($val->config));
		}
		return $result;
	}

	/**
	 * Save global config for a module.
	 *
	 * @param string $module
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function insertModuleConfig(string $module, $config): DBResultHelper
	{
		$args = new \stdClass;
		$args->module = $module;
		$args->config = serialize(self::_normalizeConfig($config));
		if ($args->config === null)
		{
			$args->config = new NullValue;
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = executeQuery('module.deleteModuleConfig', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = executeQuery('module.insertModuleConfig', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		// Clear cache
		unset($GLOBALS['__ModuleConfig__'][$module]);
		Cache::clearGroup('site_and_module');
		return $output;
	}

	/**
	 * Update global config for a module.
	 *
	 * @param string $module
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function updateModuleConfig(string $module, object $config): DBResultHelper
	{
		$original_config = self::getModuleConfig($module) ?: new \stdClass;
		foreach (get_object_vars($config) as $key => $val)
		{
			$original_config->{$key} = $val;
		}

		return self::insertModuleConfig($module, $original_config);
	}

	/**
	 * Save an independent section of module config.
	 *
	 * @param string $module
	 * @param string $section
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function insertModuleSectionConfig(string $module, string $section, object $config): DBResultHelper
	{
		return self::insertModuleConfig("$module:$section", $config);
	}

	/**
	 * Update an independent section of module config.
	 *
	 * @param string $module
	 * @param string $section
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function updateModuleSectionConfig(string $module, string $section, object $config): DBResultHelper
	{
		$original_config = self::getModuleSectionConfig($module, $section) ?: new \stdClass;
		foreach (get_object_vars($config) as $key => $val)
		{
			$original_config->{$key} = $val;
		}

		return self::insertModuleSectionConfig($module, $section, $original_config);
	}

	/**
	 * Save config for a specific pair of module and module_srl.
	 *
	 * @param string $module
	 * @param int $module_srl
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function insertModulePartConfig(string $module, int $module_srl, $config): DBResultHelper
	{
		$args = new \stdClass;
		$args->module = $module;
		$args->module_srl = $module_srl;
		$args->config = serialize(self::_normalizeConfig($config));
		if ($args->config === null)
		{
			$args->config = new NullValue;
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = executeQuery('module.deleteModulePartConfig', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = executeQuery('module.insertModulePartConfig', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		// Clear cache
		unset($GLOBALS['__ModulePartConfig__'][$module][$module_srl]);
		Cache::clearGroup('site_and_module');
		return $output;
	}

	/**
	 * Update config for a specific pair of module and module_srl.
	 *
	 * @param string $module
	 * @param int $module_srl
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function updateModulePartConfig(string $module, int $module_srl, object $config): DBResultHelper
	{
		$original_config = self::getModulePartConfig($module, $module_srl) ?: new \stdClass;
		foreach (get_object_vars($config) as $key => $val)
		{
			$original_config->{$key} = $val;
		}

		return self::insertModulePartConfig($module, $module_srl, $original_config);
	}

	/**
	 * Normalize config object.
	 *
	 * @param mixed $config
	 * @return ?object
	 */
	protected static function _normalizeConfig($config): ?object
	{
		if (is_null($config))
		{
			return null;
		}
		elseif (is_array($config))
		{
			return (object)$config;
		}
		elseif ($config instanceof \ArrayObject)
		{
			return (object)($config->getArrayCopy());
		}
		elseif (!is_object($config))
		{
			return null;
		}
		else
		{
			return $config;
		}
	}
}
