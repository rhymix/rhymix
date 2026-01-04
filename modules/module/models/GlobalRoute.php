<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;

/**
 * This class manages global routes, formerly known as "action forward".
 * Actions registered as a global route can be accessed without an instance of its module,
 * or even on top of another module.
 */
class GlobalRoute
{
	/**
	 * Attributes to match database columns.
	 */
	public string $act;
	public string $module;
	public string $type;
	public $route_regexp;
	public $route_config;
	public string $global_route;

	/**
	 * Get a global route by act.
	 *
	 * @param string $act
	 * @return ?self
	 */
	public static function getGlobalRoute(string $act): ?self
	{
		$list = self::getAllGlobalRoutes();
		return isset($list[$act]) ? $list[$act] : null;
	}

	/**
	 * Get all defined global routes.
	 *
	 * @return array<self>
	 */
	public static function getAllGlobalRoutes(): array
	{
		$list = Cache::get('action_forward');
		if ($list === null)
		{
			$list = [];
			$output = executeQueryArray('module.getActionForward', [], [], self::class);
			foreach ($output->data ?? [] as $item)
			{
				if ($item->route_regexp)
				{
					$item->route_regexp = unserialize($item->route_regexp);
				}
				if ($item->route_config)
				{
					$item->route_config = unserialize($item->route_config);
				}
				$list[$item->act] = $item;
			}
			Cache::set('action_forward', $list, 0, true);
		}
		return $list;
	}

	/**
	 * Insert a global route.
	 *
	 * @param string $act
	 * @param string $module
	 * @param string $type
	 * @param string|array|null $route_regexp
	 * @param string|array|null $route_config
	 * @param string $global_route 'Y' or 'N'
	 * @return DBResultHelper
	 */
	public static function insertGlobalRoute(
		string $act,
		string $module,
		string $type,
		$route_regexp = null,
		$route_config = null,
		string $global_route = 'N'
	): DBResultHelper
	{
		$args = [
			'act' => $act,
			'module' => $module,
			'type' => $type,
			'route_regexp' => serialize($route_regexp ?: null),
			'route_config' => serialize($route_config ?: null),
			'global_route' => $global_route === 'Y' ? 'Y' : 'N',
		];

		$oDB = DB::getInstance();
		$oDB->begin();
		$output = executeQuery('module.deleteActionForward', ['act' => $act]);
		$output = executeQuery('module.insertActionForward', $args);
		$oDB->commit();

		Cache::delete('action_forward');
		return $output;
	}

	/**
	 * Delete a global route.
	 *
	 * @param ?string $act
	 * @param ?string $module
	 * @param ?string $type
	 * @return DBResultHelper
	 */
	public static function deleteGlobalRoute(
		?string $act = null,
		?string $module = null,
		?string $type = null
	): DBResultHelper
	{
		$output = executeQuery('module.deleteActionForward', [
			'act' => $act,
			'module' => $module,
			'type' => $type,
		]);

		Cache::delete('action_forward');
		return $output;
	}
}
