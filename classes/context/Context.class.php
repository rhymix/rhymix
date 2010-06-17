<?php
    /**
    * @class Context
    * @author zero (zero@nzeo.com)
    * @brief  Manages Context such as request arguments/environment variables
    * @remarks It has dual method structure, easy-to use methods which can be called as Context::methodname(), 
    *          and methods called with static object. 
    **/

    define('FOLLOW_REQUEST_SSL',0);
    define('ENFORCE_SSL',1);
    define('RELEASE_SSL',2);

    class Context {

        var $allow_rewrite = false; ///< true: using rewrite mod, false: otherwise

        var $request_method = 'GET';///< request method(GET/POST/XMLRPC)
        var $response_method = '';  ///< response method(HTML/XMLRPC). If it's not set, it follows request method.

        var $context = NULL;        ///< conatins request parameters and environment variables

        var $db_info = NULL;        ///< DB info. 
        var $ftp_info = NULL;       ///< FTP info. 

        var $ssl_actions = array(); ///< list of actions to be sent via ssl (it is used by javascript xml handler for ajax)
        var $js_files = array();    ///< list of javascript files used for display
        var $css_files = array();   ///< list of css files used for display

        var $html_header = NULL;    ///< script codes in <head>..</head>
        var $body_class = array();  ///< classnames of <body> 
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
         * @brief return static context object (Singleton)
         * @return object
         * @remarks it's to use Context without declaration of an object
         **/
        function &getInstance() {
            static $theInstance;
            if(!isset($theInstance)) $theInstance = new Context();
            return $theInstance;
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

            $this->_setRequestMethod();

            $this->_setXmlRpcArgument();
            $this->_setJSONRequestArgument();
            $this->_setRequestArgument();
            $this->_setUploadedArgument();

            $this->_loadDBInfo();

            // If XE is installed, get virtual site information 
            if(Context::isInstalled()) {
                $oModuleModel = &getModel('module');
                $site_module_info = $oModuleModel->getDefaultMid();
                // if site_srl of site_module_info is 0 (default site), compare the domain to default_url of db_config
                if($site_module_info->site_srl == 0 && $site_module_info->domain != $this->db_info->default_url) {
                    $site_module_info->domain = $this->db_info->default_url;
                }

                Context::set('site_module_info', $site_module_info);
                if($site_module_info->site_srl && isSiteID($site_module_info->domain)) Context::set('vid', $site_module_info->domain, true);

                $this->db_info->lang_type = $site_module_info->default_language;
                if(!$this->db_info->lang_type) $this->db_info->lang_type = 'en';
            }

            // Load Language File 
            $lang_supported = $this->loadLangSelected();
            // Retrieve language type set in user's cookie 
			if($this->get('l')) {
				$this->lang_type = $this->get('l');
				if($_COOKIE['lang_type'] != $this->lang_type)
				{
					setcookie('lang_type', $this->lang_type);
				}
			}
            else if($_COOKIE['lang_type']) $this->lang_type = $_COOKIE['lang_type'];

            // If it's not exists, follow default language type set in db_info 
            if(!$this->lang_type) $this->lang_type = $this->db_info->lang_type;

            // if still lang_type has not been set or has not-supported type , set as English. 
            if(!$this->lang_type) $this->lang_type = "en";
            if(is_array($lang_supported)&&!isset($lang_supported[$this->lang_type])) $this->lang_type = 'en';

            Context::set('lang_supported', $lang_supported);
            $this->setLangType($this->lang_type);

            // load module module's language file according to language setting 
            $this->loadLang(_XE_PATH_.'modules/module/lang');

            // set session handler 
            if($this->db_info->use_db_session != 'N') {
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


            // set authentication information in Context and session  
            if(Context::isInstalled()) {
                $oMemberModel = &getModel('member');
                $oMemberController = &getController('member');

                // if signed in, validate it.
                if($oMemberModel->isLogged()) {
                    $oMemberController->setSessionInfo();
                } 
                elseif($_COOKIE['xeak']) { // check auto sign-in
                    $oMemberController->doAutologin();
                }

                $this->_set('is_logged', $oMemberModel->isLogged() );
                $this->_set('logged_info', $oMemberModel->getLoggedInfo() );
            }

            // load common language file 
            $this->lang = &$GLOBALS['lang'];
            $this->_loadLang(_XE_PATH_."common/lang/");

            // check if using rewrite module  
            if(file_exists(_XE_PATH_.'.htaccess')&&$this->db_info->use_rewrite == 'Y') $this->allow_rewrite = true;
            else $this->allow_rewrite = false;


            // set locations for javascript use
            if($_SERVER['REQUEST_METHOD'] == 'GET') {
                if($this->get_vars) {
                    foreach($this->get_vars as $key => $val) {
                        if(!$val) continue;
                        if(is_array($val)&&count($val)) {
                            foreach($val as $k => $v) {
                                $url .= ($url?'&':'').$key.'['.$k.']='.urlencode($v);
                            }
                        } else {
                            $url .= ($url?'&':'').$key.'='.urlencode($val);
                        }
                    }
                    Context::set('current_url',sprintf('%s?%s', $this->getRequestUri(), $url));
                } else {
                    Context::set('current_url',$this->getUrl());
                }
            } else {
                Context::set('current_url',$this->getRequestUri());
            }
            Context::set('request_uri',Context::getRequestUri());
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
         * @brief load DB information 
         * @return none
         **/
        function loadDBInfo() {
            $oContext = &Context::getInstance();
            return $oContext->_loadDBInfo();
        }

        /**
         * @brief load DB information 
         * @return none
         **/
        function _loadDBInfo() {
            if(!$this->isInstalled()) return;

            $db_config_file = $this->getConfigFile();
            if(file_exists($db_config_file)) @include($db_config_file);

            if(!$db_info->time_zone) $db_info->time_zone = date("O");
            if(!$db_info->use_optimizer || $db_info->use_optimizer != 'N') $db_info->use_optimizer = 'Y';
            else $db_info->use_optimizer = 'N';
            if(!$db_info->qmail_compatibility || $db_info->qmail_compatibility != 'Y') $db_info->qmail_compatibility = 'N';
            else $db_info->qmail_compatibility = 'Y';
            if(!$db_info->use_ssl) $db_info->use_ssl = 'none';

            $this->_setDBInfo($db_info);

            $GLOBALS['_time_zone'] = $db_info->time_zone;
            $GLOBALS['_qmail_compatibility'] = $db_info->qmail_compatibility;
            $this->set('_use_ssl', $db_info->use_ssl);
            if($db_info->http_port)
            {
                $this->set('_http_port',  $db_info->http_port);
            }
            if($db_info->https_port)
            {
                $this->set('_https_port',  $db_info->https_port);
            }
        }

        /**
         * @brief get DB's db_type
         * @return DB's db_type string
         **/
        function getDBType() {
            $oContext = &Context::getInstance();
            return $oContext->_getDBType();
        }

        /**
         * @brief get DB's db_type
         * @return DB's db_type string
         **/
        function _getDBType() {
            return $this->db_info->db_type;
        }

        /**
         * @brief set DB information 
         * @param[in] DB information object
         * @return none
         **/
        function setDBInfo($db_info) {
            $oContext = &Context::getInstance();
            $oContext->_setDBInfo($db_info);
        }

        /**
         * @brief set DB information 
         * @param[in] DB information object
         * @return none
         **/
        function _setDBInfo($db_info) {
            $this->db_info = $db_info;
        }

        /**
         * @brief get DB information 
         * @return DB information object
         **/
        function getDBInfo() {
            $oContext = &Context::getInstance();
            return $oContext->_getDBInfo();
        }

        /**
         * @brief get DB information 
         * @return DB information object
         **/
        function _getDBInfo() {
            return $this->db_info;
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
            if(is_null($lang_supported)) {
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
            if(is_null($lang_selected)) {
                $orig_lang_file = _XE_PATH_.'common/lang/lang.info';
                $selected_lang_file = _XE_PATH_.'files/config/lang_selected.info';
                if(!file_exists($selected_lang_file) || !filesize($selected_lang_file)) {
                    $old_selected_lang_file = _XE_PATH_.'files/cache/lang_selected.info';
                    if(file_exists($old_selected_lang_file)) {
                        FileHandler::copyFile($old_selected_lang_file, $selected_lang_file);
                        FileHandler::removeFile($old_selected_lang_file);
                    }
                }

                if(!file_exists($selected_lang_file) || !filesize($selected_lang_file)) {
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
         * @return true if module handleing is necessary in the control path of current request
         **/
        function checkSSO() {
            // pass if it's not GET request or XE is not yet installed
            if(isCrawler()) return true;
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
            $oContext = &Context::getInstance();
            return $oContext->_getFTPInfo();
        }

        /**
         * @brief get FTP information object
         * @return FTP information object
         **/
        function _getFTPInfo() {
            if(!$this->isFTPRegisted()) return null;

            $ftp_config_file = $this->getFTPConfigFile();
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
            $oContext = &Context::getInstance();
            $oContext->_addBrowserTitle($site_title);
        }

        /**
         * @brief add string to browser title 
         * @param[in] $site_title string to be added
         * @return none
         **/
        function _addBrowserTitle($site_title) {
            if($this->site_title) $this->site_title .= ' - '.$site_title;
            else $this->site_title .= $site_title;
        }

        /**
         * @brief set string to browser title 
         * @param[in] $site_title string to be set 
         * @return none
         **/
        function setBrowserTitle($site_title) {
            if(!$site_title) return;
            $oContext = &Context::getInstance();
            $oContext->_setBrowserTitle($site_title);
        }

        /**
         * @brief set string to browser title 
         * @param[in] $site_title string to be set 
         * @return none
         **/
        function _setBrowserTitle($site_title) {
            $this->site_title = $site_title;
        }

        /**
         * @brief get browser title
         * @return browser title string (htmlspecialchars applied) 
         **/
        function getBrowserTitle() {
            $oContext = &Context::getInstance();
            return htmlspecialchars($oContext->_getBrowserTitle());
        }

        /**
         * @brief get browser title
         * @return browser title string 
         **/
        function _getBrowserTitle() {
            $oModuleController = &getController('module');
            $oModuleController->replaceDefinedLangCode($this->site_title);
            return $this->site_title;
        }

        /**
         * @brief load language file according to language type 
         * @param[in] $path path of the language file
         * @return none 
         **/
        function loadLang($path) {
            $oContext = &Context::getInstance();
            $oContext->_loadLang($path);
        }

        /**
         * @brief load language file according to language type 
         * @param[in] $path path of the language file
         * @return none 
         * @remarks using $loaded_lang_files it does not load once-loaded files
         **/
        function _loadLang($path) {
            global $lang;
			if(!is_object($lang)) $lang = new stdClass;
            if(!$this->lang_type) return;
            if(substr($path,-1)!='/') $path .= '/';
            $filename = sprintf('%s%s.lang.php', $path, $this->lang_type);
            if(!file_exists($filename)) $filename = sprintf('%s%s.lang.php', $path, 'ko');
            if(!file_exists($filename)) return;
            if(!is_array($this->loaded_lang_files)) $this->loaded_lang_files = array();
            if(in_array($filename, $this->loaded_lang_files)) return;
            $this->loaded_lang_files[] = $filename;
            if(file_exists($filename)) @include($filename);
        }

        /**
         * @brief set lang_type
         * @return none
         **/
        function setLangType($lang_type = 'ko') {
            $oContext = &Context::getInstance();
            $oContext->_setLangType($lang_type);
            $_SESSION['lang_type'] = $lang_type;
        }

        /**
         * @brief set lang_type
         * @return none
         **/
        function _setLangType($lang_type = 'ko') {
            $this->lang_type = $lang_type;
            $this->_set('lang_type',$lang_type);
        }

        /**
         * @brief get lang_type
         * @return lang_type string
         **/
        function getLangType() {
            $oContext = &Context::getInstance();
            return $oContext->_getLangType();
        }

        /**
         * @brief get lang_type
         * @return lang_type string
         **/
        function _getLangType() {
            return $this->lang_type;
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

            for($i=0;$i<count($charset_list);$i++) {
                $charset = $charset_list[$i];
                $flag = true;
                foreach($obj as $key=>$val) {
                    if(!$val) continue;
                    if($val && iconv($charset,$charset,$val)!=$val) $flag = false;
                }
                if($flag == true) {
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
        function setResponseMethod($method = "HTML") {
            $oContext = &Context::getInstance();
            return $oContext->_setResponseMethod($method);
        }

        /**
         * @brief force to set response method
         * @param[in] $method response method (HTML/XMLRPC/JSON)
         * @return none
         **/
        function _setResponseMethod($method = "HTML") {
            $this->response_method = $method;
        }

        /*
         * @brief get reponse method
         * @return response method string (if it's not set, returns request method)
         */
        function getResponseMethod() {
            $oContext = &Context::getInstance();
            return $oContext->_getResponseMethod();
        }

        /*
         * @brief get reponse method
         * @return response method string (if it's not set, returns request method)
         */
        function _getResponseMethod() {
            if($this->response_method) return $this->response_method;

            $RequestMethod = $this->_getRequestMethod();
            if($RequestMethod=="XMLRPC") return "XMLRPC";
            else if($RequestMethod=="JSON") return "JSON";
            return "HTML";
        }

        /**
         * @brief determine request method (GET/POST/XMLRPC/JSON)
         * @param[in] $type request method
         * @return none
         **/
        function setRequestMethod($type) {
            $oContext = &Context::getInstance();
            $oContext->_setRequestMethod($type);
        }


        /**
         * @brief deteremine request method (GET/POST/XMLRPC/JSON)
         * @param[in] $type request method
         * @return none
         **/
        function _setRequestMethod($type = '') {
            if($type) return $this->request_method = $type;

            if(strpos($_SERVER['CONTENT_TYPE'],'json')) return $this->request_method = 'JSON';
            if($GLOBALS['HTTP_RAW_POST_DATA']) return $this->request_method = "XMLRPC";

            $this->request_method = $_SERVER['REQUEST_METHOD'];
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
                if($this->_getRequestMethod()=='GET'&&isset($_GET[$key])) $set_to_vars = true;
                elseif($this->_getRequestMethod()=='POST'&&isset($_POST[$key])) $set_to_vars = true;
                else $set_to_vars = false;
                $this->_set($key, $val, $set_to_vars);
            }
        }

        /**
         * @brief handle request arguments for JSON 
         * @return none
         **/
        function _setJSONRequestArgument() {
            if($this->_getRequestMethod() != 'JSON') return;

            $params = array();
            parse_str($GLOBALS['HTTP_RAW_POST_DATA'],$params);

            foreach($params as $key => $val) {
                $val = $this->_filterRequestVar($key, $val,0);
                $this->_set($key, $val, true);
            }
        }

        /**
         * @brief handle request arguments for XML RPC
         * @return none
         **/
        function _setXmlRpcArgument() {
            if($this->_getRequestMethod() != 'XMLRPC') return;
            $oXml = new XmlParser();
            $xml_obj = $oXml->parse();

            $params = $xml_obj->methodcall->params;
            unset($params->node_name);

            unset($params->attrs);
            if(!count($params)) return;
            foreach($params as $key => $obj) {
                $val = $this->_filterRequestVar($key, $obj->body,0);
                $this->_set($key, $val, true);
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
            $oContext = &Context::getInstance();
            return $oContext->_isUploaded();
        }

        /**
         * @brief Check if there exists uploaded file
         * @return true: exists, false: otherwise
         **/
        function _isUploaded() {
            return $this->is_uploaded;
        }

        /**
         * @brief handle uploaded file
         * @return none
         **/
        function _setUploadedArgument() {
            if($this->_getRequestMethod() != 'POST') return;
            if(!preg_match("/multipart\/form-data/i",$_SERVER['CONTENT_TYPE'])) return;
            if(!$_FILES) return;

            foreach($_FILES as $key => $val) {
                $tmp_name = $val['tmp_name'];
                if(!$tmp_name || !is_uploaded_file($tmp_name)) continue;
                $this->_set($key, $val, true);
                $this->is_uploaded = true;
            }
        }

        /**
         * @brief return request method (GET/POST/XMLRPC/JSON);
         * @return request method type
         **/
        function getRequestMethod() {
            $oContext = &Context::getInstance();
            return $oContext->_getRequestMethod();
        }

        /**
         * @brief return request method (GET/POST/XMLRPC/JSON);
         * @return request method type
         **/
        function _getRequestMethod() {
            return $this->request_method;
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
            $oContext = &Context::getInstance();
            return $oContext->_getUrl($num_args, $args_list, $domain, $encode);
        }

        /**
         * @brief make URL with args_list upon request URL
         * @return result URL
         **/
        function _getUrl($num_args=0, $args_list=array(), $domain = null, $encode = true) {
            static $site_module_info = null;
            static $current_info = null;

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
            if(!$this->get_vars || $args_list[0]=='') {
                // rearrange args_list
                if(is_array($args_list) && $args_list[0]=='') array_shift($args_list);
            } else {
                // Otherwise, make GET variables into array
                $get_vars = get_object_vars($this->get_vars);
            }

            // arrange args_list
            for($i=0,$c=count($args_list);$i<$c;$i=$i+2) {
                $key = $args_list[$i];
                $val = trim($args_list[$i+1]);

                // If value is not set, remove the key
                if(!isset($val) || strlen($val)<1) {
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
            switch($get_vars['act']) {
                case 'dispMemberFriend' : $get_vars['act'] = 'dispCommunicationFriend'; break;
                case 'dispMemberMessages' : $get_vars['act'] = 'dispCommunicationMessages'; break;
                case 'dispDocumentAdminManageDocument' : $get_vars['act'] = 'dispDocumentManageDocument'; break;
                case 'dispModuleAdminSelectList' : $get_vars['act'] = 'dispModuleSelectList'; break;
            }

            // organize URL
            $query = null;
            if($var_count = count($get_vars)) {
                // If using rewrite mod 
                if($this->allow_rewrite) {
                    $var_keys = array_keys($get_vars);
                    asort($var_keys);
                    $target = implode('.',$var_keys);
                    switch($target) {
                        case 'vid' : $query = $get_vars['vid']; break;
                        case 'mid' : $query = $get_vars['mid']; break;
                        case 'document_srl' : $query = $get_vars['document_srl']; break;
                        case 'document_srl.mid' : $query = $get_vars['mid'].'/'.$get_vars['document_srl']; break;
                        case 'entry.mid' : $query = $get_vars['mid'].'/entry/'.$get_vars['entry']; break;
                        case 'act.document_srl.key' : $query = $get_vars['act']=='trackback'?$get_vars['document_srl'].'/'.$get_vars['key'].'/'.$get_vars['act']:''; break;
                        case 'mid.vid' : $query = $get_vars['vid'].'/'.$get_vars['mid']; break;
                        case 'document_srl.vid' : $query = $get_vars['vid'].'/'.$get_vars['document_srl']; break;
                        case 'document_srl.mid.vid' : $query = $get_vars['vid'].'/'.$get_vars['mid'].'/'.$get_vars['document_srl']; break;
                        case 'entry.mid.vid' : $query = $get_vars['vid'].'/'.$get_vars['mid'].'/entry/'.$get_vars['entry']; break;
                        case 'act.document_srl.key.vid' : $query = $get_vars['act']=='trackback'?$get_vars['vid'].'/'.$get_vars['document_srl'].'/'.$get_vars['key'].'/'.$get_vars['act']:''; break;
                    }
                }

                if(!$query) {
                    foreach($get_vars as $key => $val) {
                        if(is_array($val) && count($val)) {
                            foreach($val as $k => $v) $query .= ($query?'&':'').$key.'['.$k.']='.urlencode($v);
                        } else {
                            $query .= ($query?'&':'').$key.'='.urlencode($val);
                        }
                    }
                    if($query) $query = '?'.$query;
                }
            }
            
            // If using SSL always
            if(Context::get('_use_ssl')=='always') {
                $query = $this->getRequestUri(ENFORCE_SSL, $domain).$query;
            // optional SSL use 
            } elseif(Context::get('_use_ssl')=='optional') {
                $ssl_mode = RELEASE_SSL;
                if($get_vars['act'] && $this->_isExistsSSLAction($get_vars['act'])) $ssl_mode = ENFORCE_SSL;
                $query = $this->getRequestUri($ssl_mode, $domain).$query;
            // no SSL 
            } else {
                // currently on SSL but target is not based on SSL
                if($_SERVER['HTTPS']=='on' ) $query = $this->getRequestUri(ENFORCE_SSL, $domain).$query;

                // if $domain is set 
                else if($domain) $query = $this->getRequestUri(FOLLOW_REQUEST_SSL, $domain).$query;

                else $query = getScriptPath().$query;
            }

            if($encode) return htmlspecialchars($query);
            return $query;
        }

        /**
         * @brief 요청이 들어온 URL에서 argument를 제거하여 return
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
                case FOLLOW_REQUEST_SSL :
                        if($current_use_ssl) $use_ssl = true;
                        else $use_ssl = false;
                    break;
                case ENFORCE_SSL :
                        $use_ssl = true;
                    break;
                case RELEASE_SSL :
                        $use_ssl = false;
                    break;
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
                if(Context::get("_https_port") && Context::get("_https_port") != 443) {
                    $url_info['port'] = Context::get("_https_port");
                }
                elseif($url_info['port']==443)
                {
                    unset($url_info['port']);
                }
            } else {
                if(Context::get("_http_port") && Context::get("_http_port") != 80) {
                    $url_info['port'] = Context::get("_http_port");
                }
                elseif($url_info['port']==80) 
                {
                    unset($url_info['port']);
                }
            }

            $url[$ssl_mode][$domain_key] = sprintf("%s://%s%s%s",$use_ssl?'https':$url_info['scheme'], $url_info['host'], $url_info['port']&&$url_info['port']!=80?':'.$url_info['port']:'',$url_info['path']);

            return $url[$ssl_mode][$domain_key];
        }

        /**
         * @brief key/val로 context vars 세팅
         **/
        function set($key, $val, $set_to_get_vars = false) {
            $oContext = &Context::getInstance();
            $oContext->_set($key, $val, $set_to_get_vars);
        }

        /**
         * @brief key/val로 context vars 세팅
         **/
        function _set($key, $val, $set_to_get_vars = false) {
            $this->context->{$key} = $val;
            if($set_to_get_vars || $this->get_vars->{$key}) $this->get_vars->{$key} = $val;
        }

        /**
         * @brief key값에 해당하는 값을 return
         **/
        function get($key) {
            $oContext = &Context::getInstance();
            return $oContext->_get($key);
        }

        /**
         * @brief key값에 해당하는 값을 return
         **/
        function _get($key) {
            return $this->context->{$key};
        }

        /**
         * @brief 받고자 하는 변수만 object에 입력하여 받음
         *
         * key1, key2, key3 .. 등의 인자를 주어 여러개의 변수를 object vars로 세팅하여 받을 수 있음
         **/
        function gets() {
            $num_args = func_num_args();
            if($num_args<1) return;
            $args_list = func_get_args();

            $oContext = &Context::getInstance();
            return $oContext->_gets($num_args, $args_list);
        }

        /**
         * @brief 받고자 하는 변수만 object에 입력하여 받음
         *
         * key1, key2, key3 .. 등의 인자를 주어 여러개의 변수를 object vars로 세팅하여 받을 수 있음
         **/
        function _gets($num_args, $args_list) {
            for($i=0;$i<$num_args;$i++) {
                $args = $args_list[$i];
                $output->{$args} = $this->_get($args);
            }
            return $output;
        }

        /**
         * @brief 모든 데이터를 return
         **/
        function getAll() {
            $oContext = &Context::getInstance();
            return $oContext->_getAll();
        }

        /**
         * @brief 모든 데이터를 return
         **/
        function _getAll() {
            return $this->context;
        }

        /**
         * @brief GET/POST/XMLRPC에서 넘어온 변수값을 return
         **/
        function getRequestVars() {
            $oContext = &Context::getInstance();
            return $oContext->_getRequestVars();
        }

        /**
         * @brief GET/POST/XMLRPC에서 넘어온 변수값을 return
         **/
        function _getRequestVars() {
            return clone($this->get_vars);
        }

        /**
         * @brief SSL로 인증되어야 할 action이 있을 경우 등록
         * common/js/xml_handler.js에서 이 action들에 대해서 https로 전송되도록 함
         **/
        function addSSLAction($action) {
            $oContext = &Context::getInstance();
            return $oContext->_addSSLAction($action);
        }

        function _addSSLAction($action) {
            if(in_array($action, $this->ssl_actions)) return;
            $this->ssl_actions[] = $action;
        }

        function getSSLActions() {
            $oContext = &Context::getInstance();
            return $oContext->_getSSLActions();
        }

        function _getSSLActions() {
            return $this->ssl_actions;
        }

        function isExistsSSLAction($action) {
            $oContext = &Context::getInstance();
            return $oContext->_isExistsSSLAction($action);
        }

        function _isExistsSSLAction($action) {
            return in_array($action, $this->ssl_actions);
        }

        /**
         * @brief js file을 추가
         **/
        function addJsFile($file, $optimized = true, $targetie = '',$index=null) {
            $oContext = &Context::getInstance();
            return $oContext->_addJsFile($file, $optimized, $targetie,$index);
        }

        /**
         * @brief js file을 추가
         **/
        function _addJsFile($file, $optimized = true, $targetie = '',$index=null) {
            if(strpos($file,'://')===false && $file{0}!='/' && $file{0}!='.') $file = './'.$file;
			$file = preg_replace('@/\./|(?<!:)\/\/@', '/', $file);
            while(strpos($file,'/../')) $file = preg_replace('/\/([^\/]+)\/\.\.\//s','/',$file,1);

            if(in_array($file, $this->js_files)) return;

            if(is_null($index)) $index=count($this->js_files);
            for($i=$index;array_key_exists($i,$this->js_files);$i++);
            $this->js_files[$i] = array('file' => $file, 'optimized' => $optimized, 'targetie' => $targetie);
        }

        /**
         * @brief js file을 제거
         **/
        function unloadJsFile($file, $optimized = true, $targetie = '') {
            $oContext = &Context::getInstance();
            return $oContext->_unloadJsFile($file, $optimized, $targetie);
        }

        /**
         * @brief js file을 제거
         **/
        function _unloadJsFile($file, $optimized, $targetie) {
            foreach($this->js_files as $key => $val) {
                if(realpath($val['file'])==realpath($file) && $val['optimized'] == $optimized && $val['targetie'] == $targetie) {
                    unset($this->js_files[$key]);
                    return;
                }
            }
        }

        /**
         * @brief 모든 JS File을 제거
         **/
        function unloadAllJsFiles() {
            $oContext = &Context::getInstance();
            return $oContext->_unloadAllJsFiles();
        }

        function _unloadAllJsFiles() {
            $this->js_files = array();
        }

        /**
         * @brief javascript filter 추가
         **/
        function addJsFilter($path, $filename) {
            $oXmlFilter = new XmlJSFilter($path, $filename);
            $oXmlFilter->compile();
        }

        /**
         * @brief array_unique와 동작은 동일하나 file 첨자에 대해서만 동작함
         **/
        function _getUniqueFileList($files) {
            ksort($files);
            $files = array_values($files);
            $filenames = array();
            $size = count($files);
            for($i = 0; $i < $size; ++ $i)
            {
                if(in_array($files[$i]['file'], $filenames))
                    unset($files[$i]);
                $filenames[] = $files[$i]['file'];
            }

            return $files;
        }

        /**
         * @brief js file 목록을 return
         **/
        function getJsFile() {
            $oContext = &Context::getInstance();
            return $oContext->_getJsFile();
        }

        /**
         * @brief js file 목록을 return
         **/
        function _getJsFile() {
            require_once(_XE_PATH_."classes/optimizer/Optimizer.class.php");
            $oOptimizer = new Optimizer();
            return $oOptimizer->getOptimizedFiles($this->_getUniqueFileList($this->js_files), "js");
        }

        /**
         * @brief CSS file 추가
         **/
        function addCSSFile($file, $optimized = true, $media = 'all', $targetie = '',$index = null) {
            $oContext = &Context::getInstance();
            return $oContext->_addCSSFile($file, $optimized, $media, $targetie,$index);
        }

        /**
         * @brief CSS file 추가
         **/
        function _addCSSFile($file, $optimized = true, $media = 'all', $targetie = '', $index = null) {
            if(strpos($file,'://')===false && substr($file,0,1)!='/' && substr($file,0,1)!='.') $file = './'.$file;
            $file = str_replace(array('/./','//'),'/',$file);
            while(strpos($file,'/../')) $file = preg_replace('/\/([^\/]+)\/\.\.\//s','/',$file,1);
            
            if(in_array($file, $this->css_files)) return;

            if(is_null($index)) $index=count($this->css_files);
            for($i=$index;array_key_exists($i,$this->css_files);$i++);

            //if(preg_match('/^http:\/\//i',$file)) $file = str_replace(realpath("."), ".", realpath($file));
            $this->css_files[$i] = array('file' => $file, 'optimized' => $optimized, 'media' => $media, 'targetie' => $targetie);
        }

        /**
         * @brief css file을 제거
         **/
        function unloadCSSFile($file, $optimized = true, $media = 'all', $targetie = '') {
            $oContext = &Context::getInstance();
            return $oContext->_unloadCSSFile($file, $optimized, $media, $targetie);
        }

        /**
         * @brief css file을 제거
         **/
        function _unloadCSSFile($file, $optimized, $media, $targetie) {
            foreach($this->css_files as $key => $val) {
                if(realpath($val['file'])==realpath($file) && $val['optimized'] == $optimized && $val['media'] == $media && $val['targetie'] == $targetie) {
                    unset($this->css_files[$key]);
                    return;
                }
            }
        }

        /**
         * @brief 모든 CSS File을 제거
         **/
        function unloadAllCSSFiles() {
            $oContext = &Context::getInstance();
            return $oContext->_unloadAllCSSFiles();
        }

        function _unloadAllCSSFiles() {
            $this->css_files = array();
        }

        /**
         * @brief CSS file 목록 return
         **/
        function getCSSFile() {
            $oContext = &Context::getInstance();
            return $oContext->_getCSSFile();
        }

        /**
         * @brief CSS file 목록 return
         **/
        function _getCSSFile() {
            require_once(_XE_PATH_."classes/optimizer/Optimizer.class.php");
            $oOptimizer = new Optimizer();
            return $oOptimizer->getOptimizedFiles($this->_getUniqueFileList($this->css_files), "css");
        }

        /**
         * @brief javascript plugin load
         **/
        function loadJavascriptPlugin($plugin_name) {
            $oContext = &Context::getInstance();
            return $oContext->_loadJavascriptPlugin($plugin_name);
        }

        function _loadJavascriptPlugin($plugin_name) {
            static $loaded_plugins = array();
            if($loaded_plugins[$plugin_name]) return;
            $loaded_plugins[$plugin_name] = true;
			if($plugin_name == "ui.datepicker") return $this->_loadJavascriptPlugin("ui");

            $plugin_path = './common/js/plugins/'.$plugin_name.'/';
            if(!is_dir($plugin_path)) return;

            $info_file = $plugin_path.'plugin.load';
            if(!file_exists($info_file)) return;

            $list = file($info_file);
            for($i=0,$cnt=count($list);$i<$cnt;$i++) {
                $filename = trim($list[$i]);
                if(!$filename) continue;
                if(substr($filename,0,2)=='./') $filename = substr($filename,2);
                if(preg_match('/\.js$/i',$filename)) $this->_addJsFile($plugin_path.$filename, true, '', null);
                elseif(preg_match('/\.css$/i',$filename)) $this->_addCSSFile($plugin_path.$filename, true, 'all','', null);
            }

            if(is_dir($plugin_path.'lang')) $this->_loadLang($plugin_path.'lang');
        }

        /**
         * @brief HtmlHeader 추가
         **/
        function addHtmlHeader($header) {
            $oContext = &Context::getInstance();
            return $oContext->_addHtmlHeader($header);
        }

        /**
         * @brief HtmlHeader 추가
         **/
        function _addHtmlHeader($header) {
            $this->html_header .= "\n".$header;
        }

        /**
         * @brief HtmlHeader return
         **/
        function getHtmlHeader() {
            $oContext = &Context::getInstance();
            return $oContext->_getHtmlHeader();
        }

        /**
         * @brief HtmlHeader return
         **/
        function _getHtmlHeader() {
            return $this->html_header;
        }

        /**
         * @brief Html Body에 css class 추가
         **/
        function addBodyClass($class_name) {
            $oContext = &Context::getInstance();
            return $oContext->_addBodyClass($class_name);
        }

        /**
         * @brief Html Body에 css class 추가
         **/
        function _addBodyClass($class_name) {
	    $this->body_class[] = $class_name;
        }

        /**
         * @brief Html Body에 css class return
         **/
        function getBodyClass() {
            $oContext = &Context::getInstance();
            return $oContext->_getBodyClass();
        }

        /**
         * @brief Html Body에 css class return
         **/
        function _getBodyClass() {
	    $this->body_class = array_unique($this->body_class);
	    if(count($this->body_class)>0) return sprintf(' class="%s"', join(' ',$this->body_class));
            else return '';
        }


        /**
         * @brief BodyHeader 추가
         **/
        function addBodyHeader($header) {
            $oContext = &Context::getInstance();
            return $oContext->_addBodyHeader($header);
        }

        /**
         * @brief BodyHeader 추가
         **/
        function _addBodyHeader($header) {
            $this->body_header .= "\n".$header;
        }

        /**
         * @brief BodyHeader return
         **/
        function getBodyHeader() {
            $oContext = &Context::getInstance();
            return $oContext->_getBodyHeader();
        }

        /**
         * @brief BodyHeader return
         **/
        function _getBodyHeader() {
            return $this->body_header;
        }

        /**
         * @brief HtmlFooter 추가
         **/
        function addHtmlFooter($footer) {
            $oContext = &Context::getInstance();
            return $oContext->_addHtmlFooter($footer);
        }

        /**
         * @brief HtmlFooter 추가
         **/
        function _addHtmlFooter ($footer) {
            $this->html_footer .= ($this->Htmlfooter?"\n":"").$footer;
        }

        /**
         * @brief HtmlFooter return
         **/
        function getHtmlFooter() {
            $oContext = &Context::getInstance();
            return $oContext->_getHtmlFooter();
        }

        /**
         * @brief HtmlFooter return
         **/
        function _getHtmlFooter() {
            return $this->html_footer;
        }

        /**
         * @brief db설정내용이 저장되어 있는 config file의 path를 return
         **/
        function getConfigFile() {
            return _XE_PATH_."files/config/db.config.php";
        }

        /**
         * @brief ftp설정내용이 저장되어 있는 config file의 path를 return
         **/
        function getFTPConfigFile() {
            return _XE_PATH_."files/config/ftp.config.php";
        }

        /**
         * @brief 설치가 되어 있는지에 대한 체크
         *
         * 단순히 db config 파일의 존재 유무로 설치 여부를 체크한다
         **/
        function isInstalled() {
            return file_exists(Context::getConfigFile()) && filesize(Context::getConfigFile());
        }

        /**
         * @brief 내용의 위젯이나 기타 기능에 대한 code를 실제 code로 변경
         **/
        function transContent($content) {
            return $content;
        }

        /**
         * @brief rewrite mod 사용에 대한 변수 return
         **/
        function isAllowRewrite() {
            $oContext = &Context::getInstance();
            return $oContext->allow_rewrite;
        }

		/**
		 * @brief 로컬 경로를 웹 경로로 변경
		 */
		function pathToUrl($path) {
			$xe   = _XE_PATH_;
			$path = strtr($path, "\\", "/");

			$base_url = preg_replace('@^https?://[^/]+/?@', '', Context::getRequestUri());

			$_xe   = explode('/', $xe);
			$_path = explode('/', $path);
			$_base = explode('/', $base_url);

			if (!$_base[count($_base)-1]) array_pop($_base);

			foreach($_xe as $idx=>$dir) {
				if($_path[0] != $dir) break;
				array_shift($_path);
			}

			$idx = count($_xe) - $idx - 1;
			while($idx--) {
				if (count($_base)) array_shift($_base);
				else array_unshift($_base, '..');
			}

			if (count($_base)) {
				array_unshift($_path, implode('/', $_base));
			}

			$path = '/'.implode('/', $_path);

			return $path;
		}
    }
?>
