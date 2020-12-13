<?php

namespace Rhymix\Framework;

/**
 * The router class.
 */
class Router
{
	/**
	 * List of XE-compatible global routes.
	 */
	protected static $_global_routes = array(
		'$document_srl' => array(
			'regexp' => '#^(?<document_srl>[0-9]+)$#',
			'vars' => ['document_srl' => 'int'],
			'priority' => 0,
		),
		'$mid' => array(
			'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/?$#',
			'vars' => ['mid' => 'any'],
			'priority' => 0,
		),
		'$act' => array(
			'regexp' => '#^(?<act>rss|atom)$#',
			'vars' => ['act' => 'word'],
			'priority' => 0,
		),
		'$mid/$document_srl' => array(
			'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/(?<document_srl>[0-9]+)$#',
			'vars' => ['mid' => 'any', 'document_srl' => 'int'],
			'priority' => 30,
		),
		'$mid/category/$category' => array(
			'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/category/(?<category>[0-9]+)$#',
			'vars' => ['mid' => 'any', 'category' => 'int'],
			'priority' => 10,
		),
		'$mid/entry/$entry' => array(
			'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/entry/(?<entry>[^/]+)$#',
			'vars' => ['mid' => 'any', 'entry' => 'any'],
			'priority' => 0,
		),
		'$mid/$act' => array(
			'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/(?<act>rss|atom|api)$#',
			'vars' => ['mid' => 'any', 'act' => 'word'],
			'priority' => 20,
		),
		'files/download/$file_srl/$file_key/$filename' => array(
			'regexp' => '#^files/download/(?<file_srl>[0-9]+)/(?<file_key>[a-zA-Z0-9_-]+)/(?<filename>[^/]+)$#',
			'vars' => ['file_srl' => 'int', 'file_key' => 'any', 'filename' => 'any'],
			'extra_vars' => ['act' => 'procFileOutput'],
			'priority' => 0,
		),
	);
	
	/**
	 * List of legacy modules whose URLs should not be shortened.
	 */
	protected static $_except_modules = array(
		'socialxe' => true,
	);
	
	/**
	 * Internal cache for module and route information.
	 */
	protected static $_action_cache_prefix = array();
	protected static $_action_cache_module = array();
	protected static $_global_forwarded_cache = array();
	protected static $_internal_forwarded_cache = array();
	protected static $_route_cache = array();
	
	/**
	 * Return the currently configured rewrite level.
	 * 
	 * 0 = None
	 * 1 = XE-compatible rewrite rules only
	 * 2 = Full rewrite support
	 * 
	 * @return int
	 */
	public static function getRewriteLevel(): int
	{
		$level = Config::get('url.rewrite');
		if ($level === null)
		{
			$level = Config::get('use_rewrite') ? 1 : 0;
		}
		return intval($level);
	}
	
