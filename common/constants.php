<?php

/**
 * RX_VERSION is the version number of the Rhymix CMS.
 */
define('RX_VERSION', '1.8.19');

/**
 * RX_MICROTIME is the startup time of the current script, in microseconds since the Unix epoch.
 */
define('RX_MICROTIME', microtime(true));

/**
 * RX_TIME is the startup time of the current script, in seconds since the Unix epoch.
 */
define('RX_TIME', intval(RX_MICROTIME));

/**
 * RX_BASEDIR is the SERVER-SIDE absolute path of Rhymix (with trailing slash).
 */
define('RX_BASEDIR', str_replace('\\', '/', dirname(__DIR__)) . '/');

/**
 * RX_BASEURL is the CLIENT-SIDE absolute path of Rhymix (with trailing slash, relative to the document root).
 */
if (isset($_SERVER['DOCUMENT_ROOT']) && !strncmp(RX_BASEDIR,  str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), strlen($_SERVER['DOCUMENT_ROOT'])))
{
    define('RX_BASEURL', str_replace('//', '/', '/' . trim(substr(RX_BASEDIR, strlen($_SERVER['DOCUMENT_ROOT'])), '/') . '/'));
}
elseif (isset($_SERVER['PHP_SELF']) && ($len = strlen($_SERVER['PHP_SELF'])) && $len >= 10 && substr($_SERVER['PHP_SELF'], $len - 10) === '/index.php')
{
    define('RX_BASEURL', str_replace('//', '/', '/' . trim(str_replace('\\', '/', substr($_SERVER['PHP_SELF'], 0, $len - 10)), '/') . '/'));
}
else
{
    define('RX_BASEURL', '/');
}

/**
 * RX_REQUEST_URL is the remainder of the current URL (not including RX_BASEURL).
 */
if (isset($_SERVER['REQUEST_URI']))
{
    define('RX_REQUEST_URL', RX_BASEURL === '/' ? substr($_SERVER['REQUEST_URI'], 1) : (substr($_SERVER['REQUEST_URI'], strlen(RX_BASEURL)) ?: ''));
}
else
{
    define('RX_REQUEST_URL', '');
}

/**
 * RX_CLIENT_IP_VERSION and RX_CLIENT_IP contain information about the current visitor's IP address.
 */
if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
{
	include_once __DIR__ . '/framework/filters/ipfilter.php';
	Rhymix\Framework\Filters\IpFilter::getCloudFlareRealIP();
}
if (isset($_SERVER['REMOTE_ADDR']) && preg_match('/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/', $_SERVER['REMOTE_ADDR'], $matches))
{
    define('RX_CLIENT_IP_VERSION', 4);
    define('RX_CLIENT_IP', $matches[0]);
}
elseif (isset($_SERVER['REMOTE_ADDR']) && @inet_pton($_SERVER['REMOTE_ADDR']) !== false)
{
    define('RX_CLIENT_IP_VERSION', 6);
    define('RX_CLIENT_IP', $_SERVER['REMOTE_ADDR']);
}
else
{
    define('RX_CLIENT_IP_VERSION', 4);
    define('RX_CLIENT_IP', '0.0.0.0');
}

/*
 * RX_SSL is true if the current request uses SSL/TLS.
 */
if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
{
    define('RX_SSL', true);
}
elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
{
    define('RX_SSL', true);
}
elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
{
    define('RX_SSL', true);
}
elseif (isset($_SERVER['HTTP_CF_VISITOR']) && stripos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
{
    define('RX_SSL', true);
}
elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
{
    define('RX_SSL', true);
}
else
{
    define('RX_SSL', false);
}

/**
 * RX_POST is true if the current request uses the POST method.
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
    define('RX_POST', true);
}
else
{
    define('RX_POST', false);
}

/**
 * XE core compatibility constants (may be used by XE-compatible plugins and themes).
 */
if (!defined('__XE__'))
{
	define('__XE__', true);
}
define('__XE_VERSION__', RX_VERSION);
define('__XE_VERSION_ALPHA__', false);
define('__XE_VERSION_BETA__', false);
define('__XE_VERSION_RC__', false);
define('__XE_VERSION_STABLE__', true);
define('__XE_MIN_PHP_VERSION__', '5.5.9');
define('__XE_RECOMMEND_PHP_VERSION__', '5.5.9');
define('__ZBXE__', true);
define('__ZBXE_VERSION__', RX_VERSION);
define('_XE_PATH_', RX_BASEDIR);
define('_XE_PACKAGE_', 'XE');
define('_XE_LOCATION_', 'en');
define('_XE_LOCATION_SITE_', 'https://www.xpressengine.com/');
define('_XE_DOWNLOAD_SERVER_', 'https://download.xpressengine.com/');
define('__PROXY_SERVER__', null);
define('__DEBUG__', 0);

/**
 * Other useful constants.
 */
define('DIGITS', '0123456789');
define('XDIGITS', '0123456789abcdef');
define('ALPHABETS', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
define('UPPER', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
define('LOWER', 'abcdefghijklmnopqrstuvwxyz');
define('CR', "\r");
define('CRLF', "\r\n");
define('LF', "\n");
define('FOLLOW_REQUEST_SSL', 0);
define('ENFORCE_SSL', 1);
define('RELEASE_SSL', 2);
