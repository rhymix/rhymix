<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;

/**
 * This class manages caches for various module-related data
 * to speed up frequent lookups.
 */
class ModuleCache
{
	/**
	 * Internal data maps.
	 */
	public static array $prefix2module_srl = [];
	public static array $module_srl2prefix = [];
	public static array $module_srl2domain = [];
	public static array $moduleConfig = [];
	public static array $modulePartConfig = [];
	public static array $modulePermissions = [];

	/**
	 * Clear all caches.
	 *
	 * @return void
	 */
	public static function clearAll(): void
	{
		Cache::clearGroup('site_and_module');

		self::$prefix2module_srl = [];
		self::$module_srl2prefix = [];
		self::$module_srl2domain = [];
		self::$moduleConfig = [];
		self::$modulePartConfig = [];
		self::$modulePermissions = [];
	}
}