	/**
	 * Extract request arguments from the current URL.
	 * 
	 * @param string $method
	 * @param string $url
	 * @param int $rewrite_level
	 * @return object
	 */
	public static function parseURL(string $method, string $url, int $rewrite_level)
	{
		// Get the local part of the current URL.
		if (starts_with(\RX_BASEURL, $url))
		{
			$url = substr($url, strlen(\RX_BASEURL));
		}
		
		// Prepare the return object.
		$result = new \stdClass;
		$result->status = 200;
		$result->url = '';
		$result->module = '';
		$result->mid = '';
		$result->act = '';
		$result->forwarded = false;
		$result->args = array();
		
		// Separate additional arguments from the URL.
		$args = array();
		$argstart = strpos($url, '?');
		if ($argstart !== false)
		{
			@parse_str(substr($url, $argstart + 1), $args);
			$url = substr($url, 0, $argstart);
			$result->args = $args;
		}
		
		// Decode the URL into plain UTF-8.
		$url = $result->url = urldecode($url);
		if ($url === '')
		{
			return $result;
		}
		if (function_exists('mb_check_encoding') && !mb_check_encoding($url, 'UTF-8'))
		{
			$result->status = 404;
			return $result;
		}
		
		// Try to detect the prefix. This might be $mid.
		if ($rewrite_level >= 2 && preg_match('#^([a-zA-Z0-9_-]+)(?:/(.*))?$#s', $url, $matches))
		{
			// Separate the prefix and the internal part of the URL.
			$prefix = $matches[1];
			$internal_url = $matches[2] ?? '';
			$prefix_type = 'mid';
			
			// Find the module associated with this prefix.
			$module_name = '';
			$action_info = self::_getActionInfoByPrefix($prefix, $module_name);
			if ($action_info === false)
			{
				$action_info = self::_getActionInfoByModule($prefix);
				if ($action_info !== false)
				{
					$module_name = $prefix;
					$prefix_type = 'module';
				}
			}
			
			// If a module is found, try its routes.
			if ($action_info)
			{
				// Try the index action.
				if ($internal_url === '' && !isset($args['act']) && $action_info->default_index_act)
				{
					$allargs = array_merge($args, [$prefix_type => $prefix]);
					$result->module = $module_name;
					$result->mid = $prefix_type === 'mid' ? $prefix : '';
					$result->args = $allargs;
					return $result;
				}
				
				// Try the list of routes defined by the module.
				foreach ($action_info->route->{$method} as $regexp => $action)
				{
					if (preg_match($regexp, $internal_url, $matches))
					{
						$matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
						$allargs = array_merge($args, $matches, [$prefix_type => $prefix, 'act' => $action]);
						$result->module = $module_name;
						$result->mid = $prefix_type === 'mid' ? $prefix : '';
						$result->act = $action;
						$result->args = $allargs;
						return $result;
					}
				}
				
				// Check other modules.
				if ($prefix_type === 'mid')
				{
					$forwarded_routes = self::_getForwardedRoutes('internal');
					foreach ($forwarded_routes[$method] ?: [] as $regexp => $action)
					{
						if (preg_match($regexp, $internal_url, $matches))
						{
							$matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
							$allargs = array_merge($args, $matches, [$prefix_type => $prefix, 'act' => $action[1]]);
							$result->module = $action[0];
							$result->mid = $prefix;
							$result->act = $action[1];
							$result->forwarded = true;
							$result->args = $allargs;
							return $result;
						}
					}
				}
				
				// Try the generic mid/act pattern.
				if (preg_match('#^[a-zA-Z0-9_]+$#', $internal_url))
				{
					$allargs = array_merge($args, [$prefix_type => $prefix, 'act' => $internal_url]);
					$result->module = $module_name;
					$result->mid = $prefix_type === 'mid' ? $prefix : '';
					$result->act = $internal_url;
					$result->forwarded = true;
					$result->args = $allargs;
					return $result;
				}
				
				// If the module defines a 404 error handler, call it.
				if ($internal_url && isset($action_info->error_handlers[404]))
				{
					$allargs = array_merge($args, [$prefix_type => $prefix, 'act' => $action_info->error_handlers[404]]);
					$result->module = $module_name;
					$result->mid = $prefix_type === 'mid' ? $prefix : '';
					$result->act = $action_info->error_handlers[404];
					$result->forwarded = false;
					$result->args = $allargs;
					return $result;
				}
			}
		}
		
		// Try registered global routes.
		if ($rewrite_level >= 2)
		{
			$global_routes = self::_getForwardedRoutes('global');
			foreach ($global_routes[$method] ?: [] as $regexp => $action)
			{
				if (preg_match($regexp, $url, $matches))
				{
					$matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
					$allargs = array_merge($args, $matches, ['act' => $action[1]]);
					$result->module = $action[0];
					$result->act = $action[1];
					$result->forwarded = true;
					$result->args = $allargs;
					return $result;
			}
			}
		}
		
		// Try XE-compatible global routes.
		foreach (self::$_global_routes as $route_info)
		{
			if (preg_match($route_info['regexp'], $url, $matches))
			{
				$matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
				$allargs = array_merge($args, $matches, $route_info['extra_vars'] ?? []);
				$result->module = $allargs['module'] ?? '';
				$result->mid = $allargs['mid'] ?: '';
				$result->act = $allargs['act'] ?: '';
				$result->forwarded = false;
				$result->args = $allargs;
				return $result;
			}
		}
		
		// If no pattern matches, return either an empty route or a 404 error.
		$result->module = isset($args['module']) ? $args['module'] : '';
		$result->mid = isset($args['mid']) ? $args['mid'] : '';
		$result->act = isset($args['act']) ? $args['act'] : '';
		$result->args = $args;
		if ($url === '' || $url === 'index.php')
		{
			$result->url = '';
			return $result;
		}
		else
		{
			$result->status = 404;
			return $result;
		}
	}
	
