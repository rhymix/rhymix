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
        '$document_srl' => array(
            'regexp' => '#^(?<document_srl>[0-9]+)$#',
            'vars' => ['document_srl' => 'int'],
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
     * Internal cache for module and route information.
     */
    protected static $_action_cache_prefix = array();
    protected static $_action_cache_module = array();
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
     * @param int $rewrite_level
     * @return array
     */
    public static function getRequestArguments(int $rewrite_level): array
    {
        // Get the request method.
        $method = $_SERVER['REQUEST_METHOD'] ?: 'GET';
        
        // Get the local part of the current URL.
        $url = $_SERVER['REQUEST_URI'];
        if (starts_with(\RX_BASEURL, $url))
        {
            $url = substr($url, strlen(\RX_BASEURL));
        }
        
        // Separate additional arguments from the URL.
        $args = array();
        $argstart = strpos($url, '?');
        if ($argstart !== false)
        {
            @parse_str(substr($url, $argstart + 1), $args);
            $url = substr($url, 0, $argstart);
        }
        
        // Decode the URL into plain UTF-8.
        $url = urldecode($url);
        if ($url === '' || (function_exists('mb_check_encoding') && !mb_check_encoding($url, 'UTF-8')))
        {
            return array();
        }
        
        // Try to detect the prefix. This might be $mid.
        if ($rewrite_level > 1 && preg_match('#^([a-zA-Z0-9_-]+)(?:/(.*))?#s', $url, $matches))
        {
            // Separate the prefix and the internal part of the URL.
            $prefix = $matches[1];
            $internal_url = $matches[2] ?? '';
            
            // Find the module associated with this prefix.
            $action_info = self::_getActionInfoByPrefix($prefix);
            if ($action_info)
            {
                // Try the list of routes defined by the module.
                foreach ($action_info->route->{$method} as $regexp => $action)
                {
                    if (preg_match($regexp, $internal_url, $matches))
                    {
                        $matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
                        $allargs = array_merge(['mid' => $prefix, 'act' => $action], $matches, $args);
                        return $allargs;
                    }
                }
                
                // Check other modules.
                $all_routes = self::_getAllCachedRoutes();
                foreach ($all_routes->{$method} as $regexp => $action)
                {
                    if (preg_match($regexp, $internal_url, $matches))
                    {
                        $matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
                        $allargs = array_merge(['mid' => $prefix, 'act' => $action[1]], $matches, $args);
                        return $allargs;
                    }
                }
                
                // Try the generic mid/act pattern.
                if (preg_match('#^[a-zA-Z0-9_]+$#', $internal_url))
                {
                    $allargs = array_merge(['mid' => $prefix, 'act' => $internal_url], $args);
                    return $allargs;
                }
            }
        }
        
        // Try XE-compatible global routes.
        foreach (self::$_global_routes as $route_info)
        {
            if (preg_match($route_info['regexp'], $url, $matches))
            {
                $matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
                $allargs = array_merge($route_info['extra_vars'] ?? [], $matches, $args);
                return $allargs;
            }
        }
        
        // If no pattern matches, return an empty array.
        return array();
    }
    
    /**
     * Create a URL for the given set of arguments.
     * 
     * @param array $args
     * @param int $rewrite_level
     * @return string
     */
    public static function getURLFromArguments(array $args, int $rewrite_level): string
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
        if (isset(self::$_route_cache[$keys_string]))
        {
            return self::_insertRouteVars(self::$_route_cache[$keys_string], $args);
        }
        
        // If $mid exists, try routes defined in the module.
        if ($rewrite_level >= 2 && isset($args['mid']))
        {
            // Remove $mid from arguments and work with the remainder.
            $args2 = $args; unset($args2['mid'], $args2['act']);
            
            // Get module action info.
            $action_info = self::_getActionInfoByPrefix($args['mid']);
            
            // If there is no $act, use the default action.
            $act = isset($args['act']) ? $args['act'] : $action_info->default_index_act;
            
            // Check if $act has any routes defined.
            $action = $action_info->action->{$act} ?? null;
            if ($action && $action->route)
            {
                $result = self::_getBestMatchingRoute($action->route, $args2);
                if ($result !== false)
                {
                    self::$_route_cache[$keys_string] = '$mid/' . $result . '$act:delete';
                    return $args['mid'] . '/' . self::_insertRouteVars($result, $args2);
                }
            }
            
            // Check other modules for $act.
            $all_routes = self::_getAllCachedRoutes();
            if (isset($all_routes->reverse[$act]))
            {
                $result = self::_getBestMatchingRoute($all_routes->reverse[$act], $args2);
                if ($result !== false)
                {
                    self::$_route_cache[$keys_string] = '$mid/' . $result . '$act:delete';
                    return $args['mid'] . '/' . self::_insertRouteVars($result, $args2);
                }
            }
            
            // Check XE-compatible routes that start with $mid and contain no $act.
            if (!isset($args['act']) || ($args['act'] === 'rss' || $args['act'] === 'atom' || $args['act'] === 'api'))
            {
                $result = self::_getBestMatchingRoute(self::$_global_routes, $args2);
                if ($result !== false)
                {
                    self::$_route_cache[$keys_string] = $result;
                    return self::_insertRouteVars($result, $args2);
                }
            }
            
            // Try the generic mid/act pattern.
            self::$_route_cache[$keys_string] = '$mid/$act';
            return $args['mid'] . '/' . $args['act'] . (count($args2) ? ('?' . http_build_query($args2)) : '');
        }
        
        // Try XE-compatible global routes.
        if ($rewrite_level >= 1)
        {
            if (!isset($args['act']) || ($args['act'] === 'rss' || $args['act'] === 'atom'))
            {
                $result = self::_getBestMatchingRoute(self::$_global_routes, $args);
                if ($result !== false)
                {
                    self::$_route_cache[$keys_string] = $result;
                    return self::_insertRouteVars($result, $args);
                }
            }
        }
        
        // If no route matches, just create a query string.
        self::$_route_cache[$keys_string] = 'index.php';
        return 'index.php?' . http_build_query($args);
    }
    
    /**
     * Load and cache module action info.
     * 
     * @param string $prefix
     * @return object
     */
    protected static function _getActionInfoByPrefix(string $prefix)
    {
        if (isset(self::$_action_cache_prefix[$prefix]))
        {
            return self::_getActionInfoByModule(self::$_action_cache_prefix[$prefix]) ?: false;
        }
        
        $module_info = \ModuleModel::getModuleInfoByMid($prefix);
        if ($module_info && $module_info->module)
        {
            self::$_action_cache_prefix[$prefix] = $module_info->module;
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
     * Get the list of all cached routes from all modules.
     * 
     * @return object
     */
    protected static function _getAllCachedRoutes()
    {
		$cache_key = 'site_and_module:action_with_routes';
		$result = Cache::get($cache_key);
		if ($result === null)
		{
			$result = (object)array('GET' => [], 'POST' => [], 'reverse' => []);
			Cache::set($cache_key, $result, 0, true);
		}
		return $result;
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
                    $reordered_routes[$route] = $route_vars['priority'] ?: count($matched_arguments);
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
                return $match[0];
            }
        }, $route);
        
        // Add a query string for the remaining arguments.
        return $route . (count($vars) ? ('?' . http_build_query($vars)) : '');
    }
}
