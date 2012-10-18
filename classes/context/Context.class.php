<?php
define('FOLLOW_REQUEST_SSL',0);
define('ENFORCE_SSL',1);
define('RELEASE_SSL',2);

/**
 * Manages Context such as request arguments/environment variables
 * It has dual method structure, easy-to use methods which can be called as Context::methodname(),and methods called with static object.
 *
 * @author NHN (developers@xpressengine.com)
 */
class Context {

	/**
	 * Allow rewrite
	 * @var bool true: using rewrite mod, false: otherwise
	 */
	var $allow_rewrite = false;
	/**
	 * Request method
	 * @var string GET|POST|XMLRPC
	 */
	var $request_method  = 'GET';
	/**
	 * Response method.If it's not set, it follows request method.
	 * @var string HTML|XMLRPC 
	 */
	var $response_method = '';
	/**
	 * Conatins request parameters and environment variables
	 * @var object
	 */
	var $context  = NULL;
	/**
	 * DB info 
	 * @var object
	 */
	var $db_info  = NULL;
	/**
	 * FTP info 
	 * @var object
	 */
	var $ftp_info = NULL;
	/**
	 * ssl action cache file
	 * @var array
	 */
	var $sslActionCacheFile = './files/cache/sslCacheFile.php';
	/**
	 * List of actions to be sent via ssl (it is used by javascript xml handler for ajax)
	 * @var array
	 */
	var $ssl_actions = array();
	/**
	 * obejct oFrontEndFileHandler()
	 * @var object
	 */
	var $oFrontEndFileHandler;
	/**
	 * script codes in <head>..</head>
	 * @var string
	 */
	var $html_header = NULL;
	/**
	 * class names of <body>
	 * @var array
	 */
	var $body_class  = array();
	/**
	 * codes after <body>
	 * @var string
	 */
	var $body_header = NULL;
	/**
	 * class names before </body>
	 * @var string
	 */
	var $html_footer = NULL;
	/**
	 * path of Xpress Engine 
	 * @var string
	 */
	var $path = '';

	// language information - it is changed by HTTP_USER_AGENT or user's cookie
	/**
	 * language type 
	 * @var string
	 */
	var $lang_type = '';
	/**
	 * contains language-specific data
	 * @var object 
	 */
	var $lang = NULL;
	/**
	 * list of loaded languages (to avoid re-loading them)
	 * @var array
	 */
	var $loaded_lang_files = array();
	/**
	 * site's browser title
	 * @var string
	 */
	var $site_title = '';
	/**
	 * variables from GET or form submit
	 * @var mixed
	 */
	var $get_vars = NULL;
	/**
	 * Checks uploaded 
	 * @var bool true if attached file exists
	 */
	var $is_uploaded = false;

	/**
	 * returns static context object (Singleton). It's to use Context without declaration of an object
	 *
	 * @return object Instance
	 */
	function &getInstance() {
		static $theInstance = null;
		if(!$theInstance) $theInstance = new Context();

		// include ssl action cache file
		$theInstance->sslActionCacheFile = FileHandler::getRealPath($theInstance->sslActionCacheFile);
		if(is_readable($theInstance->sslActionCacheFile))
		{
			require_once($theInstance->sslActionCacheFile);
			if(isset($sslActions))
			{
				$theInstance->ssl_actions = $sslActions;
			}
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
	}

	/**
	 * Initialization, it sets DB information, request arguments and so on.
	 *
	 * @see This function should be called only once
	 * @return void
	 */
	function init() {
		// set context variables in $GLOBALS (to use in display handler)
		$this->context = &$GLOBALS['__Context__'];
		$this->context->lang = &$GLOBALS['lang'];
		$this->context->_COOKIE = $_COOKIE;

		$this->setRequestMethod('');

		$this->_setXmlRpcArgument();
		$this->_setJSONRequestArgument();
		$this->_setRequestArgument();
		$this->_setUploadedArgument();

		$this->loadDBInfo();

		// If XE is installed, get virtual site information
		if(Context::isInstalled()) {
			$oModuleModel = &getModel('module');
			$site_module_info = $oModuleModel->getDefaultMid();
			// if site_srl of site_module_info is 0 (default site), compare the domain to default_url of db_config
			if($site_module_info->site_srl == 0 && $site_module_info->domain != $this->db_info->default_url) {
				$site_module_info->domain = $this->db_info->default_url;
			}

			$this->set('site_module_info', $site_module_info);
			if($site_module_info->site_srl && isSiteID($site_module_info->domain)) $this->set('vid', $site_module_info->domain, true);

			$this->db_info->lang_type = $site_module_info->default_language;
			if(!$this->db_info->lang_type) $this->db_info->lang_type = 'en';
			if(!$this->db_info->use_db_session) $this->db_info->use_db_session = 'N';
		}

		// Load Language File
		$lang_supported = $this->loadLangSelected();

		// Retrieve language type set in user's cookie
		if($this->get('l')) {
			$this->lang_type = $this->get('l');
			if($_COOKIE['lang_type'] != $this->lang_type) {
				setcookie('lang_type', $this->lang_type, time()+3600*24*1000, '/');
			}
		} elseif($_COOKIE['lang_type']) {
			$this->lang_type = $_COOKIE['lang_type'];
		}

		// If it's not exists, follow default language type set in db_info
		if(!$this->lang_type) $this->lang_type = $this->db_info->lang_type;

		// if still lang_type has not been set or has not-supported type , set as English.
		if(!$this->lang_type) $this->lang_type = 'en';
		if(is_array($lang_supported)&&!isset($lang_supported[$this->lang_type])) $this->lang_type = 'en';

		$this->set('lang_supported', $lang_supported);
		$this->setLangType($this->lang_type);

		// load module module's language file according to language setting
		$this->loadLang(_XE_PATH_.'modules/module/lang');

		// set session handler
		if(Context::isInstalled() && $this->db_info->use_db_session == 'Y') {
			$oSessionModel = &getModel('session');
			$oSessionController = &getController('session');
			session_set_save_handler(
				array(&$oSessionController, 'open'),
				array(&$oSessionController, 'close'),
				array(&$oSessionModel, 'read'),
				array(&$oSessionController, 'write'),
				array(&$oSessionController, 'destroy'),
				array(&$oSessionController, 'gc')
			);
		}
		session_start();
		if($sess=$_POST[session_name()]) session_id($sess);

		// set authentication information in Context and session
		if(Context::isInstalled()) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();

			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');

			if($oMemberController && $oMemberModel)
			{
				// if signed in, validate it.
				if($oMemberModel->isLogged()) {
					$oMemberController->setSessionInfo();
				}
				elseif($_COOKIE['xeak']) { // check auto sign-in
					$oMemberController->doAutologin();
				}

				$this->set('is_logged', $oMemberModel->isLogged() );
				$this->set('logged_info', $oMemberModel->getLoggedInfo() );
			}
		}

		// load common language file
		$this->lang = &$GLOBALS['lang'];
		$this->loadLang(_XE_PATH_.'common/lang/');

		// check if using rewrite module
		if(file_exists(_XE_PATH_.'.htaccess')&&$this->db_info->use_rewrite == 'Y') $this->allow_rewrite = true;
		else $this->allow_rewrite = false;

		// set locations for javascript use
		if($_SERVER['REQUEST_METHOD'] == 'GET') {
			if($this->get_vars) {
				foreach($this->get_vars as $key=>$val) {
					if(is_array($val)&&count($val)) {
						foreach($val as $k => $v) {
							$url .= ($url?'&':'').$key.'['.$k.']='.urlencode($v);
						}
					} elseif ($val) {
						$url .= ($url?'&':'').$key.'='.urlencode($val);
					}
				}
				$this->set('current_url',sprintf('%s?%s', Context::getRequestUri(), $url));
			} else {
				$this->set('current_url',$this->getUrl());
			}
		} else {
			$this->set('current_url',Context::getRequestUri());
		}
		$this->set('request_uri',Context::getRequestUri());
	}