	/**
	 * Create a URL for the given set of arguments.
	 * 
	 * @param array $args
	 * @param int $rewrite_level
	 * @return string
	 */
	public static function getURL(array $args, int $rewrite_level): string
	{
		// If rewrite is turned off, just create a query string.
		if ($rewrite_level == 0)
		{
			return 'index.php?' . http_build_query($args);
		}
		
		// Cache the number of arguments and their keys.
		$count = count($args);
		$keys = array_keys($args);
		
		// If there are no arguments, return the URL of the main page.
		if ($count == 0)
		{
			return '';
		}
		
		// If there is only one argument, try either $mid or $document_srl.
		if ($rewrite_level >= 1 && $count == 1 && ($keys[0] === 'mid' || $keys[0] === 'document_srl'))
		{
			return urlencode($args[$keys[0]]);
		}
		
		// If the list of keys is already cached, return the corresponding route.
		$keys_sorted = $keys; sort($keys_sorted);
		$keys_string = implode('.', $keys_sorted) . ':' . ($args['mid'] ?? '') . ':' . ($args['act'] ?? '');
		if (isset(self::$_route_cache[$rewrite_level][$keys_string]))
		{
			return self::_insertRouteVars(self::$_route_cache[$rewrite_level][$keys_string], $args);
		}
		
		// Remove $mid and $act from arguments and work with the remainder.
		$args2 = $args; unset($args2['module'], $args2['mid'], $args2['act']);
		
		// If $mid exists, try routes defined in the module.
		if ($rewrite_level >= 2 && (isset($args['mid']) || isset($args['module'])))
		{
			// Get module action info.
			if (isset($args['mid']))
			{
				$action_info = self::_getActionInfoByPrefix($args['mid']);
				$prefix_type = 'mid';
			}
			elseif (isset($args['module']))
			{
				$action_info = self::_getActionInfoByModule($args['module']);
				$prefix_type = 'module';
			}
			
			// If there is no $act, use the default action.
			$act = isset($args['act']) ? $args['act'] : $action_info->default_index_act;
			
			// Check if $act has any routes defined.
			$action = $action_info->action->{$act} ?? null;
			if ($action && $action->route)
			{
				$result = self::_getBestMatchingRoute($action->route, $args2);
				if ($result !== false)
				{
					self::$_route_cache[$rewrite_level][$keys_string] = '$' . $prefix_type . '/' . $result . '$act:delete';
					$internal_url = self::_insertRouteVars($result, $args2);
					return $args[$prefix_type] . ($internal_url ? ('/' . $internal_url) : '');
				}
			}
			
			// Check other modules for $act.
			if ($prefix_type === 'mid')
			{
				$forwarded_routes = self::_getForwardedRoutes('internal');
				if (isset($forwarded_routes['reverse'][$act]))
				{
					$result = self::_getBestMatchingRoute($forwarded_routes['reverse'][$act], $args2);
					if ($result !== false)
					{
						self::$_route_cache[$rewrite_level][$keys_string] = '$' . $prefix_type . '/' . $result . '$act:delete';
						$internal_url = self::_insertRouteVars($result, $args2);
						return $args[$prefix_type] . ($internal_url ? ('/' . $internal_url) : '');
					}
				}
			}
			
			// Try the generic mid/act pattern.
			if (($prefix_type !== 'module' || !isset(self::$_except_modules[$args[$prefix_type]])) && isset($args['act']))
			{
				self::$_route_cache[$rewrite_level][$keys_string] = '$' . $prefix_type . '/$act';
				$internal_url = $args['act'] . (count($args2) ? ('?' . http_build_query($args2)) : '');
				return $args[$prefix_type] . ($internal_url ? ('/' . $internal_url) : '');
			}
		}
		
		// Try registered global routes.
		if ($rewrite_level >= 2 && isset($args['act']))
		{
			$global_routes = self::_getForwardedRoutes('global');
			if (isset($global_routes['reverse'][$args['act']]))
			{
				$result = self::_getBestMatchingRoute($global_routes['reverse'][$args['act']], $args2);
				if ($result !== false)
				{
					self::$_route_cache[$rewrite_level][$keys_string] = $result . '$act:delete';
					return self::_insertRouteVars($result, $args2);
				}
			}
		}
		
		// Try XE-compatible global routes.
		if ($rewrite_level >= 1)
		{
			if (!isset($args['act']) || ($args['act'] === 'rss' || $args['act'] === 'atom'))
			{
				$result = self::_getBestMatchingRoute(self::$_global_routes, $args);
				if ($result !== false)
				{
					self::$_route_cache[$rewrite_level][$keys_string] = $result;
					return self::_insertRouteVars($result, $args);
				}
			}
		}
		
		// If no route matches, just create a query string.
		self::$_route_cache[$rewrite_level][$keys_string] = 'index.php';
		return 'index.php?' . http_build_query($args);
	}
	
	/**
	 * Load and cache module action info.
	 * 
	 * @param string $prefix
	 * @return object
	 */
	protected static function _getActionInfoByPrefix(string $prefix, string &$module_name = '')
	{
		if (isset(self::$_action_cache_prefix[$prefix]))
		{
			$module_name = self::$_action_cache_prefix[$prefix];
			return self::_getActionInfoByModule(self::$_action_cache_prefix[$prefix]) ?: false;
		}
		
		$module_info = \ModuleModel::getModuleInfoByMid($prefix);
		if ($module_info && $module_info->module)
		{
			$module_name = self::$_action_cache_prefix[$prefix] = $module_info->module;
			return self::_getActionInfoByModule(self::$_action_cache_prefix[$prefix]) ?: false;
		}
		else
		{
			return self::$_action_cache_prefix[$prefix] = false;
		}
	}
	
