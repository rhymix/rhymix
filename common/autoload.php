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
if (PHP_VERSION_ID < 70205)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Rhymix requires PHP 7.2.5 or higher.';
	exit(1);
}

/**
 * Set error reporting rules.
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

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
 * Define the list of legacy class names for the autoloader.
 */
$GLOBALS['RX_AUTOLOAD_FILE_MAP'] = array_change_key_case(array(
	'CacheHandler' => 'classes/cache/CacheHandler.class.php',
	'Context' => 'classes/context/Context.class.php',
	'DB' => 'classes/db/DB.class.php',
	'DisplayHandler' => 'classes/display/DisplayHandler.class.php',
	'HTMLDisplayHandler' => 'classes/display/HTMLDisplayHandler.php',
	'JSCallbackDisplayHandler' => 'classes/display/JSCallbackDisplayHandler.php',
	'JSONDisplayHandler' => 'classes/display/JSONDisplayHandler.php',
	'RawDisplayHandler' => 'classes/display/RawDisplayHandler.php',
	'VirtualXMLDisplayHandler' => 'classes/display/VirtualXMLDisplayHandler.php',
	'XMLDisplayHandler' => 'classes/display/XMLDisplayHandler.php',
	'EditorHandler' => 'classes/editor/EditorHandler.class.php',
	'ExtraVar' => 'classes/extravar/Extravar.class.php',
	'ExtraItem' => 'classes/extravar/Extravar.class.php',
	'FileHandler' => 'classes/file/FileHandler.class.php',
	'FileObject' => 'classes/file/FileObject.class.php',
	'FrontEndFileHandler' => 'classes/frontendfile/FrontEndFileHandler.class.php',
	'Handler' => 'classes/handler/Handler.class.php',
	'XEHttpRequest' => 'classes/httprequest/XEHttpRequest.class.php',
	'Mail' => 'classes/mail/Mail.class.php',
	'Mobile' => 'classes/mobile/Mobile.class.php',
	'ModuleHandler' => 'classes/module/ModuleHandler.class.php',
	'ModuleObject' => 'classes/module/ModuleObject.class.php',
	'PageHandler' => 'classes/page/PageHandler.class.php',
	'EmbedFilter' => 'classes/security/EmbedFilter.class.php',
	'IpFilter' => 'classes/security/IpFilter.class.php',
	'Password' => 'classes/security/Password.class.php',
	'Purifier' => 'classes/security/Purifier.class.php',
	'Security' => 'classes/security/Security.class.php',
	'UploadFileFilter' => 'classes/security/UploadFileFilter.class.php',
	'TemplateHandler' => 'classes/template/TemplateHandler.class.php',
	'Validator' => 'classes/validator/Validator.class.php',
	'WidgetHandler' => 'classes/widget/WidgetHandler.class.php',
	'GeneralXmlParser' => 'classes/xml/GeneralXmlParser.class.php',
	'Xml_Node_' => 'classes/xml/XmlParser.class.php',
	'XmlGenerator' => 'classes/xml/XmlGenerator.class.php',
	'XmlJsFilter' => 'classes/xml/XmlJsFilter.class.php',
	'XmlLangParser' => 'classes/xml/XmlLangParser.class.php',
	'XmlParser' => 'classes/xml/XmlParser.class.php',
	'XeXmlParser' => 'classes/xml/XmlParser.class.php',
	'Ftp' => 'common/libraries/ftp.php',
	'Tar' => 'common/libraries/tar.php',
	'CryptoCompat' => 'common/libraries/cryptocompat.php',
	'VendorPass' => 'common/libraries/vendorpass.php',
), CASE_LOWER);

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
 * Load user configuration.
 */
if(file_exists(RX_BASEDIR . 'config/config.user.inc.php'))
{
	require_once RX_BASEDIR . 'config/config.user.inc.php';
}

/**
 * Load system configuration.
 */
Rhymix\Framework\Config::init();

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
