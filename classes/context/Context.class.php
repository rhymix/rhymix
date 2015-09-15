<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

define('FOLLOW_REQUEST_SSL', 0);
define('ENFORCE_SSL', 1);
define('RELEASE_SSL', 2);

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
	 * Conatins request parameters and environment variables
	 * @var object
	 */
	public $context = NULL;

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
	 * path of Xpress Engine
	 * @var string
	 */
	public $path = '';
	// language information - it is changed by HTTP_USER_AGENT or user's cookie
	/**
	 * language type
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
	 * returns static context object (Singleton). It's to use Context without declaration of an object
	 *
	 * @return object Instance
	 */
	function &getInstance()
	{
		static $theInstance = null;
		if(!$theInstance)
		{
			$theInstance = new Context();
		}

		return $theInstance;
	}

	/**
	 * Cunstructor
	 *
	 * @return void
	 */
	function Context()
	{
		$this->oFrontEndFileHandler = new FrontEndFileHandler();
		$this->get_vars = new stdClass();

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
	function init()
	{
		if(!isset($GLOBALS['HTTP_RAW_POST_DATA']) && version_compare(PHP_VERSION, '5.6.0', '>=') === true) {
			if(simplexml_load_string(file_get_contents("php://input")) !== false) $GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
		}

		// set context variables in $GLOBALS (to use in display handler)
		$this->context = &$GLOBALS['__Context__'];
		$this->context->lang = &$GLOBALS['lang'];
		$this->context->_COOKIE = $_COOKIE;

		// 20140429 editor/image_link
		$this->_checkGlobalVars();

		$this->setRequestMethod('');

		$this->_setXmlRpcArgument();
		$this->_setJSONRequestArgument();
		$this->_setRequestArgument();
		$this->_setUploadedArgument();

		$this->loadDBInfo();
		if($this->db_info->use_sitelock == 'Y')
		{
			if(is_array($this->db_info->sitelock_whitelist)) $whitelist = $this->db_info->sitelock_whitelist;

			if(!IpFilter::filter($whitelist))
			{
				$title = ($this->db_info->sitelock_title) ? $this->db_info->sitelock_title : 'Maintenance in progress...';
				$message = $this->db_info->sitelock_message;

				define('_XE_SITELOCK_', TRUE);
				define('_XE_SITELOCK_TITLE_', $title);
				define('_XE_SITELOCK_MESSAGE_', $message);

				header("HTTP/1.1 403 Forbidden");
				if(FileHandler::exists(_XE_PATH_ . 'common/tpl/sitelock.user.html'))
				{
					include _XE_PATH_ . 'common/tpl/sitelock.user.html';
				}
				else
				{
					include _XE_PATH_ . 'common/tpl/sitelock.html';
				}
				exit;
			}
		}

		// If XE is installed, get virtual site information
		if(self::isInstalled())
		{
			$oModuleModel = getModel('module');
			$site_module_info = $oModuleModel->getDefaultMid();

			if(!isset($site_module_info))
			{
				$site_module_info = new stdClass();
			}

			// if site_srl of site_module_info is 0 (default site), compare the domain to default_url of db_config
			if($site_module_info->site_srl == 0 && $site_module_info->domain != $this->db_info->default_url)
			{
				$site_module_info->domain = $this->db_info->default_url;
			}

			$this->set('site_module_info', $site_module_info);
			if($site_module_info->site_srl && isSiteID($site_module_info->domain))
			{
				$this->set('vid', $site_module_info->domain, TRUE);
			}

			if(!isset($this->db_info))
			{
				$this->db_info = new stdClass();
			}

			$this->db_info->lang_type = $site_module_info->default_language;
			if(!$this->db_info->lang_type)
			{
				$this->db_info->lang_type = 'en';
			}
			if(!$this->db_info->use_db_session)
			{
				$this->db_info->use_db_session = 'N';
			}
		}

		// Load Language File
		$lang_supported = $this->loadLangSelected();

		// Retrieve language type set in user's cookie
		if($this->lang_type = $this->get('l'))
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

		// If it's not exists, follow default language type set in db_info
		if(!$this->lang_type)
		{
			$this->lang_type = $this->db_info->lang_type;
		}

		// if still lang_type has not been set or has not-supported type , set as English.
		if(!$this->lang_type)
		{
			$this->lang_type = 'en';
		}
		if(is_array($lang_supported) && !isset($lang_supported[$this->lang_type]))
		{
			$this->lang_type = 'en';
		}

		$this->set('lang_supported', $lang_supported);
		$this->setLangType($this->lang_type);

		// load module module's language file according to language setting
		$this->loadLang(_XE_PATH_ . 'modules/module/lang');

		// set session handler
		if(self::isInstalled() && $this->db_info->use_db_session == 'Y')
		{
			$oSessionModel = getModel('session');
			$oSessionController = getController('session');
			session_set_save_handler(
					array(&$oSessionController, 'open'), array(&$oSessionController, 'close'), array(&$oSessionModel, 'read'), array(&$oSessionController, 'write'), array(&$oSessionController, 'destroy'), array(&$oSessionController, 'gc')
			);
		}

		if($sess = $_POST[session_name()]) session_id($sess);
		session_start();

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

				$this->set('is_logged', $oMemberModel->isLogged());
				$this->set('logged_info', $oMemberModel->getLoggedInfo());
			}
		}

		// load common language file
		$this->lang = &$GLOBALS['lang'];
		$this->loadLang(_XE_PATH_ . 'common/lang/');

		// check if using rewrite module
		$this->allow_rewrite = ($this->db_info->use_rewrite == 'Y' ? TRUE : FALSE);

		// set locations for javascript use
		$url = array();
		$current_url = self::getRequestUri();
		if($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			if($this->get_vars)
			{
				$url = array();
				foreach($this->get_vars as $key => $val)
				{
					if(is_array($val) && count($val) > 0)
					{
						foreach($val as $k => $v)
						{
							$url[] = $key . '[' . $k . ']=' . urlencode($v);
						}
					}
					elseif($val)
					{
						$url[] = $key . '=' . urlencode($val);
					}
				}

				$current_url = self::getRequestUri();
				if($url) $current_url .= '?' . join('&', $url);
			}
			else
			{
				$current_url = $this->getUrl();
			}
		}
		else
		{
			$current_url = self::getRequestUri();
		}

		$this->set('current_url', $current_url);
		$this->set('request_uri', self::getRequestUri());

		if(strpos($current_url, 'xn--') !== FALSE)
		{
			$this->set('current_url', self::decodeIdna($current_url));
		}

		if(strpos(self::getRequestUri(), 'xn--') !== FALSE)
		{
			$this->set('request_uri', self::decodeIdna(self::getRequestUri()));
		}
	}

	/**
	 * Finalize using resources, such as DB connection
	 *
	 * @return void
	 */
	function close()
	{
		session_write_close();
	}

	/**
	 * Load the database information
	 *
	 * @return void
	 */
	function loadDBInfo()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!$self->isInstalled())
		{
			return;
		}

		$config_file = $self->getConfigFile();
		if(is_readable($config_file))
		{
			include($config_file);
		}

		// If master_db information does not exist, the config file needs to be updated
		if(!isset($db_info->master_db))
		{
			$db_info->master_db = array();
			$db_info->master_db["db_type"] = $db_info->db_type;
			unset($db_info->db_type);
			$db_info->master_db["db_port"] = $db_info->db_port;
			unset($db_info->db_port);
			$db_info->master_db["db_hostname"] = $db_info->db_hostname;
			unset($db_info->db_hostname);
			$db_info->master_db["db_password"] = $db_info->db_password;
			unset($db_info->db_password);
			$db_info->master_db["db_database"] = $db_info->db_database;
			unset($db_info->db_database);
			$db_info->master_db["db_userid"] = $db_info->db_userid;
			unset($db_info->db_userid);
			$db_info->master_db["db_table_prefix"] = $db_info->db_table_prefix;
			unset($db_info->db_table_prefix);

			if(isset($db_info->master_db["db_table_prefix"]) && substr_compare($db_info->master_db["db_table_prefix"], '_', -1) !== 0)
			{
				$db_info->master_db["db_table_prefix"] .= '_';
			}

			$db_info->slave_db = array($db_info->master_db);
			$self->setDBInfo($db_info);

			$oInstallController = getController('install');
			$oInstallController->makeConfigFile();
		}

		if(!$db_info->use_prepared_statements)
		{
			$db_info->use_prepared_statements = 'Y';
		}

		if(!$db_info->time_zone)
			$db_info->time_zone = date('O');
		$GLOBALS['_time_zone'] = $db_info->time_zone;

		if($db_info->qmail_compatibility != 'Y')
			$db_info->qmail_compatibility = 'N';
		$GLOBALS['_qmail_compatibility'] = $db_info->qmail_compatibility;

		if(!$db_info->use_db_session)
			$db_info->use_db_session = 'N';
		if(!$db_info->use_ssl)
			$db_info->use_ssl = 'none';
		$this->set('_use_ssl', $db_info->use_ssl);

		$self->set('_http_port', ($db_info->http_port) ? $db_info->http_port : NULL);
		$self->set('_https_port', ($db_info->https_port) ? $db_info->https_port : NULL);

		if(!$db_info->sitelock_whitelist) {
			$db_info->sitelock_whitelist = '127.0.0.1';
		}

		if(is_string($db_info->sitelock_whitelist)) {
			$db_info->sitelock_whitelist = explode(',', $db_info->sitelock_whitelist);
		}

		$self->setDBInfo($db_info);
	}

	/**
	 * Get DB's db_type
	 *
	 * @return string DB's db_type
	 */
	function getDBType()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->db_info->master_db["db_type"];
	}

	/**
	 * Set DB information
	 *
	 * @param object $db_info DB information
	 * @return void
	 */
	function setDBInfo($db_info)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->db_info = $db_info;
	}

	/**
	 * Get DB information
	 *
	 * @return object DB information
	 */
	function getDBInfo()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->db_info;
	}

	/**
	 * Return ssl status
	 *
	 * @return object SSL status (Optional - none|always|optional)
	 */
	function getSslStatus()
	{
		$dbInfo = self::getDBInfo();
		return $dbInfo->use_ssl;
	}

	/**
	 * Return default URL
	 *
	 * @return string Default URL
	 */
	function getDefaultUrl()
	{
		$db_info = self::getDBInfo();
		return $db_info->default_url;
	}

	/**
	 * Find supported languages
	 *
	 * @return array Supported languages
	 */
	function loadLangSupported()
	{
		static $lang_supported = null;
		if(!$lang_supported)
		{
			$langs = file(_XE_PATH_ . 'common/lang/lang.info');
			foreach($langs as $val)
			{
				list($lang_prefix, $lang_text) = explode(',', $val);
				$lang_text = trim($lang_text);
				$lang_supported[$lang_prefix] = $lang_text;
			}
		}
		return $lang_supported;
	}

	/**
	 * Find selected languages to serve in the site
	 *
	 * @return array Selected languages
	 */
	function loadLangSelected()
	{
		static $lang_selected = null;
		if(!$lang_selected)
		{
			$orig_lang_file = _XE_PATH_ . 'common/lang/lang.info';
			$selected_lang_file = _XE_PATH_ . 'files/config/lang_selected.info';
			if(!FileHandler::hasContent($selected_lang_file))
			{
				$old_selected_lang_file = _XE_PATH_ . 'files/cache/lang_selected.info';
				FileHandler::moveFile($old_selected_lang_file, $selected_lang_file);
			}

			if(!FileHandler::hasContent($selected_lang_file))
			{
				$buff = FileHandler::readFile($orig_lang_file);
				FileHandler::writeFile($selected_lang_file, $buff);
				$lang_selected = self::loadLangSupported();
			}
			else
			{
				$langs = file($selected_lang_file);
				foreach($langs as $val)
				{
					list($lang_prefix, $lang_text) = explode(',', $val);
					$lang_text = trim($lang_text);
					$lang_selected[$lang_prefix] = $lang_text;
				}
			}
		}
		return $lang_selected;
	}

	/**
	 * Single Sign On (SSO)
	 *
	 * @return bool True : Module handling is necessary in the control path of current request , False : Otherwise
	 */
	function checkSSO()
	{
		// pass if it's not GET request or XE is not yet installed
		if($this->db_info->use_sso != 'Y' || isCrawler())
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

		// for sites recieving SSO valdiation
		if($default_url == self::getRequestUri())
		{
			if(self::get('default_url'))
			{
				$url = base64_decode(self::get('default_url'));
				$url_info = parse_url($url);

				$oModuleModel = getModel('module');
				$site_info = $oModuleModel->getSiteInfoByDomain($url_info['host']);
				if(!$site_info->site_srl) {
					$oModuleObject = new ModuleObject();
					$oModuleObject->stop('msg_invalid_request');

					return false;
				}

				$url_info['query'].= ($url_info['query'] ? '&' : '') . 'SSOID=' . session_id();
				$redirect_url = sprintf('%s://%s%s%s?%s', $url_info['scheme'], $url_info['host'], $url_info['port'] ? ':' . $url_info['port'] : '', $url_info['path'], $url_info['query']);
				header('location:' . $redirect_url);

				return FALSE;
			}
			// for sites requesting SSO validation
		}
		else
		{
			// result handling : set session_name()
			if($session_name = self::get('SSOID'))
			{
				setcookie(session_name(), $session_name);

				$url = preg_replace('/([\?\&])$/', '', str_replace('SSOID=' . $session_name, '', self::getRequestUrl()));
				header('location:' . $url);
				return FALSE;
				// send SSO request
			}
			else if(!self::get('SSOID') && $_COOKIE['sso'] != md5(self::getRequestUri()))
			{
				setcookie('sso', md5(self::getRequestUri()), 0, '/');
				$url = sprintf("%s?default_url=%s", $default_url, base64_encode(self::getRequestUrl()));
				header('location:' . $url);
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Check if FTP info is registered
	 *
	 * @return bool True: FTP information is registered, False: otherwise
	 */
	function isFTPRegisted()
	{
		return file_exists(self::getFTPConfigFile());
	}

	/**
	 * Get FTP information
	 *
	 * @return object FTP information
	 */
	function getFTPInfo()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!$self->isFTPRegisted())
		{
			return null;
		}

		include($self->getFTPConfigFile());

		return $ftp_info;
	}

	/**
	 * Add string to browser title
	 *
	 * @param string $site_title Browser title to be added
	 * @return void
	 */
	function addBrowserTitle($site_title)
	{
		if(!$site_title)
		{
			return;
		}
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if($self->site_title)
		{
			$self->site_title .= ' - ' . $site_title;
		}
		else
		{
			$self->site_title = $site_title;
		}
	}

	/**
	 * Set string to browser title
	 *
	 * @param string $site_title Browser title  to be set
	 * @return void
	 */
	function setBrowserTitle($site_title)
	{
		if(!$site_title)
		{
			return;
		}
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->site_title = $site_title;
	}

	/**
	 * Get browser title
	 *
	 * @return string Browser title(htmlspecialchars applied)
	 */
	function getBrowserTitle()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($self->site_title);

		return htmlspecialchars($self->site_title, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
	}

	/**
	 * Return layout's title
	 * @return string layout's title
	 */
	public function getSiteTitle()
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
	function _getBrowserTitle()
	{
		return $this->getBrowserTitle();
	}

	/**
	 * Load language file according to language type
	 *
	 * @param string $path Path of the language file
	 * @return void
	 */
	function loadLang($path)
	{
		global $lang;

		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		if(!$self->lang_type)
		{
			return;
		}
		if(!is_object($lang))
		{
			$lang = new stdClass;
		}

		if(!($filename = $self->_loadXmlLang($path)))
		{
			$filename = $self->_loadPhpLang($path);
		}

		if(!is_array($self->loaded_lang_files))
		{
			$self->loaded_lang_files = array();
		}
		if(in_array($filename, $self->loaded_lang_files))
		{
			return;
		}

		if($filename && is_readable($filename))
		{
			$self->loaded_lang_files[] = $filename;
			include($filename);
		}
		else
		{
			$self->_evalxmlLang($path);
		}
	}

	/**
	 * Evaluation of xml language file
	 *
	 * @param string Path of the language file
	 * @return void
	 */
	function _evalxmlLang($path)
	{
		global $lang;

		if(!$path) return;

		$_path = 'eval://' . $path;

		if(in_array($_path, $this->loaded_lang_files))
		{
			return;
		}

		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}

		$oXmlLangParser = new XmlLangParser($path . 'lang.xml', $this->lang_type);
		$content = $oXmlLangParser->getCompileContent();

		if($content)
		{
			$this->loaded_lang_files[] = $_path;
			eval($content);
		}
	}

	/**
	 * Load language file of xml type
	 *
	 * @param string $path Path of the language file
	 * @return string file name
	 */
	function _loadXmlLang($path)
	{
		if(!$path) return;

		$oXmlLangParser = new XmlLangParser($path . ((substr_compare($path, '/', -1) !== 0) ? '/' : '') . 'lang.xml', $this->lang_type);
		return $oXmlLangParser->compile();
	}

	/**
	 * Load language file of php type
	 *
	 * @param string $path Path of the language file
	 * @return string file name
	 */
	function _loadPhpLang($path)
	{
		if(!$path) return;

		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		$path_tpl = $path . '%s.lang.php';
		$file = sprintf($path_tpl, $this->lang_type);

		$langs = array('ko', 'en'); // this will be configurable.
		while(!is_readable($file) && $langs[0])
		{
			$file = sprintf($path_tpl, array_shift($langs));
		}

		if(!is_readable($file))
		{
			return FALSE;
		}
		return $file;
	}

	/**
	 * Set lang_type
	 *
	 * @param string $lang_type Language type.
	 * @return void
	 */
	function setLangType($lang_type = 'ko')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$self->lang_type = $lang_type;
		$self->set('lang_type', $lang_type);

		$_SESSION['lang_type'] = $lang_type;
	}

	/**
	 * Get lang_type
	 *
	 * @return string Language type
	 */
	function getLangType()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->lang_type;
	}

	/**
	 * Return string accoring to the inputed code
	 *
	 * @param string $code Language variable name
	 * @return string If string for the code exists returns it, otherwise returns original code
	 */
	function getLang($code)
	{
		if(!$code)
		{
			return;
		}
		if($GLOBALS['lang']->{$code})
		{
			return $GLOBALS['lang']->{$code};
		}
		return $code;
	}

	/**
	 * Set data to lang variable
	 *
	 * @param string $code Language variable name
	 * @param string $val `$code`s value
	 * @return void
	 */
	function setLang($code, $val)
	{
		if(!isset($GLOBALS['lang']))
		{
			$GLOBALS['lang'] = new stdClass();
		}
		$GLOBALS['lang']->{$code} = $val;
	}

	/**
	 * Convert strings of variables in $source_object into UTF-8
	 *
	 * @param object $source_obj Conatins strings to convert
	 * @return object converted object
	 */
	function convertEncoding($source_obj)
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
	function checkConvertFlag(&$val, $key = null, $charset = null)
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
	function doConvertEncoding(&$val, $key = null, $charset)
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
	function convertEncodingStr($str)
	{
        if(!$str) return null;
		$obj = new stdClass();
		$obj->str = $str;
		$obj = self::convertEncoding($obj);
		return $obj->str;
	}

	function decodeIdna($domain)
	{
		if(strpos($domain, 'xn--') !== FALSE)
		{
			require_once(_XE_PATH_ . 'libs/idna_convert/idna_convert.class.php');
			$IDN = new idna_convert(array('idn_version' => 2008));
			$domain = $IDN->decode($domain);
		}

		return $domain;
	}

	/**
	 * Force to set response method
	 *
	 * @param string $method Response method. [HTML|XMLRPC|JSON]
	 * @return void
	 */
	function setResponseMethod($method = 'HTML')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);
		$self->response_method = isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Get reponse method
	 *
	 * @return string Response method. If it's not set, returns request method.
	 */
	function getResponseMethod()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if($self->response_method)
		{
			return $self->response_method;
		}

		$method = $self->getRequestMethod();
		$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);

		return isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Determine request method
	 *
	 * @param string $type Request method. (Optional - GET|POST|XMLRPC|JSON)
	 * @return void
	 */
	function setRequestMethod($type = '')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$self->js_callback_func = $self->getJSCallbackFunc();

		($type && $self->request_method = $type) or
				((strpos($_SERVER['CONTENT_TYPE'], 'json') || strpos($_SERVER['HTTP_CONTENT_TYPE'], 'json')) && $self->request_method = 'JSON') or
				($GLOBALS['HTTP_RAW_POST_DATA'] && $self->request_method = 'XMLRPC') or
				($self->js_callback_func && $self->request_method = 'JS_CALLBACK') or
				($self->request_method = $_SERVER['REQUEST_METHOD']);
	}

	/**
	 * handle global arguments
	 *
	 * @return void
	 */
	function _checkGlobalVars()
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
	function _setRequestArgument()
	{
		if(!count($_REQUEST))
		{
			return;
		}

		$requestMethod = $this->getRequestMethod();
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
			elseif($requestMethod == 'POST' && isset($_POST[$key]))
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

			$this->set($key, $val, $set_to_vars);
		}
	}

	function _recursiveCheckVar($val)
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
	function _setJSONRequestArgument()
	{
		if($this->getRequestMethod() != 'JSON')
		{
			return;
		}

		$params = array();
		parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $params);

		foreach($params as $key => $val)
		{
			$this->set($key, $this->_filterRequestVar($key, $val, 1), TRUE);
		}
	}

	/**
	 * Handle request arguments for XML RPC
	 *
	 * @return void
	 */
	function _setXmlRpcArgument()
	{
		if($this->getRequestMethod() != 'XMLRPC')
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
			$this->set($key, $this->_filterXmlVars($key, $val), TRUE);
		}
	}

	/**
	 * Filter xml variables
	 *
	 * @param string $key Variable key
	 * @param object $val Variable value
	 * @return mixed filtered value
	 */
	function _filterXmlVars($key, $val)
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

		$stack = new stdClass();
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
	function _filterRequestVar($key, $val, $do_stripslashes = 1)
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
	function isUploaded()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->is_uploaded;
	}

	/**
	 * Handle uploaded file
	 *
	 * @return void
	 */
	function _setUploadedArgument()
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
				$this->set($key, $val, TRUE);
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
				$this->set($key, $files, TRUE);
			}
		}
	}

	/**
	 * Return request method
	 * @return string Request method type. (Optional - GET|POST|XMLRPC|JSON)
	 */
	function getRequestMethod()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->request_method;
	}

	/**
	 * Return request URL
	 * @return string request URL
	 */
	function getRequestUrl()
	{
		static $url = null;
		if(is_null($url))
		{
			$url = self::getRequestUri();
			if(count($_GET) > 0)
			{
				foreach($_GET as $key => $val)
				{
					$vars[] = $key . '=' . ($val ? urlencode(self::convertEncodingStr($val)) : '');
				}
				$url .= '?' . join('&', $vars);
			}
		}
		return $url;
	}

	/**
	 * Return js callback func.
	 * @return string callback func.
	 */
	function getJSCallbackFunc()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
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
	function getUrl($num_args = 0, $args_list = array(), $domain = null, $encode = TRUE, $autoEncode = FALSE)
	{
		static $site_module_info = null;
		static $current_info = null;

		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

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
				$current_info = parse_url(($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . getScriptPath());
			}
			if($domain_info['host'] . $domain_info['path'] == $current_info['host'] . $current_info['path'])
			{
				unset($domain);
			}
			else
			{
				$domain = preg_replace('/^(http|https):\/\//i', '', trim($domain));
				if(substr_compare($domain, '/', -1) !== 0)
				{
					$domain .= '/';
				}
			}
		}

		$get_vars = array();

		// If there is no GET variables or first argument is '' to reset variables
		if(!$self->get_vars || $args_list[0] == '')
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
			$get_vars = get_object_vars($self->get_vars);
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
		if($act_alias[$act])
		{
			$get_vars['act'] = $act_alias[$act];
		}

		// organize URL
		$query = '';
		if(count($get_vars) > 0)
		{
			// if using rewrite mod
			if($self->allow_rewrite)
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

			if(!$query)
			{
				$queries = array();
				foreach($get_vars as $key => $val)
				{
					if(is_array($val) && count($val) > 0)
					{
						foreach($val as $k => $v)
						{
							$queries[] = $key . '[' . $k . ']=' . urlencode($v);
						}
					}
					elseif(!is_array($val))
					{
						$queries[] = $key . '=' . urlencode($val);
					}
				}
				if(count($queries) > 0)
				{
					$query = 'index.php?' . join('&', $queries);
				}
			}
		}

		// If using SSL always
		$_use_ssl = $self->get('_use_ssl');
		if($_use_ssl == 'always')
		{
			$query = $self->getRequestUri(ENFORCE_SSL, $domain) . $query;
			// optional SSL use
		}
		elseif($_use_ssl == 'optional')
		{
			$ssl_mode = (($self->get('module') === 'admin') || ($get_vars['module'] === 'admin') || (isset($get_vars['act']) && $self->isExistsSSLAction($get_vars['act']))) ? ENFORCE_SSL : RELEASE_SSL;
			$query = $self->getRequestUri($ssl_mode, $domain) . $query;
			// no SSL
		}
		else
		{
			// currently on SSL but target is not based on SSL
			if($_SERVER['HTTPS'] == 'on')
			{
				$query = $self->getRequestUri(ENFORCE_SSL, $domain) . $query;
			}
			else if($domain) // if $domain is set
			{
				$query = $self->getRequestUri(FOLLOW_REQUEST_SSL, $domain) . $query;
			}
			else
			{
				$query = getScriptPath() . $query;
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
	function getRequestUri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null)
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

		$current_use_ssl = ($_SERVER['HTTPS'] == 'on');

		switch($ssl_mode)
		{
			case FOLLOW_REQUEST_SSL: $use_ssl = $current_use_ssl;
				break;
			case ENFORCE_SSL: $use_ssl = TRUE;
				break;
			case RELEASE_SSL: $use_ssl = FALSE;
				break;
		}

		if($domain)
		{
			$target_url = trim($domain);
			if(substr_compare($target_url, '/', -1) !== 0)
			{
				$target_url.= '/';
			}
		}
		else
		{
			$target_url = $_SERVER['HTTP_HOST'] . getScriptPath();
		}

		$url_info = parse_url('http://' . $target_url);

		if($current_use_ssl != $use_ssl)
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
	function set($key, $val, $set_to_get_vars = 0)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->context->{$key} = $val;
		if($set_to_get_vars === FALSE)
		{
			return;
		}
		if($val === NULL || $val === '')
		{
			unset($self->get_vars->{$key});
			return;
		}
		if($set_to_get_vars || $self->get_vars->{$key})
		{
			$self->get_vars->{$key} = $val;
		}
	}

	/**
	 * Return key's value
	 *
	 * @param string $key Key
	 * @return string Key
	 */
	function get($key)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!isset($self->context->{$key}))
		{
			return null;
		}
		return $self->context->{$key};
	}

	/**
	 * Get one more vars in object vars with given arguments(key1, key2, key3,...)
	 *
	 * @return object
	 */
	function gets()
	{
		$num_args = func_num_args();
		if($num_args < 1)
		{
			return;
		}
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$args_list = func_get_args();
		$output = new stdClass();
		foreach($args_list as $v)
		{
			$output->{$v} = $self->get($v);
		}
		return $output;
	}

	/**
	 * Return all data
	 *
	 * @return object All data
	 */
	function getAll()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->context;
	}

	/**
	 * Return values from the GET/POST/XMLRPC
	 *
	 * @return Object Request variables.
	 */
	function getRequestVars()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		if($self->get_vars)
		{
			return clone($self->get_vars);
		}
		return new stdClass;
	}

	/**
	 * Register if an action is to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	function addSSLAction($action)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!is_readable($self->sslActionCacheFile))
		{
			$buff = '<?php if(!defined("__XE__"))exit;';
			FileHandler::writeFile($self->sslActionCacheFile, $buff);
		}

		if(!isset($self->ssl_actions[$action]))
		{
			$self->ssl_actions[$action] = 1;
			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
			FileHandler::writeFile($self->sslActionCacheFile, $sslActionCacheString, 'a');
		}
	}

	/**
	 * Register if actions are to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	function addSSLActions($action_array)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!is_readable($self->sslActionCacheFile))
		{
			unset($self->ssl_actions);
			$buff = '<?php if(!defined("__XE__"))exit;';
			FileHandler::writeFile($self->sslActionCacheFile, $buff);
		}

		foreach($action_array as $action)
		{
			if(!isset($self->ssl_actions[$action]))
			{
				$self->ssl_actions[$action] = 1;
				$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
				FileHandler::writeFile($self->sslActionCacheFile, $sslActionCacheString, 'a');
			}
		}
	}

	/**
	 * Delete if action is registerd to be encrypted by SSL.
	 *
	 * @param string $action act name
	 * @return void
	 */
	function subtractSSLAction($action)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if($self->isExistsSSLAction($action))
		{
			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
			$buff = FileHandler::readFile($self->sslActionCacheFile);
			$buff = str_replace($sslActionCacheString, '', $buff);
			FileHandler::writeFile($self->sslActionCacheFile, $buff);
		}
	}

	/**
	 * Get SSL Action
	 *
	 * @return string acts in array
	 */
	function getSSLActions()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		if($self->getSslStatus() == 'optional')
		{
			return $self->ssl_actions;
		}
	}

	/**
	 * Check SSL action are existed
	 *
	 * @param string $action act name
	 * @return bool If SSL exists, return TRUE.
	 */
	function isExistsSSLAction($action)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return isset($self->ssl_actions[$action]);
	}

	/**
	 * Normalize file path
	 *
	 * @deprecated
	 * @param string $file file path
	 * @return string normalized file path
	 */
	function normalizeFilePath($file)
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
	function getAbsFileUrl($file)
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
	function loadFile($args)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		$self->oFrontEndFileHandler->loadFile($args);
	}

	/**
	 * Unload front end file
	 *
	 * @param string $file File name with path
	 * @param string $targetIe Target IE
	 * @param string $media Media query
	 * @return void
	 */
	function unloadFile($file, $targetIe = '', $media = 'all')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetIe, $media);
	}

	/**
	 * Unload front end file all
	 *
	 * @param string $type Unload target (optional - all|css|js)
	 * @return void
	 */
	function unloadAllFiles($type = 'all')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles($type);
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
	function addJsFile($file, $optimized = FALSE, $targetie = '', $index = 0, $type = 'head', $isRuleset = FALSE, $autoPath = null)
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

		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->loadFile(array($file, $type, $targetie, $index));
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
	function unloadJsFile($file, $optimized = FALSE, $targetie = '')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie);
	}

	/**
	 * Unload all javascript files
	 *
	 * @return void
	 */
	function unloadAllJsFiles()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles('js');
	}

	/**
	 * Add javascript filter
	 *
	 * @param string $path File path
	 * @param string $filename File name
	 * @return void
	 */
	function addJsFilter($path, $filename)
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
	function _getUniqueFileList($files)
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
	function getJsFile($type = 'head')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->oFrontEndFileHandler->getJsFileList($type);
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
	function addCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '', $index = 0)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->loadFile(array($file, $media, $targetie, $index));
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
	function unloadCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '')
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie, $media);
	}

	/**
	 * Unload all css files
	 *
	 * @return void
	 */
	function unloadAllCSSFiles()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles('css');
	}

	/**
	 * Return a list of css files
	 *
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	function getCSSFile()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->oFrontEndFileHandler->getCssFileList();
	}

	/**
	 * Returns javascript plugin file info
	 * @param string $pluginName
	 * @return stdClass
	 */
	function getJavascriptPluginInfo($pluginName)
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
		$result = new stdClass();
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
	function loadJavascriptPlugin($plugin_name)
	{
		static $loaded_plugins = array();

		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
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
				$self->loadFile(array($plugin_path . $filename, 'body', '', 0), TRUE);
			}
			if(substr_compare($filename, '.css', -4) === 0)
			{
				$self->loadFile(array($plugin_path . $filename, 'all', '', 0), TRUE);
			}
		}

		if(is_dir($plugin_path . 'lang'))
		{
			$self->loadLang($plugin_path . 'lang');
		}
	}

	/**
	 * Add html code before </head>
	 *
	 * @param string $header add html code before </head>.
	 * @return void
	 */
	function addHtmlHeader($header)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->html_header .= "\n" . $header;
	}

	function clearHtmlHeader()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->html_header = '';
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </head>
	 */
	function getHtmlHeader()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->html_header;
	}

	/**
	 * Add css class to Html Body
	 *
	 * @param string $class_name class name
	 */
	function addBodyClass($class_name)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->body_class[] = $class_name;
	}

	/**
	 * Return css class to Html Body
	 *
	 * @return string Return class to html body
	 */
	function getBodyClass()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->body_class = array_unique($self->body_class);

		return (count($self->body_class) > 0) ? sprintf(' class="%s"', join(' ', $self->body_class)) : '';
	}

	/**
	 * Add html code after <body>
	 *
	 * @param string $header Add html code after <body>
	 */
	function addBodyHeader($header)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->body_header .= "\n" . $header;
	}

	/**
	 * Returns added html code by addBodyHeader()
	 *
	 * @return string Added html code after <body>
	 */
	function getBodyHeader()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->body_header;
	}

	/**
	 * Add html code before </body>
	 *
	 * @param string $footer Add html code before </body>
	 */
	function addHtmlFooter($footer)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->html_footer .= ($self->Htmlfooter ? "\n" : '') . $footer;
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </body>
	 */
	function getHtmlFooter()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		return $self->html_footer;
	}

	/**
	 * Get config file
	 *
	 * @retrun string The path of the config file that contains database settings
	 */
	function getConfigFile()
	{
		return _XE_PATH_ . 'files/config/db.config.php';
	}

	/**
	 * Get FTP config file
	 *
	 * @return string The path of the config file that contains FTP settings
	 */
	function getFTPConfigFile()
	{
		return _XE_PATH_ . 'files/config/ftp.config.php';
	}

	/**
	 * Checks whether XE is installed
	 *
	 * @return bool True if the config file exists, otherwise FALSE.
	 */
	function isInstalled()
	{
		return FileHandler::hasContent(self::getConfigFile());
	}

	/**
	 * Transforms codes about widget or other features into the actual code, deprecatred
	 *
	 * @param string Transforms codes
	 * @return string Transforms codes
	 */
	function transContent($content)
	{
		return $content;
	}

	/**
	 * Check whether it is allowed to use rewrite mod
	 *
	 * @return bool True if it is allowed to use rewrite mod, otherwise FALSE
	 */
	function isAllowRewrite()
	{
		$oContext = self::getInstance();
		return $oContext->allow_rewrite;
	}

	/**
	 * Converts a local path into an URL
	 *
	 * @param string $path URL path
	 * @return string Converted path
	 */
	function pathToUrl($path)
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
	function getMetaTag()
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();

		if(!is_array($self->meta_tags))
		{
			$self->meta_tags = array();
		}

		$ret = array();
		foreach($self->meta_tags as $key => $val)
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
	function addMetaTag($name, $content, $is_http_equiv = FALSE)
	{
		is_a($this, 'Context') ? $self = $this : $self = self::getInstance();
		$self->meta_tags[$name . "\t" . ($is_http_equiv ? '1' : '0')] = $content;
	}

}
/* End of file Context.class.php */
/* Location: ./classes/context/Context.class.php */
