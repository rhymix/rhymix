<?php

/**
 * Skip if Rhymix is already loaded.
 */
if (defined('RX_VERSION'))
{
	return;
}

/**
 * Check PHP version.
 */
if (PHP_VERSION_ID < 70400)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Rhymix requires PHP 7.4 or higher.';
	exit(1);
}

/**
 * Set error reporting rules.
 */
error_reporting(E_ALL);

/**
 * Suppress date/time errors until the internal time zone is set (see below).
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * Set the default character encoding.
 */
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding'))
{
	mb_internal_encoding('UTF-8');
}
if (function_exists('mb_regex_encoding'))
{
	mb_regex_encoding('UTF-8');
}

/**
 * Load constants and common functions.
 */
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/legacy.php';

/**
 * Define the autoloader.
 */
spl_autoload_register(function($class_name)
{
	$class_name = str_replace('\\', '/', $class_name);
	$filename1 = null;
	$filename2 = null;
	$lang_plugin = null;
	$lang_path = null;

	// Try namespaced classes, legacy classes, and module classes.
	if (preg_match('!^Rhymix/(Framework|Addons|Modules|Plugins|Themes|Widgets)/((\w+)/(?:\w+/)*)?(\w+)$!', $class_name, $matches))
	{
		$dir = RX_BASEDIR . ($matches[1] === 'Framework' ? 'common/framework' : strtolower($matches[1])) . '/' . strtolower($matches[2]);
		$filename1 = $dir . $matches[4] . '.php';
		$filename2 = $dir . strtolower($matches[4]) . '.php';
		if ($matches[1] !== 'Framework' && !empty($matches[3]))
		{
			$lang_plugin = strtolower($matches[3]);
			$lang_path = RX_BASEDIR . strtolower($matches[1]) . '/' . $lang_plugin . '/lang';
		}
	}
	elseif (isset($GLOBALS['RX_AUTOLOAD_FILE_MAP'][$lc_class_name = strtolower($class_name)]))
	{
		$filename1 = RX_BASEDIR . $GLOBALS['RX_AUTOLOAD_FILE_MAP'][$lc_class_name];
	}
	elseif (preg_match('/^([a-zA-Z0-9_]+?)(Admin)?(View|Controller|Model|Item|Api|Wap|Mobile)?$/', $class_name, $matches))
	{
		$module = strtolower($matches[1]);
		$filename1 = RX_BASEDIR . 'modules/' . $module . '/' . $module .
			(!empty($matches[2]) ? '.admin' : '') .
			(!empty($matches[3]) ? ('.' . strtolower($matches[3])) : '.class') . '.php';
		if ($module !== 'module')
		{
			$lang_plugin = $module;
			$lang_path = RX_BASEDIR . 'modules/' . $module . '/lang';
		}
	}
	elseif (isset($GLOBALS['RX_NAMESPACES']) && preg_match($GLOBALS['RX_NAMESPACES']['regexp'], $class_name, $matches))
	{
		$plugin_path = $GLOBALS['RX_NAMESPACES']['mapping'][strtr($matches[1], '/', '\\')] ?? '';
		if ($plugin_path)
		{
			$dir = RX_BASEDIR . $plugin_path . '/' . strtolower($matches[2]);
			$filename1 = $dir . $matches[3] . '.php';
			$filename2 = $dir . strtolower($matches[3]) . '.php';
			$lang_plugin = array_last(explode('/', $plugin_path));
			$lang_path = RX_BASEDIR . $plugin_path . '/lang';
		}
	}

	// Load the PHP file.
	if ($filename1 && file_exists($filename1))
	{
		include $filename1;
	}
	elseif ($filename2 && file_exists($filename2))
	{
		include $filename2;
	}

	// Load the lang file for the plugin.
	if ($lang_plugin)
	{
		Context::loadLang($lang_path, $lang_plugin);
	}
});

/**
 * Also include the Composer autoloader.
 */
require_once RX_BASEDIR . 'common/vendor/autoload.php';

/**
 * Load essential classes.
 */
require_once RX_BASEDIR . 'classes/context/Context.class.php';
require_once RX_BASEDIR . 'classes/object/Object.class.php';
require_once RX_BASEDIR . 'common/framework/Cache.php';
require_once RX_BASEDIR . 'common/framework/Config.php';
require_once RX_BASEDIR . 'common/framework/DateTime.php';
require_once RX_BASEDIR . 'common/framework/Debug.php';
require_once RX_BASEDIR . 'common/framework/Lang.php';

/**
 * Load system configuration.
 */
Rhymix\Framework\Config::init();

/**
 * Load user configuration.
 */
if(file_exists(RX_BASEDIR . 'config/config.user.inc.php'))
{
	require_once RX_BASEDIR . 'config/config.user.inc.php';
}

/**
 * Install the debugger.
 */
Rhymix\Framework\Debug::registerErrorHandlers(error_reporting());

/**
 * Set the internal timezone.
 */
$internal_timezone = Rhymix\Framework\DateTime::getTimezoneNameByOffset(config('locale.internal_timezone') ?? intval(date('Z')));
date_default_timezone_set($internal_timezone);

/**
 * Set certificate authorities for curl and openssl.
 */
ini_set('curl.cainfo', RX_BASEDIR . 'common/vendor/composer/ca-bundle/res/cacert.pem');
ini_set('openssl.cafile', RX_BASEDIR . 'common/vendor/composer/ca-bundle/res/cacert.pem');

/**
 * Initialize the cache handler.
 */
Rhymix\Framework\Cache::init(Rhymix\Framework\Config::get('cache'));
