<?php
  /**
   * @file   : rss.php
   * @author : zero <zero@nzeo.com>
   * @desc   : rss를 출력하기 위한 파일. index.php와 거의 동일하나 act를 dispRss 로 고정시키는 것만 다름
   **/

  // 필요한 설정 파일들을 include
  require_once("./config/config.inc.php");

  // Request Method와 설정값들을 세팅
  $oContext = &Context::getInstance();
  $oContext->init();

  // act값을 dispRss로 강제 설정
  // 각 모듈마다 dispRss가 필수적으로 있어야 함
  Context::set('act', 'dispRss');

  // ModuleHandler 호출하여 content 출력
  $oModuleHandler = new ModuleHandler();
  $oModule = $oModuleHandler->proc();
?>
