<?php

namespace Rhymix\Modules\Module\Models;

use Context;

/**
 * This class manages URL prefixes for module instances, also known as "mid".
 */
class Prefix
{
	/**
	 * Check if a given prefix (mid) is valid.
	 *
	 * @param string $prefix
	 * @param ?string $module
	 * @return bool
	 */
	public static function isValidPrefix(string $prefix, ?string $module = null): bool
	{
		// Check the format.
		if (!preg_match('/^[a-z]([a-z0-9_]+)$/i', $prefix))
		{
			return false;
		}

		// Check if it is a reserved word.
		$prefix = strtolower($prefix);
		if (Context::isReservedWord($prefix))
		{
			if ($module === null || $prefix !== strtolower($module))
			{
				return false;
			}
		}
		if (in_array($prefix, ['rss', 'atom', 'api']))
		{
			return false;
		}

		// Check if it conflicts with top-level directories.
		$dirs = array_map('strtolower', glob(\RX_BASEDIR . '*', \GLOB_ONLYDIR | \GLOB_NOSORT));
		if (in_array($prefix, $dirs))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if a given prefix (mid) is already in use.
	 *
	 * @param string $prefix
	 * @return bool
	 */
	public static function exists(string $prefix): bool
	{
		$output = executeQuery('module.isExistsModuleName', ['mid' => $prefix]);
		return $output->data->count ? true : false;
	}

	/**
	 * Get the next available prefix starting with the given word.
	 *
	 * @param string $prefix
	 * @return string
	 */
	public static function getNextAvailablePrefix(string $prefix): string
	{
		$prefix = trim($prefix);
		if ($prefix === '')
		{
			return '';
		}

		$max = 0;
		$len = strlen($prefix);
		$output = executeQueryArray('module.getMidInfo', ['mid_prefix' => $prefix], ['mid']);
		foreach ($output->data as $info)
		{
			$suffix = substr($info->mid, $len);
			if (ctype_digit($suffix))
			{
				$max = max($max, intval($suffix));
			}
		}

		return $prefix . ($max + 1);
	}

	/**
	 * Find the module_srl corresponding to a list of prefixes (mid).
	 *
	 * @param array $prefix
	 * @return array
	 */
	public static function getModuleSrlByPrefix(array $prefix): array
	{
		if (count($prefix) === 0)
		{
			return [];
		}
		if (count($prefix) === 1)
		{
			$first_prefix = array_first($prefix);
			if (isset(ModuleCache::$prefix2module_srl[$first_prefix]))
			{
				return [$first_prefix => ModuleCache::$prefix2module_srl[$first_prefix]];
			}
		}

		$output = executeQueryArray('module.getModuleSrlByMid', ['mid' => array_values($prefix)]);
		$result = [];
		foreach ($output->data as $row)
		{
			$result[$row->mid] = $row->module_srl;
			ModuleCache::$prefix2module_srl[$row->mid] = $row->module_srl;
		}
		return $result;
	}

	/**
	 * Find the prefixes (mid) corresponding to a list of module_srl.
	 *
	 * @param array $module_srl
	 * @return array
	 */
	public static function getPrefixByModuleSrl(array $module_srl): array
	{
		if (count($module_srl) === 0)
		{
			return [];
		}
		if (count($module_srl) === 1)
		{
			$first_module_srl = array_first($module_srl);
			if (isset(ModuleCache::$module_srl2prefix[$first_module_srl]))
			{
				return [$first_module_srl => ModuleCache::$module_srl2prefix[$first_module_srl]];
			}
		}

		$args = [
			'module_srls' => array_values($module_srl),
			'sort_index' => 'module_srl',
		];
		$output = executeQueryArray('module.getMidList', $args, ['module_srl', 'mid']);
		$result = [];
		foreach ($output->data as $row)
		{
			$result[$row->module_srl] = $row->mid;
			ModuleCache::$module_srl2prefix[$row->module_srl] = $row->mid;
		}
		return $result;
	}
}
