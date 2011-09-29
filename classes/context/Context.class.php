<?php
/**
 * @class Context
 * @author NHN (developers@xpressengine.com)
 * @brief  Manages Context such as request arguments/environment variables
 * @remarks It has dual method structure, easy-to use methods which can be called as Context::methodname(),
 *          and methods called with static object.
 **/

define('FOLLOW_REQUEST_SSL',0);
define('ENFORCE_SSL',1);
define('RELEASE_SSL',2);

class Context {

	var $allow_rewrite = false; ///< true: using rewrite mod, false: otherwise

	var $request_method  = 'GET'; ///< request method(GET/POST/XMLRPC)
	var $response_method = '';    ///< response method(HTML/XMLRPC). If it's not set, it follows request method.

	var $context  = NULL;       ///< conatins request parameters and environment variables

	var $db_info  = NULL;       ///< DB info.
	var $ftp_info = NULL;       ///< FTP info.

	var $ssl_actions = array(); ///< list of actions to be sent via ssl (it is used by javascript xml handler for ajax)
	var $oFrontEndFileHandler;

	var $html_header = NULL;    ///< script codes in <head>..</head>
	var $body_class  = array();  ///< classnames of <body>
	var $body_header = NULL;    ///< codes after <body>
	var $html_footer = NULL;    ///< codes before </body>

	var $path = '';             ///< path of Xpress Engine

	// language information - it is changed by HTTP_USER_AGENT or user's cookie
	var $lang_type = '';        ///< language type
	var $lang = NULL;           ///< contains language-specific data
	var $loaded_lang_files = array(); ///< list of loaded languages (to avoid re-loading them)

	var $site_title = '';       ///< site's browser title

	var $get_vars = NULL;       ///< variables from GET or form submit

	var $is_uploaded = false;   ///< true if attached file exists

	/**
	 * @brief returns static context object (Singleton)
	 * @return object
	 * @remarks it's to use Context without declaration of an object
	 **/
	function &getInstance() {
		static $theInstance = null;
		if(!$theInstance) $theInstance = new Context();

		return $theInstance;
	}

	/**
	 * @brief cunstructor
	 **/
	function Context()
	{
		$this->oFrontEndFileHandler = new FrontEndFileHandler();
	}

	/**
	 * @brief initialization, it sets DB information, request arguments and so on.
	 * @return none
	 * @remarks this function should be called only once
	 **/
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
		if(Context::isInstalled() && $this->db_info->use_db_session != 'N') {
			$oSessionModel = &getModel('session');
			$oSessionController = &getController('session');
			session_set_save_handler(
				array(&$oSessionController,"open"),
				array(&$oSessionController,"close"),
				array(&$oSessionModel,"read"),
				array(&$oSessionController,"write"),
				array(&$oSessionController,"destroy"),
				array(&$oSessionController,"gc")
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
					if(!$val) continue;
					if(is_array($val)&&count($val)) {
						foreach($val as $k => $v) {
							$url .= ($url?'&':'').$key.'['.$k.']='.urlencode($v);
						}
					} else {
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
	 * @brief finalize using resources, such as DB connection
	 * @return none
	 **/
	function close() {
		// Session Close
		if(function_exists('session_write_close')) session_write_close();

		// DB close
		$oDB = &DB::getInstance();
		if(is_object($oDB)&&method_exists($oDB, 'close')) $oDB->close();
	}

	/**
	 * @brief load the database information
	 * @return none
	 **/
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

		if(!$db_info->time_zone) $db_info->time_zone = date("O");
		$GLOBALS['_time_zone'] = $db_info->time_zone;

		if($db_info->qmail_compatibility != 'Y') $db_info->qmail_compatibility = 'N';
		$GLOBALS['_qmail_compatibility'] = $db_info->qmail_compatibility;

		if(!$db_info->use_ssl) $db_info->use_ssl = 'none';
		$this->set('_use_ssl', $db_info->use_ssl);

		if($db_info->http_port)  $self->set('_http_port', $db_info->http_port);
		if($db_info->https_port) $self->set('_https_port', $db_info->https_port);

		$self->setDBInfo($db_info);
	}

	/**
	 * @brief get DB's db_type
	 * @return DB's db_type string
	 **/
	function getDBType() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->db_info->master_db["db_type"];
	}

	/**
	 * @brief set DB information
	 * @param[in] DB information object
	 * @return none
	 **/
	function setDBInfo($db_info) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->db_info = $db_info;
	}

