<?php
  /**
   * @file   : config/config.inc.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본적으로 사용하는 class파일의 include 및 환경 설정을 함
   **/

  // 기본적인 상수 선언
  // 웹에서 직접 호출되는 것을 막기 위해 체크하는 상수 선언
  define('__ZB5__', true);

  // 기본 함수 라이브러리 파일
  require_once("./config/func.inc.php");

  // debug mode = true 일때 files/_debug_message.php 에 디버그 내용이 쌓임
  define('__DEBUG__', true);
  if(__DEBUG__) define('__StartTime__', getMicroTime());

  // 세션 설정
  @session_cache_limiter('no-cache, must-revalidate');
  @session_start();

  // 기본적인 class 파일 include
  if(__DEBUG__) define('__RequireClassStartTime__', getMicroTime());
  require_once("./classes/xml/XmlParser.class.php");
  require_once("./classes/context/Context.class.php");
  require_once("./classes/db/DB.class.php");
  require_once("./classes/file/FileHandler.class.php");
  require_once("./classes/output/Output.class.php");
  require_once("./classes/module/Module.class.php");
  require_once("./classes/display/DisplayHandler.class.php");
  require_once("./classes/module/ModuleHandler.class.php");
  require_once('./modules/module_manager/module_manager.module.php');
  //require_once("./classes/addon/AddOnHandler.class.php");
  //require_once("./classes/layout/LayoutHandler.class.php");
  if(__DEBUG__) define('__RequireClassEndTime__', getMicroTime());
?>
