<?php
    /**
    * @class Context 
    * @author zero (zero@nzeo.com)
    * @brief  Request Argument/환경변수등의 모든 Context를 관리
    *
    * Context 클래스는 Context::methodname() 처럼 쉽게 사용하기 위해 만들어진 객체를 받아서\n
    * 호출하는 구조를 위해 이중 method 구조를 가지고 있다.\n
    * php5에서 static variables를 사용하게 된다면 불필요한 구조를 제거할 수 있다.\n
    * php5 쓰고 싶어라.. ㅡ.ㅜ
    **/

    class Context {

        var $request_method = 'GET'; ///< @brief GET/POST/XMLRPC 중 어떤 방식으로 요청이 왔는지에 대한 값이 세팅. GET/POST/XML 3가지가 있음

        var $context = NULL; ///< @brief request parameter 및 각종 환경 변수등을 정리하여 담을 변수 

        var $db_info = NULL; ///< @brief DB 정보

        var $js_files = array(); ///< @brief display시에 사용하게 된 js files의 목록

        var $css_files = array(); ///< @brief display시에 사용하게 된 css files의 목록

        var $html_header = NULL; ///< @brief display시에 사용하게 된 <head>..</head>내의 스크립트. 거의 사용할 일은 없음

        /**
         * @brief 언어 정보
         *
         * 기본으로 ko. HTTP_USER_AGENT나 사용자의 직접 세팅(쿠키이용)등을 통해 변경됨
         **/
        var $lang_type = 'ko'; ///< 언어 종류
        var $lang = NULL; ///< 언어 데이터를 담고 있는 변수
        var $loaded_lang_files = array(); ///< 로딩된 언어파일의 목록 (재로딩을 피하기 위함)

        var $site_title = ''; ///< @brief 현 사이트의 browser title. Context::setBrowserTitle() 로 변경 가능

        var $get_vars = NULL; ///< @brief form이나 get으로 요청이 들어온 변수만 별도로 관리 

        var $is_uploaded = false; ///< @brief 첨부파일이 업로드 된 요청이였는지에 대한 체크 플래그

        /**
         * @brief Context 객체를  GLOBALS 변수에 생성
         *
         * Context는 어디서든 객체 선언없이 사용하기 위해서 static 하게 사용\n
         * php5라면 GLOBALS가 아닌 static으로 처리 가능
         **/
        function &getInstance() {
            if(!$GLOBALS['__ContextInstance__']) $GLOBALS['__ContextInstance__'] = new Context();
            return $GLOBALS['__ContextInstance__'];
        }

        /**
         * @brief DB정보, Request Argument등을 세팅
         *
         * Context::init()은 단 한번만 호출되어야 하며 init()시에
         * Request Argument, DB/언어/세션정보등의 모든 정보를 세팅한다
         **/
        function init() {
            // context 변수를 $GLOBALS의 변수로 지정
            $this->context = &$GLOBALS['__Context__'];
            $this->context->lang = &$GLOBALS['lang'];

            // 기본적인 DB정보 세팅
            $this->_loadDBInfo();

            // 기본 언어파일 로드
            $this->lang = &$GLOBALS['lang'];
            $this->_loadLang("./common/lang/");

            // Request Method 설정
            $this->_setRequestMethod();

            // Request Argument 설정
            $this->_setXmlRpcArgument();
            $this->_setRequestArgument();
            $this->_setUploadedArgument();

            // 인증관련 데이터를 Context에 설정
            $oMember = getModule('member','model');
            if($oMember->isLogged()) {
                $this->_set('is_logged', true);
                $this->_set('logged_info', $_SESSION['logged_info']);
            } else {
                $this->_set('is_logged', false);
                $this->_set('logged_info', NULL);
            }
        }

        /**
         * @brief DB 정보를 설정하고 DB Type과 DB 정보를 return
         **/
        function _loadDBInfo() {
            if(!$this->isInstalled()) return;

            // db 정보 설정
            $db_config_file = $this->getConfigFile();
            if(file_exists($db_config_file)) include $db_config_file;

            $this->_setDBInfo($db_info);
        }

        /**
         * @brief DB의 db_type을 return
         **/
        function getDBType() {
            $oContext = &Context::getInstance();
            return $oContext->_getDBType();
        }

        /**
         * @brief DB의 db_type을 return
         **/
        function _getDBType() {
            return $this->db_info->db_type;
        }

        /**
         * @brief DB 정보가 담긴 object를 return
         **/
        function setDBInfo($db_info) {
            $oContext = &Context::getInstance();
            $oContext->_setDBInfo($db_info);
        }

        /**
         * @brief DB 정보가 담긴 object를 return
         **/
        function _setDBInfo($db_info) {
            $this->db_info = $db_info;
        }

        /**
         * @brief DB 정보가 담긴 object를 return
         **/
        function getDBInfo() {
            $oContext = &Context::getInstance();
            return $oContext->_getDBInfo();
        }

        /**
         * @brief DB 정보가 담긴 object를 return
         **/
        function _getDBInfo() {
            return $this->db_info;
        }

        /**
         * @brief 사이트 title setting
         **/
        function setBrowserTitle($site_title) {
            $oContext = &Context::getInstance();
            $oContext->_setBrowserTitle($site_title);
        }

        /**
         * @brief 사이트 title setting
         **/
        function _setBrowserTitle($site_title) {
            $this->site_title = $site_title;
        }

        /**
         * @brief 사이트 title return
         **/
        function getBrowserTitle() {
            $oContext = &Context::getInstance();
            return $oContext->_getBrowserTitle();
        }

        /**
         * @brief 사이트 title return
         **/
        function _getBrowserTitle() {
            return $this->site_title;
        }

        /**
         * @brief 지정된 언어파일 로드
         **/
        function loadLang($path) {
            $oContext = &Context::getInstance();
            $oContext->_loadLang($path);
        }

        /**
         * @brief 지정된 언어파일 로드
         *
         * loaded_lang_files 변수를 이용하여 한번 로드된 파일을 다시 로드하지 않음
         **/
        function _loadLang($path) {
            global $lang;
            if(substr($path,-1)!='/') $path .= '/';
            $filename = sprintf('%s%s.lang.php', $path, $this->lang_type);
            if(!file_exists($filename)) $filename = sprintf('%slang/%s.lang.php', $path, $this->lang_type);
            if(!file_exists($filename)) return;
            if(in_array($filename, $this->loaded_lang_files)) return;
            $this->loaded_lang_files[] = $filename;
            include ($filename);
        }

        /**
         * @brief lang_type을 세팅 (기본 ko)
         **/
        function setLangType($lang_type = 'ko') {
            $oContext = &Context::getInstance();
            $oContext->_setLangType($lang);
        }

        /**
         * @brief lang_type을 세팅 (기본 ko)
         **/
        function _setLangType($lang_type = 'ko') {
        $this->lang_type = $lang_type;
        }

        /**
         * @brief lang_type을 return
         **/
        function getLangType() {
            $oContext = &Context::getInstance();
            return $oContext->_getLangType();
        }

        /**
         * @brief lang_type을 return
         **/
        function _getLangType() {
            return $this->lang_type;
        }

        /**
         * @brief code에 해당하는 문자열을 return
         *
         * 만약 code에 해당하는 문자열이 없다면 code를 그대로 리턴
         **/
        function getLang($code) {
            if($GLOBALS['lang']->{$code}) return $GLOBALS['lang']->{$code};
            return $code;
        }

        /**
         * @brief 직접 lang 변수에 데이터를 추가
         **/
        function setLang($code, $val) {
            $GLOBALS['lang']->{$code} = $val;
        }

        /**
         * @brief object내의 variables의 문자열을 utf8로 변경
         **/
        function convertEncoding($source_obj) {
            $charset_list = array(
            'UTF-8', 'EUC-KR', 'CP949', 'ISO-8859-1', 'EUC-JP', 'SHIFT_JIS', 'CP932',
            'EUC-CN', 'HZ', 'GBK', 'GB18030', 'EUC-TW', 'BIG5', 'CP950', 'BIG5-HKSCS',
            'ISO-2022-CN', 'ISO-2022-CN-EXT', 'ISO-2022-JP', 'ISO-2022-JP-2', 'ISO-2022-JP-1',
            'ISO-8859-6', 'ISO-8859-8', 'JOHAB', 'ISO-2022-KR', 'CP1255', 'CP1256', 'CP862',
            'ASCII', 'ISO-8859-1', 'ISO-8850-2', 'ISO-8850-3', 'ISO-8850-4', 'ISO-8850-5',
            'ISO-8850-7', 'ISO-8850-9', 'ISO-8850-10', 'ISO-8850-13', 'ISO-8850-14',
            'ISO-8850-15', 'ISO-8850-16', 'CP1250', 'CP1251', 'CP1252', 'CP1253', 'CP1254',
            'CP1257', 'CP850', 'CP866',
            );

            $obj = clone($source_obj);

            for($i=0;$i<count($charset_list);$i++) {
                $charset = $charset_list[$i];
                $flag = true;
                foreach($obj as $key=>$val) {
                    if(!$val) continue;
                    if($val && !iconv($charset,'UTF-8',$val)) $flag = false;
                }
                if($flag == true) {
                    foreach($obj as $key => $val) $obj->{$key} = iconv($charset,'UTF-8',$val);
                    return $obj;
                }
            }
        }

        /**
         * @brief request method가 어떤것인지 판단하여 저장 (GET/POST/XMLRPC)
         **/
        function _setRequestMethod() {
            if($GLOBALS['HTTP_RAW_POST_DATA']) $this->request_method = "XMLRPC";
            else $this->request_method = $_SERVER['REQUEST_METHOD'];
        }

        /**
         * @brief GET/POST방식일 경우 처리
         **/
        function _setRequestArgument() {
            if($this->_getRequestMethod() == 'XMLRPC') return;
            if(!count($_REQUEST)) return;
            foreach($_REQUEST as $key => $val) {
                if(is_array($val)) {
                    for($i=0;$i<count($val);$i++) {
                        if(get_magic_quotes_gpc()) $val[$i] = stripslashes($val[$i]);
                        $val[$i] = trim($val[$i]);
                    }
                } else {
                    if(get_magic_quotes_gpc()) $val = stripslashes($val);
                    $val = trim($val);
                }
                if(!$val) continue;

                if($this->_getRequestMethod()=='GET'&&$_GET[$key]) $set_to_vars = true;
                elseif($this->_getRequestMethod()=='POST'&&$_POST[$key]) $set_to_vars = true;
                else $set_to_vars = false;
                $this->_set($key, $val, $set_to_vars);
            }
        }

        /**
         * @brief XML RPC일때역시 
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
                $val = trim($obj->body);
                $this->_set($key, $val, true);
            }
        }

        /**
         * @brief 업로드 되었을 경우 return true
         **/
        function isUploaded() {
            $oContext = &Context::getInstance();
            return $oContext->_isUploaded();
        }

        /**
         * @brief 업로드 되었을 경우 return true
         **/
        function _isUploaded() {
            return $this->is_uploaded;
        }

        /**
         * @brief 업로드된 파일이 있을 경우도 역시 context에 통합 처리 (단 정상적인 업로드인지 체크)
         **/
        function _setUploadedArgument() {
            if($this->_getRequestMethod() != 'POST') return;
            if(!eregi("^multipart\/form-data", $_SERVER['CONTENT_TYPE'])) return;
            if(!$_FILES) return;

            foreach($_FILES as $key => $val) {
                $tmp_name = $val['tmp_name'];
                if(!$tmp_name || !is_uploaded_file($tmp_name)) continue;
                $this->_set($key, $val, true);
                $this->is_uploaded = true;
            }
        }

        /**
         * @brief Request Method값을 return (GET/POST/XMLRPC);
         **/
        function getRequestMethod() {
            $oContext = &Context::getInstance();
            return $oContext->_getRequestMethod();
        }

        /**
         * @brief Request Method값을 return (GET/POST/XMLRPC);
         **/
        function _getRequestMethod() {
            return $this->request_method;
        }

        /**
         * @brief 요청받은 url에 args_list를 적용하여 return
         **/
        function getUrl($num_args, $args_list) {
            $oContext = &Context::getInstance();
            return $oContext->_getUrl($num_args, $args_list);
        }

        /**
         * @brief 요청받은 url에 args_list를 적용하여 return
         **/
        function _getUrl($num_args, $args_list) {
            if(!is_object($this->get_vars)) $get_vars = null;
            else $get_vars = clone($this->get_vars);

            for($i=0;$i<$num_args;$i=$i+2) {
                $key = $args_list[$i];
                $val = $args_list[$i+1];
                $get_vars->{$key} = trim($val);
            }

            $var_count = count(get_object_vars($get_vars));
            if(!$var_count) return;

            foreach($get_vars as $key => $val) {
                if(!$val) continue;
                $url_list[] = sprintf("%s=%s",$key, $val);
            }

            preg_match("/([a-zA-Z\_]+)\.php/i", $_SERVER['PHP_SELF'], $match);
            $filename = $match[0];
            if($filename == 'index.php') $filename = '';

            return './'.$filename.'?'.htmlspecialchars(implode('&', $url_list));
        }

        /**
         * @brief 요청이 들어온 URL에서 argument를 제거하여 return
         **/
        function getRequestUri() {
            $hostname = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            if($port!=80) $hostname .= ":{$port}";
            $path = $_SERVER['REDIRECT_URL'];
            $path = $_SERVER['REDIRECT_URL']?$_SERVER['REDIRECT_URL']:preg_replace('/([a-zA-Z0-9\_]+).php/i','',$_SERVER['PHP_SELF']);
            return sprintf("http://%s%s",$hostname,$path);
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
         * @brief js file을 추가
         **/
        function addJsFile($file) {
            $oContext = &Context::getInstance();
            return $oContext->_addJsFile($file);
        }

        /**
         * @brief js file을 추가
         **/
        function _addJsFile($file) {
            if(in_array($file, $this->js_files)) return;
            $this->js_files[] = $file;
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
            return $this->js_files;
        }

        /**
         * @brief CSS file 추가
         **/
        function addCSSFile($file) {
            $oContext = &Context::getInstance();
            return $oContext->_addCSSFile($file);
        }

        /**
         * @brief CSS file 추가
         **/
        function _addCSSFile($file) {
            if(in_array($file, $this->css_files)) return;
            $this->css_files[] = $file;
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
            return $this->css_files;
        }

        /**
         * @brief HtmlHeader 추가
         **/
        function addHtmlHeader($file) {
            $oContext = &Context::getInstance();
            return $oContext->_addHtmlHeader($file);
        }

        /**
         * @brief HtmlHeader 추가
         **/
        function _addHtmlHeader($file) {
            $this->HtmlHeader .= ($this->HtmlHeader?"\n":"").$file;
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
            return $this->HtmlHeader;
        }

        /**
         * @brief db설정내용이 저장되어 있는 config file의 path를 return
         **/
        function getConfigFile() {
            return "./files/config/db.config.php";
        }

        /**
         * @brief 설치가 되어 있는지에 대한 체크
         *
         * 단순히 db config 파일의 존재 유무로 설치 여부를 체크한다
         **/
        function isInstalled() {
            return file_exists(Context::getConfigFile());
        }

    }
?>
