<?php
    /**
     * @file   config/config.inc.php
     * @author NHN (developers@xpressengine.com)
     * @brief set the include of the class file and other environment configurations
     **/

    @error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
	@ini_set('session.cookie_httponly', 1);

    if(!defined('__ZBXE__')) exit();

    /**
     * @brief display XE's full version
     * Even The file should be revised when releasing altough no change is made
     **/
	define('__XE_VERSION__', '1.5.2');
    define('__ZBXE_VERSION__', __XE_VERSION__); // deprecated : __ZBXE_VERSION__ will be removed. Use __XE_VERSION__ instead.

    /**
     * @brief The base path to where you installed zbXE Wanted
     **/
    define('_XE_PATH_', str_replace('config/config.inc.php', '', str_replace('\\', '/', __FILE__)));


	/**
	 * @brief 쿠키 이외의 값에서도 세션을 인식할 수 있도록 함(파일업로드 등에서의 문제 수정)
	 **/
	ini_set('session.use_only_cookies', 0);


    if(file_exists(_XE_PATH_.'config/package.inc.php')) {
        require _XE_PATH_.'config/package.inc.php';
    } else {
		define('_XE_PACKAGE_','XE');
		define('_XE_LOCATION_','en');
		define('_XE_LOCATION_SITE_','http://www.xpressengine.org/');
		define('_XE_DOWNLOAD_SERVER_','http://en.download.xpressengine.org/');
	}

    /**
     * @brief user configuration files which override the default settings
     * save the following information into config/config.user.inc.php
     * <?php
     * define('__DEBUG__', 0);
     * define('__DEBUG_OUTPUT__', 0);
     * define('__DEBUG_PROTECT__', 1);
     * define('__DEBUG_PROTECT_IP__', '127.0.0.1');
     * define('__DEBUG_DB_OUTPUT__', 0);
     * define('__LOG_SLOW_QUERY__', 0);
     * define('__OB_GZHANDLER_ENABLE__', 1);
     * define('__ENABLE_PHPUNIT_TEST__', 0);
     * define('__PROXY_SERVER__', 'http://domain:port/path');
	 * define('__XE_CDN_PREFIX__', 'http://yourCdnDomain.com/path/');
	 * define('__XE_CDN_VERSION__', 'yourCndVersion');
     */
    if(file_exists(_XE_PATH_.'config/config.user.inc.php')) {
        require _XE_PATH_.'config/config.user.inc.php';
    }

    /**
     * @brief output debug message(bit value)
     * 0: generate debug messages/not display
     * 1: display messages through debugPrint() function
     * 2: output execute time, Request/Response info
     * 4: output DB query history
     **/
    if(!defined('__DEBUG__')) define('__DEBUG__', 0);

    /**
     * @brief output location of debug message
     * 0: connect to the files/_debug_message.php and output
     * 1: HTML output as a comment on the bottom (when response method is the HTML)
     * 2: Firebug console output (PHP 4 & 5. Firebug/FirePHP plug-in required)
     **/
    if(!defined('__DEBUG_OUTPUT__')) define('__DEBUG_OUTPUT__', 0);

    /**
     * @brief output comments of the firePHP console and browser
     * 0: No limit (not recommended)
     * 1: Allow only specified IP addresses
     **/
    if(!defined('__DEBUG_PROTECT__')) define('__DEBUG_PROTECT__', 1);
    if(!defined('__DEBUG_PROTECT_IP__')) define('__DEBUG_PROTECT_IP__', '127.0.0.1');

    /**
     * @brief DB error message definition
     * 0: No output
     * 1: files/_debug_db_query.php connected to the output
     **/
    if(!defined('__DEBUG_DB_OUTPUT__')) define('__DEBUG_DB_OUTPUT__', 0);

    /**
     * @brief Query log for only timeout query among DB queries
     * 0: Do not leave a log
     * = 0: leave a log when the slow query takes over specified seconds
     * Log file is saved as ./files/_db_slow_query.php file
     **/
    if(!defined('__LOG_SLOW_QUERY__')) define('__LOG_SLOW_QUERY__', 0);

    /**
     * @brief Leave DB query information
     * 0: Do not add information to the query
     * 1: Comment the XML Query ID
     **/
    if(!defined('__DEBUG_QUERY__')) define('__DEBUG_QUERY__', 0);

    /**
     * @brief option to enable/disable a compression feature using ob_gzhandler
     * 0: Not used
     * 1: Enabled
     * Only particular servers may have a problem in IE browser when sending a compression
     **/
    if(!defined('__OB_GZHANDLER_ENABLE__')) define('__OB_GZHANDLER_ENABLE__', 1);

    /**
     * @brief decide to use/not use the php unit test (Path/tests/index.php)
     * 0: Not used
     * 1: Enabled
     **/
    if(!defined('__ENABLE_PHPUNIT_TEST__')) define('__ENABLE_PHPUNIT_TEST__', 0);

    /**
     * @brief __PROXY_SERVER__ has server information to request to the external through the target server
     * FileHandler:: getRemoteResource uses the constant
     **/
    if(!defined('__PROXY_SERVER__')) define('__PROXY_SERVER__', null);

	/**
	 * @brief CDN prefix
	 **/
	if(!defined('__XE_CDN_PREFIX__')) define('__XE_CDN_PREFIX__', 'http://static.xpressengine.com/core/');

	/**
	 * @brief CDN version
	 **/
	if(!defined('__XE_CDN_VERSION__')) define('__XE_CDN_VERSION__', '%__XE_CDN_VERSION__%');

    /**
     * @brief Require specific files when using Firebug console output
     **/
    if((__DEBUG_OUTPUT__ == 2) && version_compare(PHP_VERSION, '6.0.0') === -1) {
        require _XE_PATH_.'libs/FirePHPCore/FirePHP.class.php';
    }

	/**
	 * @brief Set Timezone as server time
	 **/
	if(version_compare(PHP_VERSION, '5.3.0') >= 0)
	{
		date_default_timezone_set(@date_default_timezone_get());
	}

	if(!defined('__XE_LOADED_CLASS__')){
		/**
		 * @brief Require a function-defined-file for simple use
		 **/
		require(_XE_PATH_.'config/func.inc.php');

		if(__DEBUG__) define('__StartTime__', getMicroTime());

		/**
		 * @brief include the class files
		 * @TODO : When _autoload() can be used for PHP5 based applications, it will be removed.
		 **/
		if(__DEBUG__) define('__ClassLoadStartTime__', getMicroTime());
		require(_XE_PATH_.'classes/object/Object.class.php');
		require(_XE_PATH_.'classes/extravar/Extravar.class.php');
		require(_XE_PATH_.'classes/handler/Handler.class.php');
		require(_XE_PATH_.'classes/xml/XmlParser.class.php');
		require(_XE_PATH_.'classes/xml/XmlGenerator.class.php');
		require(_XE_PATH_.'classes/xml/XmlJsFilter.class.php');
		require(_XE_PATH_.'classes/xml/XmlLangParser.class.php');
		require(_XE_PATH_.'classes/cache/CacheHandler.class.php');
		require(_XE_PATH_.'classes/context/Context.class.php');
		require(_XE_PATH_.'classes/db/DB.class.php');
		require(_XE_PATH_.'classes/file/FileHandler.class.php');
		require(_XE_PATH_.'classes/widget/WidgetHandler.class.php');
		require(_XE_PATH_.'classes/editor/EditorHandler.class.php');
		require(_XE_PATH_.'classes/module/ModuleObject.class.php');
		require(_XE_PATH_.'classes/module/ModuleHandler.class.php');
		require(_XE_PATH_.'classes/display/DisplayHandler.class.php');
		require(_XE_PATH_.'classes/template/TemplateHandler.class.php');
		require(_XE_PATH_.'classes/mail/Mail.class.php');
		require(_XE_PATH_.'classes/page/PageHandler.class.php');
		require(_XE_PATH_.'classes/mobile/Mobile.class.php');
		require(_XE_PATH_.'classes/validator/Validator.class.php');
		require(_XE_PATH_.'classes/frontendfile/FrontEndFileHandler.class.php');
		require(_XE_PATH_.'classes/security/Security.class.php');
		if(__DEBUG__) $GLOBALS['__elapsed_class_load__'] = getMicroTime() - __ClassLoadStartTime__;
	}
?>
