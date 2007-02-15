<?php
  /**
   * @file   : modules/module_manager/module_manager.admin.php
   * @author : zero <zero@nzeo.com>
   * @desc   : module_manager의 관리자 파일
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class module_manager_admin extends Module {

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = 'dispContent';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/system_install/";
     **/

    // 초기화
    function init() {/*{{{*/

      // 기본 정보를 읽음
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
     **/

    // 출력 부분
    function dispContent() {/*{{{*/
      // 등록된 모듈의 목록을 구해옴
      $installed_module_list = $this->getModulesInfo();
      Context::set('installed_module_list', $installed_module_list);

      // 템플릿 파일 지정
      $this->setTemplateFile('index');
    }/*}}}*/

    // 실행 부분

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
    function getModulesInfo() {/*{{{*/
      // DB 객체 생성
      $oDB = &DB::getInstance();

      // 다운받은 모듈과 설치된 모듈의 목록을 구함
      $downloaded_list = FileHandler::readDir('./files/modules');
      $installed_list = FileHandler::readDir('./modules');

      // 찾아진 모듈목록에서 admin은 제외시킴
      $searched_list = array_merge($downloaded_list, $installed_list);
      if(!count($searched_list)) return;

      for($i=0;$i<count($searched_list);$i++) {

        // 모듈의 이름
        $module_name = $searched_list[$i];

        // 모듈의 경로 (files/modules가 우선)
        $path = sprintf("./files/modules/%s/", $module_name);
        if(!is_dir(!$path)) $path = sprintf("./modules/%s/", $module_name);
        if(!is_dir($path)) continue;

        // schemas내의 테이블 생성 xml파일수를 구함
        $tmp_files = FileHandler::readDir($path."schemas");
        $table_count = count($tmp_files);

        // 테이블이 설치되어 있는지 체크
        $created_table_count = 0;
        for($j=0;$j<count($tmp_files);$j++) {
          list($table_name) = explode(".",$tmp_files[$j]);
          if($oDB->isTableExists($table_name)) $created_table_count ++;
        }

        // 해당 모듈의 정보를 구함
        $info = module_manager::loadModuleXml($path);
        unset($obj);

        $info->module = $module_name;
        $info->created_table_count = $created_table_count;
        $info->table_count = $table_count;
        $info->path = $path;

        $list[] = $info;
      }
      return $list;
    }/*}}}*/
  }

?>
