<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Manages Context such as request arguments/environment variables
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Context
{
	/**
	 * Allow rewrite
	 * @var bool TRUE: using rewrite mod, FALSE: otherwise
	 */
	public $allow_rewrite = FALSE;

	/**
	 * Request method
	 * @var string GET|POST|XMLRPC|JSON
	 */
	public $request_method = 'GET';

	/**
	 * Response method.If it's not set, it follows request method.
	 * @var string HTML|XMLRPC|JSON|JS_CALLBACK
	 */
	public $response_method = '';

	/**
	 * js callback function name.
	 * @var string
	 */
	public $js_callback_func = '';

	/**
	 * DB info
	 * @var object
	 */
	public $db_info = NULL;

	/**
	 * FTP info
	 * @var object
	 */
	public $ftp_info = NULL;

	/**
	 * site's browser title
	 * @var string
	 */
	public $browser_title = '';

	/**
	 * script codes in <head>..</head>
	 * @var string
	 */
	public $html_header = '';

	/**
	 * class names of <body>
	 * @var array
	 */
	public $body_class = array();

	/**
	 * codes after <body>
	 * @var string
	 */
	public $body_header = '';

	/**
	 * class names before </body>
	 * @var string
	 */
	public $html_footer = '';

	/**
	 * Meta tags
	 * @var array
	 */
	public $meta_tags = array();

	/**
	 * OpenGraph metadata
	 * @var array
	 */
	public $opengraph_metadata = array();
	
	/**
	 * Canonical URL
	 * @var string
	 */
	public $canonical_url = '';

	/**
	 * language type - changed by HTTP_USER_AGENT or user's cookie
	 * @var string
	 */
	public $lang_type = '';

	/**
	 * contains language-specific data
	 * @var object
	 */
	public $lang = NULL;

	/**
	 * Checks uploaded
	 * @var bool TRUE if attached file exists
	 */
	public $is_uploaded = FALSE;

	/**
	 * Checks if the site is locked
	 * @var bool TRUE if the site is locked
	 */
	public $is_site_locked = FALSE;

	/**
	 * Result of initial security check
	 * @var string|bool
	 */
	public $security_check = 'OK';

	/**
	 * Singleton instance
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Flag to prevent calling init() twice
	 */
	private static $_init_called = false;

	/**
	 * object oFrontEndFileHandler()
	 * @var object
	 */
	private static $_oFrontEndFileHandler = null;

	/**
	 * SSL action cache file
	 * @var array
	 */
	private static $_ssl_actions_cache_file = 'files/cache/common/ssl_actions.php';

	/**
	 * SSL action cache
	 */
	private static $_ssl_actions = array();

	/**
	 * Plugin blacklist cache
	 */
	private static $_blacklist = null;

	/**
	 * Reserved words cache
	 */
	private static $_reserved_words = null;

	/**
	 * Reserved keys cache
	 */
	private static $_reserved_keys = array(
		'_rx_ajax_compat' => true,
		'_rx_csrf_token' => true,
	);

	/**
	 * Pattern for request vars check
	 * @var array
	 */
	private static $_check_patterns = array(
		'@<(?:\?|%)@' => 'DENY ALL',
		'@<script\s*?language\s*?=@i' => 'DENY ALL',
		'@</?script@i' => 'ALLOW ADMIN ONLY',
	);

	/**
	 * variables from current request
	 * @var object
	 */
	private static $_get_vars = NULL;

	/**
	 * variables from user (Context::get, Context::set)
	 * @var object
	 */
	private static $_tpl_vars = NULL;

	/**
	 * Obtain a singleton instance of Context.
	 *
	 * @return object Instance
	 */
	public static function getInstance()
	{
		if(self::$_instance === null)
		{
			// Create a singleton instance and initialize static properties.
			self::$_instance = new Context();
			self::$_oFrontEndFileHandler = self::$_instance->oFrontEndFileHandler = new FrontEndFileHandler();
			self::$_get_vars = self::$_get_vars ?: new stdClass;
			self::$_tpl_vars = self::$_tpl_vars ?: new stdClass;

			// Include SSL action cache file.
			self::$_ssl_actions_cache_file = RX_BASEDIR . self::$_ssl_actions_cache_file;
			if(Rhymix\Framework\Storage::exists(self::$_ssl_actions_cache_file))
			{
				self::$_ssl_actions = (include self::$_ssl_actions_cache_file) ?: array();
			}
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct()
	{
		
	}

	/**
	 * Initialization, it sets DB information, request arguments and so on.
	 *
	 * @return void
	 */
	public static function init()
	{
		// Prevent calling init() twice.
		if(self::$_init_called)
		{
			return;
		}
		self::$_init_called = true;
		
		// Obtain a singleton instance if not already given.
		if(self::$_instance === null)
		{
			self::$_instance = self::getInstance();
		}
		
		// Set information about the current request.
		self::_checkGlobalVars();
		self::setRequestMethod();
		self::setRequestArguments();
		self::setUploadInfo();
		
		// Load system configuration.
		self::loadDBInfo();
		
		// If Rhymix is installed, get virtual site information.
		if(self::isInstalled())
		{
			$oModuleModel = getModel('module');
			$site_module_info = $oModuleModel->getDefaultMid() ?: new stdClass;
			self::set('site_module_info', $site_module_info);
			self::set('_default_timezone', ($site_module_info->settings && $site_module_info->settings->timezone) ? $site_module_info->settings->timezone : null);
			self::set('_default_url', self::$_instance->db_info->default_url = self::getDefaultUrl($site_module_info));
			self::set('_http_port', self::$_instance->db_info->http_port = $site_module_info->http_port ?: null);
			self::set('_https_port', self::$_instance->db_info->https_port = $site_module_info->https_port ?: null);
			self::set('_use_ssl', self::$_instance->db_info->use_ssl = $site_module_info->security ?: 'none');
			if (PHP_SAPI === 'cli')
			{
				self::set('_default_url', $default_url = config('url.default'));
				if (!defined('RX_BASEURL'))
				{
					define('RX_BASEURL', parse_url($default_url, PHP_URL_PATH));
				}
			}
		}
		else
		{
			$site_module_info = new stdClass;
			$site_module_info->domain = $_SERVER['HTTP_HOST'];
			$site_module_info->security = RX_SSL ? 'always' : 'none';
			$site_module_info->settings = new stdClass;
			$site_module_info->is_default_replaced = true;
			self::set('site_module_info', $site_module_info);
		}
		
		// Redirect to SSL if the current domain always uses SSL.
		if ($site_module_info->security === 'always' && !RX_SSL && PHP_SAPI !== 'cli' && !$site_module_info->is_default_replaced)
		{
			$ssl_url = self::getDefaultUrl($site_module_info) . RX_REQUEST_URL;
			self::setCacheControl(0);
			header('Location: ' . $ssl_url, true, 301);
			exit;
		}

		// Load language support.
		$enabled_langs = self::loadLangSelected();
		$set_lang_cookie = false;
		self::set('lang_supported', $enabled_langs);
		
		if($lang_type = self::get('l'))
		{
			if($_COOKIE['lang_type'] !== $lang_type)
			{
				$set_lang_cookie = true;
			}
		}
		elseif($_COOKIE['lang_type'])
		{
			$lang_type = $_COOKIE['lang_type'];
		}
		elseif(config('locale.auto_select_lang') && count($enabled_langs) > 1)
		{
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			{
				foreach($enabled_langs as $lang_code => $lang_name)
				{
					if(!strncasecmp($lang_code, $_SERVER['HTTP_ACCEPT_LANGUAGE'], strlen($lang_code)))
					{
						$lang_type = $lang_code;
						$set_lang_cookie = true;
					}
				}
			}
		}
		
		$lang_type = preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_type);
		if ($set_lang_cookie)
		{
			setcookie('lang_type', $lang_type, time() + 86400 * 365, '/', null, !!config('session.use_ssl_cookies'));
		}
		
		if(!$lang_type || !isset($enabled_langs[$lang_type]))
		{
			if($site_module_info->settings->language)
			{
				$lang_type = self::$_instance->db_info->lang_type = $site_module_info->settings->language;
			}
			else
			{
				$lang_type = self::$_instance->db_info->lang_type ?: 'ko';
			}
		}

		$lang = Rhymix\Framework\Lang::getInstance($lang_type);
		$lang->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		$lang->loadDirectory(RX_BASEDIR . 'modules/module/lang', 'module');
		self::setLangType(self::$_instance->lang_type = $lang_type);
		self::set('lang', self::$_instance->lang = $lang);
		
		// Set global variables for backward compatibility.
		$GLOBALS['oContext'] = self::$_instance;
		$GLOBALS['__Context__'] = &self::$_tpl_vars;
		$GLOBALS['_time_zone'] = self::$_instance->db_info->time_zone;
		$GLOBALS['lang'] = &$lang;
		
		// set session handler
		if(self::isInstalled() && config('session.use_db'))
		{
			$oSessionModel = getModel('session');
			$oSessionController = getController('session');
			ini_set('session.serialize_handler', 'php');
			session_set_save_handler(
					array(&$oSessionController, 'open'), array(&$oSessionController, 'close'), array(&$oSessionModel, 'read'), array(&$oSessionController, 'write'), array(&$oSessionController, 'destroy'), array(&$oSessionController, 'gc')
			);
		}
		
		// start session
		$relax_key_checks = (self::$_get_vars->act === 'procFileUpload' && preg_match('/shockwave\s?flash/i', $_SERVER['HTTP_USER_AGENT']));
		Rhymix\Framework\Session::checkSSO($site_module_info);
		Rhymix\Framework\Session::start(false, $relax_key_checks);

		// start output buffer
		if (\PHP_SAPI !== 'cli')
		{
			ob_start();
		}

		// set authentication information in Context and session
		if (self::isInstalled())
		{
			$oModuleModel = getModel('module');
			$oModuleModel->loadModuleExtends();

			if (Rhymix\Framework\Session::getMemberSrl())
			{
				getController('member')->setSessionInfo();
			}
			else
			{
				self::set('is_logged', false);
				self::set('logged_info', Rhymix\Framework\Session::getMemberInfo());
			}
		}
		
		// set locations for javascript use
		$current_url = $request_uri = self::getRequestUri();
		if ($_SERVER['REQUEST_METHOD'] == 'GET' && self::$_get_vars)
		{
			if ($query_string = http_build_query(self::$_get_vars))
			{
				$current_url .= '?' . $query_string;
			}
		}
		if (strpos($current_url, 'xn--') !== false)
		{
			$current_url = Rhymix\Framework\URL::decodeIdna($current_url);
		}
		if (strpos($request_uri, 'xn--') !== false)
		{
			$request_uri = Rhymix\Framework\URL::decodeIdna($request_uri);
		}
		self::set('current_url', $current_url);
		self::set('request_uri', $request_uri);
		
		// set mobile status
		self::set('m', Mobile::isFromMobilePhone() ? 1 : 0);
		
		// If the site is locked, display the locked page.
		if(config('lock.locked'))
		{
			self::enforceSiteLock();
		}
	}

	/**
	 * Get the session status
	 * 
	 * @return bool
	 */
	public static function getSessionStatus()
	{
		return Rhymix\Framework\Session::isStarted();
	}

	/**
	 * Start the session if $_SESSION was touched
	 * 
	 * @return void
	 */
	public static function checkSessionStatus($force = false)
	{
		return Rhymix\Framework\Session::checkStart($force);
	}

	/**
	 * Finalize using resources, such as DB connection
	 *
	 * @return void
	 */
	public static function close()
	{
		// Save debugging information.
		if (!DisplayHandler::$debug_printed)
		{
			DisplayHandler::getDebugInfo();
		}
		
		// Check session status and close it if open.
		if (Rhymix\Framework\Session::checkStart())
		{
			Rhymix\Framework\Session::close();
		}
	}

	/**
	 * set Cache-Control header
	 *
	 * @return void
	 */
	public static function setCacheControl($ttl = 0, $public = true)
	{
		if($ttl == 0)
		{
			header('Cache-Control: ' . ($public ? 'public, ' : 'private, ') . 'must-revalidate, post-check=0, pre-check=0, no-store, no-cache');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		}
		elseif($ttl == -1)
		{
			header('Cache-Control: ' . ($public ? 'public, ' : 'private, ') . 'must-revalidate, post-check=0, pre-check=0');
		}
		else
		{
			header('Cache-Control: ' . ($public ? 'public, ' : 'private, ') . 'must-revalidate, max-age=' . (int)$ttl);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (int)$ttl) . ' GMT');
		}
	}

	/**
	 * Load the database information
	 *
	 * @return void
	 */
	public static function loadDBInfo($config = null)
	{
		// Load new configuration format.
		if ($config === null)
		{
			$config = Rhymix\Framework\Config::getAll();
		}
		if (!count($config))
		{
			self::$_instance->db_info = self::$_instance->db_info ?: new stdClass;
			return;
		}

		// Copy to old format for backward compatibility.
		self::$_instance->db_info = self::convertDBInfo($config);
		self::$_instance->allow_rewrite = self::$_instance->db_info->use_rewrite === 'Y';
	}

	/**
	 * Convert Rhymix configuration to XE DBInfo format
	 * 
	 * @param array $config
	 * @return object
	 */
	public static function convertDBInfo($config)
	{
		$db_info = new stdClass;
		$db_info->master_db = array(
			'db_type' => $config['db']['master']['type'] . ($config['db']['master']['engine'] === 'innodb' ? '_innodb' : ''),
			'db_hostname' => $config['db']['master']['host'],
			'db_port' => $config['db']['master']['port'],
			'db_userid' => $config['db']['master']['user'],
			'db_password' => $config['db']['master']['pass'],
			'db_database' => $config['db']['master']['database'],
			'db_table_prefix' => $config['db']['master']['prefix'],
			'db_charset' => $config['db']['master']['charset'],
		);
		$db_info->slave_db = array();
		foreach ($config['db'] as $key => $dbconfig)
		{
			if ($key !== 'master')
			{
				$db_info->slave_db[] = array(
					'db_type' => $dbconfig['type'] . ($dbconfig['engine'] === 'innodb' ? '_innodb' : ''),
					'db_hostname' => $dbconfig['host'],
					'db_port' => $dbconfig['port'],
					'db_userid' => $dbconfig['user'],
					'db_password' => $dbconfig['pass'],
					'db_database' => $dbconfig['database'],
					'db_table_prefix' => $dbconfig['prefix'],
					'db_charset' => $dbconfig['charset'],
				);
			}
		}
		if (!count($db_info->slave_db))
		{
			$db_info->slave_db = array($db_info->master_db);
		}
		$db_info->use_object_cache = $config['cache']['type'] ?: null;
		$db_info->ftp_info = new stdClass;
		$db_info->ftp_info->ftp_host = $config['ftp']['host'];
		$db_info->ftp_info->ftp_port = $config['ftp']['port'];
		$db_info->ftp_info->ftp_user = $config['ftp']['user'];
		$db_info->ftp_info->ftp_pasv = $config['ftp']['pasv'] ? 'Y' : 'N';
		$db_info->ftp_info->ftp_root_path = $config['ftp']['path'];
		$db_info->ftp_info->sftp = $config['ftp']['sftp'] ? 'Y' : 'N';
		$db_info->lang_type = $config['locale']['default_lang'];
		$db_info->time_zone = $config['locale']['internal_timezone'];
		$db_info->time_zone = sprintf('%s%02d%02d', $db_info->time_zone >= 0 ? '+' : '-', abs($db_info->time_zone) / 3600, (abs($db_info->time_zone) % 3600 / 60));
		$db_info->delay_session = $config['session']['delay'] ? 'Y' : 'N';
		$db_info->use_db_session = $config['session']['use_db'] ? 'Y' : 'N';
		$db_info->minify_scripts = $config['view']['minify_scripts'] ? 'Y' : 'N';
		$db_info->admin_ip_list = count($config['admin']['allow']) ? $config['admin']['allow'] : null;
		$db_info->use_sitelock = $config['lock']['locked'] ? 'Y' : 'N';
		$db_info->sitelock_title = $config['lock']['title'];
		$db_info->sitelock_message = $config['lock']['message'];
		$db_info->sitelock_whitelist = count($config['lock']['allow']) ? $config['lock']['allow'] : array('127.0.0.1');
		$db_info->embed_white_iframe = $config['mediafilter']['iframe'] ?: $config['embedfilter']['iframe'];
		$db_info->embed_white_object = $config['mediafilter']['object'] ?: $config['embedfilter']['object'];
		$db_info->use_mobile_view = (isset($config['mobile']['enabled']) ? $config['mobile']['enabled'] : $config['use_mobile_view']) ? 'Y' : 'N';
		$db_info->use_prepared_statements = $config['use_prepared_statements'] ? 'Y' : 'N';
		$db_info->use_rewrite = $config['use_rewrite'] ? 'Y' : 'N';
		$db_info->use_sso = $config['use_sso'] ? 'Y' : 'N';
		if (is_array($config['other']))
		{
			foreach ($config['other'] as $key => $value)
			{
				$db_info->{$key} = $value;
			}
		}
		return $db_info;
	}

	/**
	 * Get DB's db_type
	 *
	 * @return string DB's db_type
	 */
	public static function getDBType()
	{
		return self::$_instance->db_info->master_db["db_type"];
	}

	/**
	 * Set DB information
	 *
	 * @param object $db_info DB information
	 * @return void
	 */
	public static function setDBInfo($db_info)
	{
		self::$_instance->db_info = $db_info;
	}

	/**
	 * Get DB information
	 *
	 * @return object DB information
	 */
	public static function getDBInfo()
	{
		return self::$_instance->db_info;
	}

	/**
	 * Return ssl status
	 *
	 * @return object SSL status (Optional - none|always|optional)
	 */
	public static function getSslStatus()
	{
		return self::get('_use_ssl');
	}

	/**
	 * Return default URL
	 *
	 * @param object $site_module_info (optional)
	 * @return string Default URL
	 */
	public static function getDefaultUrl($site_module_info = null)
	{
		if ($site_module_info === null && ($default_url = self::get('_default_url')))
		{
			return $default_url;
		}
		
		if ($site_module_info === null)
		{
			$site_module_info = self::get('site_module_info');
		}
		
		$prefix = $site_module_info->security === 'always' ? 'https://' : 'http://';
		$hostname = $site_module_info->domain;
		$port = $site_module_info->security === 'always' ? $site_module_info->https_port : $site_module_info->http_port;
		$result = $prefix . $hostname . ($port ? sprintf(':%d', $port) : '') . RX_BASEURL;
		return $result;
	}

	/**
	 * Find supported languages
	 *
	 * @return array Supported languages
	 */
	public static function loadLangSupported()
	{
		$list = Rhymix\Framework\Lang::getSupportedList();
		return array_map(function($val) { return $val['name']; }, $list);
	}

	/**
	 * Find selected languages to serve in the site
	 *
	 * @return array Selected languages
	 */
	public static function loadLangSelected()
	{
		static $lang_selected = array();
		if(!count($lang_selected))
		{
			$supported = Rhymix\Framework\Lang::getSupportedList();
			$selected = Rhymix\Framework\Config::get('locale.enabled_lang');
			if ($selected)
			{
				foreach ($selected as $lang)
				{
					$lang_selected[$lang] = $supported[$lang]['name'];
				}
			}
			else
			{
				$lang_selected = array_map(function($val) { return $val['name']; }, $supported);
			}
		}
		return $lang_selected;
	}

	/**
	 * Single Sign On (SSO)
	 *
	 * @return bool True : Module handling is necessary in the control path of current request , False : Otherwise
	 */
	public static function checkSSO()
	{
		return true;
	}

	/**
	 * Check if FTP info is registered
	 *
	 * @return bool True: FTP information is registered, False: otherwise
	 */
	public static function isFTPRegisted()
	{
		$ftp_info = self::$_instance->db_info->ftp_info;
		return ($ftp_info->ftp_user && $ftp_info->ftp_root_path);
	}

	/**
	 * Get FTP information
	 *
	 * @return object FTP information
	 */
	public static function getFTPInfo()
	{
		$ftp_info = self::$_instance->db_info->ftp_info;
		if (!$ftp_info->ftp_user || !$ftp_info->ftp_root_path)
		{
			return null;
		}
		return $ftp_info;
	}

	/**
	 * Append string to browser title
	 *
	 * @param string $title Browser title to be appended
	 * @return void
	 */
	public static function addBrowserTitle($title)
	{
		if(!$title)
		{
			return;
		}
		if(self::$_instance->browser_title)
		{
			self::$_instance->browser_title .= ' - ' . $title;
		}
		else
		{
			self::$_instance->browser_title = $title;
		}
	}

	/**
	 * Prepend string to browser title
	 *
	 * @param string $title Browser title to be prepended
	 * @return void
	 */
	public static function prependBrowserTitle($title)
	{
		if(!$title)
		{
			return;
		}
		if(self::$_instance->browser_title)
		{
			self::$_instance->browser_title = $title . ' - ' . self::$_instance->browser_title;
		}
		else
		{
			self::$_instance->browser_title = $title;
		}
	}

	/**
	 * Set string to browser title
	 *
	 * @param string $title Browser title  to be set
	 * @param array $vars
	 * @return void
	 */
	public static function setBrowserTitle($title, $vars = array())
	{
		if (!$title)
		{
			return;
		}
		if (count($vars))
		{
			$title = trim(trim(preg_replace_callback('/\\$(\w+)/', function($matches) use($vars) {
				return isset($vars[strtolower($matches[1])]) ? $vars[strtolower($matches[1])] : $matches[0];
			}, $title), ' -'));
		}
		self::$_instance->browser_title = $title;
	}

	/**
	 * Get browser title
	 *
	 * @return string Browser title(htmlspecialchars applied)
	 */
	public static function getBrowserTitle()
	{
		if (!self::$_instance->browser_title)
		{
			return '';
		}
		getController('module')->replaceDefinedLangCode(self::$_instance->browser_title);
		return htmlspecialchars(self::$_instance->browser_title, ENT_QUOTES, 'UTF-8', FALSE);
	}

	/**
	 * Return site title
	 * 
	 * @return string
	 */
	public static function getSiteTitle()
	{
		$domain_info = self::get('site_module_info');
		if ($domain_info && $domain_info->settings && $domain_info->settings->title)
		{
			$title = trim($domain_info->settings->title);
			getController('module')->replaceDefinedLangCode($title);
			return $title;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Return site subtitle
	 * 
	 * @return string
	 */
	public static function getSiteSubtitle()
	{
		$domain_info = self::get('site_module_info');
		if ($domain_info && $domain_info->settings && $domain_info->settings->subtitle)
		{
			$subtitle = trim($domain_info->settings->subtitle);
			getController('module')->replaceDefinedLangCode($subtitle);
			return $subtitle;
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get browser title
	 * @deprecated
	 */
	public static function _getBrowserTitle()
	{
		return self::getBrowserTitle();
	}

	/**
	 * Load language file according to language type
	 *
	 * @param string $path Path of the language file
	 * @return void
	 */
	public static function loadLang($path)
	{
		if (preg_match('@/(modules|addons|plugins|widgets)/([a-zA-Z0-9_-]+)/lang/?(?:lang\.xml)?$@', str_replace('\\', '/', $path), $matches))
		{
			$path = \RX_BASEDIR . $matches[1] . '/' . $matches[2] . '/lang';
			$plugin_name = $matches[2];
		}
		else
		{
			$plugin_name = null;
		}
		
		if (!$GLOBALS['lang'] instanceof Rhymix\Framework\Lang)
		{
			$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance(self::$_instance->lang_type ?: config('locale.default_lang') ?: 'ko');
			$GLOBALS['lang']->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		}
		
		return $GLOBALS['lang']->loadDirectory($path, $plugin_name);
	}

	/**
	 * Set lang_type
	 *
	 * @param string $lang_type Language type.
	 * @return void
	 */
	public static function setLangType($lang_type = 'ko')
	{
		if (!self::$_instance->db_info)
		{
			self::$_instance->db_info = new stdClass;
		}
		
		self::$_instance->db_info->lang_type = $lang_type;
		self::$_instance->lang_type = $lang_type;
		self::set('lang_type', $lang_type);

		if(self::getSessionStatus())
		{
			$_SESSION['lang_type'] = $lang_type;
		}
	}

	/**
	 * Get lang_type
	 *
	 * @return string Language type
	 */
	public static function getLangType()
	{
		return self::$_instance->lang_type;
	}

	/**
	 * Return string accoring to the inputed code
	 *
	 * @param string $code Language variable name
	 * @return string If string for the code exists returns it, otherwise returns original code
	 */
	public static function getLang($code)
	{
		if (!$GLOBALS['lang'] instanceof Rhymix\Framework\Lang)
		{
			$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance(self::$_instance->lang_type ?: config('locale.default_lang') ?: 'ko');
			$GLOBALS['lang']->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		}
		
		return $GLOBALS['lang']->get($code);
	}

	/**
	 * Set data to lang variable
	 *
	 * @param string $code Language variable name
	 * @param string $val `$code`s value
	 * @return void
	 */
	public static function setLang($code, $val)
	{
		if (!$GLOBALS['lang'] instanceof Rhymix\Framework\Lang)
		{
			$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance(self::$_instance->lang_type ?: config('locale.default_lang') ?: 'ko');
			$GLOBALS['lang']->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		}
		
		$GLOBALS['lang']->set($code, $val);
	}

	/**
	 * Convert strings of variables in $source_object into UTF-8
	 *
	 * @param object $source_obj Conatins strings to convert
	 * @return object converted object
	 */
	public static function convertEncoding($source_obj)
	{
		$charset_list = array(
			'UTF-8', 'EUC-KR', 'CP949', 'ISO8859-1', 'EUC-JP', 'SHIFT_JIS',
			'CP932', 'EUC-CN', 'HZ', 'GBK', 'GB18030', 'EUC-TW', 'BIG5',
			'CP950', 'BIG5-HKSCS', 'ISO8859-6', 'ISO8859-8', 'JOHAB', 'CP1255',
			'CP1256', 'CP862', 'ASCII', 'ISO8859-1', 'CP1250', 'CP1251',
			'CP1252', 'CP1253', 'CP1254', 'CP1257', 'CP850', 'CP866'
		);

		$obj = clone $source_obj;

		foreach($charset_list as $charset)
		{
			array_walk($obj,'Context::checkConvertFlag',$charset);
			$flag = self::checkConvertFlag($flag = TRUE);
			if($flag)
			{
				if($charset == 'UTF-8')
				{
					return $obj;
				}
				array_walk($obj,'Context::doConvertEncoding',$charset);
				return $obj;
			}
		}
		return $obj;
	}

	/**
	 * Check flag
	 *
	 * @param mixed $val
	 * @param string $key
	 * @param mixed $charset charset
	 * @see arrayConvWalkCallback will replaced array_walk_recursive in >=PHP5
	 * @return void
	 */
	public static function checkConvertFlag(&$val, $key = null, $charset = null)
	{
		static $flag = TRUE;
		if($charset)
		{
			if(is_array($val))
				array_walk($val,'Context::checkConvertFlag',$charset);
			else if($val && iconv($charset,$charset,$val)!=$val) $flag = FALSE;
			else $flag = FALSE;
		}
		else
		{
			$return = $flag;
			$flag = TRUE;
			return $return;
		}
	}

	/**
	 * Convert array type variables into UTF-8
	 *
	 * @param mixed $val
	 * @param string $key
	 * @param string $charset character set
	 * @see arrayConvWalkCallback will replaced array_walk_recursive in >=PHP5
	 * @return object converted object
	 */
	public static function doConvertEncoding(&$val, $key = null, $charset)
	{
		if (is_array($val))
		{
			array_walk($val,'Context::doConvertEncoding',$charset);
		}
		else $val = iconv($charset,'UTF-8',$val);
	}

	/**
	 * Convert strings into UTF-8
	 *
	 * @param string $str String to convert
	 * @return string converted string
	 */
	public static function convertEncodingStr($str)
	{
        if (!$str || utf8_check($str))
        {
        	return $str;
        }
        
		$obj = new stdClass;
		$obj->str = $str;
		$obj = self::convertEncoding($obj);
		return $obj->str;
	}

	/**
	 * Encode UTF-8 domain into IDNA (punycode)
	 * 
	 * @param string $domain Domain to convert
	 * @return string Converted string
	 */
	public static function encodeIdna($domain)
	{
		return Rhymix\Framework\URL::encodeIdna($domain);
	}

	/**
	 * Convert IDNA (punycode) domain into UTF-8
	 * 
	 * @param string $domain Domain to convert
	 * @return string Converted string
	 */
	public static function decodeIdna($domain)
	{
		return Rhymix\Framework\URL::decodeIdna($domain);
	}

	/**
	 * Check the hostname for invalid characters.
	 *
	 * @return void
	 */
	private static function _checkGlobalVars()
	{
		if (!self::_recursiveCheckVar($_SERVER['HTTP_HOST']) || preg_match("/[\,\"\'\{\}\[\]\(\);$]/", $_SERVER['HTTP_HOST']))
		{
			self::$_instance->security_check = 'DENY ALL';
		}
	}

	/**
	 * Force to set response method
	 *
	 * @param string $method Response method. [HTML|XMLRPC|JSON]
	 * @return void
	 */
	public static function setResponseMethod($method = 'HTML')
	{
		$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);
		self::$_instance->response_method = isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Get reponse method
	 *
	 * @return string Response method. If it's not set, returns request method.
	 */
	public static function getResponseMethod()
	{
		if(self::$_instance->response_method)
		{
			return self::$_instance->response_method;
		}

		$method = self::getRequestMethod();
		$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);

		return isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Return request method
	 * @return string Request method type. (Optional - GET|POST|XMLRPC|JSON)
	 */
	public static function getRequestMethod()
	{
		return self::$_instance->request_method;
	}

	/**
	 * Determine request method
	 *
	 * @param string $type Request method. (Optional - GET|POST|XMLRPC|JSON)
	 * @return void
	 */
	public static function setRequestMethod($type = '')
	{
		if ($type)
		{
			self::$_instance->request_method = $type;
			return;
		}
		elseif (self::$_instance->js_callback_func = self::getJSCallbackFunc())
		{
			self::$_instance->request_method = 'JS_CALLBACK';
		}
		else
		{
			self::$_instance->request_method = $_SERVER['REQUEST_METHOD'];
		}
		
		// Check POST data
		if(self::$_instance->request_method === 'POST')
		{
			// Set HTTP_RAW_POST_DATA for compatibility with XE third-party programs.
			if(!isset($GLOBALS['HTTP_RAW_POST_DATA']))
			{
				$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
			}
			
			// Pretend that this request is XMLRPC for compatibility with XE third-party.
			if(isset($_POST['_rx_ajax_compat']) && $_POST['_rx_ajax_compat'] === 'XMLRPC')
			{
				self::$_instance->request_method = 'XMLRPC';
				return;
			}
			
			// Check JSON
			foreach(array($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_CONTENT_TYPE'], $_SERVER['CONTENT_TYPE']) as $header)
			{
				if(strpos($header, 'json') !== false)
				{
					self::$_instance->request_method = 'JSON';
					return;
				}
			}
			
			// Check XMLRPC
			if(!$_POST && !empty($GLOBALS['HTTP_RAW_POST_DATA']))
			{
				self::$_instance->request_method = 'XMLRPC';
				return;
			}
		}
	}

	/**
	 * handle request arguments for GET/POST
	 *
	 * @return void
	 */
	public static function setRequestArguments()
	{
		foreach($_REQUEST as $key => $val)
		{
			if($val === '' || isset(self::$_reserved_keys[$key]) || self::get($key))
			{
				continue;
			}
			
			$key = escape($key);
			$val = self::_filterRequestVar($key, $val);
			$set_to_vars = false;
			
			if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$key]))
			{
				$set_to_vars = true;
			}
			elseif($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key]))
			{
				$set_to_vars = true;
			}
			
			self::set($key, $val, $set_to_vars);
		}
		
		// Set deprecated request parameters.
		if(!$_POST && !empty($GLOBALS['HTTP_RAW_POST_DATA']))
		{
			if(self::getRequestMethod() === 'XMLRPC')
			{
				if(!Rhymix\Framework\Security::checkXXE($GLOBALS['HTTP_RAW_POST_DATA']))
				{
					header("HTTP/1.0 400 Bad Request");
					exit;
				}
				if(function_exists('libxml_disable_entity_loader'))
				{
					libxml_disable_entity_loader(true);
				}
				
				$oXml = new XmlParser();
				$params = $oXml->parse($GLOBALS['HTTP_RAW_POST_DATA'])->methodcall->params;
				unset($params->node_name, $params->attrs, $params->body);
				
				foreach((array)$params as $key => $val)
				{
					$key = escape($key);
					$val = self::_filterXmlVars($key, $val);
					self::set($key, $val, true);
				}
			}
			elseif(self::getRequestMethod() === 'JSON')
			{
				$params = array();
				parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $params);	
				foreach($params as $key => $val)
				{
					$key = escape($key);
					$val = self::_filterRequestVar($key, $val);
					self::set($key, $val, true);
				}
			}
		}
	}
	
	/**
	 * Handle uploaded file info.
	 * 
	 * @return void
	 */
	private static function setUploadInfo()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$_FILES)
		{
			return;
		}
		if (stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data') === false)
		{
			return;
		}

		foreach ($_FILES as $key => $val)
		{
			$tmp_name = $val['tmp_name'];
			if(!is_array($tmp_name))
			{
				if($val['name'] === '' && $val['size'] == 0)
				{
					continue;
				}
				if(!UploadFileFilter::check($tmp_name, $val['name']))
				{
					self::$_instance->security_check = 'DENY ALL';
					unset($_FILES[$key]);
					continue;
				}
				$val['name'] = escape($val['name'], false);
				self::set($key, $val, true);
				self::set('is_uploaded', true);
				self::$_instance->is_uploaded = true;
			}
			else
			{
				$files = array();
				foreach ($tmp_name as $i => $j)
				{
					if($val['name'][$i] === '' && $val['size'][$i] == 0)
					{
						continue;
					}
					if(!UploadFileFilter::check($val['tmp_name'][$i], $val['name'][$i]))
					{
						self::$_instance->security_check = 'DENY ALL';
						$files = array();
						unset($_FILES[$key]);
						break;
					}
					$file = array();
					$file['name'] = $val['name'][$i];
					$file['type'] = $val['type'][$i];
					$file['tmp_name'] = $val['tmp_name'][$i];
					$file['error'] = $val['error'][$i];
					$file['size'] = $val['size'][$i];
					$files[] = $file;
				}
				if(count($files))
				{
					self::set($key, $files, true);
				}
			}
		}
	}

	/**
	 * Check if a value (or array of values) matches a pattern defined in this class.
	 * 
	 * @param mixed $val Values to check
	 * @return bool
	 */
	private static function _recursiveCheckVar($val)
	{
		if(is_string($val))
		{
			foreach(self::$_check_patterns as $pattern => $status)
			{
				if(preg_match($pattern, $val))
				{
					self::$_instance->security_check = $status;
					if($status === 'DENY ALL')
					{
						return false;
					}
				}
			}
		}
		else if(is_array($val))
		{
			foreach($val as $val2)
			{
				$result = self::_recursiveCheckVar($val2);
				if(!$result)
				{
					return false;
				}
			}
		}
		
		return true;
	}

	/**
	 * Filter xml variables
	 *
	 * @param string $key Variable key
	 * @param object $val Variable value
	 * @return mixed filtered value
	 */
	private static function _filterXmlVars($key, $val)
	{
		$result = array();
		if(!$is_array = is_array($val))
		{
			$val = array($val);
		}
		foreach($val as $_key => $_val)
		{
			unset($_val->node_name, $_val->attrs);
			
			$args = new stdClass;
			foreach((array)$_val as $name => $node)
			{
				if(isset($node->attrs->type) && $node->attrs->type == 'array')
				{
					$node = array($node);
				}
				
				if($name == 'body' && count((array)$_val) === 1)
				{
					$_val = self::_filterRequestVar($key, $node);
					break;
				}
				elseif($name == 'value' && is_array($node))
				{
					$_val = self::_filterXmlVars($name, $node);
					break;
				}
				else
				{
					$args->$name = self::_filterXmlVars($name, $node);
				}
			}
			$result[escape($_key)] = !empty((array)$args) ? $args : $_val;
		}
		
		return $is_array ? $result : $result[0];
	}

	/**
	 * Filter request variable
	 *
	 * @see Cast variables, such as _srl, page, and cpage, into interger
	 * @param string $key Variable key
	 * @param string $val Variable value
	 * @return mixed filtered value. Type are string or array
	 */
	private static function _filterRequestVar($key, $val)
	{
		if(starts_with('XE_VALIDATOR_', $key, false) && $key !== 'xe_validator_id')
		{
			return;
		}
		
		$result = array();
		if(!$is_array = is_array($val))
		{
			$val = array($val);
		}
		foreach($val as $_key => $_val)
		{
			if(is_array($_val))
			{
				$_val = self::_filterRequestVar($key, $_val);
			}
			elseif($_val = trim($_val))
			{
				if(in_array($key, array('page', 'cpage')) || ends_with('srl', $key, false) && preg_match('/[^0-9,]/', $_val))
				{
					$_val = (int)$_val;
				}
				elseif(in_array($key, array('mid', 'vid', 'search_target', 'search_keyword', 'xe_validator_id')) || $_SERVER['REQUEST_METHOD'] === 'GET')
				{
					$_val = escape($_val, false);
					if(ends_with('url', $key, false))
					{
						$_val = strtr($_val, array('&amp;' => '&'));
					}
				}
			}
			$result[escape($_key)] = $_val;
			self::_recursiveCheckVar($_val);
		}
		
		return $is_array ? $result : $result[0];
	}

	/**
	 * Check if there exists uploaded file
	 *
	 * @return bool True: exists, False: otherwise
	 */
	public static function isUploaded()
	{
		return self::$_instance->is_uploaded;
	}

	/**
	 * Handle uploaded file
	 *
	 * @return void
	 */
	public static function _setUploadedArgument()
	{
		self::setUploadInfo();
	}

	/**
	 * Enforce site lock.
	 */
	private static function enforceSiteLock()
	{
		// Allow if the current user is logged in as administrator, or trying to log in.
		$logged_info = self::get('logged_info');
		if ($logged_info && $logged_info->is_admin === 'Y')
		{
			return;
		}
		elseif (in_array(self::get('act'), array('procMemberLogin', 'dispMemberLogout')))
		{
			return;
		}
		
		// Allow if the current user is in the list of allowed IPs.
		if (PHP_SAPI === 'cli')
		{
			return;
		}
		if (Rhymix\Framework\Filters\IpFilter::inRanges(RX_CLIENT_IP, config('lock.allow')))
		{
			return;
		}
		
		// Set headers and constants for backward compatibility.
		header('HTTP/1.1 503 Service Unavailable');
		define('_XE_SITELOCK_', TRUE);
		define('_XE_SITELOCK_TITLE_', config('lock.title') ?: self::getLang('admin.sitelock_in_use'));
		define('_XE_SITELOCK_MESSAGE_', config('lock.message'));
		unset($_SESSION['XE_VALIDATOR_RETURN_URL']);
		self::$_instance->is_site_locked = true;
		
		// Load the sitelock template.
		if(FileHandler::exists(RX_BASEDIR . 'common/tpl/sitelock.user.html'))
		{
			include RX_BASEDIR . 'common/tpl/sitelock.user.html';
		}
		else
		{
			self::displayErrorPage(_XE_SITELOCK_TITLE_, _XE_SITELOCK_MESSAGE_, 503);
		}
		exit;
	}
	
	/**
	 * Display a generic error page and exit.
	 * 
	 * @param string $title
	 * @param string $message
	 * @return void
	 */
	public static function displayErrorPage($title = 'Error', $message = '', $status = 500)
	{
		// Change current directory to the Rhymix installation path.
		chdir(\RX_BASEDIR);
		
		// Set the title.
		self::setBrowserTitle(self::getSiteTitle());
		self::addBrowserTitle($title);
		
		// Set the message.
		$oMessageObject = getView('message');
		$oMessageObject->setError(-1);
		if ($status != 200)
		{
			$oMessageObject->setHttpStatusCode($status);
		}
		
		if (in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON', 'JS_CALLBACK')))
		{
			$oMessageObject->setMessage(trim($title . "\n\n" . $message));
		}
		else
		{
			$oMessageObject->setMessage($title);
			$oMessageObject->dispMessage($message);
		}
		
		// Display the message.
		$oModuleHandler = new ModuleHandler;
		$oModuleHandler->displayContent($oMessageObject);
	}

	/**
	 * Return request URL
	 * @return string request URL
	 */
	public static function getRequestUrl()
	{
		static $url = null;
		if(is_null($url))
		{
			$url = self::getRequestUri() . RX_REQUEST_URL;
		}
		return $url;
	}

	/**
	 * Return js callback func.
	 * @return string callback func.
	 */
	public static function getJSCallbackFunc()
	{
		$js_callback_func = isset($_GET['xe_js_callback']) ? $_GET['xe_js_callback'] : $_POST['xe_js_callback'];

		if(!preg_match('/^[a-z0-9\.]+$/i', $js_callback_func))
		{
			unset($js_callback_func);
			unset($_GET['xe_js_callback']);
			unset($_POST['xe_js_callback']);
		}

		return $js_callback_func;
	}

	/**
	 * Make URL with args_list upon request URL
	 *
	 * @param int $num_args Arguments nums
	 * @param array $args_list Argument list for set url
	 * @param string $domain Domain
	 * @param bool $encode If TRUE, use url encode.
	 * @param bool $autoEncode If TRUE, url encode automatically, detailed. Use this option, $encode value should be TRUE
	 * @return string URL
	 */
	public static function getUrl($num_args = 0, $args_list = array(), $domain = null, $encode = TRUE, $autoEncode = FALSE)
	{
		static $current_domain = null;
		static $site_module_info = null;
		if ($site_module_info === null)
		{
			$site_module_info = self::get('site_module_info');
		}
		if ($current_domain === null)
		{
			$current_domain = parse_url(Rhymix\Framework\URL::getCurrentDomainURL(), PHP_URL_HOST);
		}

		// Find the canonical form of the domain.
		if ($domain)
		{
			if (strpos($domain, '/') !== false)
			{
				$domain = Rhymix\Framework\URL::getDomainFromURL($domain);
			}
			if (strpos($domain, 'xn--') !== false)
			{
				$domain = Rhymix\Framework\URL::decodeIdna($domain);
			}
		}
		else
		{
			$domain = $site_module_info->domain;
		}

		// If the domain is the same as the current domain, do not use it.
		if ($domain && $domain === $current_domain)
		{
			$domain = null;
		}

		// Get URL parameters. If the first argument is '', reset existing parameters.
		if (!self::$_get_vars || strval($args_list[0]) === '')
		{
			$get_vars = array();
			if(is_array($args_list) && strval($args_list[0]) === '')
			{
				array_shift($args_list);
			}
		}
		elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			$get_vars = get_object_vars(self::$_get_vars);
		}
		else
		{
			$preserve_vars = array('module', 'mid', 'act', 'page', 'document_srl', 'search_target', 'search_keyword');
			$preserve_keys = array_combine($preserve_vars, array_fill(0, count($preserve_vars), true));
			$get_vars = array_intersect_key(get_object_vars(self::$_get_vars), $preserve_keys);
		}
		
		// arrange args_list
		for($i = 0, $c = count($args_list); $i < $c; $i += 2)
		{
			$key = $args_list[$i];
			$val = trim($args_list[$i + 1]);
			// If value is not set, remove the key
			if(!isset($val) || !strlen($val))
			{
				unset($get_vars[$key]);
				continue;
			}
			// set new variables
			$get_vars[$key] = $val;
		}
		
		// remove vid, rnd
		unset($get_vars['rnd']);
		unset($get_vars['vid']);
		
		// for compatibility to lower versions
		$act = $get_vars['act'];
		$act_alias = array(
			'dispMemberFriend' => 'dispCommunicationFriend',
			'dispMemberMessages' => 'dispCommunicationMessages',
			'dispDocumentAdminManageDocument' => 'dispDocumentManageDocument',
			'dispModuleAdminSelectList' => 'dispModuleSelectList'
		);
		if(isset($act_alias[$act]))
		{
			$get_vars['act'] = $act_alias[$act];
		}
		
		// organize URL
		$query = '';
		if(count($get_vars) > 0)
		{
			// if using rewrite mod
			if(self::$_instance->allow_rewrite)
			{
				$var_keys = array_keys($get_vars);
				sort($var_keys);
				$target = join('.', $var_keys);
				$act = $get_vars['act'];
				$mid = $get_vars['mid'];
				$key = $get_vars['key'];
				$srl = $get_vars['document_srl'];
				$tmpArray = array('rss' => 1, 'atom' => 1, 'api' => 1);
				$is_feed = isset($tmpArray[$act]);
				$target_map = array(
					'mid' => $mid,
					'category.mid' => "$mid/category/" . $get_vars['category'],
					'entry.mid' => "$mid/entry/" . $get_vars['entry'],
					'document_srl' => $srl,
					'document_srl.mid' => "$mid/$srl",
					'act' => ($is_feed && $act !== 'api') ? $act : '',
					'act.mid' => $is_feed ? "$mid/$act" : '',
					'act.document_srl.key' => ($act == 'trackback') ? "$srl/$key/$act" : '',
					'act.document_srl.key.mid' => ($act == 'trackback') ? "$mid/$srl/$key/$act" : '',
				);
				$query = $target_map[$target];
			}
			if(!$query && count($get_vars) > 0)
			{
				$query = 'index.php?' . http_build_query($get_vars);
			}
		}
		
		// If using SSL always
		if($site_module_info->security == 'always')
		{
			if(!$domain && RX_SSL)
			{
				$query = RX_BASEURL . $query;
			}
			else
			{
				$query = self::getRequestUri(ENFORCE_SSL, $domain) . $query;
			}
		}
		// optional SSL use
		elseif($site_module_info->security == 'optional')
		{
			$ssl_mode = ((self::get('module') === 'admin') || ($get_vars['module'] === 'admin') || (isset($get_vars['act']) && self::isExistsSSLAction($get_vars['act']))) ? ENFORCE_SSL : RELEASE_SSL;
			if(!$domain && (RX_SSL && ENFORCE_SSL) || (!RX_SSL && RELEASE_SSL))
			{
				$query = RX_BASEURL . $query;
			}
			else
			{
				$query = self::getRequestUri($ssl_mode, $domain) . $query;
			}
		}
		// no SSL
		else
		{
			// currently on SSL but target is not based on SSL
			if(!$domain && RX_SSL)
			{
				$query = RX_BASEURL . $query;
			}
			elseif(RX_SSL)
			{
				$query = self::getRequestUri(ENFORCE_SSL, $domain) . $query;
			}
			elseif($domain)
			{
				$query = self::getRequestUri(FOLLOW_REQUEST_SSL, $domain) . $query;
			}
			else
			{
				$query = RX_BASEURL . $query;
			}
		}

		if(!$encode)
		{
			return $query;
		}

		if(!$autoEncode)
		{
			return htmlspecialchars($query, ENT_QUOTES, 'UTF-8', FALSE);
		}

		$output = array();
		$encode_queries = array();
		$parsedUrl = parse_url($query);
		parse_str($parsedUrl['query'], $output);
		foreach($output as $key => $value)
		{
			if(preg_match('/&([a-z]{2,}|#\d+);/', urldecode($value)))
			{
				$value = urlencode(htmlspecialchars_decode(urldecode($value)));
			}
			$encode_queries[] = $key . '=' . $value;
		}

		return htmlspecialchars($parsedUrl['path'] . '?' . join('&', $encode_queries), ENT_QUOTES, 'UTF-8', FALSE);
	}

	/**
	 * Return after removing an argument on the requested URL
	 *
	 * @param string $ssl_mode SSL mode
	 * @param string $domain Domain
	 * @retrun string converted URL
	 */
	public static function getRequestUri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null)
	{
		static $domain_infos = array();

		// Check HTTP Request
		if(!isset($_SERVER['SERVER_PROTOCOL']))
		{
			return;
		}

		$site_module_info = self::get('site_module_info');
		if ($site_module_info->security === 'always')
		{
			$ssl_mode = ENFORCE_SSL;
		}

		switch($ssl_mode)
		{
			case FOLLOW_REQUEST_SSL: $use_ssl = RX_SSL;
				break;
			case ENFORCE_SSL: $use_ssl = TRUE;
				break;
			case RELEASE_SSL: $use_ssl = FALSE;
				break;
		}

		if ($domain !== null && $domain !== false && $domain !== $site_module_info->domain)
		{
			if (!isset($domain_infos[$domain]))
			{
				$domain_infos[$domain] = getModel('module')->getSiteInfoByDomain($domain);
			}
			$site_module_info = $domain_infos[$domain] ?: $site_module_info;
		}
		
		$prefix = ($use_ssl && $site_module_info->security !== 'none') ? 'https://' : 'http://';
		$hostname = $site_module_info->domain;
		$port = ($use_ssl && $site_module_info->security !== 'none') ? $site_module_info->https_port : $site_module_info->http_port;
		$result = $prefix . $hostname . ($port ? sprintf(':%d', $port) : '') . RX_BASEURL;
		return $result;
	}

	/**
	 * Set a context value with a key
	 *
	 * @param string $key Key
	 * @param mixed $val Value
	 * @param mixed $set_to_get_vars If not FALSE, Set to get vars.
	 * @return void
	 */
	public static function set($key, $val, $set_to_get_vars = false)
	{
		if(empty($key))
		{
			trigger_error('Called Context::set() with an empty key', \E_USER_WARNING);
			return;
		}
		
		self::$_tpl_vars->{$key} = $val;

		if($set_to_get_vars || isset(self::$_get_vars->{$key}))
		{
			if($val === NULL || $val === '')
			{
				unset(self::$_get_vars->{$key});
			}
			else
			{
				self::$_get_vars->{$key} = $val;
			}
		}
	}

	/**
	 * Return key's value
	 *
	 * @param string $key Key
	 * @return mixed
	 */
	public static function get($key)
	{
		if(empty($key))
		{
			trigger_error('Called Context::get() with an empty key', \E_USER_WARNING);
			return;
		}
		
		if(isset(self::$_tpl_vars->{$key}))
		{
			return self::$_tpl_vars->{$key};
		}
		elseif(isset(self::$_instance->{$key}))
		{
			return self::$_instance->{$key};
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get one more vars in object vars with given arguments(key1, key2, key3,...)
	 *
	 * @return object
	 */
	public static function gets()
	{
		$num_args = func_num_args();
		if($num_args < 1)
		{
			return;
		}

		$args_list = func_get_args();
		$output = new stdClass;
		self::$_tpl_vars = self::$_tpl_vars !== null ? self::$_tpl_vars : new stdClass;
		foreach($args_list as $key)
		{
			$output->{$key} = isset(self::$_tpl_vars->{$key}) ? self::$_tpl_vars->{$key} : (isset(self::$_instance->{$key}) ? self::$_instance->{$key} : null);
		}
		return $output;
	}

	/**
	 * Return all data
	 *
	 * @return object All data
	 */
	public static function getAll()
	{
		return self::$_tpl_vars !== null ? self::$_tpl_vars : new stdClass;
	}

	/**
	 * Return values from the GET/POST/XMLRPC
	 *
	 * @return object Request variables.
	 */
	public static function getRequestVars()
	{
		if(self::$_get_vars)
		{
			return clone(self::$_get_vars);
		}
		return new stdClass;
	}

	/**
	 * Clear all values from GET/POST/XMLRPC
	 * 
	 * @return void
	 */
	public static function clearRequestVars()
	{
		self::$_get_vars = new stdClass;
	}

	/**
	 * Clear all user-set values
	 * 
	 * @return void
	 */
	public static function clearUserVars()
	{
		self::$_tpl_vars = new stdClass;
	}

	/**
	 * Register if an action is to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function addSSLAction($action)
	{
		if(isset(self::$_ssl_actions[$action]))
		{
			return;
		}
		
		self::$_ssl_actions[$action] = 1;
		$buff = '<?php return ' . var_export(self::$_ssl_actions, true) . ';';
		Rhymix\Framework\Storage::write(self::$_ssl_actions_cache_file, $buff);
	}

	/**
	 * Register if actions are to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function addSSLActions($action_array)
	{
		$changed = false;
		foreach($action_array as $action)
		{
			if(!isset(self::$_ssl_actions[$action]))
			{
				self::$_ssl_actions[$action] = 1;
				$changed = true;
			}
		}
		if(!$changed)
		{
			return;
		}
		
		$buff = '<?php return ' . var_export(self::$_ssl_actions, true) . ';';
		Rhymix\Framework\Storage::write(self::$_ssl_actions_cache_file, $buff);
	}

	/**
	 * Delete if action is registerd to be encrypted by SSL.
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function subtractSSLAction($action)
	{
		if(!isset(self::$_ssl_actions[$action]))
		{
			return;
		}
		
		unset(self::$_ssl_actions[$action]);
		$buff = '<?php return ' . var_export(self::$_ssl_actions, true) . ';';
		Rhymix\Framework\Storage::write(self::$_ssl_actions_cache_file, $buff);
	}

	/**
	 * Get SSL Action
	 *
	 * @return string acts in array
	 */
	public static function getSSLActions()
	{
		if(self::getSslStatus() == 'optional')
		{
			return self::$_ssl_actions;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Check SSL action are existed
	 *
	 * @param string $action act name
	 * @return bool If SSL exists, return TRUE.
	 */
	public static function isExistsSSLAction($action)
	{
		return isset(self::$_ssl_actions[$action]);
	}

	/**
	 * Normalize file path
	 *
	 * @deprecated
	 * @param string $file file path
	 * @return string normalized file path
	 */
	public static function normalizeFilePath($file)
	{
		if($file{0} != '/' && $file{0} != '.' && strpos($file, '://') === FALSE)
		{
			$file = './' . $file;
		}
		$file = preg_replace('@/\./|(?<!:)\/\/@', '/', $file);
		while(strpos($file, '/../') !== FALSE)
		{
			$file = preg_replace('/\/([^\/]+)\/\.\.\//s', '/', $file, 1);
		}

		return $file;
	}

	/**
	 * Get abstract file url
	 *
	 * @deprecated
	 * @param string $file file path
	 * @return string Converted file path
	 */
	public static function getAbsFileUrl($file)
	{
		$file = self::normalizeFilePath($file);
		if(strpos($file, './') === 0)
		{
			$file = dirname($_SERVER['SCRIPT_NAME']) . '/' . substr($file, 2);
		}
		elseif(strpos($file, '../') === 0)
		{
			$file = self::normalizeFilePath(dirname($_SERVER['SCRIPT_NAME']) . "/{$file}");
		}

		return $file;
	}

	/**
	 * Load front end file
	 *
	 * @param array $args array
	 * case js :
	 * 		$args[0]: file name,
	 * 		$args[1]: type (head | body),
	 * 		$args[2]: target IE,
	 * 		$args[3]: index
	 * case css :
	 * 		$args[0]: file name,
	 * 		$args[1]: media,
	 * 		$args[2]: target IE,
	 * 		$args[3]: index
	 *
	 */
	public static function loadFile($args)
	{
		self::$_oFrontEndFileHandler->loadFile($args);
	}

	/**
	 * Unload front end file
	 *
	 * @param string $file File name with path
	 * @param string $targetIe Target IE
	 * @param string $media Media query
	 * @return void
	 */
	public static function unloadFile($file, $targetIe = '', $media = 'all')
	{
		self::$_oFrontEndFileHandler->unloadFile($file, $targetIe, $media);
	}

	/**
	 * Unload front end file all
	 *
	 * @param string $type Unload target (optional - all|css|js)
	 * @return void
	 */
	public static function unloadAllFiles($type = 'all')
	{
		self::$_oFrontEndFileHandler->unloadAllFiles($type);
	}

	/**
	 * Add the js file
	 *
	 * @deprecated
	 * @param string $file File name with path
	 * @param string $optimized optimized (That seems to not use)
	 * @param string $targetie target IE
	 * @param string $index index
	 * @param string $type Added position. (head:<head>..</head>, body:<body>..</body>)
	 * @param bool $isRuleset Use ruleset
	 * @param string $autoPath If path not readed, set the path automatically.
	 * @return void
	 */
	public static function addJsFile($file, $optimized = FALSE, $targetie = '', $index = 0, $type = 'head', $isRuleset = FALSE, $autoPath = null)
	{
		if($isRuleset)
		{
			if(strpos($file, '#') !== FALSE)
			{
				$file = str_replace('#', '', $file);
				if(!is_readable($file))
				{
					$file = $autoPath;
				}
			}
			$validator = new Validator($file);
			$validator->setCacheDir('files/cache');
			$file = $validator->getJsPath();
		}

		self::$_oFrontEndFileHandler->loadFile(array($file, $type, $targetie, $index));
	}

	/**
	 * Remove the js file
	 *
	 * @deprecated
	 * @param string $file File name with path
	 * @param string $optimized optimized (That seems to not use)
	 * @param string $targetie target IE
	 * @return void
	 */
	public static function unloadJsFile($file, $optimized = FALSE, $targetie = '')
	{
		self::$_oFrontEndFileHandler->unloadFile($file, $targetie);
	}

	/**
	 * Unload all javascript files
	 *
	 * @return void
	 */
	public static function unloadAllJsFiles()
	{
		self::$_oFrontEndFileHandler->unloadAllFiles('js');
	}

	/**
	 * Add javascript filter
	 *
	 * @param string $path File path
	 * @param string $filename File name
	 * @return void
	 */
	public static function addJsFilter($path, $filename)
	{
		$oXmlFilter = new XmlJSFilter($path, $filename);
		$oXmlFilter->compile();
	}

	/**
	 * Same as array_unique but works only for file subscript
	 *
	 * @deprecated
	 * @param array $files File list
	 * @return array File list
	 */
	public static function _getUniqueFileList($files)
	{
		ksort($files);
		$files = array_values($files);
		$filenames = array();
		for($i = 0, $c = count($files); $i < $c; ++$i)
		{
			if(in_array($files[$i]['file'], $filenames))
			{
				unset($files[$i]);
			}
			$filenames[] = $files[$i]['file'];
		}

		return $files;
	}

	/**
	 * Returns the list of javascripts that matches the given type.
	 *
	 * @param string $type Added position. (head:<head>..</head>, body:<body>..</body>)
	 * @param bool $finalize (optional)
	 * @return array Returns javascript file list. Array contains file, targetie.
	 */
	public static function getJsFile($type = 'head', $finalize = false)
	{
		return self::$_oFrontEndFileHandler->getJsFileList($type, $finalize);
	}

	/**
	 * Add CSS file
	 *
	 * @deprecated
	 * @param string $file File name with path
	 * @param string $optimized optimized (That seems to not use)
	 * @param string $media Media query
	 * @param string $targetie target IE
	 * @param string $index index
	 * @return void
	 *
	 */
	public static function addCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '', $index = 0)
	{
		self::$_oFrontEndFileHandler->loadFile(array($file, $media, $targetie, $index));
	}

	/**
	 * Remove css file
	 *
	 * @deprecated
	 * @param string $file File name with path
	 * @param string $optimized optimized (That seems to not use)
	 * @param string $media Media query
	 * @param string $targetie target IE
	 * @return void
	 */
	public static function unloadCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '')
	{
		self::$_oFrontEndFileHandler->unloadFile($file, $targetie, $media);
	}

	/**
	 * Unload all css files
	 *
	 * @return void
	 */
	public static function unloadAllCSSFiles()
	{
		self::$_oFrontEndFileHandler->unloadAllFiles('css');
	}

	/**
	 * Return a list of css files
	 *
	 * @param bool $finalize (optional)
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	public static function getCSSFile($finalize = false)
	{
		return self::$_oFrontEndFileHandler->getCssFileList($finalize);
	}

	/**
	 * Returns javascript plugin file info
	 * @param string $pluginName
	 * @return stdClass
	 */
	public static function getJavascriptPluginInfo($pluginName)
	{
		if($plugin_name == 'ui.datepicker')
		{
			$plugin_name = 'ui';
		}

		$plugin_path = './common/js/plugins/' . $pluginName . '/';
		$info_file = $plugin_path . 'plugin.load';
		if(!is_readable($info_file))
		{
			return;
		}

		$list = file($info_file);
		$result = new stdClass;
		$result->jsList = array();
		$result->cssList = array();

		foreach($list as $filename)
		{
			$filename = trim($filename);
			if(!$filename)
			{
				continue;
			}

			if(strncasecmp('./', $filename, 2) === 0)
			{
				$filename = substr($filename, 2);
			}

			if(substr_compare($filename, '.js', -3) === 0)
			{
				$result->jsList[] = $plugin_path . $filename;
			}
			elseif(substr_compare($filename, '.css', -4) === 0)
			{
				$result->cssList[] = $plugin_path . $filename;
			}
		}

		if(is_dir($plugin_path . 'lang'))
		{
			$result->langPath = $plugin_path . 'lang';
		}

		return $result;
	}
	/**
	 * Load javascript plugin
	 *
	 * @param string $plugin_name plugin name
	 * @return void
	 */
	public static function loadJavascriptPlugin($plugin_name)
	{
		static $loaded_plugins = array();

		if($plugin_name == 'ui.datepicker')
		{
			$plugin_name = 'ui';
		}

		if($loaded_plugins[$plugin_name])
		{
			return;
		}
		$loaded_plugins[$plugin_name] = TRUE;

		$plugin_path = './common/js/plugins/' . $plugin_name . '/';
		$info_file = $plugin_path . 'plugin.load';
		if(!is_readable($info_file))
		{
			return;
		}

		$list = file($info_file);
		foreach($list as $filename)
		{
			$filename = trim($filename);
			if(!$filename)
			{
				continue;
			}

			if(strncasecmp('./', $filename, 2) === 0)
			{
				$filename = substr($filename, 2);
			}
			if(substr_compare($filename, '.js', -3) === 0)
			{
				self::loadFile(array($plugin_path . $filename, 'body', '', 0), TRUE);
			}
			if(substr_compare($filename, '.css', -4) === 0)
			{
				self::loadFile(array($plugin_path . $filename, 'all', '', 0), TRUE);
			}
		}

		if(is_dir($plugin_path . 'lang'))
		{
			self::loadLang($plugin_path . 'lang');
		}
	}

	/**
	 * Add html code before </head>
	 *
	 * @param string $header add html code before </head>.
	 * @return void
	 */
	public static function addHtmlHeader($header)
	{
		self::$_instance->html_header .= "\n" . $header;
	}

	public static function clearHtmlHeader()
	{
		self::$_instance->html_header = '';
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </head>
	 */
	public static function getHtmlHeader()
	{
		return self::$_instance->html_header;
	}

	/**
	 * Add css class to Html Body
	 *
	 * @param string $class_name class name
	 */
	public static function addBodyClass($class_name)
	{
		self::$_instance->body_class[] = $class_name;
	}

	/**
	 * Return css class to Html Body
	 *
	 * @return string Return class to html body
	 */
	public static function getBodyClass()
	{
		self::$_instance->body_class = array_unique(self::$_instance->body_class);

		return (count(self::$_instance->body_class) > 0) ? sprintf(' class="%s"', join(' ', self::$_instance->body_class)) : '';
	}

	/**
	 * Add html code after <body>
	 *
	 * @param string $header Add html code after <body>
	 */
	public static function addBodyHeader($header)
	{
		self::$_instance->body_header .= "\n" . $header;
	}

	/**
	 * Returns added html code by addBodyHeader()
	 *
	 * @return string Added html code after <body>
	 */
	public static function getBodyHeader()
	{
		return self::$_instance->body_header;
	}

	/**
	 * Add html code before </body>
	 *
	 * @param string $footer Add html code before </body>
	 */
	public static function addHtmlFooter($footer)
	{
		self::$_instance->html_footer .= (self::$_instance->Htmlfooter ? "\n" : '') . $footer;
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </body>
	 */
	public static function getHtmlFooter()
	{
		return self::$_instance->html_footer;
	}

	/**
	 * Get config file
	 *
	 * @retrun string The path of the config file that contains database settings
	 */
	public static function getConfigFile()
	{
		return RX_BASEDIR . Rhymix\Framework\Config::$old_db_config_filename;
	}

	/**
	 * Get FTP config file
	 *
	 * @return string The path of the config file that contains FTP settings
	 */
	public static function getFTPConfigFile()
	{
		return RX_BASEDIR . Rhymix\Framework\Config::$old_ftp_config_filename;
	}

	/**
	 * Set a validator message
	 * 
	 * @param string $id
	 * @param string $message
	 * @param string $type (optional)
	 */
	public static function setValidatorMessage($id, $message, $type = 'info')
	{
		$_SESSION['XE_VALIDATOR_ID'] = $id;
		$_SESSION['XE_VALIDATOR_MESSAGE'] = $message;
		$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = $type;
		$_SESSION['XE_VALIDATOR_ERROR'] = $type === 'error' ? -1 : 0;
		$_SESSION['XE_VALIDATOR_RETURN_URL'] = null;
	}

	/**
	 * Checks whether XE is installed
	 *
	 * @return bool True if the config file exists, otherwise FALSE.
	 */
	public static function isInstalled()
	{
		return (bool)config('config_version');
	}

	/**
	 * Checks whether the site is locked
	 *
	 * @return bool True if the site is locked, otherwise false
	 */
	public static function isLocked()
	{
		return (bool)self::$_instance->is_site_locked;
	}

	/**
	 * Transforms codes about widget or other features into the actual code, deprecatred
	 *
	 * @param string Transforms codes
	 * @return string Transforms codes
	 */
	public static function transContent($content)
	{
		return $content;
	}

	/**
	 * Check whether it is allowed to use rewrite mod
	 *
	 * @return bool True if it is allowed to use rewrite mod, otherwise FALSE
	 */
	public static function isAllowRewrite()
	{
		return self::$_instance->allow_rewrite;
	}

	/**
	 * Check whether an addon, module, or widget is blacklisted
	 * 
	 * @param string $plugin_name
	 * @return bool
	 */
	public static function isBlacklistedPlugin($plugin_name)
	{
		if (self::$_blacklist === null)
		{
			self::$_blacklist = (include RX_BASEDIR . 'common/defaults/blacklist.php');
			if (!is_array(self::$_blacklist))
			{
				self::$_blacklist = array();
			}
		}
		
		return isset(self::$_blacklist[$plugin_name]);
	}

	/**
	 * Check whether a word is reserved in Rhymix
	 * 
	 * @param string $word
	 * @return bool
	 */
	public static function isReservedWord($word)
	{
		if (self::$_reserved_words === null)
		{
			self::$_reserved_words = (include RX_BASEDIR . 'common/defaults/reserved.php');
			if (!is_array(self::$_reserved_words))
			{
				self::$_reserved_words = array();
			}
		}
		
		return isset(self::$_reserved_words[$word]);
	}

	/**
	 * Converts a local path into an URL
	 *
	 * @param string $path URL path
	 * @return string Converted path
	 */
	public static function pathToUrl($path)
	{
		$xe = _XE_PATH_;
		$path = strtr($path, "\\", "/");

		$base_url = preg_replace('@^https?://[^/]+/?@', '', self::getRequestUri());

		$_xe = explode('/', $xe);
		$_path = explode('/', $path);
		$_base = explode('/', $base_url);

		if(!$_base[count($_base) - 1])
		{
			array_pop($_base);
		}

		foreach($_xe as $idx => $dir)
		{
			if($_path[0] != $dir)
			{
				break;
			}
			array_shift($_path);
		}

		$idx = count($_xe) - $idx - 1;
		while($idx--)
		{
			if(count($_base) > 0)
			{
				array_shift($_base);
			}
			else
			{
				array_unshift($_base, '..');
			}
		}

		if(count($_base) > 0)
		{
			array_unshift($_path, join('/', $_base));
		}

		$path = '/' . join('/', $_path);
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		return $path;
	}

	/**
	 * Get meta tag
	 * 
	 * @param string $name (optional)
	 * @return array The list of meta tags
	 */
	public static function getMetaTag($name = null)
	{
		if ($name !== null)
		{
			return isset(self::$_instance->meta_tags[$name]) ? self::$_instance->meta_tags[$name]['content'] : null;
		}
		
		$ret = array();
		foreach(self::$_instance->meta_tags as $name => $content)
		{
			$ret[] = array('name' => $name, 'is_http_equiv' => $content['is_http_equiv'], 'content' => escape($content['content'], false));
		}

		return $ret;
	}

	/**
	 * Add meta tag
	 *
	 * @param string $name name of meta tag
	 * @param string $content content of meta tag
	 * @param mixed $is_http_equiv value of http_equiv
	 * @return void
	 */
	public static function addMetaTag($name, $content, $is_http_equiv = false)
	{
		getController('module')->replaceDefinedLangCode($content);
		self::$_instance->meta_tags[$name] = array('is_http_equiv' => (bool)$is_http_equiv, 'content' => $content);
	}
	
	/**
	 * Get OpenGraph metadata
	 * 
	 * @return array
	 */
	public static function getOpenGraphData()
	{
		$ret = array();
		foreach(self::$_instance->opengraph_metadata as $key => $val)
		{
			if ($val[1] === false || $val[1] === null)
			{
				continue;
			}
			$ret[] = array('property' => escape($val[0], false), 'content' => escape($val[1], false));
		}
		return $ret;
	}
	
	/**
	 * Add OpenGraph metadata
	 * 
	 * @param string $name
	 * @param mixed $content
	 * @return void
	 */
	public static function addOpenGraphData($name, $content)
	{
		if (is_array($content))
		{
			foreach ($content as $key => $val)
			{
				self::addOpenGraphData("$name:$key", $val);
			}
		}
		else
		{
			self::$_instance->opengraph_metadata[] = array($name, $content);
		}
	}
	
	/**
	 * Set canonical URL
	 * 
	 * @param string $url
	 * @return void
	 */
	public static function setCanonicalURL($url)
	{
		self::$_instance->canonical_url = escape($url, false);
	}
	
	/**
	 * Get canonical URL
	 * 
	 * @return string
	 */
	public static function getCanonicalURL()
	{
		return self::$_instance->canonical_url;
	}
}
/* End of file Context.class.php */
/* Location: ./classes/context/Context.class.php */
