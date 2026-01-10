<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\DB;
use Rhymix\Framework\Storage;
use BaseObject;

class Updater
{
	/**
	 * Check if a module needs to be installed.
	 *
	 * @param string $module_name
	 * @return bool
	 */
	public static function needsInstall(string $module_name): bool
	{
		$oDB = DB::getInstance();
		$module_dir = \RX_BASEDIR . 'modules/' . $module_name . '/';
		if (Storage::isDirectory($module_dir. 'schemas'))
		{
			$schema_files = glob($module_dir . 'schemas/*.xml');
			foreach ($schema_files as $file)
			{
				$table_name = basename($file, '.xml');
				if (!$oDB->isTableExists($table_name))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Install a module.
	 */
	public static function installModule()
	{

	}

	/**
	 * Check if a module needs an update.
	 *
	 * @param string $module_name
	 * @return bool
	 */
	public static function needsUpdate(string $module_name): bool
	{
		$oDummy = ModuleDefinition::getInstallClass($module_name);
		if ($oDummy && method_exists($oDummy, 'checkUpdate'))
		{
			return call_user_func([$oDummy, 'checkUpdate']);
		}
		return false;
	}

	/**
	 * Update a module.
	 */
	public static function updateModule()
	{

	}

	/**
	 * Register global routes.
	 *
	 * @param string $module_name
	 * @return BaseObject
	 */
	public static function registerGlobalRoutes(string $module_name): BaseObject
	{
		$action_forward = GlobalRoute::getAllGlobalRoutes();
		$module_action_info = ModuleDefinition::getModuleActionXml($module_name);

		// Get the list of forwardable actions and their routes.
		$forwardable_routes = array();
		foreach ($module_action_info->action ?: [] as $action_name => $action_info)
		{
			if (count($action_info->route) && $action_info->standalone === 'true')
			{
				$forwardable_routes[$action_name] = array(
					'type' => $module_action_info->action->{$action_name}->type,
					'regexp' => array(),
					'config' => $action_info->route,
					'global_route' => $action_info->global_route === 'true' ? 'Y' : 'N',
				);
			}
		}
		foreach ($module_action_info->route->GET as $regexp => $action_name)
		{
			if (isset($forwardable_routes[$action_name]))
			{
				$forwardable_routes[$action_name]['regexp'][] = ['GET', $regexp];
			}
		}
		foreach ($module_action_info->route->POST as $regexp => $action_name)
		{
			if (isset($forwardable_routes[$action_name]))
			{
				$forwardable_routes[$action_name]['regexp'][] = ['POST', $regexp];
			}
		}

		// Insert or delete from the action_forward table.
		foreach ($forwardable_routes as $action_name => $route_info)
		{
			if (!isset($action_forward[$action_name]))
			{
				$output = GlobalRoute::insertGlobalRoute($action_name, $module_name, $route_info['type'],
					$route_info['regexp'], $route_info['config'], $route_info['global_route']);
				if (!$output->toBool())
				{
					return $output;
				}
			}
			elseif ($action_forward[$action_name]->route_regexp !== $route_info['regexp'] ||
				$action_forward[$action_name]->route_config !== $route_info['config'] ||
				$action_forward[$action_name]->global_route !== $route_info['global_route'])
			{
				$output = GlobalRoute::deleteGlobalRoute($action_name, $module_name, $route_info['type']);
				if (!$output->toBool())
				{
					return $output;
				}

				$output = GlobalRoute::insertGlobalRoute($action_name, $module_name, $route_info['type'],
					$route_info['regexp'], $route_info['config'], $route_info['global_route']);
				if (!$output->toBool())
				{
					return $output;
				}
			}
		}

		// Clean up any action-forward routes that are no longer needed.
		foreach ($forwardable_routes as $action_name => $route_info)
		{
			unset($action_forward[$action_name]);
		}
		foreach ($action_forward as $action_name => $forward_info)
		{
			if ($forward_info->module === $module_name && $forward_info->route_regexp !== null)
			{
				$output = GlobalRoute::deleteGlobalRoute($action_name, $module_name);
				if (!$output->toBool())
				{
					return $output;
				}
			}
		}

		return new BaseObject();
	}

	/**
	 * Register event handlers.
	 *
	 * @param string $module_name
	 * @return BaseObject
	 */
	public static function registerEventHandlers(string $module_name): BaseObject
	{
		$module_action_info = ModuleDefinition::getModuleActionXml($module_name);
		$registered = [];

		// Insert new event handlers.
		foreach ($module_action_info->event_handlers ?? [] as $ev)
		{
			$key = implode(':', [$ev->event_name, $ev->position, $module_name, $ev->class_name, $ev->method]);
			$registered[$key] = true;
			if(!Event::isRegisteredHandler($ev->event_name, $ev->position, $module_name, $ev->class_name, $ev->method))
			{
				$output = Event::registerHandler($ev->event_name, $ev->position, $module_name, $ev->class_name, $ev->method);
				if (!$output->toBool())
				{
					return $output;
				}
			}
		}

		// Remove event handlers that are no longer defined by this module.
		if (count($registered))
		{
			// Dummy call to refresh cache
			Event::getRegisteredHandlers('null', 'null');

			foreach (ModuleCache::$registeredHandlers as $event_name => $val1)
			{
				foreach ($val1 as $position => $val2)
				{
					foreach ($val2 as $item)
					{
						if ($item->module === $module_name)
						{
							$key = implode(':', [$event_name, $position, $item->module, $item->type, $item->called_method]);
							if (!isset($registered[$key]))
							{
								$output = Event::unregisterHandler($event_name, $position, $item->module, $item->type, $item->called_method);
								if (!$output->toBool())
								{
									return $output;
								}
							}
						}
					}
				}
			}
		}

		return new BaseObject();
	}

	/**
	 * Register namespace prefixes.
	 *
	 * @param string $module_name
	 * @return BaseObject
	 */
	public static function registerNamespacePrefixes(string $module_name): BaseObject
	{
		$module_action_info = ModuleDefinition::getModuleActionXml($module_name);
		$namespaces = config('namespaces') ?? [];
		$changed = false;

		// Add all namespaces defined by this module.
		foreach ($module_action_info->namespaces ?? [] as $name)
		{
			if (preg_match('/^Rhymix\\\\/i', $name))
			{
				continue;
			}

			if (!isset($namespaces['mapping'][$name]))
			{
				$namespaces['mapping'][$name] = 'modules/' . $module_name;
				$changed = true;
			}
		}

		// Remove namespaces that are no longer defined by this module.
		foreach ($namespaces['mapping'] ?? [] as $name => $path)
		{
			$attached_module = preg_replace('!^modules/!', '', $path);
			if ($attached_module === $module_name && !in_array($name, $module_action_info->namespaces ?? []))
			{
				unset($namespaces['mapping'][$name]);
				$changed = true;
			}
		}

		// Generate a regular expression for routing.
		$regexp = [];
		unset($namespaces['regexp']);
		foreach ($namespaces['mapping'] ?? [] as $name => $path)
		{
			$regexp[] = preg_quote(strtr($name, '\\', '/'), '!');
		}
		if (count($regexp))
		{
			usort($regexp, function($a, $b) { return strlen($b) - strlen($a); });
			$namespaces['regexp'] = '!^(' . implode('|', $regexp) . ')/((?:\\w+/)*)(\\w+)$!';
		}

		// Update system configuration.
		if ($changed)
		{
			\Rhymix\Framework\Config::set('namespaces', $namespaces);
			\Rhymix\Framework\Config::save();
		}

		return new BaseObject();
	}

	/**
	 * Register default prefixes.
	 *
	 * @param string $module_name
	 * @return BaseObject
	 */
	public static function registerDefaultPrefixes(string $module_name): BaseObject
	{
		return new BaseObject();
	}
}
