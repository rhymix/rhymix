<?php
  /**
   * @file   : index.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 모든 요청(request)의 관문으로 main() 역할을 함
   *           Main class의 instance를 생성하여 constructor 실행하는 역할만 수행
   **/

  // 필요한 설정 파일들을 include
  require_once("./config/config.inc.php");

  // Request Method와 설정값들을 세팅
  $oContext = &Context::getInstance();
  $oContext->init();

  // ModuleHandler 호출하여 content 출력
  $oModuleHandler = new ModuleHandler();
  $oModule = $oModuleHandler->proc();

  // DisplayHandler로 컨텐츠 출력
  $oDisplayHandler = new DisplayHandler();
  $oDisplayHandler->printContent($oModule);
?>
