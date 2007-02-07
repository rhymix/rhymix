<?php
  /**
   * @file   : modules/module/module_manager.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나
   *           Module class에서 상속을 받아서 사용
   *           모듈과 관련된 method들이 존재
   **/

  class module_manager extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = '';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/module/";
     **/

    // 초기화
    function init() {/*{{{*/
      Context::loadLang($this->module_path.'lang');
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      return true;
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
      return true;
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
     *
     * 변수의 사용은 Context::get('이름')으로 얻어오면 된다
     **/

    /**
     * 여기서부터는 관련 lib...
     **/
    // public string getModuleInfoByDocument($document_srl)/*{{{*/
    // $document_srl로 모듈정보를 구함
    function getModuleInfoByDocument($document_srl) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('module_manager.getModuleInfoByDocument', $args);
      // extra_vars의 정리
      $module_info = module_manager::extractExtraVar($output->data);
      return $module_info;
    }/*}}}*/

    // public object getModuleInfo($mid='')/*{{{*/
    // $mid의 정보를 load, mid값이 없다면 기본 mid 정보를 읽음
    function getModuleInfo($mid='') {
      // DB에서 가져옴
      $oDB = &DB::getInstance();
      if($mid) {
        $args->mid = $mid;
        $output = $oDB->executeQuery('module_manager.getMidInfo', $args);
      } 
      if(!$output->data) $output = $oDB->executeQuery('module_manager.getDefaultMidInfo');
      return module_manager::arrangeModuleInfo($output->data);
    }/*}}}*/

    // public object getModuleInfoByModuleSrl($module_srl='')/*{{{*/
    // $modulr_srl로 모듈의 정보를 읽음
    function getModuleInfoByModuleSrl($module_srl='') {
      $oDB = &DB::getInstance();
      $args->module_srl = $module_srl;
      $output = $oDB->executeQuery('module_manager.getMidInfo', $args);
      if(!$output->data) return;
      return module_manager::arrangeModuleInfo($output->data);
    }/*}}}*/

    // public object arrangeModuleInfo($source_module_info)/*{{{*/
    // grant, extraVar등의 정리
    function arrangeModuleInfo($source_module_info) {
      if(!$source_module_info) return;

      // serialize되어 있는 변수들 추출
      $extra_vars = $source_module_info->extra_vars;
      $grant = $source_module_info->grant;
      //$admin_id = $source_module_info->admin_id;

      unset($source_module_info->extra_vars);
      unset($source_module_info->grant);
      //unset($source_module_info->admin_id);
      
      $module_info = clone($source_module_info);

      // extra_vars의 정리
      if($extra_vars) {
        $extra_vars = unserialize($extra_vars);
        foreach($extra_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
      }

      // 권한의 정리
      if($grant) $module_info->grant = unserialize($grant);

      // 관리자 아이디의 정리
      if($module_info->admin_id) {
        $module_info->admin_id = explode(',',$module_info->admin_id);
      }

      return $module_info;
    }/*}}}*/

    // public object getModuleObject($module)/*{{{*/
    // module을 찾아서 instance를 생성하여 return
    function getModuleObject($module) {
      // 요청받은 모듈이 있는지 확인
      $module = strtolower($module);

      // global 변수에 저장한 객체가 있으면 그걸 return
      if($GLOBALS['_loaded_module'][$module]) return $GLOBALS['_loaded_module'][$module];

      $class_path = sprintf('./modules/%s/', $module);
      if(!is_dir($class_path)) $class_path = sprintf('./classs/modules/%s/', $module);
      if(!is_dir($class_path)) return NULL;

      $class_file = $class_path.$module.'.module.php';
      if(!file_exists($class_file)) return NULL;

      // 새로 객체 생성
      require_once($class_file);
      $eval_str = sprintf('$oModule = new %s();', $module);
      eval($eval_str);
      $oModule->setModulePath($class_path);

      // 언어파일 읽기
      Context::loadLang($class_path.'lang');

      $GLOBALS['_loaded_module'][$module] = $oModule;

      // 객체 리턴
      return $oModule;
    }/*}}}*/

    // public object getAdminModuleObject($module)/*{{{*/
    // module의 관리자를 찾아서 instance를 생성하여 return
    function getAdminModuleObject($module) {
      // 요청받은 모듈이 있는지 확인
      $module = strtolower($module);

      // global 변수에 저장한 객체가 있으면 그걸 return
      if($GLOBALS['_loaded_admin_module'][$module]) return $GLOBALS['_loaded_admin_module'][$module];

      $class_path = sprintf('./modules/%s/', $module);
      if(!is_dir($class_path)) $class_path = sprintf('./classs/modules/%s/', $module);
      if(!is_dir($class_path)) return NULL;

      $class_file = $class_path.$module.'.admin.php';
      if(!file_exists($class_file)) return NULL;

      // 새로 객체 생성
      require_once($class_file);
      $eval_str = sprintf('$oModule = new %s_admin();', $module);
      eval($eval_str);
      $oModule->setModulePath($class_path);
      $oModule->init();

      $template_path = sprintf("%sadmin/", $class_path);
      $oModule->setTemplatePath($template_path);

      // 언어파일 읽기
      Context::loadLang($class_path.'lang');

      // global 변수에 객체 저장
      $GLOBALS['_loaded_admin_module'][$module] = $oModule;

      return $oModule;
    }/*}}}*/

    // public object makeDefaultModule() /*{{{*/
    // 설치시 기본 모듈을 생성
    function makeDefaultModule() {
      $oDB = &DB::getInstance();

      // 설치된 기본 모듈이 있는지 확인
      $output = $oDB->executeQuery('module_manager.getDefaultMidInfo');
      if($output->data) return;

      // 기본 모듈 입력
      $args->mid = 'board';
      $args->browser_title = '테스트 모듈';
      $args->is_default = 'Y';
      $args->module = 'board';
      $args->skin = 'default';

      $extra_vars->colorset = 'normal';
      $args->extra_vars = serialize($extra_vars);

      return $this->insertModule($args);
    }/*}}}*/

    // public object insertModule($args)/*{{{*/
    // 모듈 입력
    function insertModule($args) {
      // 등록하려는 모듈의 path를 구함
      $oModule = getModule($args->module);

      // 선택된 스킨정보에서 colorset을 구함
      $skin_info = $this->loadSkinInfo($oModule->module_path, $args->skin);
      $extra_vars->colorset = $skin_info->colorset[0]->name;

      // db에 입력
      $oDB = &DB::getInstance();
      $args->module_srl = $oDB->getNextSequence();
      $args->extra_vars = serialize($extra_vars);
      $output = $oDB->executeQuery('module_manager.insertModule', $args);
      $output->add('module_srl',$args->module_srl);
      return $output;
    }/*}}}*/

    // public object updateModule($args)/*{{{*/
    // 모듈 입력
    function updateModule($args) {
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('module_manager.updateModule', $args);
      $output->add('module_srl',$args->module_srl);
      return $output;
    }/*}}}*/

    // public object updateModuleExtraVars($args)/*{{{*/
    // 모듈 입력
    function updateModuleExtraVars($module_srl, $extra_vars) {
      $oDB = &DB::getInstance();
      $args->module_srl = $module_srl;
      $args->extra_vars = $extra_vars;
      $output = $oDB->executeQuery('module_manager.updateModuleExtraVars', $args);
      return $output;
    }/*}}}*/

    // public object updateModuleGrant($module_srl, $grant)/*{{{*/
    // 모듈 입력
    function updateModuleGrant($module_srl, $grant) {
      $oDB = &DB::getInstance();
      $args->module_srl = $module_srl;
      $args->grant = $grant;
      $output = $oDB->executeQuery('module_manager.updateModuleGrant', $args);
      return $output;
    }/*}}}*/

    // public object deleteModule($module_srl)/*{{{*/
    // 모듈 입력
    function deleteModule($module_srl) {
      $oDB = &DB::getInstance();
      $oDocument = getModule('document');

      // addon 삭제
      // plugin 삭제

      // document 삭제
      $output = $oDocument->deleteModuleDocument($module_srl);
      if(!$output->toBool()) return $output;
      
      // category 삭제
      $output = $oDocument->deleteModuleCategory($module_srl);
      if(!$output->toBool()) return $output;
      
      // trackbacks 삭제
      $oTrackback = getModule('trackback');
      $output = $oTrackback->deleteModuleTrackbacks($module_srl);
      if(!$output->toBool()) return $output;
      
      // comments 삭제
      $oComment = getModule('comment');
      $output = $oComment->deleteModuleComments($module_srl);
      if(!$output->toBool()) return $output;
      
      // tags 삭제
      $oTag = getModule('tag');
      $output = $oTag->deleteModuleTags($module_srl);
      if(!$output->toBool()) return $output;

      // files 삭제
      $output = $oDocument->deleteModuleFiles($module_srl);
      if(!$output->toBool()) return $output;

      // module 정보 삭제
      $args->module_srl = $module_srl;
      $output = $oDB->executeQuery('module_manager.deleteModule', $args);

      return $output;
    }/*}}}*/

    // public object clearDefaultModule() /*{{{*/
    // 모든 모듈의 is_default='N'로 세팅
    function clearDefaultModule() {
      $oDB = &DB::getInstance();
      return  $oDB->executeQuery('module_manager.clearDefaultModule');
    }/*}}}*/

    // public object getAdminModuleList() /*{{{*/
    // 관리자 모듈 목록을 가져옴
    function getAdminModuleList() {
      // 다운받은 모듈과 설치된 모듈의 목록을 구함
      $downloaded_list = FileHandler::readDir('./files/modules');
      $installed_list = FileHandler::readDir('./modules');

      // 찾아진 모듈목록에서 admin은 제외시킴
      $searched_list = array_merge($downloaded_list, $installed_list);
      if(!count($searched_list)) return;

      for($i=0;$i<count($searched_list);$i++) {
        $module_name = $searched_list[$i];
        $path = sprintf("./files/modules/%s/", $module_name);
        $file = sprintf("%s%s.admin.php",$path, $module_name);
        if(!file_exists($file)) {
          $path = sprintf("./modules/%s/", $module_name);
          $file = sprintf("%s%s.admin.php",$path, $module_name);
        }
        if(!file_exists($file)) continue;

        $module_info = module_manager::loadModuleXml($path);
        $list[$module_name] = $module_info->title;
      }
      if($list) asort($list);
      return $list;
    }/*}}}*/

    function loadModuleXml($module_path) {/*{{{*/
      // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
      $xml_file = sprintf("%s/module.xml", $module_path);
      if(!file_exists($xml_file)) return;
      $oXmlParser = new XmlParser();
      $xml_obj = $oXmlParser->loadXmlFile($xml_file);

      // 스킨 정보를 이용 변수 정리
      if(!$xml_obj) return;

      // 스킨이름
      $info->title = $xml_obj->title->body;

      // 작성자 정보
      $module_info->title = $xml_obj->title->body;
      $module_info->version = $xml_obj->attrs->version;
      $module_info->author->name = $xml_obj->author->name->body;
      $module_info->author->email_address = $xml_obj->author->attrs->email_address;
      $module_info->author->homepage = $xml_obj->author->attrs->link;
      $module_info->author->date = $xml_obj->author->attrs->date;
      $module_info->author->description = $xml_obj->author->description->body;

      // history 
      if(!is_array($xml_obj->history->author)) $history[] = $xml_obj->history->author;
      else $history = $xml_obj->history->author;
      foreach($history as $item) {
        unset($obj);
        $obj->name = $item->name->body;
        $obj->email_address = $item->attrs->email_address;
        $obj->homepage = $item->attrs->link;
        $obj->date = $item->attrs->date;
        $obj->description = $item->description->body;
        $module_info->history[] = $obj;
      }

      return $module_info;
    }/*}}}*/

    function getSkins($module_path) {/*{{{*/
      $skins_path = sprintf("%s/skins/", $module_path);
      $list = FileHandler::readDir($skins_path);
      return $list;
    }/*}}}*/

    function loadSkinInfo($module_path, $skin) {/*{{{*/
      // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
      $skin_xml_file = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
      if(!file_exists($skin_xml_file)) return;
      $oXmlParser = new XmlParser();
      $xml_obj = $oXmlParser->loadXmlFile($skin_xml_file);

      // 스킨 정보를 이용 변수 정리
      if(!$xml_obj) return;

      // 스킨이름
      $skin_info->title = $xml_obj->title->body;

      // 작성자 정보
      $skin_info->maker->name = $xml_obj->maker->name->body;
      $skin_info->maker->email_address = $xml_obj->maker->attrs->email_address;
      $skin_info->maker->homepage = $xml_obj->maker->attrs->link;
      $skin_info->maker->date = $xml_obj->maker->attrs->date;
      $skin_info->maker->description = $xml_obj->maker->description->body;

      // colorset
      if(!is_array($xml_obj->colorset->color)) $colorset[] = $xml_obj->colorset->color;
      else $colorset = $xml_obj->colorset->color;
      foreach($colorset as $color) {
        $name = $color->attrs->name;
        $title = $color->title->body;
        $screenshot = $color->attrs->src;
        if($screenshot && file_exists($screenshot)) $screenshot = sprintf("%sskins/%s/%s",$module_path,$skin,$screenshot);
        else $screenshot = "";

        unset($obj);
        $obj->name = $name;
        $obj->title = $title;
        $obj->screenshot = $screenshot;
        $skin_info->colorset[] = $obj;
      }

      // 스킨에서 사용되는 변수들
      if(!is_array($xml_obj->extra_vars->var)) $extra_vars[] = $xml_obj->extra_vars->var;
      else $extra_vars = $xml_obj->extra_vars->var;
      foreach($extra_vars as $var) {
        $name = $var->attrs->name;
        $type = $var->attrs->type;
        $title = $var->title->body;
        $description = $var->description->body;
        if($var->default) {
          unset($default);
          if(is_array($var->default)) {
            for($i=0;$i<count($var->default);$i++) $default[] = $var->default[$i]->body;
          } else {
            $default = $var->default->body;
          }
        }
        $width = $var->attrs->width;
        $height = $var->attrs->height;

        unset($obj);
        $obj->title = $title;
        $obj->description = $description;
        $obj->name = $name;
        $obj->type = $type;
        $obj->default = $default;
        $obj->width = $width;
        $obj->height = $height;

        $skin_info->extra_vars[] = $obj;
      }

      return $skin_info;
    }/*}}}*/
  }
?>