	/**
	 * Finalize using resources, such as DB connection
	 *
	 * @return void
	 */
	function close() {
		// Session Close
		if(function_exists('session_write_close')) session_write_close();

		// DB close
		$oDB = &DB::getInstance();
		if(is_object($oDB)&&method_exists($oDB, 'close')) $oDB->close();
	}

	/**
	 * Load the database information
	 *
	 * @return void
	 */
	function loadDBInfo() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if(!$self->isInstalled()) return;

		$config_file = $self->getConfigFile();
		if(is_readable($config_file)) @include($config_file);

                // If master_db information does not exist, the config file needs to be updated
                if(!isset($db_info->master_db)) {
                    $db_info->master_db = array();
                    $db_info->master_db["db_type"] = $db_info->db_type; unset($db_info->db_type);
                    $db_info->master_db["db_port"] = $db_info->db_port; unset($db_info->db_port);
                    $db_info->master_db["db_hostname"] = $db_info->db_hostname; unset($db_info->db_hostname);
                    $db_info->master_db["db_password"] = $db_info->db_password; unset($db_info->db_password);
                    $db_info->master_db["db_database"] = $db_info->db_database; unset($db_info->db_database);
                    $db_info->master_db["db_userid"] = $db_info->db_userid; unset($db_info->db_userid);
                    $db_info->master_db["db_table_prefix"] = $db_info->db_table_prefix; unset($db_info->db_table_prefix);
                    if(substr($db_info->master_db["db_table_prefix"],-1)!='_') $db_info->master_db["db_table_prefix"] .= '_';

                    $slave_db = $db_info->master_db;
                    $db_info->slave_db = array($slave_db);
					
                    $self->setDBInfo($db_info);

                    $oInstallController = &getController('install');
                    $oInstallController->makeConfigFile();
                }
		
		if(!$db_info->use_prepared_statements) 
		{
			$db_info->use_prepared_statements = 'Y';
		}
				
		if(!$db_info->time_zone) $db_info->time_zone = date('O');
		$GLOBALS['_time_zone'] = $db_info->time_zone;

		if($db_info->qmail_compatibility != 'Y') $db_info->qmail_compatibility = 'N';
		$GLOBALS['_qmail_compatibility'] = $db_info->qmail_compatibility;

		if(!$db_info->use_db_session) $db_info->use_db_session = 'N';
		if(!$db_info->use_ssl) $db_info->use_ssl = 'none';
		$this->set('_use_ssl', $db_info->use_ssl);

		if($db_info->http_port)  $self->set('_http_port', $db_info->http_port);
		if($db_info->https_port) $self->set('_https_port', $db_info->https_port);