	/**
	 * @brief get DB information
	 * @return DB information object
	 **/
	function getDBInfo() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->db_info;
	}

	/**
	 * @brief return default URL
	 * @return default URL string
	 **/
	function getDefaultUrl() {
		$db_info = Context::getDBInfo();
		return $db_info->default_url;
	}

	/**
	 * @brief find supported languages
	 * @return array of supported languages
	 **/
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
	 * @brief find selected languages to serve in the site
	 * @return array of selected languages
	 **/
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
	 * @brief Single Sign On (SSO)
	 * @return true if module handling is necessary in the control path of current request
	 **/
	function checkSSO() {
		// pass if it's not GET request or XE is not yet installed
		if($this->db_info->use_sso != 'Y' || isCrawler()) return true;
		if(Context::getRequestMethod()!='GET' || !Context::isInstalled() || in_array(Context::get('act'),array('rss','atom'))) return true;

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
				header("location:".$redirect_url);
				return false;
			}
		// for sites requesting SSO validation
		} else {
			// result handling : set session_name()
			if(Context::get('SSOID')) {
				$session_name = Context::get('SSOID');
				setcookie(session_name(), $session_name);

				$url = preg_replace('/([\?\&])$/','',str_replace('SSOID='.$session_name,'',Context::getRequestUrl()));
				header("location:".$url);
				return false;
			// send SSO request
			} else if($_COOKIE['sso']!=md5(Context::getRequestUri()) && !Context::get('SSOID')) {
				setcookie('sso',md5(Context::getRequestUri()),0,'/');
				$url = sprintf("%s?default_url=%s", $default_url, base64_encode(Context::getRequestUrl()));
				header("location:".$url);
				return false;
			}
		}

		return true;
	}

	/**
	 * @biref check if FTP info is registered
	 * @return true: FTP information is registered, false: otherwise
	 **/
	function isFTPRegisted() {
		$ftp_config_file = Context::getFTPConfigFile();
		if(file_exists($ftp_config_file)) return true;
		return false;
	}

	/**
	 * @brief get FTP information object
	 * @return FTP information object
	 **/
	function getFTPInfo() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if(!$self->isFTPRegisted()) return null;

		$ftp_config_file = $self->getFTPConfigFile();
		@include($ftp_config_file);

		return $ftp_info;
	}

	/**
	 * @brief add string to browser title
	 * @param[in] $site_title string to be added
	 * @return none
	 **/
	function addBrowserTitle($site_title) {
		if(!$site_title) return;
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if($self->site_title) $self->site_title .= ' - '.$site_title;
		else $self->site_title = $site_title;
	}

	/**
	 * @brief set string to browser title
	 * @param[in] $site_title string to be set
	 * @return none
	 **/
	function setBrowserTitle($site_title) {
		if(!$site_title) return;
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->site_title = $site_title;
	}

	/**
	 * @brief get browser title
	 * @return browser title string (htmlspecialchars applied)
	 **/
	function getBrowserTitle() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$oModuleController = &getController('module');
		$oModuleController->replaceDefinedLangCode($self->site_title);

		return htmlspecialchars($self->site_title);
	}
	/**
	 * @deprecated
	 */
	function _getBrowserTitle() { return $this->getBrowserTitle(); }

	/**
	 * @brief load language file according to language type
	 * @param[in] $path path of the language file
	 * @return none
	 **/
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

	function _loadXmlLang($path) {
		if(substr($path,-1)!='/') $path .= '/';
		$file = $path.'lang.xml';

		$oXmlLangParser = new XmlLangParser($file, $this->lang_type);
		$file = $oXmlLangParser->compile();

		return $file;
	}

	function _loadPhpLang($path) {

		if(substr($path,-1)!='/') $path .= '/';
		$path_tpl = $path.'%s.lang.php';
		$file = sprintf($path_tpl, $self->lang_type);

		$langs = array('ko','en'); // this will be configurable.
		while(!is_readable($file) && $langs[0]) {
			$file = sprintf($path_tpl, array_shift($langs));
		}

		if(!is_readable($file)) return false;
		return $file;
	}

	/**
	 * @brief set lang_type
	 * @return none
	 **/
	function setLangType($lang_type = 'ko') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$self->lang_type = $lang_type;
		$self->set('lang_type', $lang_type);

		$_SESSION['lang_type'] = $lang_type;
	}

	/**
	 * @brief get lang_type
	 * @return lang_type string
	 **/
	function getLangType() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->lang_type;
	}

	/**
	 * @brief return string accoring to the inputed code
	 * @param[in] $code language variable name
	 * @return if string for the code exists returns it, otherwise returns original code
	 **/
	function getLang($code) {
		if(!$code) return;
		if($GLOBALS['lang']->{$code}) return $GLOBALS['lang']->{$code};
		return $code;
	}

	/**
	 * @brief set data to lang variable
	 * @return none
	 **/
	function setLang($code, $val) {
		$GLOBALS['lang']->{$code} = $val;
	}

	/**
	 * @brief convert strings of variables in $source_object into UTF-8
	 * @param[in] $source_obj object conatins strings to convert
	 * @return converted object
	 **/
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
				if($val && iconv($charset,$charset,$val)!=$val) $flag = false;
			}
			if($flag) {
				if($charset == 'UTF-8') return $obj;
				foreach($obj as $key => $val) $obj->{$key} = iconv($charset,'UTF-8',$val);
				return $obj;
			}
		}

		return $obj;
	}

	/**
	 * @brief convert strings into UTF-8
	 * @param[in] $str string to convert
	 * @return converted string
	 **/
	function convertEncodingStr($str) {
		$obj->str = $str;
		$obj = Context::convertEncoding($obj);
		return $obj->str;
	}

	/**
	 * @brief force to set response method
	 * @param[in] $method response method (HTML/XMLRPC/JSON)
	 * @return none
	 **/
	function setResponseMethod($method='HTML') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		$methods = array('HTML','XMLRPC','JSON');
		$self->response_method = in_array($method, $methods)?$method:$methods[0];
	}

	/*
	 * @brief get reponse method
	 * @return response method string (if it's not set, returns request method)
	 */
	function getResponseMethod() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if($self->response_method) return $self->response_method;

		$method  = $self->getRequestMethod();
		$methods = array('HTML','XMLRPC','JSON');

		return in_array($method, $methods)?$method:$methods[0];
	}

	/**
	 * @brief determine request method (GET/POST/XMLRPC/JSON)
	 * @param[in] $type request method
	 * @return none
	 **/
	function setRequestMethod($type) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		($type && $self->request_method=$type) or
		(strpos($_SERVER['CONTENT_TYPE'],'json') && $this->request_method='JSON') or
		($GLOBALS['HTTP_RAW_POST_DATA'] && $this->request_method='XMLRPC') or
		($self->request_method = $_SERVER['REQUEST_METHOD']);
	}

	/**
	 * @brief handle request areguments for GET/POST
	 * @return none
	 **/
	function _setRequestArgument() {
		if(!count($_REQUEST)) return;

		foreach($_REQUEST as $key => $val) {
			if($val === "" || Context::get($key)) continue;
			$val = $this->_filterRequestVar($key, $val);

			if($this->getRequestMethod()=='GET'&&isset($_GET[$key])) $set_to_vars = true;
			elseif($this->getRequestMethod()=='POST'&&isset($_POST[$key])) $set_to_vars = true;
			else $set_to_vars = false;

			$this->set($key, $val, $set_to_vars);
		}
	}

	/**
	 * @brief handle request arguments for JSON
	 * @return none
	 **/
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
	 * @brief handle request arguments for XML RPC
	 * @return none
	 **/
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
	 * @brief Filter request variable
	 * @param[in] $key variable key
	 * @param[in] $val variable value
	 * @param[in] $do_stripslashes whether to strip slashes
	 * @remarks cast variables, such as _srl, page, and cpage, into interger
	 * @return filtered value
	 **/
	function _filterRequestVar($key, $val, $do_stripslashes = 1) {
		if( ($key == "page" || $key == "cpage" || substr($key,-3)=="srl")) return !preg_match('/^[0-9,]+$/',$val)?(int)$val:$val;
		if(is_array($val) && count($val) ) {
			foreach($val as $k => $v) {
				if($do_stripslashes && version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $v = stripslashes($v);
				$v = trim($v);
				$val[$k] = $v;
			}
		} else {
			if($do_stripslashes && version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $val = stripslashes($val);
			$val = trim($val);
		}
		return $val;
	}

	/**
	 * @brief Check if there exists uploaded file
	 * @return true: exists, false: otherwise
	 **/
	function isUploaded() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->is_uploaded;
	}

	/**
	 * @brief handle uploaded file
	 * @return none
	 **/
	function _setUploadedArgument() {
		if($this->getRequestMethod() != 'POST') return;
		if(!preg_match("/multipart\/form-data/i",$_SERVER['CONTENT_TYPE'])) return;
		if(!$_FILES) return;

		foreach($_FILES as $key => $val) {
			$tmp_name = $val['tmp_name'];
			if(!$tmp_name || !is_uploaded_file($tmp_name)) continue;
			$this->set($key, $val, true);
			$this->is_uploaded = true;
		}
	}

	/**
	 * @brief return request method (GET/POST/XMLRPC/JSON);
	 * @return request method type
	 **/
	function getRequestMethod() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->request_method;
	}

	/**
	 * @brief return request URL
	 * @return request URL
	 **/
	function getRequestUrl() {
		static $url = null;
		if(is_null($url)) {
			$url = Context::getRequestUri();
			if(count($_GET)) {
				foreach($_GET as $key => $val) $vars[] = $key.'='.urlencode(Context::convertEncodingStr($val));
				$url .= '?'.implode('&',$vars);
			}
		}
		return $url;
	}

	/**
	 * @brief make URL with args_list upon request URL
	 * @return result URL
	 **/
	function getUrl($num_args=0, $args_list=array(), $domain = null, $encode = true) {
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

				$is_feed = in_array($act, array('rss', 'atom', 'api'));

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
					'act.document_srl.key.vid'=>($act=='trackback')?"$vid/$srl/$key/$act":''
				);

				$query  = $target_map[$target];
			}

			if(!$query) {
				$queries = array();
				foreach($get_vars as $key => $val) {
					if(is_array($val) && count($val)) {
						foreach($val as $k => $v) $queries[] = $key.'['.$k.']='.urlencode($v);
					} else {
						$queries[] = $key.'='.urlencode($val);
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

		return $encode?htmlspecialchars($query):$query;
	}

	/**
	 * @brief Return after removing an argument on the requested URL
	 **/
	function getRequestUri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null) {
		static $url = array();

		// HTTP Request가 아니면 패스
		if(!isset($_SERVER['SERVER_PROTOCOL'])) return ;
		if(Context::get('_use_ssl') == "always") $ssl_mode = ENFORCE_SSL;

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

		$url[$ssl_mode][$domain_key] = sprintf("%s://%s%s%s",$use_ssl?'https':$url_info['scheme'], $url_info['host'], $url_info['port']&&$url_info['port']!=80?':'.$url_info['port']:'',$url_info['path']);

		return $url[$ssl_mode][$domain_key];
	}

	/**
	 * @brief set a context value with a key
	 **/
	function set($key, $val, $set_to_get_vars=0) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->context->{$key} = $val;
		if($set_to_get_vars === false) return;
		if($set_to_get_vars || $self->get_vars->{$key}) $self->get_vars->{$key} = $val;
	}

	/**
	 * @brief return key value
	 **/
	function get($key) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();

		if(!isset($self->context->{$key})) return null;
		return $self->context->{$key};
	}

	/**
    * @brief get a specified var in object
    *
    * get one more vars in object vars with given arguments(key1, key2, key3,...)
	 **/
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
	 * @brief Return all data
	 **/
	function getAll() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->context;
	}

	/**
	 * @brief Return values from the GET/POST/XMLRPC
	 **/
	function getRequestVars() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return clone($self->get_vars);
	}

	/**
    * @brief Register if actions is to be encrypted by SSL
    * Those actions are sent to https in common/js/xml_handler.js
	 **/
	function addSSLAction($action) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if(in_array($action, $self->ssl_actions)) return;
		$self->ssl_actions[] = $action;
	}

	function getSSLActions() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->ssl_actions;
	}

	function isExistsSSLAction($action) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return in_array($action, $self->ssl_actions);
	}

	/**
	 * @brief normalize file path
	 * @return normalized file path
	 * @deprecated
	 */
	function normalizeFilePath($file) {
		if(strpos($file,'://')===false && $file{0}!='/' && $file{0}!='.') $file = './'.$file;
		$file = preg_replace('@/\./|(?<!:)\/\/@', '/', $file);
		while(strpos($file,'/../')) $file = preg_replace('/\/([^\/]+)\/\.\.\//s','/',$file,1);

		return $file;
	}

	/**
	 * @deprecated
	 **/
	function getAbsFileUrl($file) {
		$file = Context::normalizeFilePath($file);
		if(strpos($file,'./')===0) $file = dirname($_SERVER['SCRIPT_NAME']).'/'.substr($file,2);
		elseif(strpos($file,'../')===0) $file = Context::normalizeFilePath(dirname($_SERVER['SCRIPT_NAME'])."/{$file}");

		return $file;
	}

	/**
	 * @brief load front end file
	 * @params $args array
	 * case js
	 *		$args[0]: file name
	 *		$args[1]: type (head | body)
	 *		$args[2]: target IE
	 *		$args[3]: index
	 * case css
	 *		$args[0]: file name
	 *		$args[1]: media
	 *		$args[2]: target IE
	 *		$args[3]: index
	 **/
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

	function unloadFile($file, $targetIe = '', $media = 'all')
	{
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetIe, $media);
	}

	function unloadAllFiles($type = 'all')
	{
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadAllFiles($type);
	}

	/**
	 * @brief Add the js file
	 * @deprecated
	 **/
	function addJsFile($file, $optimized = false, $targetie = '',$index=0, $type='head') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->loadFile(array($file, $type, $targetie, $index));
	}

	/**
	 * @brief Remove the js file
	 * @deprecated
	 **/
	function unloadJsFile($file, $optimized = false, $targetie = '') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie);
	}

	/**
	 * @brief unload all javascript files
	 **/
	function unloadAllJsFiles() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadAllJsFiles();
	}

	/**
	 * @brief Add javascript filter
	 **/
	function addJsFilter($path, $filename) {
		$oXmlFilter = new XmlJSFilter($path, $filename);
		$oXmlFilter->compile();
	}
	/**
	 * @brief Same as array_unique but works only for file subscript
	 * @deprecated
 	 **/
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
	 * @brief returns the list of javascripts that matches the given type.
	 **/
	function getJsFile($type='head') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->oFrontEndFileHandler->getJsFileList($type);
	}

	/**
	 * @brief Add CSS file
	 * @deprecated
	 **/
	function addCSSFile($file, $optimized=false, $media='all', $targetie='',$index=0) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->loadFile(array($file, $media, $targetie, $index));
	}

	/**
	 * @brief Remove css file
	 * @deprecated
	 **/
	function unloadCSSFile($file, $optimized = false, $media = 'all', $targetie = '') {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadFile($file, $targetie, $media);
	}

	/**
	 * @brief unload all css files
	 **/
	function unloadAllCSSFiles() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->oFrontEndFileHandler->unloadAllCssFiles();
	}

	/**
	 * @brief return a list of css files
	 **/
	function getCSSFile() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->oFrontEndFileHandler->getCssFileList();
	}

	/**
	 * @brief javascript plugin load
	 **/
	function loadJavascriptPlugin($plugin_name) {
		static $loaded_plugins = array();

		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		if($plugin_name == 'ui.datepicker') $plugin_name = 'ui';

		if($loaded_plugins[$plugin_name]) return;
		$loaded_plugins[$plugin_name] = true;

		$plugin_path = "./common/js/plugins/$plugin_name/";
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
	 * @brief Add HtmlHeader
	 **/
	function addHtmlHeader($header) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->html_header .= "\n".$header;
	}

	/**
	 * @brief HtmlHeader return
	 **/
	function getHtmlHeader() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->html_header;
	}

	/**
	 * @brief Add css class to Html Body
	 **/
	function addBodyClass($class_name) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_class[] = $class_name;
	}

	/**
	 * @brief Return css class to Html Body
	 **/
	function getBodyClass() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_class = array_unique($self->body_class);

		return count($self->body_class)?sprintf(' class="%s"', implode(' ',$self->body_class)):'';
	}

	/**
	 * @brief add BodyHeader
	 **/
	function addBodyHeader($header) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->body_header .= "\n".$header;
	}

	/**
	 * @brief returns BodyHeader
	 **/
	function getBodyHeader() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->body_header;
	}

	/**
	 * @brief add HtmlFooter
	 **/
	function addHtmlFooter($footer) {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		$self->html_footer .= ($self->Htmlfooter?"\n":'').$footer;
	}

	/**
	 * @brief returns HtmlFooter
	 **/
	function getHtmlFooter() {
		is_a($this,'Context')?$self=&$this:$self=&Context::getInstance();
		return $self->html_footer;
	}

	/**
	 * @brief returns the path of the config file that contains database settings
	 **/
	function getConfigFile() {
		return _XE_PATH_."files/config/db.config.php";
	}

	/**
	 * @brief returns the path of the config file that contains FTP settings
	 **/
	function getFTPConfigFile() {
		return _XE_PATH_."files/config/ftp.config.php";
	}

	/**
	 * @brief Checks whether XE is installed
	 * @return true if the config file exists, otherwise false.
	 **/
	function isInstalled() {
		return FileHandler::hasContent(Context::getConfigFile());
	}

	/**
	 * @brief Transforms codes about widget or other features into the actual code, deprecatred
	 **/
	function transContent($content) {
		return $content;
	}

	/**
	 * @brief Check whether it is allowed to use rewrite mod
	 * @return true if it is allowed to use rewrite mod, otherwise false
	 **/
	function isAllowRewrite() {
		$oContext = &Context::getInstance();
		return $oContext->allow_rewrite;
	}

	/**
	 * @brief Converts a local path into an URL
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
}
?>
