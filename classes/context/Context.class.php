<?php
    /**
    * @class Context 
    * @author zero (zero@nzeo.com)
    * @brief  Request Argument/환경변수등의 모든 Context를 관리
    *
    * Context 클래스는 Context::methodname() 처럼 쉽게 사용하기 위해 만들어진 객체를 받아서
    * 호출하는 구조를 위해 이중 method 구조를 가지고 있다.
    * php5에서 static variables를 사용하게 된다면 불필요한 구조를 제거할 수 있다.
    * php5 쓰고 싶당.. ㅡ.ㅜ
    **/

    class Context {

        var $request_method = 'GET'; ///< @brief GET/POST/XMLRPC 중 어떤 방식으로 요청이 왔는지에 대한 값이 세팅. GET/POST/XML 3가지가 있음
        var $response_method = ''; ///< @brief HTML/XMLRPC 중 어떤 방식으로 결과를 출력할지 결정. (강제 지정전까지는 request_method를 따름)

        var $context = NULL; ///< @brief request parameter 및 각종 환경 변수등을 정리하여 담을 변수 

        var $db_info = NULL; ///< @brief DB 정보

        var $js_files = array(); ///< @brief display시에 사용하게 되는 js files의 목록

        var $css_files = array(); ///< @brief display시에 사용하게 되는 css files의 목록

        var $html_header = NULL; ///< @brief display시에 사용하게 되는 <head>..</head>내의 스크립트코드
        var $html_footer = NULL; ///< @brief display시에 사용하게 되는 </body> 바로 앞에 추가될 코드

        var $path = ''; ///< zbxe의 경로

        /**
         * @brief 언어 정보
         *
         * 기본으로 ko. HTTP_USER_AGENT나 사용자의 직접 세팅(쿠키이용)등을 통해 변경됨
         **/
        var $lang_type = ''; ///< 언어 종류
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
            $this->context->_COOKIE = $_COOKIE;

            // 기본적인 DB정보 세팅
            $this->_loadDBInfo();

            // 쿠키로 설정된 언어타입 가져오기 
            if($_COOKIE['lang_type']) $this->lang_type = $_COOKIE['lang_type'];

            // 등록된 기본 언어파일 찾기
            $lang_files = FileHandler::readDir('./common/lang');
            $accept_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach($lang_files as $key => $val) {
                list($lang_prefix) = explode('.',$val);
                $lang_supported[] = $lang_prefix;
                if(!$this->lang_type && ereg($lang_prefix, strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
                    $this->lang_type = $lang_prefix;
                    setcookie('lang_type', $this->lang_type, time()+60*60*24*365, '/');
                    break;
                }
            }

            Context::set('lang_supported', $lang_supported);

            $this->setLangType($this->lang_type);

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
            $oMember = getModel('member');
            if($oMember->isLogged()) {
                $this->_set('is_logged', true);
                $this->_set('logged_info', $_SESSION['logged_info']);
            } else {
                $this->_set('is_logged', false);
                $this->_set('logged_info', NULL);
            }

            // rewrite 모듈사용 상태 체크
            if(file_exists('./.htaccess')&&$this->db_info->use_rewrite == 'Y') $this->allow_rewrite = true;
            else $this->allow_rewrite = false;

            // 상대 경로 설정
            $this->path = $this->getRequestUri();

            // rewrite module때문에 javascript에서 location.href 문제 해결을 위해 직접 실제 경로 설정
            if($_SERVER['REQUEST_METHOD'] == 'GET') {
                if($this->get_vars) {
                    foreach($this->get_vars as $key => $val) {
                        if(!$val) continue;
                        $url .= ($url?'&':'').$key.'='.$val;
                    }
                    Context::set('current_url',sprintf('%s?%s',$this->path, $url));
                } else {
                    Context::set('current_url',$this->getUrl());
                }
            } else {
                Context::set('current_url',$this->getRequestUri());
            }
            Context::set('request_uri',Context::getRequestUri());
        }

        /**
         * @brief DB및 기타 자원들의 close
         **/
        function close() {
            // DB close
            $oDB = &DB::getInstance();
            if(is_object($oDB)&&method_exists($oDB, 'close')) $oDB->close();
        }

        /**
         * @brief DB 정보를 설정하고 DB Type과 DB 정보를 return
         **/
        function _loadDBInfo() {
            if(!$this->isInstalled()) return;

            // db 정보 설정
            $db_config_file = $this->getConfigFile();
            if(file_exists($db_config_file)) @include($db_config_file);

            $this->_setDBInfo($db_info);

            $GLOBALS['_time_zone'] = $db_info->time_zone;
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
            if(!$site_title) return;
            $oContext = &Context::getInstance();
            $oContext->_setBrowserTitle($site_title);
        }

        /**
         * @brief 사이트 title setting
         **/
        function _setBrowserTitle($site_title) {
            $this->site_title = htmlspecialchars($site_title);
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
            if(!file_exists($filename)) $filename = sprintf('%s%s.lang.php', $path, 'ko');
            if(!file_exists($filename)) return;
            if(!is_array($this->loaded_lang_files)) $this->loaded_lang_files = array();
            if(in_array($filename, $this->loaded_lang_files)) return;
            $this->loaded_lang_files[] = $filename;
            include($filename);
        }

        /**
         * @brief lang_type을 세팅 (기본 ko)
         **/
        function setLangType($lang_type = 'ko') {
            $oContext = &Context::getInstance();
            $oContext->_setLangType($lang_type);
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
            if(!$code) return;
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
         * @brief response method를 강제로 지정 (기본으로는 request method를 이용함)
         *
         * method의 종류에는 HTML/ TEXT/ XMLRPC가 있음
         **/
        function setResponseMethod($method = "HTML") {
            $oContext = &Context::getInstance();
            return $oContext->_setResponseMethod($method);
        }

        function _setResponseMethod($method = "HTML") {
            $this->response_method = $method;
        }

        /**
         * @brief response method 값을 return
         *
         * method의 종류에는 HTML/ TEXT/ XMLRPC가 있음
         * 별도로 response method를 지정하지 않았다면 request method로 판단하여 결과 return
         **/
        function getResponseMethod() {
            $oContext = &Context::getInstance();
            return $oContext->_getResponseMethod();
        }

        function _getResponseMethod() {
            if($this->response_method) return $this->response_method;

            if($this->_getRequestMethod()=="XMLRPC") return "XMLRPC";
            return "HTML";
        }

        /**
         * @brief request method가 어떤것인지 판단하여 저장 (GET/POST/XMLRPC)
         **/
        function setRequestMethod($type) {
            $oContext = &Context::getInstance();
            $oContext->_setRequestMethod($type);
        }


        /**
         * @brief request method가 어떤것인지 판단하여 저장 (GET/POST/XMLRPC)
         **/
        function _setRequestMethod($type = '') {
            if($type) return $this->request_method = $type;

            if($GLOBALS['HTTP_RAW_POST_DATA']) return $this->request_method = "XMLRPC";

            $this->request_method = $_SERVER['REQUEST_METHOD'];
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
         * @brief XML RPC일때
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
        function getUrl($num_args=0, $args_list=array()) {
            $oContext = &Context::getInstance();
            return $oContext->_getUrl($num_args, $args_list);
        }

        /**
         * @brief 요청받은 url에 args_list를 적용하여 return
         **/
        function _getUrl($num_args=0, $args_list=array()) {
            if(!$this->get_vars || $args_list[0]=='') {
                $get_vars = null;
                if($args_list[0]=='') {
                    array_shift($args_list);
                    $num_args = count($args_list);
                }
            } else {
                $get_vars = get_object_vars($this->get_vars);
            }

            for($i=0;$i<$num_args;$i=$i+2) {
                $key = $args_list[$i];
                $val = trim($args_list[$i+1]);
                if(!$val) unset($get_vars[$key]);
                else $get_vars[$key] = $val;
            }

            $var_count = count($get_vars);
            if(!$var_count) return '';

            // rewrite모듈을 사용하고 인자의 값이 4개 이하일 경우
            if($this->allow_rewrite && $var_count < 4) {
                $var_keys = array_keys($get_vars);

                if($var_count == 1) {
                    if($var_keys[0]=='mid') return $this->path.$get_vars['mid'];
                    elseif($var_keys[0]=='document_srl') return $this->path.$get_vars['document_srl'];
                } elseif($var_count == 2) {
                    asort($var_keys);
                    $target = implode('.',$var_keys);
                    if($target=='act.mid' && !ereg('([A-Z]+)',$get_vars['act'])) return sprintf('%s%s/%s',$this->path,$get_vars['mid'],$get_vars['act']);
                    elseif($target=='document_srl.mid')  return sprintf('%s%s/%s',$this->path,$get_vars['mid'],$get_vars['document_srl']);
                    elseif($target=='act.document_srl')  return sprintf('%s%s/%s',$this->path,$get_vars['document_srl'],$get_vars['act']);
                    elseif($target=='mid.page')  return sprintf('%s%s/page/%s',$this->path,$get_vars['mid'],$get_vars['page']);
                    elseif($target=='category.mid')  return sprintf('%s%s/category/%s',$this->path,$get_vars['mid'],$get_vars['category']);
                } elseif($var_count == 3) {
                    asort($var_keys);
                    $target = implode('.',$var_keys);
                    if($target=='category.mid.page') {
                        return sprintf('%s%s/category/%s/page/%s',$this->path,$get_vars['mid'],$get_vars['category'],$get_vars['page']);
                    } elseif($target=='mid.search_keyword.search_target' && $get_vars['search_target']=='tag') {
                        return sprintf('%s%s/tag/%s',$this->path,$get_vars['mid'],str_replace(' ','-',$get_vars['search_keyword']));
                    } elseif($target=='mid.search_keyword.search_target' && $get_vars['search_target']=='regdate') {
                        if(strlen($get_vars['search_keyword'])==8) return sprintf('%s%s/%04d/%02d/%02d',$this->path,$get_vars['mid'],substr($get_vars['search_keyword'],0,4),substr($get_vars['search_keyword'],4,2),substr($get_vars['search_keyword'],6,2));
                        elseif(strlen($get_vars['search_keyword'])==6) return sprintf('%s%s/%04d/%02d',$this->path,$get_vars['mid'],substr($get_vars['search_keyword'],0,4),substr($get_vars['search_keyword'],4,2));
                    }
                }
            }

            // rewrite 모듈을 사용하지 않고 인자의 값이 2개 이상이거나 rewrite모듈을 위한 인자로 적당하지 않을 경우
            foreach($get_vars as $key => $val) {
                if(!$val) continue;
                $url .= ($url?'&':'').$key.'='.$val;
            }

            return $this->path.'?'.htmlspecialchars($url);
        }

        /**
         * @brief 요청이 들어온 URL에서 argument를 제거하여 return
         **/
        function getRequestUri() {
            $hostname = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            if($port!=80) $hostname .= ":{$port}";
            $path = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
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

        /**
         * @brief 내용의 위젯이나 기타 기능에 대한 code를 실제 code로 변경
         **/
        function transContent($content) {
            // 위젯 코드 변경 
            $content = preg_replace_callback('!<img([^\>]*)widget=([^\>]*?)\>!is', array($this,'_transWidget'), $content);

            // 메타 파일 변경
            $content = preg_replace_callback('!<\!\-\-Meta:([^\-]*?)\-\->!is', array($this,'_transMeta'), $content);
            
            // 에디터 컴포넌트를 찾아서 결과 코드로 변환
            $content = preg_replace_callback('!<div([^\>]*)editor_component=([^\>]*)>(.*?)\<\/div\>!is', array($this,'_transEditorComponent'), $content);
            $content = preg_replace_callback('!<img([^\>]*)editor_component=([^\>]*?)\>!is', array($this,'_transEditorComponent'), $content);

            // body 내의 <style ..></style>를 header로 이동
            $content = preg_replace_callback('!<style(.*?)<\/style>!is', array($this,'_moveStyleToHeader'), $content);

            // <br> 코드 변환
            $content = preg_replace('/<br([^>\/]*)(\/>|>)/i','<br$1 />', $content);

            // 몇가지 대문자 태그를 소문자로 변경
            //$content = preg_replace_callback('!<(\/){0,1}([A-Z]+)([^>]*?)>!s',array($this,'_transTagToLowerCase'), $content);

            // <img ...> 코드를 <img ... /> 코드로 변환
            $content = preg_replace('/<img(.*?)(\/){0,1}>/i','<img$1 />', $content);

            // blogapi tool에서 삽입된 코드 삭제
            //$content = str_replace('atomicselection="true"','',$content);

            return $content;
        }

        /**
         * @brief IE위지윅에디터에서 태그가 대문자로 사용되기에 이를 소문자로 치환
         **/
        function _transTagToLowerCase($matches) {
            return sprintf('<%s%s%s>', $matches[1], strtolower($matches[2]), $matches[3]);
        }

        /**
         * @brief <!--Meta:파일이름.(css|js)-->를 변경
         **/
        function _transMeta($matches) {
            if(eregi('\.css$', $matches[1])) $this->addCSSFile($matches[1]);
            elseif(eregi('\.js$', $matches[1])) $this->addJSFile($matches[1]);
        }

        /**
         * @brief <body>내의 <style태그를 header로 이동
         **/
        function _moveStyleToHeader($matches) {
            $this->addHtmlHeader($matches[0]);
            return '';
        }

        /**
         * @brief 내용의 에디터 컴포넌트 코드를 변환
         **/
        function _fixQuotation($matches) {
            $key = $matches[1];
            $val = $matches[2];
            if(substr($val,0,1)!='"') $val = '"'.$val.'"';
            return sprintf('%s=%s', $key, $val);
        }

        function _transEditorComponent($matches) {
            // IE에서는 태그의 특성중에서 " 를 빼어 버리는 경우가 있기에 정규표현식으로 추가해줌
            $buff = $matches[0];
            $buff = preg_replace_callback('/([^=^"^ ]*)=([^ ]*)/i', array($this, _fixQuotation), $buff);
            $buff = str_replace("&","&amp;",$buff);

            // 위젯에서 생성된 코드 (img, div태그내에 editor_widget코드 존재)의 parameter를 추출
            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);
            if($xml_doc->div) $xml_doc = $xml_doc->div;

            $xml_doc->body = $matches[3];

            // attribute가 없으면 return
            $editor_component = $xml_doc->attrs->editor_component;
            if(!$editor_component) return $matches[0];

            // component::transHTML() 을 이용하여 변환된 코드를 받음
            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($editor_component, 0);
            if(!is_object($oComponent)||!method_exists($oComponent, 'transHTML')) return $matches[0];

            return $oComponent->transHTML($xml_doc);
        }

        /**
         * @brief 위젯 코드를 실제 php코드로 변경
         **/
        function _transWidget($matches) {
            // IE에서는 태그의 특성중에서 " 를 빼어 버리는 경우가 있기에 정규표현식으로 추가해줌
            $buff = $matches[0];
            $buff = preg_replace('/([^=^"^ ]*)=([^"])([^=^ ]*)/i', '$1="$2$3"', $buff);
            $buff = str_replace("&","&amp;",$buff);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse(trim($buff));

            if($xml_doc->img) $vars = $xml_doc->img->attrs;
            else $vars = $xml_doc->attrs;

            if(!$vars->widget) return "";

            // 캐시 체크
            $widget_sequence = $vars->widget_sequence;
            $widget_cache = $vars->widget_cache;
            if($widget_cache && $widget_sequence)  {
                $output = WidgetHandler::getCache($widget_sequence, $widget_cache);
                if($output) return $output;
            }

            // 위젯의 이름을 구함
            $widget = $vars->widget;
            unset($vars->widget);
            
            return WidgetHandler::execute($widget, $vars);
        }

    }
?>