		$self->setDBInfo($db_info);
	}

	/**
	 * Get DB's db_type
	 *
	 * @return string DB's db_type
	 */
	function getDBType() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->db_info->master_db["db_type"];
	}

	/**
	 * Set DB information
	 *
	 * @param object $db_info DB information
	 * @return void
	 */
	function setDBInfo($db_info) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->db_info = $db_info;
	}

	/**
	 * Get DB information
	 *
	 * @return object DB information
	 */
	function getDBInfo() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->db_info;
	}

	/**
	 * Return ssl status
	 *
	 * @return object SSL status (Optional - none|always|optional) 
	 */
	function getSslStatus()
	{
		$dbInfo = Context::getDBInfo();
		return $dbInfo->use_ssl;
	}

	/**
	 * Return default URL
	 *
	 * @return string Default URL
	 */
	function getDefaultUrl() {
		$db_info = Context::getDBInfo();
		return $db_info->default_url;
	}

	/**
	 * Find supported languages
	 *
	 * @return array Supported languages
	 */
	function loadLangSupported() {
		static $lang_supported = null;
		if(!$lang_supported) {
			$langs = file(_XE_PATH_.'common/lang/lang.info');
			foreach($langs as $val) {
				list($lang_prefix, $lang_text) = explode(',',$val);
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
	function loadLangSelected() {
		static $lang_selected = null;
		if(!$lang_selected) {
			$orig_lang_file = _XE_PATH_.'common/lang/lang.info';
			$selected_lang_file = _XE_PATH_.'files/config/lang_selected.info';
			if(!FileHandler::hasContent($selected_lang_file)) {
				$old_selected_lang_file = _XE_PATH_.'files/cache/lang_selected.info';
				FileHandler::moveFile($old_selected_lang_file, $selected_lang_file);
			}

			if(!FileHandler::hasContent($selected_lang_file)) {
				$buff = FileHandler::readFile($orig_lang_file);
				FileHandler::writeFile($selected_lang_file, $buff);
				$lang_selected = Context::loadLangSupported();
			} else {
				$langs = file($selected_lang_file);
				foreach($langs as $val) {
					list($lang_prefix, $lang_text) = explode(',',$val);
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
	function checkSSO() {
		// pass if it's not GET request or XE is not yet installed
		if($this->db_info->use_sso != 'Y' || isCrawler()) return true;
		$checkActList = array('rss'=>1, 'atom'=>1);
		if(Context::getRequestMethod()!='GET' || !Context::isInstalled() || isset($checkActList[Context::get('act')])) return true;

		// pass if default URL is not set
		$default_url = trim($this->db_info->default_url);
		if(!$default_url) return true;
		if(substr($default_url,-1)!='/') $default_url .= '/';

		// for sites recieving SSO valdiation
		if($default_url == Context::getRequestUri()) {
			if(Context::get('default_url')) {
				$url = base64_decode(Context::get('default_url'));
				$url_info = parse_url($url);
				$url_info['query'].= ($url_info['query']?'&':'').'SSOID='.session_id();
				$redirect_url = sprintf('%s://%s%s%s?%s',$url_info['scheme'],$url_info['host'],$url_info['port']?':'.$url_info['port']:'',$url_info['path'], $url_info['query']);
				header('location:'.$redirect_url);
				return false;
			}
		// for sites requesting SSO validation
		} else {
			// result handling : set session_name()
			if(Context::get('SSOID')) {
				$session_name = Context::get('SSOID');
				setcookie(session_name(), $session_name);

				$url = preg_replace('/([\?\&])$/','',str_replace('SSOID='.$session_name,'',Context::getRequestUrl()));
				header('location:'.$url);
				return false;
			// send SSO request
			} else if($_COOKIE['sso']!=md5(Context::getRequestUri()) && !Context::get('SSOID')) {
				setcookie('sso',md5(Context::getRequestUri()),0,'/');
				$url = sprintf("%s?default_url=%s", $default_url, base64_encode(Context::getRequestUrl()));
				header('location:'.$url);
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if FTP info is registered
	 *
	 * @return bool True: FTP information is registered, False: otherwise
	 */
	function isFTPRegisted() {
		$ftp_config_file = Context::getFTPConfigFile();
		if(file_exists($ftp_config_file)) return true;
		return false;
	}

	/**
	 * Get FTP information
	 *
	 * @return object FTP information
	 */
	function getFTPInfo() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if(!$self->isFTPRegisted()) return null;

		$ftp_config_file = $self->getFTPConfigFile();
		@include($ftp_config_file);

		return $ftp_info;
	}

	/**
	 * Add string to browser title
	 *
	 * @param string $site_title Browser title to be added
	 * @return void
	 */
	function addBrowserTitle($site_title) {
		if(!$site_title) return;
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if($self->site_title) $self->site_title .= ' - '.$site_title;
		else $self->site_title = $site_title;
	}

	/**
	 * Set string to browser title
	 *
	 * @param string $site_title Browser title  to be set
	 * @return void
	 */
	function setBrowserTitle($site_title) {
		if(!$site_title) return;
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->site_title = $site_title;
	}

	/**
	 * Get browser title
	 *
	 * @return string Browser title(htmlspecialchars applied)
	 */
	function getBrowserTitle() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$oModuleController = &getController('module');
		$oModuleController->replaceDefinedLangCode($self->site_title);

		return htmlspecialchars($self->site_title);
	}
	/**
	 * Get browser title
	 * @deprecated
	 */
	function _getBrowserTitle() { return $this->getBrowserTitle(); }

	/**
	 * Load language file according to language type
	 *
	 * @param string $path Path of the language file
	 * @return void
	 */
	function loadLang($path) {
		global $lang;

		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if(!is_object($lang)) $lang = new stdClass;
		if(!$self->lang_type) return;

		$filename = $self->_loadXmlLang($path);
		if(!$filename) $filename = $self->_loadPhpLang($path);

		if(!is_array($self->loaded_lang_files)) $self->loaded_lang_files = array();
		if(in_array($filename, $self->loaded_lang_files)) return;

		if ($filename && is_readable($filename)){
			$self->loaded_lang_files[] = $filename;
			@include($filename);
		}else{
			$self->_evalxmlLang($path);
		}
	}

	/**
	 * Evaluation of xml language file
	 *
	 * @param string Path of the language file
	 * @return void
	 */
	function _evalxmlLang($path) {
		global $lang;
		
		$_path = 'eval://'.$path;

		if(in_array($_path, $this->loaded_lang_files)) return;

		if(substr($path,-1)!='/') $path .= '/';
		$file = $path.'lang.xml';

		$oXmlLangParser = new XmlLangParser($file, $this->lang_type);
		$content = $oXmlLangParser->getCompileContent();

		if ($content){
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
	function _loadXmlLang($path) {
		if(substr($path,-1)!='/') $path .= '/';
		$file = $path.'lang.xml';

		$oXmlLangParser = new XmlLangParser($file, $this->lang_type);
		$file = $oXmlLangParser->compile();

		return $file;
	}

	/**
	 * Load language file of php type
	 *
	 * @param string $path Path of the language file
	 * @return string file name
	 */
	function _loadPhpLang($path) {
		if(substr($path,-1)!='/') $path .= '/';
		$path_tpl = $path.'%s.lang.php';
		$file = sprintf($path_tpl, $this->lang_type);

		$langs = array('ko','en'); // this will be configurable.
		while(!is_readable($file) && $langs[0]) {
			$file = sprintf($path_tpl, array_shift($langs));
		}

		if(!is_readable($file)) return false;
		return $file;
	}

	/**
	 * Set lang_type
	 *
	 * @param string $lang_type Language type.
	 * @return void
	 */
	function setLangType($lang_type = 'ko') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$self->lang_type = $lang_type;
		$self->set('lang_type', $lang_type);

		$_SESSION['lang_type'] = $lang_type;
	}

	/**
	 * Get lang_type
	 *
	 * @return string Language type
	 */
	function getLangType() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->lang_type;
	}

	/**
	 * Return string accoring to the inputed code
	 *
	 * @param string $code Language variable name
	 * @return string If string for the code exists returns it, otherwise returns original code
	 */
	function getLang($code) {
		if(!$code) return;
		if($GLOBALS['lang']->{$code}) return $GLOBALS['lang']->{$code};
		return $code;
	}

	/**
	 * Set data to lang variable
	 *
	 * @param string $code Language variable name
	 * @param string $val `$code`s value
	 * @return void
	 */
	function setLang($code, $val) {
		$GLOBALS['lang']->{$code} = $val;
	}

	/**
	 * Convert strings of variables in $source_object into UTF-8
	 *
	 * @param object $source_obj Conatins strings to convert
	 * @return object converted object
	 */
	function convertEncoding($source_obj) {
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

		$obj = clone($source_obj);

		foreach($charset_list as $charset) {
			$flag = true;
			foreach($obj as $key=>$val) {
				if(!$val) continue;
				if(!is_array($val) && iconv($charset,$charset,$val)!=$val) $flag = false;
				else if(is_array($val))
				{
					$userdata = array('charset1'=>$charset,'charset2'=>$charset,'useFlag'=>true);
					Context::arrayConvWalkCallback($val,null,$userdata);
					if($userdata['returnFlag'] === false) $flag = false;
				}
			}
			if($flag) {
				if($charset == 'UTF-8') return $obj;
				foreach($obj as $key => $val)
				{
					if(!is_array($val)) $obj->{$key} = iconv($charset,'UTF-8',$val);
					else Context::arrayConvWalkCallback($val,null,array($charset,'UTF-8'));
				}

				return $obj;
			}
		}

		return $obj;
	}
	/**
	 * Convert array type variables into UTF-8 
	 *
	 * @param mixed $val
	 * @param string $key
	 * @param mixed $userdata charset1 charset2 useFlag retrunFlag
	 * @see arrayConvWalkCallback will replaced array_walk_recursive in >=PHP5
	 * @return object converted object
	 */
	function arrayConvWalkCallback(&$val, $key = null, &$userdata)
	{
		if (is_array($val)) array_walk($val,'Context::arrayConvWalkCallback',&$userdata);
		else 
		{
			if(!$userdata['useFlag']) $val = iconv($userdata['charset1'],$userdata['charset2'],$val);
			else
			{
				if(iconv($charset,$charset,$val)!=$val) $userdata['returnFlag'] = (bool)false;
			}
		}
	}

	/**
	 * Convert strings into UTF-8
	 *
	 * @param string $str String to convert
	 * @return string converted string
	 */
	function convertEncodingStr($str) {
		$obj->str = $str;
		$obj = Context::convertEncoding($obj);
		return $obj->str;
	}

	/**
	 * Force to set response method
	 *
	 * @param string $method Response method. [HTML|XMLRPC|JSON]
	 * @return void
	 */
	function setResponseMethod($method='HTML') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$methods = array('HTML'=>1, 'XMLRPC'=>1, 'JSON'=>1, 'JS_CALLBACK' => 1);
		$self->response_method = isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Get reponse method
	 *
	 * @return string Response method. If it's not set, returns request method.
	 */
	function getResponseMethod() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if($self->response_method) return $self->response_method;

		$method  = $self->getRequestMethod();
		$methods = array('HTML'=>1, 'XMLRPC'=>1, 'JSON'=>1, 'JS_CALLBACK' => 1);

		return isset($methods[$method]) ? $method : 'HTML';
	}

	/**
	 * Determine request method
	 *
	 * @param string $type Request method. (Optional - GET|POST|XMLRPC|JSON)
	 * @return void
	 */
	function setRequestMethod($type='') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$xe_js_callback = isset($_GET['xe_js_callback']) ? $_GET['xe_js_callback'] : $_POST['xe_js_callback'];

		($type && $self->request_method=$type) or
		(strpos($_SERVER['CONTENT_TYPE'],'json') && $self->request_method='JSON') or
		($GLOBALS['HTTP_RAW_POST_DATA'] && $self->request_method='XMLRPC') or
		($xe_js_callback && $self->request_method='JS_CALLBACK') or
		($self->request_method = $_SERVER['REQUEST_METHOD']);
	}

	/**
	 * handle request areguments for GET/POST
	 *
	 * @return void
	 */
	function _setRequestArgument() {
		if(!count($_REQUEST)) return;

		foreach($_REQUEST as $key => $val) {
			if($val === '' || Context::get($key)) continue;
			$val = $this->_filterRequestVar($key, $val);

			if($this->getRequestMethod()=='GET'&&isset($_GET[$key])) $set_to_vars = true;
			elseif($this->getRequestMethod()=='POST'&&isset($_POST[$key])) $set_to_vars = true;
			else $set_to_vars = false;

			if($set_to_vars)
			{
				$val = preg_replace('/<\?/i', '', $val);
				$val = preg_replace('/<\%/i', '', $val);
				$val = preg_replace('/<script\s+language\s*=\s*("|\')php("|\')\s*>/ism', '', $val);
			}

			$this->set($key, $val, $set_to_vars);
		}
	}

	/**
	 * Handle request arguments for JSON
	 *
	 * @return void
	 */
	function _setJSONRequestArgument() {
		if($this->getRequestMethod() != 'JSON') return;

		$params = array();
		parse_str($GLOBALS['HTTP_RAW_POST_DATA'],$params);

		foreach($params as $key => $val) {
			$val = $this->_filterRequestVar($key, $val,0);
			$this->set($key, $val, true);
		}
	}

	/**
	 * Handle request arguments for XML RPC
	 *
	 * @return void
	 */
	function _setXmlRpcArgument() {
		if($this->getRequestMethod() != 'XMLRPC') return;
		$oXml = new XmlParser();
		$xml_obj = $oXml->parse();

		$params = $xml_obj->methodcall->params;
		unset($params->node_name);

		unset($params->attrs);
		if(!count($params)) return;
		foreach($params as $key => $obj) {
			$val = $this->_filterRequestVar($key, $obj->body,0);
			$this->set($key, $val, true);
		}
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
	function _filterRequestVar($key, $val, $do_stripslashes = 1) {
		$isArray = TRUE;
		if(!is_array($val))
		{
			$isArray = FALSE;
			$val = array($val);
		}

		foreach($val as $k => $v)
		{
			if($key === 'page' || $key === 'cpage' || substr($key, -3) === 'srl')
			{
				$val[$k] = !preg_match('/^[0-9,]+$/', $v) ? (int)$v : $v;
			}
			elseif($key === 'mid' || $key === 'vid' || $key === 'search_keyword')
			{
				$val[$k] = htmlspecialchars($v);
			}
			else
			{
				if($do_stripslashes && version_compare(PHP_VERSION, '5.9.0', '<') && get_magic_quotes_gpc())
				{
					$v = stripslashes($v);
				}

				if(!is_array($v))
				{
					$val[$k] = trim($v);
				}
			}
		}

		if($isArray)
		{
			return $val;
		}
		else
		{
			return $val[0];
		}
	}

	/**
	 * Check if there exists uploaded file
	 *
	 * @return bool True: exists, False: otherwise
	 */
	function isUploaded() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->is_uploaded;
	}

	/**
	 * Handle uploaded file
	 *
	 * @return void
	 */
	function _setUploadedArgument() {
		if($this->getRequestMethod() != 'POST') return;
		if(!preg_match('/multipart\/form-data/i',$_SERVER['CONTENT_TYPE'])) return;
		if(!$_FILES) return;

		foreach($_FILES as $key => $val) {
			$tmp_name = $val['tmp_name'];
			if(!is_array($tmp_name)){
				if(!$tmp_name || !is_uploaded_file($tmp_name)) continue;
				$val['name'] = htmlspecialchars($val['name']);
				$this->set($key, $val, true);
				$this->is_uploaded = true;
			}else {
				for($i=0;$i< count($tmp_name);$i++){
					if($val['size'][$i] > 0){
						$file['name']=$val['name'][$i];
						$file['type']=$val['type'][$i];
						$file['tmp_name']=$val['tmp_name'][$i];
						$file['error']=$val['error'][$i];
						$file['size']=$val['size'][$i];
						$files[] = $file;
					}
				}
				$this->set($key, $files, true);
			}
		}
	}

	/**
	 * Return request method
	 * @return string Request method type. (Optional - GET|POST|XMLRPC|JSON)
	 */
	function getRequestMethod() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->request_method;
	}

	/**
	 * Return request URL
	 * @return string request URL
	 */
	function getRequestUrl() {
		static $url = null;
		if(is_null($url)) {
			$url = Context::getRequestUri();
			if(count($_GET))
			{
				foreach($_GET as $key => $val)
				{
					$vars[] = $key . '=' . ($val ? urlencode(Context::convertEncodingStr($val)) : '');
				}
				$url .= '?' . join('&', $vars);
			}
		}
		return $url;
	}

	/**
	 * Make URL with args_list upon request URL
	 *
	 * @param int $num_args Arguments nums
	 * @param array $args_list Argument list for set url
	 * @param string $domain Domain
	 * @param bool $encode If true, use url encode.
	 * @param bool $autoEncode If true, url encode automatically, detailed. Use this option, $encode value should be true
	 * @return string URL
	 */
	function getUrl($num_args=0, $args_list=array(), $domain = null, $encode = true, $autoEncode = false) {
		static $site_module_info = null;
		static $current_info = null;

		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		// retrieve virtual site information
		if(is_null($site_module_info)) $site_module_info = Context::get('site_module_info');

		// If $domain is set, handle it (if $domain is vid type, remove $domain and handle with $vid)
		if($domain && isSiteID($domain)) {
			$vid = $domain;
			$domain = '';
		}

		// If $domain, $vid are not set, use current site information
		if(!$domain && !$vid) {
			if($site_module_info->domain && isSiteID($site_module_info->domain)) $vid = $site_module_info->domain;
			else $domain = $site_module_info->domain;
		}

		// if $domain is set, compare current URL. If they are same, remove the domain, otherwise link to the domain.
		if($domain) {
			$domain_info = parse_url($domain);
			if(is_null($current_info)) $current_info = parse_url(($_SERVER['HTTPS']=='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].getScriptPath());
			if($domain_info['host'].$domain_info['path']==$current_info['host'].$current_info['path']) {
				unset($domain);
			} else {
				$domain = preg_replace('/^(http|https):\/\//i','', trim($domain));
				if(substr($domain,-1) != '/') $domain .= '/';
			}
		}

		$get_vars = null;

		// If there is no GET variables or first argument is '' to reset variables
		if(!$self->get_vars || $args_list[0]=='') {
			// rearrange args_list
			if(is_array($args_list) && $args_list[0]=='') array_shift($args_list);
		} else {
			// Otherwise, make GET variables into array
			$get_vars = get_object_vars($self->get_vars);
		}

		// arrange args_list
		for($i=0,$c=count($args_list);$i<$c;$i=$i+2) {
			$key = $args_list[$i];
			$val = trim($args_list[$i+1]);

			// If value is not set, remove the key
			if(!isset($val) || !strlen($val)) {
			  unset($get_vars[$key]);
			  continue;
			}
			// set new variables
			$get_vars[$key] = $val;
		}

		// remove vid, rnd
		unset($get_vars['rnd']);
		if($vid) $get_vars['vid'] = $vid;
		else unset($get_vars['vid']);

		// for compatibility to lower versions
		$act = $get_vars['act'];
		$act_alias = array(
			'dispMemberFriend'=>'dispCommunicationFriend',
			'dispMemberMessages'=>'dispCommunicationMessages',
			'dispDocumentAdminManageDocument'=>'dispDocumentManageDocument',
			'dispModuleAdminSelectList'=>'dispModuleSelectList'
		);
		if($act_alias[$act]) $get_vars['act'] = $act_alias[$act];

		// organize URL
		$query = '';
		if(count($get_vars)) {
			// if using rewrite mod
			if($self->allow_rewrite) {
				$var_keys = array_keys($get_vars);
				sort($var_keys);

				$target = implode('.', $var_keys);

				$act = $get_vars['act'];
				$vid = $get_vars['vid'];
				$mid = $get_vars['mid'];
				$key = $get_vars['key'];
				$srl = $get_vars['document_srl'];

				$tmpArray = array('rss'=>1, 'atom'=>1, 'api'=>1);
				$is_feed = isset($tmpArray[$act]);

				$target_map = array(
					'vid'=>$vid,
					'mid'=>$mid,
					'mid.vid'=>"$vid/$mid",

					'entry.mid'    =>"$mid/entry/".$get_vars['entry'],
					'entry.mid.vid'=>"$vid/$mid/entry/".$get_vars['entry'],

					'document_srl'=>$srl,
					'document_srl.mid'=>"$mid/$srl",
					'document_srl.vid'=>"$vid/$srl",
					'document_srl.mid.vid'=>"$vid/$mid/$srl",

					'act.mid'    =>$is_feed?"$mid/$act":'',
					'act.mid.vid'=>$is_feed?"$vid/$mid/$act":'',
					'act.document_srl.key'    =>($act=='trackback')?"$srl/$key/$act":'',
					'act.document_srl.key.mid'=>($act=='trackback')?"$mid/$srl/$key/$act":'',
					'act.document_srl.key.vid'=>($act=='trackback')?"$vid/$srl/$key/$act":'',
					'act.document_srl.key.mid.vid'=>($act=='trackback')?"$vid/$mid/$srl/$key/$act":''
				);

				$query  = $target_map[$target];
			}

			if(!$query) {
				$queries = array();
				foreach($get_vars as $key => $val) {
					if(is_array($val) && count($val)) {
						foreach($val as $k => $v) $queries[] = $key.'['.$k.']='.urlencode($v);
					} else {
						$queries[] = $key.'='.@urlencode($val);
					}
				}
				if(count($queries)) $query = 'index.php?'.implode('&', $queries);
			}
		}

		// If using SSL always
		$_use_ssl = $self->get('_use_ssl');
		if($_use_ssl == 'always') {
			$query = $self->getRequestUri(ENFORCE_SSL, $domain).$query;
		// optional SSL use
		} elseif($_use_ssl == 'optional') {
			$ssl_mode = RELEASE_SSL;
			if($get_vars['act'] && $self->isExistsSSLAction($get_vars['act'])) $ssl_mode = ENFORCE_SSL;
			$query = $self->getRequestUri($ssl_mode, $domain).$query;
		// no SSL
		} else {
			// currently on SSL but target is not based on SSL
			if($_SERVER['HTTPS']=='on' ) $query = $self->getRequestUri(ENFORCE_SSL, $domain).$query;

			// if $domain is set
			else if($domain) $query = $self->getRequestUri(FOLLOW_REQUEST_SSL, $domain).$query;

			else $query = getScriptPath().$query;
		}

		if ($encode){
			if($autoEncode){
				$parsedUrl = parse_url($query);
				parse_str($parsedUrl['query'], $output);
				$encode_queries = array();
				foreach($output as $key=>$value){
					if (preg_match('/&([a-z]{2,}|#\d+);/', urldecode($value))){
						$value = urlencode(htmlspecialchars_decode(urldecode($value)));
					}
					$encode_queries[] = $key.'='.$value;
				}
				$encode_query = implode('&', $encode_queries);
				return htmlspecialchars($parsedUrl['path'].'?'.$encode_query);
			}
			else{
				return htmlspecialchars($query);
			}
		}else{
			return $query;		
		}
	}

	/**
	 * Return after removing an argument on the requested URL
	 *
	 * @param string $ssl_mode SSL mode
	 * @param string $domain Domain
	 * @retrun string converted URL
	 */
	function getRequestUri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null) {
		static $url = array();

		// HTTP Request가 아니면 패스
		if(!isset($_SERVER['SERVER_PROTOCOL'])) return ;
		if(Context::get('_use_ssl') == 'always') $ssl_mode = ENFORCE_SSL;

		if($domain) $domain_key = md5($domain);
		else $domain_key = 'default';

		if(isset($url[$ssl_mode][$domain_key])) return $url[$ssl_mode][$domain_key];

		$current_use_ssl = $_SERVER['HTTPS']=='on' ? true : false;

		switch($ssl_mode) {
			case FOLLOW_REQUEST_SSL: $use_ssl = $current_use_ssl; break;
			case ENFORCE_SSL: $use_ssl = true;  break;
			case RELEASE_SSL: $use_ssl = false; break;
		}

		if($domain) {
			$target_url = trim($domain);
			if(substr($target_url,-1) != '/') $target_url.= '/';
		} else {
			$target_url= $_SERVER['HTTP_HOST'].getScriptPath();
		}

		$url_info = parse_url('http://'.$target_url);

		if($current_use_ssl != $use_ssl)
		{
			unset($url_info['port']);
		}

		if($use_ssl) {
			$port = Context::get('_https_port');
			if($port && $port != 443)      $url_info['port'] = $port;
			elseif($url_info['port']==443) unset($url_info['port']);
		} else {
			$port = Context::get('_http_port');
			if($port && $port != 80)      $url_info['port'] = $port;
			elseif($url_info['port']==80) unset($url_info['port']);
		}

		$url[$ssl_mode][$domain_key] = sprintf('%s://%s%s%s',$use_ssl?'https':$url_info['scheme'], $url_info['host'], $url_info['port']&&$url_info['port']!=80?':'.$url_info['port']:'',$url_info['path']);

		return $url[$ssl_mode][$domain_key];
	}

	/**
	 * Set a context value with a key
	 *
	 * @param string $key Key
	 * @param string $val Value
	 * @param mixed $set_to_get_vars If not false, Set to get vars.
	 * @return void
	 */
	function set($key, $val, $set_to_get_vars=0) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->context->{$key} = $val;
		if($set_to_get_vars === false) return;
		if($val === NULL || $val === '')
		{
			unset($self->get_vars->{$key});
			return;
		}
		if($set_to_get_vars || $self->get_vars->{$key}) $self->get_vars->{$key} = $val;
	}

	/**
	 * Return key's value
	 *
	 * @param string $key Key
	 * @return string Key
	 */
	function get($key) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if(!isset($self->context->{$key})) return null;
		return $self->context->{$key};
	}

	/**
	 * Get one more vars in object vars with given arguments(key1, key2, key3,...)
	 *
	 * @return object
	 */
	function gets() {
		$num_args = func_num_args();
		if($num_args<1) return;
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$args_list = func_get_args();
		foreach($args_list as $v) {
			$output->{$v} = $self->get($v);
		}
		return $output;
	}

	/**
	 * Return all data
	 *
	 * @return object All data
	 */
	function getAll() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->context;
	}

	/**
	 * Return values from the GET/POST/XMLRPC
	 *
	 * @return Object Request variables.
	 */
	function getRequestVars() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if($self->get_vars) return clone($self->get_vars);
		return new stdClass;
	}


	/**
	 * Register if actions is to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
	 *
	 * @param string $action act name
	 * @return void
	 */
	function addSSLAction($action)
	{
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if(!is_readable($self->sslActionCacheFile))
		{
			$buff = '<?php if(!defined("__XE__"))exit;';
			FileHandler::writeFile($self->sslActionCacheFile, $buff);
		}

		if(!isset($self->ssl_actions[$action]))
		{
			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
			FileHandler::writeFile($self->sslActionCacheFile, $sslActionCacheString, 'a');
		}
	}

	/**
	 * Get SSL Action
	 *
	 * @return string act
	 */
	function getSSLActions() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->ssl_actions;
	}

	/**
	 * Check SSL action are existed
	 *
	 * @param string $action act name
	 * @return bool If SSL exists, return true.
	 */
	function isExistsSSLAction($action) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return isset($self->ssl_actions[$action]);
	}

	/**
	 * Normalize file path
	 *
	 * @deprecated
	 * @param string $file file path
	 * @return string normalized file path
	 */
	function normalizeFilePath($file) {
		if(strpos($file,'://')===false && $file{0}!='/' && $file{0}!='.') $file = './'.$file;
		$file = preg_replace('@/\./|(?<!:)\/\/@', '/', $file);
		while(strpos($file,'/../')) $file = preg_replace('/\/([^\/]+)\/\.\.\//s','/',$file,1);

		return $file;
	}

	/**
	 * Get abstract file url
	 *
	 * @deprecated
	 * @param string $file file path
	 * @return string Converted file path
	 */
	function getAbsFileUrl($file) {
		$file = Context::normalizeFilePath($file);
		if(strpos($file,'./')===0) $file = dirname($_SERVER['SCRIPT_NAME']).'/'.substr($file,2);
		elseif(strpos($file,'../')===0) $file = Context::normalizeFilePath(dirname($_SERVER['SCRIPT_NAME'])."/{$file}");

		return $file;
	}

	/**
	 * Load front end file
	 *
	 * @param array $args array
	 * case js : 
	 *		$args[0]: file name,
	 *		$args[1]: type (head | body),
	 *		$args[2]: target IE,
	 *		$args[3]: index
	 * case css : 
	 *		$args[0]: file name,
	 *		$args[1]: media,
	 *		$args[2]: target IE,
	 *		$args[3]: index
	 * @param bool $useCdn use cdn
	 * @param string $cdnPrefix cdn prefix
	 * @param string $cdnVersion cdn version
	 *
	 */
	function loadFile($args, $useCdn = false, $cdnPrefix = '', $cdnVersion = '')
	{
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if ($useCdn && !$cdnPrefix)
		{
			$cdnPrefix = __XE_CDN_PREFIX__;
			$cdnVersion = __XE_CDN_VERSION__;
		}

		$self->oFrontEndFileHandler->loadFile($args, $useCdn, $cdnPrefix, $cdnVersion);
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
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
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
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
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
	function addJsFile($file, $optimized = false, $targetie = '',$index=0, $type='head', $isRuleset = false, $autoPath = null) {
		if($isRuleset)
		{
			if (strpos($file, '#') !== false){
				$file = str_replace('#', '', $file);
				if (!is_readable($file)) $file = $autoPath;
			}
			$validator   = new Validator($file);
			$validator->setCacheDir('files/cache');
			$file = $validator->getJsPath();
		}

		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
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
	function unloadJsFile($file, $optimized = false, $targetie = '') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie);
	}

	/**
	 * Unload all javascript files
	 *
	 * @return void
	 */
	function unloadAllJsFiles() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles('js');
	}

	/**
	 * Add javascript filter
	 *
	 * @param string $path File path
	 * @param string $filename File name 
	 * @return void
	 */
	function addJsFilter($path, $filename) {
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
	function _getUniqueFileList($files) {
		ksort($files);
		$files = array_values($files);
		$filenames = array();
		$size = count($files);
		for($i = 0; $i < $size; ++ $i)
		{
			if(in_array($files[$i]['file'], $filenames)) unset($files[$i]);
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
	function getJsFile($type='head') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
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
	function addCSSFile($file, $optimized=false, $media='all', $targetie='',$index=0) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
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
	function unloadCSSFile($file, $optimized = false, $media = 'all', $targetie = '') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie, $media);
	}

	/**
	 * Unload all css files
	 *
	 * @return void
	 */
	function unloadAllCSSFiles() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles('css');
	}

	/**
	 * Return a list of css files
	 *
	 * @return array Returns css file list. Array contains file, media, targetie.
	 */
	function getCSSFile() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->oFrontEndFileHandler->getCssFileList();
	}

	/**
	 * Load javascript plugin
	 *
	 * @param string $plugin_name plugin name
	 * @return void
	 */
	function loadJavascriptPlugin($plugin_name) {
		static $loaded_plugins = array();

		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if($plugin_name == 'ui.datepicker') $plugin_name = 'ui';

		if($loaded_plugins[$plugin_name]) return;
		$loaded_plugins[$plugin_name] = true;

		$plugin_path = './common/js/plugins/'.$plugin_name.'/';
		$info_file   = $plugin_path.'plugin.load';
		if(!is_readable($info_file)) return;

		$list = file($info_file);
		foreach($list as $filename) {
			$filename = trim($filename);
			if(!$filename) continue;

			if(substr($filename,0,2)=='./') $filename = substr($filename,2);
			if(preg_match('/\.js$/i',  $filename))     $self->loadFile(array($plugin_path.$filename, 'body', '', 0), true);
			elseif(preg_match('/\.css$/i', $filename)) $self->loadFile(array($plugin_path.$filename, 'all', '', 0), true);
		}

		if(is_dir($plugin_path.'lang')) $self->loadLang($plugin_path.'lang');
	}

	/**
	 * Add html code before </head>
	 *
	 * @param string $header add html code before </head>.
	 * @return void
	 */
	function addHtmlHeader($header) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->html_header .= "\n".$header;
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </head>
	 */
	function getHtmlHeader() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->html_header;
	}

	/**
	 * Add css class to Html Body
	 *
	 * @param string $class_name class name
	 */
	function addBodyClass($class_name) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_class[] = $class_name;
	}

	/**
	 * Return css class to Html Body
	 *
	 * @return string Return class to html body
	 */
	function getBodyClass() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_class = array_unique($self->body_class);

		return count($self->body_class)?sprintf(' class="%s"', implode(' ',$self->body_class)):'';
	}

	/**
	 * Add html code after <body>
	 *
	 * @param string $header Add html code after <body>
	 */
	function addBodyHeader($header) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_header .= "\n".$header;
	}

	/**
	 * Returns added html code by addBodyHeader()
	 *
	 * @return string Added html code after <body>
	 */
	function getBodyHeader() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->body_header;
	}

	/**
	 * Add html code before </body>
	 *
	 * @param string $footer Add html code before </body>
	 */
	function addHtmlFooter($footer) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->html_footer .= ($self->Htmlfooter?"\n":'').$footer;
	}

	/**
	 * Returns added html code by addHtmlHeader()
	 *
	 * @return string Added html code before </body>
	 */
	function getHtmlFooter() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->html_footer;
	}

	/**
	 * Get config file
	 *
	 * @retrun string The path of the config file that contains database settings
	 */
	function getConfigFile() {
		return _XE_PATH_.'files/config/db.config.php';
	}

	/**
	 * Get FTP config file
	 *
	 * @return string The path of the config file that contains FTP settings
	 */
	function getFTPConfigFile() {
		return _XE_PATH_.'files/config/ftp.config.php';
	}

	/**
	 * Checks whether XE is installed
	 *
	 * @return bool True if the config file exists, otherwise false.
	 */
	function isInstalled() {
		return FileHandler::hasContent(Context::getConfigFile());
	}

	/**
	 * Transforms codes about widget or other features into the actual code, deprecatred
	 *
	 * @param string Transforms codes
	 * @return string Transforms codes
	 */
	function transContent($content) {
		return $content;
	}

	/**
	 * Check whether it is allowed to use rewrite mod
	 *
	 * @return bool True if it is allowed to use rewrite mod, otherwise false
	 */
	function isAllowRewrite() {
		$oContext = &Context::getInstance();
		return $oContext->allow_rewrite;
	}

	/**
	 * Converts a local path into an URL
	 *
	 * @param string $path URL path
	 * @return string Converted path
	 */
	function pathToUrl($path) {
		$xe   = _XE_PATH_;
		$path = strtr($path, "\\", "/");

		$base_url = preg_replace('@^https?://[^/]+/?@', '', Context::getRequestUri());

		$_xe   = explode('/', $xe);
		$_path = explode('/', $path);
		$_base = explode('/', $base_url);

		if(!$_base[count($_base)-1]) array_pop($_base);

		foreach($_xe as $idx=>$dir) {
			if($_path[0] != $dir) break;
			array_shift($_path);
		}

		$idx = count($_xe) - $idx - 1;
		while($idx--) {
			if(count($_base)) array_shift($_base);
			else array_unshift($_base, '..');
		}

		if(count($_base)) {
			array_unshift($_path, implode('/', $_base));
		}

		$path = '/'.implode('/', $_path);
		if(substr($path,-1)!='/') $path .= '/';
		return $path;
	}

	/**
	 * Get meta tag
	 * @return array The list of meta tags
	 */
	function getMetaTag() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if(!is_array($self->meta_tags)) $self->meta_tags = array();

		$ret = array();
		$map = &$self->meta_tags;

		foreach($map as $key=>$val) {
			list($name, $is_http_equiv) = explode("\t", $key);
			$ret[] = array('name'=>$name, 'is_http_equiv'=>$is_http_equiv, 'content' => $val);
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
	function addMetaTag($name, $content, $is_http_equiv = false) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$key = $name."\t".($is_http_equiv ? '1' : '0');
		$map = &$self->meta_tags;

		$map[$key] = $content;
	}
}
