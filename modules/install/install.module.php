<?php
  /**
   * @file   : modules/install/install.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 install module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class install extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = 'dispIntroduce';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/install/";
     **/

    // 초기화
    function init() {/*{{{*/
      Context::loadLang($this->module_path.'lang');

      // 설치시 필수항목 검사
      $this->checkInstallEnv();

      if(Context::get('install_enable')) $this->makeDefaultDirectory();
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      // 설치가 가능하면 기본 디렉토리등을 만듬
      if(!Context::get('install_enable')) {
        $this->act = 'dispIntroduce';
      }

      return true;
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
      // 설치가 불가능한 환경인데 요청이 오면 에러 표시
      if(!Context::get('install_enable')) return $this->doError('msg_already_installed');
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
    function dispIntroduce() {/*{{{*/
      // disp_license.html 파일 출력
      $this->setTemplateFile('disp_license');
    }/*}}}*/

    function dispDBInfoForm() {/*{{{*/
      // db_type이 지정되지 않았다면 다시 초기화면 출력
      if(!Context::get('db_type')) return $this->dispIntroduce();

      // disp_db_info_form.html 파일 출력
      $tpl_filename = sprintf('db_form.%s.html', Context::get('db_type'));
      $this->setTemplateFile($tpl_filename);
    }/*}}}*/

    function procInstall() {/*{{{*/
      // 설치가 되어 있는지에 대한 체크
      if(Context::isInstalled()) {
        return $this->doError('msg_already_installed');
      }

      // DB와 관련된 변수를 받음
      $db_info = Context::gets('db_type','db_hostname','db_userid','db_password','db_database','db_table_prefix');

      // DB의 타입과 정보를 등록
      Context::setDBInfo($db_info);

      // DB Instance 생성
      $oDB = &DB::getInstance();

      // DB접속이 가능한지 체크
      if(!$oDB->isConnected()) {
        return $this->doError('msg_dbconnect_failed');
      }

      // 모든 모듈의 테이블 생성
      $output = $this->makeTable();
      if(!$output->toBool()) return $output;

      // 관리자 정보 입력 (member 모듈을 찾아서 method 실행)
      $oMember = getModule('member');

      // 그룹을 입력
      $group_args->title = Context::getLang('default_group_1');
      $group_args->is_default = 'Y';
      $oMember->insertGroup($group_args);

      $group_args->title = Context::getLang('default_group_2');
      $group_args->is_default = 'N';
      $oMember->insertGroup($group_args);

      // 금지 아이디 등록
      $oMember->insertDeniedID('www','');
      $oMember->insertDeniedID('root','');
      $oMember->insertDeniedID('admin','');
      $oMember->insertDeniedID('administrator','');
      $oMember->insertDeniedID('ftp','');
      $oMember->insertDeniedID('http','');

      // 관리자 정보 세팅
      $admin_info = Context::gets('user_id','password','nick_name','user_name', 'email_address');

      // 관리자 정보 입력
      $oMember->insertAdmin($admin_info);

      // 로그인 처리시킴
      $oMember->doLogin($admin_info->user_id, $admin_info->password);

      // 기본 모듈을 생성
      $oModule = getModule('module_manager');
      $oModule->makeDefaultModule();

      // config 파일 생성
      if(!$this->makeConfigFile()) return $this->doError('msg_install_failed');

      // 설치 완료 메세지 출력
      $this->setMessage('msg_install_completed');
    }/*}}}*/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
    // public void checkInstallEnv()/*{{{*/
    // 기본적으로 필수적인 체크항목들을 검사하여 Context에 바로 넣어버림..
    function checkInstallEnv() {
      // 각 필요한 항목 체크
      $checklist = array();

      // 1. permission 체크
      if(is_writable('./')||is_writable('./files')) $checklist['permission'] = true;
      else $checklist['permission'] = false;

      // 2. xml_parser_create함수 유무 체크
      if(function_exists('xml_parser_create')) $checklist['xml'] = true;
      else $checklist['xml'] = false;

      // 3. ini_get(session.auto_start)==1 체크
      if(ini_get(session.auto_start)!=1) $checklist['session'] = true;
      else $checklist['session'] = false;

      // 4. iconv 체크
      if(function_exists('iconv')) $checklist['iconv'] = true;
      else $checklist['iconv'] = false;

      // 5. gd 체크 (imagecreatefromgif함수)
      if(function_exists('imagecreatefromgif')) $checklist['gd'] = true;
      else $checklist['gd'] = false;

      // 6. mysql_get_client_info() 체크
      if(mysql_get_client_info() < "4.1.00") $checklist['mysql'] = false;
      else $checklist['mysql'] = true;

      if(!$checklist['permission'] || !$checklist['xml'] || !$checklist['session']) $install_enable = false;
      else $install_enable = true;

      // 체크 결과를 Context에 저장
      Context::set('install_enable', $install_enable);
      Context::set('checklist', $checklist);
    }/*}}}*/

    // public void makeDefaultDirectory()/*{{{*/
    // files 및 하위 디렉토리 생성
    function makeDefaultDirectory() {
      $directory_list = array(
          './files',
          './files/modules',
          './files/plugins',
          './files/addons',
          './files/layouts',
          './files/queries',
          './files/schemas',
          './files/js_filter_compiled',
          './files/template_compiled',
          './files/config',
          './files/attach',
          './files/attach/images',
          './files/attach/binaries',
      );

      foreach($directory_list as $dir) {
        if(is_dir($dir)) continue;
        @mkdir($dir, 0707);
        @chmod($dir, 0707);
      }
    }/*}}}*/

    // public void makeTable()/*{{{*/
    // 모든 모듈의 테이블 생성 schema를 찾아서 생성
    function makeTable() {
      // db instance생성
      $oDB = &DB::getInstance();

      // 각 모듈의 schemas/*.xml 파일을 모두 찾아서 table 생성
      $module_list_1 = FileHandler::readDir('./modules/', NULL, false, true);
      $module_list_2 = FileHandler::readDir('./files/modules/', NULL, false, true);
      $module_list = array_merge($module_list_1, $module_list_2);
      foreach($module_list as $module_path) {
        $schema_dir = sprintf('%s/schemas/', $module_path);
        $schema_files = FileHandler::readDir($schema_dir, NULL, false, true);
        $file_cnt = count($schema_files);
        if(!$file_cnt) continue;

        for($i=0;$i<$file_cnt;$i++) {
          $file = trim($schema_files[$i]);
          if(!$file || substr($file,-4)!='.xml') continue;
          $output = $oDB->createTableByXmlFile($file);
          if($oDB->isError()) return $oDB->getError();
        }
      }
      return new Output();
    }/*}}}*/

    // public boolean makeConfigFile() /*{{{*/
    // config 파일을 생성
    function makeConfigFile() {
      $config_file = Context::getConfigFile();
      if(file_exists($config_file)) return;

      $db_info = Context::getDbInfo();
      if(!$db_info) return;

      $buff = '<?php if(!__ZB5__) exit();'."\n";
      foreach($db_info as $key => $val) {
        $buff .= sprintf("\$db_info->%s = \"%s\";\n", $key, $val);
      }
      $buff .= "?>";

      FileHandler::writeFile($config_file, $buff);

      if(@file_exists($config_file)) return true;
      return false;
    }/*}}}*/
  }
?>
