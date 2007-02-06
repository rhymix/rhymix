<?php
  /**
   * @file   : clases/context/Context.clas.php
   * @author : zero <zero@nzeo.com>
   * @desc   : Request Argument/Module Config/ Layout-AddOn-Plugin과 
   *           결과물까지 모두 처리
   **/

  class Context {

    // GET/POST/XMLRPC 중 어떤 방식으로 요청이 왔는지에 대한 값이 세팅
    var $request_method = 'GET';

    // request parameter 를 정리하여 담을 변수
    var $context = NULL;

    // DB 정보
    var $db_info = NULL;

    // js file
    var $js_files = array();

    // css file
    var $css_files = array();

    // html header 정보
    var $html_header = NULL;

    // 언어정보
    var $lang_type = 'ko';
    var $lang = NULL;
    var $loaded_lang_files = array();

    // 현 사이트의 title
    var $site_title = '';

    // 현재 요청 들어온 $_GET을 별도로 정리/보관 ( set method에 의해 값 변경 적용)
    var $get_vars = NULL;

    // 업로드 유무 체크
    var $is_uploaded = false;

    /**
     * Context 객체 생성 및 초기화
     **/
    // public object getInstance()/*{{{*/
    // Context는 어디서든 객체 선언없이 사용하기 위해서 static 하게 사용
    function &getInstance() {
      if(!$GLOBALS['__ContextInstance__']) $GLOBALS['__ContextInstance__'] = new Context();
      return $GLOBALS['__ContextInstance__'];
    }/*}}}*/

    // public void init()/*{{{*/
    // DB정보, Request Argument등을 세팅
    function init() {
      // context 변수를 $GLOBALS의 변수로 지정
      $this->context = &$GLOBALS['__Context__'];
      $this->context->lang = &$GLOBALS['lang'];

      // 인증관련 데이터를 Context에 설정
      $oMember = getModule('member');
      if($oMember->isLogged()) {
        $this->_set('is_logged', true);
        $this->_set('logged_info', $_SESSION['logged_info']);
      }

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
    }/*}}}*/

    /**
     * DB 정보를 설정하고 DB Type과 DB 정보를 return
     **/
    // private void _loadDBInfo()/*{{{*/
    // 설정파일을 통해 DB정보를 로드
    function _loadDBInfo() {
      if(!$this->isInstalled()) return;

      // db 정보 설정
      $db_config_file = $this->getConfigFile();
      if(file_exists($db_config_file)) {
        include $db_config_file;
      }
      $this->_setDBInfo($db_info);
    }/*}}}*/

    // public string getDBType()/*{{{*/
    // DB의 db_type을 return
    function getDBType() {
      $oContext = &Context::getInstance();
      return $oContext->_getDBType();
    }/*}}}*/

    // private string _getDBType()/*{{{*/
    function _getDBType() {
      return $this->db_info->db_type;
    }/*}}}*/

    // public object setDBInfo($db_info) /*{{{*/
    // DB 정보가 담긴 object를 return
    function setDBInfo($db_info) {
      $oContext = &Context::getInstance();
      $oContext->_setDBInfo($db_info);
    }/*}}}*/

    // private string _setDBInfo($db_info)/*{{{*/
    function _setDBInfo($db_info) {
      $this->db_info = $db_info;
    }/*}}}*/

    // public object getDBInfo() /*{{{*/
    // DB 정보가 담긴 object를 return
    function getDBInfo() {
      $oContext = &Context::getInstance();
      return $oContext->_getDBInfo();
    }/*}}}*/

    // private string _getDBInfo()/*{{{*/
    function _getDBInfo() {
      return $this->db_info;
    }/*}}}*/

    /**
     * 사이트 title 
     **/
    // public void setBrowserTitle($site_title)/*{{{*/
    function setBrowserTitle($site_title) {
      $oContext = &Context::getInstance();
      $oContext->_setBrowserTitle($site_title);
    }/*}}}*/

    // private void _setBrowserTitle($site_title)/*{{{*/
    function _setBrowserTitle($site_title) {
      $this->site_title = $site_title;
    }/*}}}*/

    // public string getBrowserTitle()/*{{{*/
    function getBrowserTitle() {
      $oContext = &Context::getInstance();
      return $oContext->_getBrowserTitle();
    }/*}}}*/

    // private string _getBrowserTitle() /*{{{*/
    function _getBrowserTitle() {
      return $this->site_title;
    }/*}}}*/

    /**
     * 언어관련
     **/
    // public void loadLang($path)/*{{{*/
    // 지정된 언어파일 로드
    function loadLang($path) {
      $oContext = &Context::getInstance();
      $oContext->_loadLang($path);
    }/*}}}*/

    // private void _loadLang($path)/*{{{*/
    // 지정된 언어파일 로드
    function _loadLang($path) {
      global $lang;
      if(substr($path,-1)!='/') $path .= '/';
      $filename = sprintf('%s%s.lang.php', $path, $this->lang_type);
      if(!file_exists($filename)) $filename = sprintf('%slang/%s.lang.php', $path, $this->lang_type);
      if(!file_exists($filename)) return;
      if(in_array($filename, $this->loaded_lang_files)) return;
      $this->loaded_lang_files[] = $filename;
      include ($filename);
    }/*}}}*/

    // public void setLangType($lang_type = 'ko')/*{{{*/
    function setLangType($lang_type = 'ko') {
      $oContext = &Context::getInstance();
      $oContext->_setLangType($lang);
    }/*}}}*/

    // private void _setLangType($lang_type = 'ko')/*{{{*/
    function _setLangType($lang_type = 'ko') {
      $this->lang_type = $lang_type;
    }/*}}}*/

    // public string getLangType()/*{{{*/
    function getLangType() {
      $oContext = &Context::getInstance();
      return $oContext->_getLangType();
    }/*}}}*/

    // private string _getLangType()/*{{{*/
    function _getLangType() {
      return $this->lang_type;
    }/*}}}*/

    // public string getLang($code)/*{{{*/
    function getLang($code) {
      if($GLOBALS['lang']->{$code}) return $GLOBALS['lang']->{$code};
      return $code;
    }/*}}}*/

    // public string setLang($code, $val)/*{{{*/
    function setLang($code, $val) {
      $GLOBALS['lang']->{$code} = $val;
    }/*}}}*/

    // public obj convertEncoding($obj) 
    // obj내의 문자들을 utf8로 변경
    function convertEncoding($source_obj) {/*{{{*/
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
    }/*}}}*/

    /**
     * Request Method 및 Argument의 설정 및 return
     **/
    // private void _setRequestMethod()/*{{{*/
    // request method가 어떤것인지 판단하여 저장 (GET/POST/XMLRPC)
    function _setRequestMethod() {
      if($GLOBALS['HTTP_RAW_POST_DATA']) $this->request_method = "XMLRPC";
      else $this->request_method = $_SERVER['REQUEST_METHOD'];
    }/*}}}*/

    // private void _setRequestArgument();/*{{{*/
    // GET/POST방식일 경우 처리
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
    }/*}}}*/

    // private void _setXmlRpcArgument()/*{{{*/
    // XML RPC일때역시 
    function _setXmlRpcArgument() {
      if($this->_getRequestMethod() != 'XMLRPC') return;
      $oXml = new XmlParser();
      $xml_obj = $oXml->parse();

      $method_name = $xml_obj->methodcall->methodname->body;
      $params = $xml_obj->methodcall->params;
      unset($params->node_name);

      unset($params->attrs);
      if(!count($params)) return;
      foreach($params as $key => $obj) {
        $val = trim($obj->body);
        $this->_set($key, $val, true);
      }
    }/*}}}*/

    // public boolean isUploaded() /*{{{*/
    // 업로드 되었을 경우 return true
    function isUploaded() {
      $oContext = &Context::getInstance();
      return $oContext->_isUploaded();
    }/*}}}*/

    // private boolean isUploaded() /*{{{*/
    // 업로드 되었을 경우 return true
    function _isUploaded() {
      return $this->is_uploaded;
    }/*}}}*/

    // private void _setUploadedArgument()/*{{{*/
    // 업로드된 파일이 있을 경우도 역시 context에 통합 처리 (단 정상적인 업로드인지 체크)
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
    }/*}}}*/

    // public string getRequestMethod()/*{{{*/
    // Request Method값을 return (GET/POST/XMLRPC);
    function getRequestMethod() {
      $oContext = &Context::getInstance();
      return $oContext->_getRequestMethod();
    }/*}}}*/

    // private string _getRequestMethod()/*{{{*/
    function _getRequestMethod() {
      return $this->request_method;
    }/*}}}*/

    // public string getUrl($num_args, $args_list)/*{{{*/
    // 요청받은 url에 args_list를 적용하여 return
    function getUrl($num_args, $args_list) {
      $oContext = &Context::getInstance();
      return $oContext->_getUrl($num_args, $args_list);
    }/*}}}*/

    // private string _getUrl($num_args, $args_list)/*{{{*/
    // 요청받은 url에 args_list를 적용하여 return
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
    }/*}}}*/

    // public string getRequestUri() /*{{{*/
    function getRequestUri() {
      $hostname = $_SERVER['SERVER_NAME'];
      $port = $_SERVER['SERVER_PORT'];
      if($port!=80) $hostname .= ":{$port}";
      $path = $_SERVER['REDIRECT_URL'];
      $path = $_SERVER['REDIRECT_URL']?$_SERVER['REDIRECT_URL']:preg_replace('/([a-zA-Z0-9\_]+).php/i','',$_SERVER['PHP_SELF']);
      return sprintf("http://%s%s",$hostname,$path);
    }/*}}}*/

    /**
     * Request Argument외의 데이터 set/get
     **/
    // public void set($key, $val, $set_to_get_vars = false)/*{{{*/
    // key/val 로 데이터 세팅
    function set($key, $val, $set_to_get_vars = false) {
      $oContext = &Context::getInstance();
      $oContext->_set($key, $val, $set_to_get_vars);
    }/*}}}*/

    // private void _set($key, $val, $set_to_get_vars = false)/*{{{*/
    function _set($key, $val, $set_to_get_vars = false) {
      $this->context->{$key} = $val;
      if($set_to_get_vars || $this->get_vars->{$key}) $this->get_vars->{$key} = $val;
    }/*}}}*/

    // public object get($key)/*{{{*/
    // key값에 해당하는 값을 return
    function get($key) {
      $oContext = &Context::getInstance();
      return $oContext->_get($key);
    }/*}}}*/

    // private object _get($key)/*{{{*/
    function _get($key) {
      return $this->context->{$key};
    }/*}}}*/

    // public object gets(void)/*{{{*/
    // 받고자 하는 변수만 object에 입력하여 받음
    function gets() {
      $num_args = func_num_args();
      if($num_args<1) return;
      $args_list = func_get_args();

      $oContext = &Context::getInstance();
      return $oContext->_gets($num_args, $args_list);
    }/*}}}*/

    // private object _gets($args_list)/*{{{*/
    function _gets($num_args, $args_list) {
      for($i=0;$i<$num_args;$i++) {
        $args = $args_list[$i];
        $output->{$args} = $this->_get($args);
      }
      return $output;
    }/*}}}*/

    // public object getAll()/*{{{*/
    // 모든 데이터를 return
    function getAll() {
      $oContext = &Context::getInstance();
      return $oContext->_getAll();
    }/*}}}*/

    // private object _getAll()/*{{{*/
    function _getAll() {
      return $this->context;
    }/*}}}*/

    // public object getRequestVars()/*{{{*/
    // GET/POST/XMLRPC에서 넘어온 변수값을 return
    function getRequestVars() {
      $oContext = &Context::getInstance();
      return $oContext->_getRequestVars();
    }/*}}}*/

    // private object _getRequestVars()/*{{{*/
    function _getRequestVars() {
      return clone($this->get_vars);
    }/*}}}*/

    /**
     * CSS/JS/HeaderText 등 html을 출력할때 사용할 값들
     **/
    // public void addJsFile($file)/*{{{*/
    // js file 추가
    function addJsFile($file) {
      $oContext = &Context::getInstance();
      return $oContext->_addJsFile($file);
    }/*}}}*/

    // private void _addJsFile($file)/*{{{*/
    function _addJsFile($file) {
      if(in_array($file, $this->js_files)) return;
      $this->js_files[] = $file;
    }/*}}}*/

    // public array getJsFile()/*{{{*/
    function getJsFile() {
      $oContext = &Context::getInstance();
      return $oContext->_getJsFile();
    }/*}}}*/

    // private array _getJsFile()/*{{{*/
    function _getJsFile() {
      return $this->js_files;
    }/*}}}*/

    // public void addCSSFile($file)/*{{{*/
    // CSS file 추가
    function addCSSFile($file) {
      $oContext = &Context::getInstance();
      return $oContext->_addCSSFile($file);
    }/*}}}*/

    // private void _addCSSFile($file)/*{{{*/
    function _addCSSFile($file) {
      if(in_array($file, $this->css_files)) return;
      $this->css_files[] = $file;
    }/*}}}*/

    // public array getCSSFile()/*{{{*/
    // CSS file 추가
    function getCSSFile() {
      $oContext = &Context::getInstance();
      return $oContext->_getCSSFile();
    }/*}}}*/

    // private array _getCSSFile()/*{{{*/
    function _getCSSFile() {
      return $this->css_files;
    }/*}}}*/

    // public void addHtmlHeader($file)/*{{{*/
    // HtmlHeader 추가
    function addHtmlHeader($file) {
      $oContext = &Context::getInstance();
      return $oContext->_addHtmlHeader($file);
    }/*}}}*/

    // private void _addHtmlHeader($file)/*{{{*/
    function _addHtmlHeader($file) {
      $this->HtmlHeader .= ($this->HtmlHeader?"\n":"").$file;
    }/*}}}*/

    // public string getHtmlHeader()/*{{{*/
    // HtmlHeader 추가
    function getHtmlHeader() {
      $oContext = &Context::getInstance();
      return $oContext->_getHtmlHeader();
    }/*}}}*/

    // private string _getHtmlHeader()/*{{{*/
    function _getHtmlHeader() {
      return $this->HtmlHeader;
    }/*}}}*/

    /**
     * 인스톨 관련
     **/
    // public String getConfigFile()/*{{{*/
    // db설정내용이 저장되어 있는 config file의 path를 return
    function getConfigFile() {
      return "./files/config/db.config.php";
    }/*}}}*/
    
    // public boolean isInstalled()/*{{{*/
    // 설치가 되어 있는지에 대한 체크
    function isInstalled() {
      return file_exists(Context::getConfigFile());
    }/*}}}*/

  }
?>
