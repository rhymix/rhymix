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
        'admin' => ['module' => 'admin'],
        '(?<act>rss|atom)' => [],
        '(?<document_srl>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/?' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/(?<document_srl>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/category/(?<category>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/entry/(?<entry>[^/]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/(?<act>rss|atom|api)' => [],
        'files/download/(?<file_srl>[0-9]+)/(?<file_key>[a-zA-Z0-9_-]+)/(?<filename>[^/]+)' => ['act' => 'procFileOutput'],
    );
    
    /**
     * Internal cache for module actions.
     */
    protected static $_action_cache_prefix = array();
    protected static $_action_cache_module = array();
    
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
            $action_info = self::_getModuleActionInfo($prefix);
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
        foreach (self::$_global_routes as $regexp => $additional_args)
        {
            if (preg_match('#^' . $regexp . '$#', $url, $matches))
            {
                $matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
                $allargs = array_merge($additional_args ?: [], $matches, $args);
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
        if ($count == 1 && ($keys[0] === 'mid' || $keys[0] === 'document_srl'))
        {
            return urlencode($args[$keys[0]]);
        }
        
        // If $mid and $act exist, try routes defined in the module.
        if (isset($args['mid']) && isset($args['act']) && $rewrite_level == 2)
        {
            // Remove $mid and $act from arguments and work with the remainder.
            $remaining_args = array_diff_key($args, ['mid' => 'mid', 'act' => 'act']);
            
            // Check if $act has any routes defined.
            $action_info = self::_getModuleActionInfo($args['mid']);
            $action = $action_info->action->{$args['act']};
            if ($action->route)
            {
                // If the action only has one route, select it.
                if (count($action->route) == 1)
                {
                    $selected_route = key($action->route);
                }
                
                // If the action has multiple routes, select the one that matches the most arguments.
                else
                {
                    // Order the routes by the number of matched arguments.
                    $reordered_routes = array();
                    foreach ($action->route as $route => $route_vars)
                    {
                        $matched_arguments = array_intersect_key(array_combine($route_vars, $route_vars), $remaining_args);
                        if (count($matched_arguments) === count($route_vars))
                        {
                            $reordered_routes[$route] = count($matched_arguments);
                        }
                    }
                    arsort($reordered_routes);
                    $selected_route = array_first_key($reordered_routes);
                }
                
                // Replace variable placeholders with actual variable values.
                $replaced_route = preg_replace_callback('#\\$([a-zA-Z0-9_]+)(?::[a-z]+)?#i', function($match) use(&$remaining_args) {
                    if (isset($remaining_args[$match[1]]))
                    {
                        $replacement = urlencode($remaining_args[$match[1]]);
                        unset($remaining_args[$match[1]]);
                        return $replacement;
                    }
                    else
                    {
                        return $match[0];
                    }
                }, $selected_route);
                
                // Add a query string for the remaining arguments.
                return $replaced_route . (count($remaining_args) ? ('?' . http_build_query($remaining_args)) : '');
            }
            
            // Otherwise, try the generic mid/act pattern.
            return $args['mid'] . '/' . $args['act'] . (count($remaining_args) ? ('?' . http_build_query($remaining_args)) : '');
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
    protected static function _getModuleActionInfo($prefix)
    {
        if (isset(self::$_action_cache_prefix[$prefix]))
        {
            if (self::$_action_cache_prefix[$prefix] && isset(self::$_action_cache_module[self::$_action_cache_prefix[$prefix]]))
            {
                return self::$_action_cache_module[self::$_action_cache_prefix[$prefix]];
            }
            else
            {
                return false;
            }
        }
        
        $module_info = \ModuleModel::getModuleInfoByMid($prefix);
        if ($module_info && $module_info->module)
        {
            self::$_action_cache_prefix[$prefix] = $module_info->module;
            if (isset(self::$_action_cache_module[$module_info->module]))
            {
                return self::$_action_cache_module[$module_info->module];
            }
            else
            {
                $action_info = \ModuleModel::getModuleActionXml($module_info->module);
                return self::$_action_cache_module[$module_info->module] = $action_info;
            }
        }
        else
        {
            self::$_action_cache_prefix[$prefix] = false;
            return false;
        }
    }
}
