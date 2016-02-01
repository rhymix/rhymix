<?php

/**
 * Legacy debug settings for XE Compatibility
 *
 * Copyright (c) NAVER <http://www.navercorp.com>
 */

/**
 * output debug message (bit value)
 *
 * 0: generate debug messages/not display
 * 1: display messages through debugPrint() function
 * 2: output execute time, Request/Response info
 * 4: output DB query history
 */
if(!defined('__DEBUG__'))
{
	define('__DEBUG__', 0);
}

/**
 * output location of debug message
 *
 * 0: connect to the files/_debug_message.php and output
 * 1: HTML output as a comment on the bottom (when response method is the HTML)
 * 2: Firebug console output (PHP 4 & 5. Firebug/FirePHP plug-in required)
 */
if(!defined('__DEBUG_OUTPUT__'))
{
	define('__DEBUG_OUTPUT__', 0);
}

/**
 * output comments of the firePHP console and browser
 *
 * 0: No limit (not recommended)
 * 1: Allow only specified IP addresses
 */
if(!defined('__DEBUG_PROTECT__'))
{
	define('__DEBUG_PROTECT__', 1);
}

/**
 * Set a ip address to allow debug
 */
if(!defined('__DEBUG_PROTECT_IP__'))
{
	define('__DEBUG_PROTECT_IP__', '127.0.0.1');
}

/**
 * DB error message definition
 *
 * 0: No output
 * 1: files/_debug_db_query.php connected to the output
 */
if(!defined('__DEBUG_DB_OUTPUT__'))
{
	define('__DEBUG_DB_OUTPUT__', 0);
}

/**
 * Query log for only timeout query among DB queries
 *
 * 0: Do not leave a log
 * > 0: leave a log when the slow query takes over specified seconds
 * Log file is saved as ./files/_slowlog_query.php file
 */
if(!defined('__LOG_SLOW_QUERY__'))
{
	define('__LOG_SLOW_QUERY__', 0);
}

/**
 * Trigger excute time log
 *
 * 0: Do not leave a log
 * > 0: leave a log when the trigger takes over specified milliseconds
 * Log file is saved as ./files/_slowlog_trigger.php
 */
if(!defined('__LOG_SLOW_TRIGGER__'))
{
	define('__LOG_SLOW_TRIGGER__', 0);
}

/**
 * Addon excute time log
 *
 * 0: Do not leave a log
 * > 0: leave a log when the trigger takes over specified milliseconds
 * Log file is saved as ./files/_slowlog_addon.php
 */
if(!defined('__LOG_SLOW_ADDON__'))
{
	define('__LOG_SLOW_ADDON__', 0);
}

/**
 * Widget excute time log
 *
 * 0: Do not leave a log
 * > 0: leave a log when the widget takes over specified milliseconds
 * Log file is saved as ./files/_slowlog_widget.php
 */
if(!defined('__LOG_SLOW_WIDGET__'))
{
	define('__LOG_SLOW_WIDGET__', 0);
}

/**
 * output comments of the slowlog files
 *
 * 0: No limit (not recommended)
 * 1: Allow only specified IP addresses
 */
if(!defined('__LOG_SLOW_PROTECT__'))
{
	define('__LOG_SLOW_PROTECT__', 1);
}

/**
 * Set a ip address to allow slowlog
 */
if(!defined('__LOG_SLOW_PROTECT_IP__'))
{
	define('__LOG_SLOW_PROTECT_IP__', '127.0.0.1');
}

/**
 * Leave DB query information
 *
 * 0: Do not add information to the query
 * 1: Comment the XML Query ID
 */
if(!defined('__DEBUG_QUERY__'))
{
	define('__DEBUG_QUERY__', 0);
}

/**
 * __PROXY_SERVER__ has server information to request to the external through the target server
 * FileHandler:: getRemoteResource uses the constant
 */
if(!defined('__PROXY_SERVER__'))
{
	define('__PROXY_SERVER__', NULL);
}
