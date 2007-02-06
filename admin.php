<?php
  /**
   * @file   : admin.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 관리자 페이지
   *           admin은 ModuleHandler를 이용하지 않는다.
   **/

  // 필요한 설정 파일들을 include
  require_once("./config/config.inc.php");

  // Request Method와 설정값들을 세팅
  $oContext = &Context::getInstance();
  $oContext->init();

  // 설치가 안되어 있다면 index.php로 이동
  if(!Context::isInstalled()) {
    header("location:./index.php");
    exit();
  }

  // sid 검사
  $sid = Context::get('sid');
  if($sid) {
    $oModule = module_manager::getAdminModuleObject($sid);
    if(!$oModule) {
      $sid = null;
      Context::set('sid',$sid);
      unset($oModule);
    }
  }

  // 관리자(admin) 모듈 객체 생성
  $oAdmin = getModule('admin');
  $oAdmin->moduleInit(null);

  // act검사
  $act = Context::get('act');
  if(!$sid&&!$oAdmin->isExistsAct($act)) $act = 'dispAdminIndex';

  // 관리자 모듈의 실행 결과가 있으면 해당 실행결과를 출력
  if($oAdmin->proc($act)) {

    $oModule = &$oAdmin;

  // 관리자 모듈의 실행 결과가 없으면 호출된 다른 모듈의 관리자를 확인
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

  // DisplayHandler로 컨텐츠 출력
  $oDisplayHandler = new DisplayHandler();
  $oDisplayHandler->printContent($oModule);
?>
