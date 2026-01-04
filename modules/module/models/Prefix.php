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
}
