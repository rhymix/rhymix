<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Manages Context such as request arguments/environment variables
 * It has dual method structure, easy-to use methods which can be called as self::methodname(),and methods called with static object.
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
	 * @var string GET|POST|XMLRPC
	 */
	public $request_method = 'GET';

	/**
	 * js callback function name.
	 * @var string
	 */
	public $js_callback_func = '';

	/**
	 * Response method.If it's not set, it follows request method.
	 * @var string HTML|XMLRPC
	 */
	public $response_method = '';

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
	 * ssl action cache file
	 * @var array
	 */
	public $sslActionCacheFile = './files/cache/sslCacheFile.php';

	/**
	 * List of actions to be sent via ssl (it is used by javascript xml handler for ajax)
	 * @var array
	 */
	public $ssl_actions = array();

	/**
	 * obejct oFrontEndFileHandler()
	 * @var object
	 */
	public $oFrontEndFileHandler;

	/**
	 * script codes in <head>..</head>
	 * @var string
	 */
	public $html_header = NULL;

	/**
	 * class names of <body>
	 * @var array
	 */
	public $body_class = array();

	/**
	 * codes after <body>
	 * @var string
	 */
	public $body_header = NULL;

	/**
	 * class names before </body>
	 * @var string
	 */
	public $html_footer = NULL;

	/**
	 * Meta tags
	 * @var array
	 */
	public $meta_tags = array();

	/**
	 * path of Xpress Engine
	 * @var string
	 */
	public $path = '';

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
	 * list of loaded languages (to avoid re-loading them)
	 * @var array
	 */
	public $loaded_lang_files = array();

	/**
	 * site's browser title
	 * @var string
	 */
	public $site_title = '';

	/**
	 * variables from GET or form submit
	 * @var mixed
	 */
	public $get_vars = NULL;

	/**
	 * variables from user (Context::get, Context::set)
	 * @var mixed
	 */
	private static $_user_vars = NULL;

	/**
	 * Checks uploaded
	 * @var bool TRUE if attached file exists
	 */
	public $is_uploaded = FALSE;

	/**
	 * Pattern for request vars check
	 * @var array
	 */
	public $patterns = array(
		'/<\?/iUsm',
		'/<\%/iUsm',
		'/<script\s*?language\s*?=\s*?("|\')?\s*?php\s*("|\')?/iUsm'
	);

	/**
	 * Check init
	 * @var bool FALSE if init fail
	 */
	public $isSuccessInit = TRUE;

	/**
	 * Singleton instance
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * returns static context object (Singleton). It's to use Context without declaration of an object
	 *
	 * @return object Instance
	 */
	public static function getInstance()
	{
		if(self::$_instance === null)
		{
			self::$_instance = new Context();
		}
		return self::$_instance;
	}

	/**
	 * Cunstructor
	 *
	 * @return void
	 */
	private function __construct()
	{
		$this->oFrontEndFileHandler = new FrontEndFileHandler();
		$this->get_vars = new stdClass;
		self::$_user_vars = new stdClass;

		// include ssl action cache file
		$this->sslActionCacheFile = FileHandler::getRealPath($this->sslActionCacheFile);
		if(is_readable($this->sslActionCacheFile))
		{
			require($this->sslActionCacheFile);
			if(isset($sslActions))
			{
				$this->ssl_actions = $sslActions;
			}
		}
	}

	/**
	 * Initialization, it sets DB information, request arguments and so on.
	 *
	 * @see This function should be called only once
	 * @return void
	 */
	public function init()
	{
		// Fix missing HTTP_RAW_POST_DATA in PHP 5.6 and above.
		if(!isset($GLOBALS['HTTP_RAW_POST_DATA']) && version_compare(PHP_VERSION, '5.6.0', '>=') === TRUE)
		{
			$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
			
			// If content is not XML or JSON, unset
			if(!preg_match('/^[\<\{\[]/', $GLOBALS['HTTP_RAW_POST_DATA']))
			{
				unset($GLOBALS['HTTP_RAW_POST_DATA']);
			}
		}
		
		// Set global variables for backward compatibility.
		$GLOBALS['__Context__'] = $this;
		$GLOBALS['lang'] = &$this->lang;
		$this->_COOKIE = $_COOKIE;
		
		// Set information about the current request.
		$this->setRequestMethod();
		$this->_checkGlobalVars();
		$this->_setXmlRpcArgument();
		$this->_setJSONRequestArgument();
		$this->_setRequestArgument();
		$this->_setUploadedArgument();
		
		if(isset($_POST['_rx_ajax_compat']) && $_POST['_rx_ajax_compat'] === 'XMLRPC')
		{
			self::$_instance->request_method = 'XMLRPC';
			self::$_instance->response_method = 'JSON';
		}
		
		// Load system configuration.
		$this->loadDBInfo();
		
		// If Rhymix is installed, get virtual site information.
		if(self::isInstalled())
		{
			$oModuleModel = getModel('module');
			$site_module_info = $oModuleModel->getDefaultMid() ?: new stdClass;
			
			// if site_srl of site_module_info is 0 (default site), compare the domain to default_url of db_config
			if($site_module_info->site_srl == 0 && $site_module_info->domain != $this->db_info->default_url)
			{
				$site_module_info->domain = $this->db_info->default_url;
			}
			
			self::set('site_module_info', $site_module_info);
			if($site_module_info->site_srl && isSiteID($site_module_info->domain))
			{
				self::set('vid', $site_module_info->domain, TRUE);
			}
		}
		else
		{
			$site_module_info = new stdClass;
		}

		// Load language support.
		$enabled_langs = self::loadLangSelected();
		self::set('lang_supported', $enabled_langs);
		
		if($this->lang_type = self::get('l'))
		{
			if($_COOKIE['lang_type'] != $this->lang_type)
			{
				setcookie('lang_type', $this->lang_type, $_SERVER['REQUEST_TIME'] + 3600 * 24 * 1000, '/');
			}
		}
		elseif($_COOKIE['lang_type'])
		{
			$this->lang_type = $_COOKIE['lang_type'];
		}
		elseif($site_module_info->default_language)
		{
			$this->lang_type = $this->db_info->lang_type = $site_module_info->default_language;
		}
		else
		{
			$this->lang_type = $this->db_info->lang_type;
		}
		
		if(!$this->lang_type || !isset($enabled_langs[$this->lang_type]))
		{
			$this->lang_type = 'ko';
		}

		self::setLangType($this->lang_type);
		
		$this->lang = Rhymix\Framework\Lang::getInstance($this->lang_type);
		$this->lang->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
		$this->lang->loadDirectory(RX_BASEDIR . 'modules/module/lang', 'module');
		
		// set session handler
		if(self::isInstalled() && config('session.use_db'))
		{
			$oSessionModel = getModel('session');
			$oSessionController = getController('session');
			session_set_save_handler(
					array(&$oSessionController, 'open'), array(&$oSessionController, 'close'), array(&$oSessionModel, 'read'), array(&$oSessionController, 'write'), array(&$oSessionController, 'destroy'), array(&$oSessionController, 'gc')
			);
		}

		// start session if it was previously started
		$session_name = session_name();
		$session_id = NULL;
		if($session_id = $_POST[$session_name])
		{
			session_id($session_id);
		}
		else
		{
			$session_id = $_COOKIE[$session_name];
		}

		if($session_id !== NULL || !config('session.delay'))
		{
			$this->setCacheControl(0, false);
			session_start();
		}
		else
		{
			ob_start();
			$this->setCacheControl(-1, true);
			register_shutdown_function(array($this, 'checkSessionStatus'));
			$_SESSION = array();
		}

		// set authentication information in Context and session
		if(self::isInstalled())
		{
			$oModuleModel = getModel('module');
			$oModuleModel->loadModuleExtends();

			$oMemberModel = getModel('member');
			$oMemberController = getController('member');

			if($oMemberController && $oMemberModel)
			{
				// if signed in, validate it.
				if($oMemberModel->isLogged())
				{
					$oMemberController->setSessionInfo();
				}
				// check auto sign-in
				elseif($_COOKIE['xeak'])
				{
					$oMemberController->doAutologin();
				}

				self::set('is_logged', $oMemberModel->isLogged());
				if($oMemberModel->isLogged())
				{
					self::set('logged_info', $oMemberModel->getLoggedInfo());
				}
			}
		}
		
		// set locations for javascript use
		$current_url = $request_uri = self::getRequestUri();
		if ($_SERVER['REQUEST_METHOD'] == 'GET' && $this->get_vars)
		{
			if ($query_string = http_build_query($this->get_vars))
			{
				$current_url .= '?' . $query_string;
			}
		}
		if (strpos($current_url, 'xn--') !== false)
		{
			$current_url = self::decodeIdna($current_url);
		}
		if (strpos($request_uri, 'xn--') !== false)
		{
			$request_uri = self::decodeIdna($request_uri);
		}
		self::set('current_url', $current_url);
		self::set('request_uri', $request_uri);
		
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
		return (session_id() !== '');
	}

	/**
	 * Start the session if $_SESSION was touched
	 * 
	 * @return void
	 */
	public static function checkSessionStatus($force_start = false)
	{
		if(self::getSessionStatus())
		{
			return;
		}
		if($force_start || (count($_SESSION) && !headers_sent()))
		{
			$tempSession = $_SESSION;
			unset($_SESSION);
			session_start();
			$_SESSION = $tempSession;
		}
	}

	/**
	 * Finalize using resources, such as DB connection
	 *
	 * @return void
	 */
	public static function close()
	{
		session_write_close();
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
			return;
		}

		// Copy to old format for backward compatibility.
		self::$_instance->db_info = self::convertDBInfo($config);
		self::$_instance->allow_rewrite = self::$_instance->db_info->use_rewrite;
		self::set('_http_port', self::$_instance->db_info->http_port ?: null);
		self::set('_https_port', self::$_instance->db_info->https_port ?: null);
		self::set('_use_ssl', self::$_instance->db_info->use_ssl);
		$GLOBALS['_time_zone'] = self::$_instance->db_info->time_zone;
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
		$db_info->use_object_cache = count($config['cache']) ? array_first($config['cache']) : null;
		$db_info->ftp_info = new stdClass;
		$db_info->ftp_info->ftp_host = $config['ftp']['host'];
		$db_info->ftp_info->ftp_port = $config['ftp']['port'];
		$db_info->ftp_info->ftp_user = $config['ftp']['user'];
		$db_info->ftp_info->ftp_pasv = $config['ftp']['pasv'] ? 'Y' : 'N';
		$db_info->ftp_info->ftp_root_path = $config['ftp']['path'];
		$db_info->ftp_info->sftp = $config['ftp']['sftp'] ? 'Y' : 'N';
		$db_info->default_url = $config['url']['default'];
		if (!$db_info->default_url)
		{
			$db_info->default_url = (RX_SSL ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . RX_BASEURL;
		}
		$db_info->http_port = $config['url']['http_port'];
		$db_info->https_port = $config['url']['https_port'];
		$db_info->use_ssl = $config['url']['ssl'];
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
		$db_info->embed_white_iframe = $config['embedfilter']['iframe'];
		$db_info->embed_white_object = $config['embedfilter']['object'];
		$db_info->use_mobile_view = $config['use_mobile_view'] ? 'Y' : 'N';
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
	 * @return string Default URL
	 */
	public static function getDefaultUrl()
	{
		return self::$_instance->db_info->default_url;
	}

	/**
	 * Find supported languages
	 *
	 * @return array Supported languages
	 */
	public static function loadLangSupported()
	{
		return Rhymix\Framework\Lang::getSupportedList();
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
					$lang_selected[$lang] = $supported[$lang];
				}
			}
			else
			{
				$lang_selected = $supported;
			}
		}
		return $lang_selected;
	}

	/**
	 * Single Sign On (SSO)
	 *
	 * @return bool True : Module handling is necessary in the control path of current request , False : Otherwise
	 */
	public function checkSSO()
	{
		// pass if it's not GET request or XE is not yet installed
		if(!config('use_sso') || isCrawler())
		{
			return TRUE;
		}
		$checkActList = array('rss' => 1, 'atom' => 1);
		if(self::getRequestMethod() != 'GET' || !self::isInstalled() || isset($checkActList[self::get('act')]))
		{
			return TRUE;
		}

		// pass if default URL is not set
		$default_url = trim($this->db_info->default_url);
		if(!$default_url)
		{
			return TRUE;
		}

		if(substr_compare($default_url, '/', -1) !== 0)
		{
			$default_url .= '/';
		}

		// Get current site information (only the base URL, not the full URL)
		$current_site = self::getRequestUri();

		// Step 1: if the current site is not the default site, send SSO validation request to the default site
		if($default_url !== $current_site && !self::get('SSOID') && $_COOKIE['sso'] !== md5($current_site))
		{
			// Set sso cookie to prevent multiple simultaneous SSO validation requests
			setcookie('sso', md5($current_site), 0, '/');
			
			// Redirect to the default site
			$redirect_url = sprintf('%s?return_url=%s', $default_url, urlencode(base64_encode($current_site)));
			header('Location:' . $redirect_url);
			return FALSE;
		}

		// Step 2: receive and process SSO validation request at the default site
		if($default_url === $current_site && self::get('return_url'))
		{
			// Get the URL of the origin site
			$url = base64_decode(self::get('return_url'));
			$url_info = parse_url($url);

			// Check that the origin site is a valid site in this XE installation (to prevent open redirect vuln)
			if(!getModel('module')->getSiteInfoByDomain(rtrim($url, '/'))->site_srl)
			{
				htmlHeader();
				echo self::getLang("msg_invalid_request");
				htmlFooter();
				return FALSE;
			}

			// Redirect back to the origin site
			$url_info['query'] .= ($url_info['query'] ? '&' : '') . 'SSOID=' . session_id();
			$redirect_url = sprintf('%s://%s%s%s%s', $url_info['scheme'], $url_info['host'], $url_info['port'] ? (':' . $url_info['port']) : '', $url_info['path'], ($url_info['query'] ? ('?' . $url_info['query']) : ''));
			header('Location:' . $redirect_url);
			return FALSE;
		}

		// Step 3: back at the origin site, set session ID to be the same as the default site
		if($default_url !== $current_site && self::get('SSOID'))
		{
			// Check that the session ID was given by the default site (to prevent session fixation CSRF)
			if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $default_url) !== 0)
			{
				htmlHeader();
				echo self::getLang("msg_invalid_request");
				htmlFooter();
				return FALSE;
			}

			// Set session ID
			setcookie(session_name(), self::get('SSOID'));

			// Finally, redirect to the originally requested URL
			$url_info = parse_url(self::getRequestUrl());
			$url_info['query'] = preg_replace('/(^|\b)SSOID=([^&?]+)/', '', $url_info['query']);
			$redirect_url = sprintf('%s://%s%s%s%s', $url_info['scheme'], $url_info['host'], $url_info['port'] ? (':' . $url_info['port']) : '', $url_info['path'], ($url_info['query'] ? ('?' . $url_info['query']) : ''));
			header('Location:' . $redirect_url);
			return FALSE;
		}

		// If none of the conditions above apply, proceed normally
		return TRUE;
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
	 * Add string to browser title
	 *
	 * @param string $site_title Browser title to be added
	 * @return void
	 */
	public static function addBrowserTitle($site_title)
	{
		if(!$site_title)
		{
			return;
		}
		if(self::$_instance->site_title)
		{
			self::$_instance->site_title .= ' - ' . $site_title;
		}
		else
		{
			self::$_instance->site_title = $site_title;
		}
	}

	/**
	 * Set string to browser title
	 *
	 * @param string $site_title Browser title  to be set
	 * @return void
	 */
	public static function setBrowserTitle($site_title)
	{
		if(!$site_title)
		{
			return;
		}
		self::$_instance->site_title = $site_title;
	}

	/**
	 * Get browser title
	 *
	 * @return string Browser title(htmlspecialchars applied)
	 */
	public static function getBrowserTitle()
	{
		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode(self::$_instance->site_title);

		return htmlspecialchars(self::$_instance->site_title, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
	}

	/**
	 * Return layout's title
	 * @return string layout's title
	 */
	public static function getSiteTitle()
	{
		$oModuleModel = getModel('module');
		$moduleConfig = $oModuleModel->getModuleConfig('module');

		if(isset($moduleConfig->siteTitle))
		{
			return $moduleConfig->siteTitle;
		}
		return '';
	}

	/**
	 * Get browser title
	 * @deprecated
	 */
	public function _getBrowserTitle()
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
		if (preg_match('@/(modules|addons|plugins)/([a-z0-9_]+)/lang/?$@', str_replace('\\', '/', $path), $matches))
		{
			$plugin_name = $matches[2];
		}
		else
		{
			$plugin_name = null;
		}
		return self::$_instance->lang->loadDirectory($path, $plugin_name);
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
		if (self::$_instance->lang)
		{
			return self::$_instance->lang->get($code);
		}
		else
		{
			return $code;
		}
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
		if (self::$_instance->lang)
		{
			self::$_instance->lang->set($code, $val);
		}
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
			'UTF-8', 'EUC-KR', 'CP949', 'ISO8859-1', 'EUC-JP', 'SHIFT_JIS', 'CP932',
			'EUC-CN', 'HZ', 'GBK', 'GB18030', 'EUC-TW', 'BIG5', 'CP950', 'BIG5-HKSCS',
			'ISO2022-CN', 'ISO2022-CN-EXT', 'ISO2022-JP', 'ISO2022-JP-2', 'ISO2022-JP-1',
			'ISO8859-6', 'ISO8859-8', 'JOHAB', 'ISO2022-KR', 'CP1255', 'CP1256', 'CP862',
			'ASCII', 'ISO8859-1', 'ISO8850-2', 'ISO8850-3', 'ISO8850-4', 'ISO8850-5',
			'ISO8850-7', 'ISO8850-9', 'ISO8850-10', 'ISO8850-13', 'ISO8850-14',
			'ISO8850-15', 'ISO8850-16', 'CP1250', 'CP1251', 'CP1252', 'CP1253', 'CP1254',
			'CP1257', 'CP850', 'CP866',
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
        if(!$str) return null;
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
		if(function_exists('idn_to_ascii'))
		{
			return idn_to_ascii($domain);
		}
		else
		{
			$encoder = new TrueBV\Punycode();
			return $encoder->encode($domain);
		}
	}

	/**
	 * Convert IDNA (punycode) domain into UTF-8
	 * 
	 * @param string $domain Domain to convert
	 * @return string Converted string
	 */
	public static function decodeIdna($domain)
	{
		if(function_exists('idn_to_utf8'))
		{
			return idn_to_utf8($domain);
		}
		else
		{
			$decoder = new TrueBV\Punycode();
			return $decoder->decode($domain);
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
	 * Determine request method
	 *
	 * @param string $type Request method. (Optional - GET|POST|XMLRPC|JSON)
	 * @return void
	 */
	public static function setRequestMethod($type = '')
	{
		self::$_instance->js_callback_func = self::$_instance->getJSCallbackFunc();
		
		if ($type)
		{
			self::$_instance->request_method = $type;
		}
		elseif (strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false || strpos($_SERVER['CONTENT_TYPE'], 'json') !== false || strpos($_SERVER['HTTP_CONTENT_TYPE'], 'json') !== false)
		{
			self::$_instance->request_method = 'JSON';
		}
		elseif ($GLOBALS['HTTP_RAW_POST_DATA'])
		{
			self::$_instance->request_method = 'XMLRPC';
		}
		elseif (self::$_instance->js_callback_func)
		{
			self::$_instance->request_method = 'JS_CALLBACK';
		}
		else
		{
			self::$_instance->request_method = $_SERVER['REQUEST_METHOD'];
		}
	}

	/**
	 * handle global arguments
	 *
	 * @return void
	 */
	private function _checkGlobalVars()
	{
		$this->_recursiveCheckVar($_SERVER['HTTP_HOST']);

		$pattern = "/[\,\"\'\{\}\[\]\(\);$]/";
		if(preg_match($pattern, $_SERVER['HTTP_HOST']))
		{
			$this->isSuccessInit = FALSE;
		}
	}

	/**
	 * handle request arguments for GET/POST
	 *
	 * @return void
	 */
	private function _setRequestArgument()
	{
		if(!count($_REQUEST))
		{
			return;
		}

		$requestMethod = self::getRequestMethod();
		foreach($_REQUEST as $key => $val)
		{
			if($val === '' || self::get($key))
			{
				continue;
			}
			$key = htmlentities($key);
			$val = $this->_filterRequestVar($key, $val);

			if($requestMethod == 'GET' && isset($_GET[$key]))
			{
				$set_to_vars = TRUE;
			}
			elseif(($requestMethod == 'POST' || $requestMethod == 'JSON') && isset($_POST[$key]))
			{
				$set_to_vars = TRUE;
			}
			elseif($requestMethod == 'JS_CALLBACK' && (isset($_GET[$key]) || isset($_POST[$key])))
			{
				$set_to_vars = TRUE;
			}
			else
			{
				$set_to_vars = FALSE;
			}

			if($set_to_vars)
			{
				$this->_recursiveCheckVar($val);
			}

			self::set($key, $val, $set_to_vars);
		}
	}

	private function _recursiveCheckVar($val)
	{
		if(is_string($val))
		{
			foreach($this->patterns as $pattern)
			{
				if(preg_match($pattern, $val))
				{
					$this->isSuccessInit = FALSE;
					return;
				}
			}
		}
		else if(is_array($val))
		{
			foreach($val as $val2)
			{
				$this->_recursiveCheckVar($val2);
			}
		}
	}

	/**
	 * Handle request arguments for JSON
	 *
	 * @return void
	 */
	private function _setJSONRequestArgument()
	{
		if(count($_POST) || self::getRequestMethod() != 'JSON')
		{
			return;
		}
		$params = array();
		parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $params);
		foreach($params as $key => $val)
		{
			self::set($key, $this->_filterRequestVar($key, $val, 1), TRUE);
		}
	}

	/**
	 * Handle request arguments for XML RPC
	 *
	 * @return void
	 */
	private function _setXmlRpcArgument()
	{
		if(self::getRequestMethod() != 'XMLRPC')
		{
			return;
		}

		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		if(Security::detectingXEE($xml))
		{
			header("HTTP/1.0 400 Bad Request");
			exit;
		}

		$oXml = new XmlParser();
		$xml_obj = $oXml->parse($xml);

		$params = $xml_obj->methodcall->params;
		unset($params->node_name, $params->attrs, $params->body);

		if(!count(get_object_vars($params)))
		{
			return;
		}

		foreach($params as $key => $val)
		{
			self::set($key, $this->_filterXmlVars($key, $val), TRUE);
		}
	}

	/**
	 * Filter xml variables
	 *
	 * @param string $key Variable key
	 * @param object $val Variable value
	 * @return mixed filtered value
	 */
	private function _filterXmlVars($key, $val)
	{
		if(is_array($val))
		{
			$stack = array();
			foreach($val as $k => $v)
			{
				$stack[$k] = $this->_filterXmlVars($k, $v);
			}

			return $stack;
		}

		$body = $val->body;
		unset($val->node_name, $val->attrs, $val->body);
		if(!count(get_object_vars($val)))
		{
			return $this->_filterRequestVar($key, $body, 0);
		}

		$stack = new stdClass;
		foreach($val as $k => $v)
		{
			$output = $this->_filterXmlVars($k, $v);
			if(is_object($v) && $v->attrs->type == 'array')
			{
				$output = array($output);
			}
			if($k == 'value' && (is_array($v) || $v->attrs->type == 'array'))
			{
				return $output;
			}

			$stack->{$k} = $output;
		}

		if(!count(get_object_vars($stack)))
		{
			return NULL;
		}

		return $stack;
	}

	/**
	 * Filter request variable
	 *
	 * @see Cast variables, such as _srl, page, and cpage, into interger
	 * @param string $key Variable key
	 * @param string $val Variable value
	 * @param string $do_stripslashes Whether to strip slashes
	 * @return mixed filtered value. Type are string or array
	 */
	public function _filterRequestVar($key, $val, $do_stripslashes = 1)
	{
		if(!($isArray = is_array($val)))
		{
			$val = array($val);
		}

		$result = array();
		foreach($val as $k => $v)
		{
			$k = htmlentities($k);
			if($key === 'page' || $key === 'cpage' || substr_compare($key, 'srl', -3) === 0)
			{
				$result[$k] = !preg_match('/^[0-9,]+$/', $v) ? (int) $v : $v;
			}
			elseif($key === 'mid' || $key === 'search_keyword')
			{
				$result[$k] = htmlspecialchars($v, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
			}
			elseif($key === 'vid')
			{
				$result[$k] = urlencode($v);
			}
			else
			{
				$result[$k] = $v;

				if($do_stripslashes && version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc())
				{
					$result[$k] = stripslashes($result[$k]);
				}

				if(!is_array($result[$k]))
				{
					$result[$k] = trim($result[$k]);
				}
			}
		}

		return $isArray ? $result : $result[0];
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
	public function _setUploadedArgument()
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST' || !$_FILES || (stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === FALSE && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data') === FALSE))
		{
			return;
		}

		foreach($_FILES as $key => $val)
		{
			$tmp_name = $val['tmp_name'];
			if(!is_array($tmp_name))
			{
				if(!$tmp_name || !is_uploaded_file($tmp_name))
				{
					continue;
				}
				$val['name'] = htmlspecialchars($val['name'], ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
				self::set($key, $val, TRUE);
				$this->is_uploaded = TRUE;
			}
			else
			{
				for($i = 0, $c = count($tmp_name); $i < $c; $i++)
				{
					if($val['size'][$i] > 0)
					{
						$file['name'] = $val['name'][$i];
						$file['type'] = $val['type'][$i];
						$file['tmp_name'] = $val['tmp_name'][$i];
						$file['error'] = $val['error'][$i];
						$file['size'] = $val['size'][$i];
						$files[] = $file;
					}
				}
				self::set($key, $files, TRUE);
			}
		}
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
		$allowed_list = config('lock.allow');
		foreach ($allowed_list as $allowed_ip)
		{
			if (Rhymix\Framework\IpFilter::inRange(RX_CLIENT_IP, $allowed_ip))
			{
				return;
			}
		}
		
		// Set headers and constants for backward compatibility.
		header('HTTP/1.1 503 Service Unavailable');
		define('_XE_SITELOCK_', TRUE);
		define('_XE_SITELOCK_TITLE_', config('lock.title') ?: self::getLang('admin.sitelock_in_use'));
		define('_XE_SITELOCK_MESSAGE_', config('lock.message'));
		unset($_SESSION['XE_VALIDATOR_RETURN_URL']);
		
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
		$oMessageObject->setHttpStatusCode($status);
		$oMessageObject->setMessage($title);
		$oMessageObject->dispMessage($message);
		
		// Display the message.
		$oModuleHandler = new ModuleHandler;
		$oModuleHandler->displayContent($oMessageObject);
		exit;
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
		static $site_module_info = null;
		static $current_info = null;

		// retrieve virtual site information
		if(is_null($site_module_info))
		{
			$site_module_info = self::get('site_module_info');
		}

		// If $domain is set, handle it (if $domain is vid type, remove $domain and handle with $vid)
		if($domain && isSiteID($domain))
		{
			$vid = $domain;
			$domain = '';
		}

		// If $domain, $vid are not set, use current site information
		if(!$domain && !$vid)
		{
			if($site_module_info->domain && isSiteID($site_module_info->domain))
			{
				$vid = $site_module_info->domain;
			}
			else
			{
				$domain = $site_module_info->domain;
			}
		}

		// if $domain is set, compare current URL. If they are same, remove the domain, otherwise link to the domain.
		if($domain)
		{
			$domain_info = parse_url($domain);
			if(is_null($current_info))
			{
				$current_info = parse_url((RX_SSL ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . RX_BASEURL);
			}
			if($domain_info['host'] . $domain_info['path'] == $current_info['host'] . $current_info['path'])
			{
				unset($domain);
			}
			else
			{
				$domain = rtrim(preg_replace('/^(http|https):\/\//i', '', trim($domain)), '/') . '/';
			}
		}

		$get_vars = array();

		// If there is no GET variables or first argument is '' to reset variables
		if(!self::$_instance->get_vars || $args_list[0] == '')
		{
			// rearrange args_list
			if(is_array($args_list) && $args_list[0] == '')
			{
				array_shift($args_list);
			}
		}
		else
		{
			// Otherwise, make GET variables into array
			$get_vars = get_object_vars(self::$_instance->get_vars);
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
		if($vid)
		{
			$get_vars['vid'] = $vid;
		}
		else
		{
			unset($get_vars['vid']);
		}

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
				$vid = $get_vars['vid'];
				$mid = $get_vars['mid'];
				$key = $get_vars['key'];
				$srl = $get_vars['document_srl'];

				$tmpArray = array('rss' => 1, 'atom' => 1, 'api' => 1);
				$is_feed = isset($tmpArray[$act]);

				$target_map = array(
					'vid' => $vid,
					'mid' => $mid,
					'mid.vid' => "$vid/$mid",
					'entry.mid' => "$mid/entry/" . $get_vars['entry'],
					'entry.mid.vid' => "$vid/$mid/entry/" . $get_vars['entry'],
					'document_srl' => $srl,
					'document_srl.mid' => "$mid/$srl",
					'document_srl.vid' => "$vid/$srl",
					'document_srl.mid.vid' => "$vid/$mid/$srl",
					'act' => ($is_feed && $act !== 'api') ? $act : '',
					'act.mid' => $is_feed ? "$mid/$act" : '',
					'act.mid.vid' => $is_feed ? "$vid/$mid/$act" : '',
					'act.document_srl.key' => ($act == 'trackback') ? "$srl/$key/$act" : '',
					'act.document_srl.key.mid' => ($act == 'trackback') ? "$mid/$srl/$key/$act" : '',
					'act.document_srl.key.vid' => ($act == 'trackback') ? "$vid/$srl/$key/$act" : '',
					'act.document_srl.key.mid.vid' => ($act == 'trackback') ? "$vid/$mid/$srl/$key/$act" : ''
				);

				$query = $target_map[$target];
			}

			if(!$query && count($get_vars) > 0)
			{
				$query = 'index.php?' . http_build_query($get_vars);
			}
		}

		// If using SSL always
		$_use_ssl = self::get('_use_ssl');
		if($_use_ssl == 'always')
		{
			$query = self::getRequestUri(ENFORCE_SSL, $domain) . $query;
		}
		// optional SSL use
		elseif($_use_ssl == 'optional')
		{
			$ssl_mode = ((self::get('module') === 'admin') || ($get_vars['module'] === 'admin') || (isset($get_vars['act']) && self::isExistsSSLAction($get_vars['act']))) ? ENFORCE_SSL : RELEASE_SSL;
			$query = self::getRequestUri($ssl_mode, $domain) . $query;
		}
		// no SSL
		else
		{
			// currently on SSL but target is not based on SSL
			if(RX_SSL)
			{
				$query = self::getRequestUri(ENFORCE_SSL, $domain) . $query;
			}
			else if($domain) // if $domain is set
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
			return htmlspecialchars($query, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
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

		return htmlspecialchars($parsedUrl['path'] . '?' . join('&', $encode_queries), ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
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
		static $url = array();

		// Check HTTP Request
		if(!isset($_SERVER['SERVER_PROTOCOL']))
		{
			return;
		}

		if(self::get('_use_ssl') == 'always')
		{
			$ssl_mode = ENFORCE_SSL;
		}

		if($domain)
		{
			$domain_key = md5($domain);
		}
		else
		{
			$domain_key = 'default';
		}

		if(isset($url[$ssl_mode][$domain_key]))
		{
			return $url[$ssl_mode][$domain_key];
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

		if($domain)
		{
			$target_url = rtrim(trim($domain), '/') . '/';
		}
		else
		{
			$target_url = $_SERVER['HTTP_HOST'] . RX_BASEURL;
		}

		$url_info = parse_url('http://' . $target_url);

		if($use_ssl != RX_SSL)
		{
			unset($url_info['port']);
		}

		if($use_ssl)
		{
			$port = self::get('_https_port');
			if($port && $port != 443)
			{
				$url_info['port'] = $port;
			}
			elseif($url_info['port'] == 443)
			{
				unset($url_info['port']);
			}
		}
		else
		{
			$port = self::get('_http_port');
			if($port && $port != 80)
			{
				$url_info['port'] = $port;
			}
			elseif($url_info['port'] == 80)
			{
				unset($url_info['port']);
			}
		}

		$url[$ssl_mode][$domain_key] = sprintf('%s://%s%s%s', $use_ssl ? 'https' : $url_info['scheme'], $url_info['host'], $url_info['port'] && $url_info['port'] != 80 ? ':' . $url_info['port'] : '', $url_info['path']);

		return $url[$ssl_mode][$domain_key];
	}

	/**
	 * Set a context value with a key
	 *
	 * @param string $key Key
	 * @param string $val Value
	 * @param mixed $set_to_get_vars If not FALSE, Set to get vars.
	 * @return void
	 */
	public static function set($key, $val, $set_to_get_vars = 0)
	{
		self::$_user_vars->{$key} = $val;
		self::$_instance->{$key} = $val;

		if($set_to_get_vars)
		{
			if($val === NULL || $val === '')
			{
				unset(self::$_instance->get_vars->{$key});
			}
			else
			{
				self::$_instance->get_vars->{$key} = $val;
			}
		}
	}

	/**
	 * Return key's value
	 *
	 * @param string $key Key
	 * @return string Key
	 */
	public static function get($key)
	{
		if(isset(self::$_user_vars->{$key}))
		{
			return self::$_user_vars->{$key};
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
		self::$_user_vars = self::$_user_vars !== null ? self::$_user_vars : new stdClass;
		foreach($args_list as $key)
		{
			$output->{$key} = isset(self::$_user_vars->{$key}) ? self::$_user_vars->{$key} : (isset(self::$_instance->{$key}) ? self::$_instance->{$key} : null);
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
		return self::$_user_vars !== null ? self::$_user_vars : new stdClass;
	}

	/**
	 * Return values from the GET/POST/XMLRPC
	 *
	 * @return Object Request variables.
	 */
	public static function getRequestVars()
	{
		if(self::$_instance->get_vars)
		{
			return clone(self::$_instance->get_vars);
		}
		return new stdClass;
	}

	/**
	 * Register if an action is to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function addSSLAction($action)
	{
		if(!is_readable(self::$_instance->sslActionCacheFile))
		{
			$buff = '<?php if(!defined("__XE__"))exit;';
			FileHandler::writeFile(self::$_instance->sslActionCacheFile, $buff);
		}

		if(!isset(self::$_instance->ssl_actions[$action]))
		{
			self::$_instance->ssl_actions[$action] = 1;
			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
			FileHandler::writeFile(self::$_instance->sslActionCacheFile, $sslActionCacheString, 'a');
		}
	}

	/**
	 * Register if actions are to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function addSSLActions($action_array)
	{
		if(!is_readable(self::$_instance->sslActionCacheFile))
		{
			unset(self::$_instance->ssl_actions);
			$buff = '<?php if(!defined("__XE__"))exit;';
			FileHandler::writeFile(self::$_instance->sslActionCacheFile, $buff);
		}

		foreach($action_array as $action)
		{
			if(!isset(self::$_instance->ssl_actions[$action]))
			{
				self::$_instance->ssl_actions[$action] = 1;
				$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
				FileHandler::writeFile(self::$_instance->sslActionCacheFile, $sslActionCacheString, 'a');
			}
		}
	}

	/**
	 * Delete if action is registerd to be encrypted by SSL.
	 *
	 * @param string $action act name
	 * @return void
	 */
	public static function subtractSSLAction($action)
	{
		if(self::isExistsSSLAction($action))
		{
			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
			$buff = FileHandler::readFile(self::$_instance->sslActionCacheFile);
			$buff = str_replace($sslActionCacheString, '', $buff);
			FileHandler::writeFile(self::$_instance->sslActionCacheFile, $buff);
		}
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
			return self::$_instance->ssl_actions;
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
		return isset(self::$_instance->ssl_actions[$action]);
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
		self::$_instance->oFrontEndFileHandler->loadFile($args);
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
		self::$_instance->oFrontEndFileHandler->unloadFile($file, $targetIe, $media);
	}

	/**
	 * Unload front end file all
	 *
	 * @param string $type Unload target (optional - all|css|js)
	 * @return void
	 */
	public static function unloadAllFiles($type = 'all')
	{
		self::$_instance->oFrontEndFileHandler->unloadAllFiles($type);
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

		self::$_instance->oFrontEndFileHandler->loadFile(array($file, $type, $targetie, $index));
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
		self::$_instance->oFrontEndFileHandler->unloadFile($file, $targetie);
	}

	/**
	 * Unload all javascript files
	 *
	 * @return void
	 */
	public static function unloadAllJsFiles()
	{
		self::$_instance->oFrontEndFileHandler->unloadAllFiles('js');
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
	 * @return array Returns javascript file list. Array contains file, targetie.
	 */
	public static function getJsFile($type = 'head')
	{
		return self::$_instance->oFrontEndFileHandler->getJsFileList($type);
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
		self::$_instance->oFrontEndFileHandler->loadFile(array($file, $media, $targetie, $index));
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
		self::$_instance->oFrontEndFileHandler->unloadFile($file, $targetie, $media);
	}

	/**
	 * Unload all css files
	 *
	 * @return void
	 */
	public static function unloadAllCSSFiles()
	{
		self::$_instance->oFrontEndFileHandler->unloadAllFiles('css');
	}

	/**
	 * Return a list of css files
	 *
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	public static function getCSSFile()
	{
		return self::$_instance->oFrontEndFileHandler->getCssFileList();
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
	 * Checks whether XE is installed
	 *
	 * @return bool True if the config file exists, otherwise FALSE.
	 */
	public static function isInstalled()
	{
		return (bool)config('config_version');
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
	 * @return array The list of meta tags
	 */
	public static function getMetaTag()
	{
		$ret = array();
		foreach(self::$_instance->meta_tags as $key => $val)
		{
			list($name, $is_http_equiv) = explode("\t", $key);
			$ret[] = array('name' => $name, 'is_http_equiv' => $is_http_equiv, 'content' => $val);
		}

		return $ret;
	}

	/**
	 * Add the meta tag
	 *
	 * @param string $name name of meta tag
	 * @param string $content content of meta tag
	 * @param mixed $is_http_equiv value of http_equiv
	 * @return void
	 */
	public static function addMetaTag($name, $content, $is_http_equiv = FALSE)
	{
		self::$_instance->meta_tags[$name . "\t" . ($is_http_equiv ? '1' : '0')] = $content;
	}

}
/* End of file Context.class.php */
/* Location: ./classes/context/Context.class.php */