	/**
	 * Load and cache module action info.
	 * 
	 * @param string $prefix
	 * @return object
	 */
	protected static function _getActionInfoByModule(string $module)
	{
		if (isset(self::$_action_cache_module[$module]))
		{
			return self::$_action_cache_module[$module];
		}
		
		$action_info = \ModuleModel::getModuleActionXml($module);
		return self::$_action_cache_module[$module] = $action_info ?: false;
	}
	
	/**
	 * Get the list of routes that are registered for action-forward.
	 * 
	 * @param string $type
	 * @return array
	 */
	protected static function _getForwardedRoutes(string $type): array
	{
		if ($type === 'internal' && count(self::$_internal_forwarded_cache))
		{
			return self::$_internal_forwarded_cache;
		}
		if ($type === 'global' && count(self::$_global_forwarded_cache))
		{
			return self::$_global_forwarded_cache;
		}
		
		self::$_global_forwarded_cache['GET'] = array();
		self::$_global_forwarded_cache['POST'] = array();
		self::$_global_forwarded_cache['reverse'] = array();
		self::$_internal_forwarded_cache['GET'] = array();
		self::$_internal_forwarded_cache['POST'] = array();
		self::$_internal_forwarded_cache['reverse'] = array();
		
		$action_forward = \ModuleModel::getActionForward();
		foreach ($action_forward as $action_name => $action_info)
		{
			if ($action_info->route_regexp)
			{
				foreach ($action_info->route_regexp as $regexp_info)
				{
					if ($action_info->global_route === 'Y')
					{
						self::$_global_forwarded_cache[$regexp_info[0]][$regexp_info[1]] = [$action_info->module, $action_name];
					}
					else
					{
						self::$_internal_forwarded_cache[$regexp_info[0]][$regexp_info[1]] = [$action_info->module, $action_name];
					}
				}
				if ($action_info->global_route === 'Y')
				{
					self::$_global_forwarded_cache['reverse'][$action_name] = $action_info->route_config;
				}
				else
				{
					self::$_internal_forwarded_cache['reverse'][$action_name] = $action_info->route_config;
				}
			}
		}
		return $type === 'internal' ? self::$_internal_forwarded_cache : self::$_global_forwarded_cache;
	}
	
	/**
	 * Find the best matching route for an array of variables.
	 * 
	 * @param array $routes
	 * @param array $vars
	 * @return string|false
	 */
	protected static function _getBestMatchingRoute(array $routes, array $vars)
	{
		// If the action only has one route, select it.
		if (count($routes) == 1)
		{
			$only_route = key($routes);
			$matched_arguments = array_intersect_key($routes[$only_route]['vars'], $vars);
			if (count($matched_arguments) !== count($routes[$only_route]['vars']))
			{
				return false;
			}
			return $only_route;
		}
		
		// If the action has multiple routes, select the one that matches the most arguments.
		else
		{
			// Order the routes by the number of matched arguments.
			$reordered_routes = array();
			foreach ($routes as $route => $route_vars)
			{
				$matched_arguments = array_intersect_key($route_vars['vars'], $vars);
				if (count($matched_arguments) === count($route_vars['vars']))
				{
					$reordered_routes[$route] = ($route_vars['priority'] * 1000) + count($matched_arguments);
				}
			}
			if (!count($reordered_routes))
			{
				return false;
			}
			arsort($reordered_routes);
			$best_route = array_first_key($reordered_routes);
			return $best_route;
		}
	}
	
	/**
	 * Insert variables into a route.
	 * 
	 * @param string $route
	 * @param array $vars
	 * @return string
	 */
	protected static function _insertRouteVars(string $route, array $vars): string
	{
		// Replace variable placeholders with actual variable values.
		$route = preg_replace_callback('#\\$([a-zA-Z0-9_]+)(:[a-z]+)?#i', function($match) use(&$vars) {
			if (isset($vars[$match[1]]))
			{
				$replacement = urlencode($vars[$match[1]]);
				unset($vars[$match[1]]);
				return (isset($match[2]) && $match[2] === ':delete') ? '' : $replacement;
			}
			else
			{
				return '';
			}
		}, $route);
		
		// Add a query string for the remaining arguments.
		return $route . (count($vars) ? ('?' . http_build_query($vars)) : '');
	}
}
