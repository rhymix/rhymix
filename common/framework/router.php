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
        ),
        '$act' => array(
            'regexp' => '#^(?<act>rss|atom)$#',
            'vars' => ['act' => 'word'],
        ),
        '$document_srl' => array(
            'regexp' => '#^(?<document_srl>[0-9]+)$#',
            'vars' => ['document_srl' => 'int'],
        ),
        '$mid/$document_srl' => array(
            'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/(?<document_srl>[0-9]+)$#',
            'vars' => ['mid' => 'any', 'document_srl' => 'int'],
        ),
        '$mid/category/$category' => array(
            'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/category/(?<category>[0-9]+)$#',
            'vars' => ['mid' => 'any', 'category' => 'int'],
        ),
        '$mid/entry/$entry' => array(
            'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/entry/(?<entry>[^/]+)$#',
            'vars' => ['mid' => 'any', 'entry' => 'any'],
        ),
        '$mid/$act' => array(
            'regexp' => '#^(?<mid>[a-zA-Z0-9_-]+)/(?<act>rss|atom|api)$#',
            'vars' => ['mid' => 'any', 'act' => 'word'],
        ),
        'files/download/$file_srl/$file_key/$filename' => array(
            'regexp' => '#^files/download/(?<file_srl>[0-9]+)/(?<file_key>[a-zA-Z0-9_-]+)/(?<filename>[^/]+)$#',
            'vars' => ['file_srl' => 'int', 'file_key' => 'any', 'filename' => 'any'],
            'extra_vars' => ['act' => 'procFileOutput'],
        ),
    );
    
    /**
     * Internal cache for module actions.
     */
    protected static $_action_cache_prefix = array();
    protected static $_action_cache_module = array();
    protected static $_rearranged_global_routes = array();
    
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
                
                // Try the generic mid/act pattern.
                if (preg_match('#^[a-zA-Z0-9_]+$#', $internal_url) && isset($action_info->action->{$internal_url}) && !$action_info->action->{$internal_url}->route)
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
                $result = self::_insertRouteVars($action->route, $args2);
                if ($result !== false)
                {
                    return $args['mid'] . '/' . $result;
                }
            }
            
            // Check XE-compatible routes that start with $mid and contain no $act.
            if (!isset($args['act']) || ($args['act'] === 'rss' || $args['act'] === 'atom' || $args['act'] === 'api'))
            {
                $result = self::_insertRouteVars(self::_getRearrangedGlobalRoutes(), $args2);
                if ($result !== false)
                {
                    return $result;
                }
            }
            
            // Try the generic mid/act pattern.
            return $args['mid'] . '/' . $args['act'] . (count($args2) ? ('?' . http_build_query($args2)) : '');
        }
        
        // Try XE-compatible global routes.
        if ($rewrite_level >= 1)
        {
            $result = self::_insertRouteVars(self::_getRearrangedGlobalRoutes(), $args);
            if ($result !== false)
            {
                return $result;
            }
        }
        
        // If no route matches, just create a query string.
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
     * Get rearranged global routes for argument matching.
     * 
     * @return array
     */
    protected static function _getRearrangedGlobalRoutes(): array
    {
        if (!self::$_rearranged_global_routes)
        {
            foreach (self::$_global_routes as $route_name => $route_info)
            {
                self::$_rearranged_global_routes[$route_name] = $route_info['vars'];
            }
        }
        
        return self::$_rearranged_global_routes;
    }
    
    /**
     * Insert variables into a route.
     * 
     * @param array $routes
     * @param array $vars
     * @return string|false
     */
    protected static function _insertRouteVars(array $routes, array $vars)
    {
        // If the action only has one route, select it.
        if (count($routes) == 1)
        {
            $selected_route = key($routes);
            $matched_arguments = array_intersect_key($routes[$selected_route], $vars);
            if (count($matched_arguments) !== count($routes[$selected_route]))
            {
                return false;
            }
        }
        
        // If the action has multiple routes, select the one that matches the most arguments.
        else
        {
            // Order the routes by the number of matched arguments.
            $reordered_routes = array();
            foreach ($routes as $route => $route_vars)
            {
                $matched_arguments = array_intersect_key($route_vars, $vars);
                if (count($matched_arguments) === count($route_vars))
                {
                    $reordered_routes[$route] = count($matched_arguments);
                }
            }
            if (!count($reordered_routes))
            {
                return false;
            }
            arsort($reordered_routes);
            $selected_route = array_first_key($reordered_routes);
        }
        
        // Replace variable placeholders with actual variable values.
        $composed_url = preg_replace_callback('#\\$([a-zA-Z0-9_]+)(?::[a-z]+)?#i', function($match) use(&$vars) {
            if (isset($vars[$match[1]]))
            {
                $replacement = urlencode($vars[$match[1]]);
                unset($vars[$match[1]]);
                return $replacement;
            }
            else
            {
                return $match[0];
            }
        }, $selected_route);
        
        // Add a query string for the remaining arguments.
        return $composed_url . (count($vars) ? ('?' . http_build_query($vars)) : '');
    }
}
