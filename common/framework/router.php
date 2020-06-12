<?php

namespace Rhymix\Framework;

/**
 * The router class.
 */
class Router
{
    /**
     * List of XE-compatible rewrite rules.
     */
    protected static $_xe_compatible_rules = array(
        'admin' => ['module' => 'admin'],
        '(?<act>rss|atom)' => [],
        '(?<document_srl>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/?' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/(?<document_srl>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/category/(?<category_srl>[0-9]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/entry/(?<entry>[^/]+)' => [],
        '(?<mid>[a-zA-Z0-9_-]+)/(?<act>rss|atom|api)' => [],
        'files/download/(?<file_srl>[0-9]+)/(?<file_key>[a-zA-Z0-9_-]+)/(?<filename>[^/]+)' => ['act' => 'procFileOutput'],
    );
    
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
     * @return array
     */
    public static function getRequestArguments(): array
    {
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
        
        // Try XE-compatible rules.
        foreach (self::$_xe_compatible_rules as $regexp => $additional_args)
        {
            if (preg_match('#^' . $regexp . '$#', $url, $matches))
            {
                $matches = array_filter($matches, 'is_string', \ARRAY_FILTER_USE_KEY);
                $allargs = array_merge($additional_args ?: [], $matches, $args ?: []);
                return $allargs;
            }
        }
        
        // If no pattern matches, return an empty array.
        return array();
    }
}
