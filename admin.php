<?php
  /**
   * @file   ./admin.php
   * @author zero <zero@nzeo.com>
   * @brief  관리자 페이지 
   * 추가되는 모듈의 관리를 위해 소스코드의 변경을 피하기 위해
   * 관리자 페이지는 각 모듈의 admin action을 호출하여 구성을 한다.
   **/

  /**
   * @brief 필요한 설정 파일들을 include
   **/
  require_once("./config/config.inc.php");

  /** 
   * @brief Context 객체를 생성하여 초기화\n
   *        모든 Request Argument/ 환경변수등을 세팅
   **/
  $oContext = &Context::getInstance();
  $oContext->init();

  /** 
   * @brief 설치가 안되어 있다면 index.php로 이동
   *        ModuleHandler를 이용하지 않기에 별도로 체크를 해주어야 함
   **/
  if(!Context::isInstalled()) {
    header("location:./index.php");
    exit();
  }

  /**
   * @brief 관리자페이지에서 모듈의 선택은 sid로 한다.
   **/
  $sid = Context::get('sid');
  if($sid) {
    $oModule = module_manager::getAdminModuleObject($sid);
    if(!$oModule) {
      $sid = null;
      Context::set('sid',$sid);
      unset($oModule);
    }
  }

  /**
   * @brief 관리자(admin) 모듈 객체 생성
   **/
  $oAdmin = getModule('admin');
  $oAdmin->moduleInit(null);

  /**
   * @brief 역시 ModuleHandler를 이용하지 않기에 직접 act 검사
   **/
  $act = Context::get('act');
  if(!$sid&&!$oAdmin->isExistsAct($act)) $act = 'dispAdminIndex';

  /**
   * @brief 관리자 모듈의 실행 결과가 있으면 해당 실행결과를 출력
   **/
  if($oAdmin->proc($act)) {

    $oModule = &$oAdmin;

  /**
   * @brief 관리자 모듈의 실행 결과가 없으면 호출된 다른 모듈의 관리자를 확인
   **/
  } else {
    $oModule = module_manager::getAdminModuleObject($sid);
    if($oModule) {
      $oModule->moduleInit(null);
      $oModule->proc();

      // 관리자용 레이아웃으로 변경
      $oModule->setLayoutPath($oAdmin->getLayoutPath());
      $oModule->setLayoutTpl($oAdmin->getLayoutTpl());
    }
  }

  /**
   * @brief DisplayHandler 객체를 생성하여 모듈의 처리 결과를 출력
   **/
  $oDisplayHandler = new DisplayHandler();
  $oDisplayHandler->printContent($oModule);
?>
