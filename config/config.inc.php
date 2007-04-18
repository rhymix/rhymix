<?php
    /**
     * @file   config/config.inc.php
     * @author zero (zero@nzeo.com)
     * @brief  기본적으로 사용하는 class파일의 include 및 환경 설정을 함
     **/

    /**
     * @brief 기본적인 상수 선언,  웹에서 직접 호출되는 것을 막기 위해 체크하는 상수 선언
     **/
    define('__ZBXE__', true);

    /**
     * @brief 간단하게 사용하기 위한 함수 정의한 파일 require
     **/
    require_once("./config/func.inc.php");

    /**
     * @brief 디버그 메세지의 출력 장소 
     * 0 : files/_debug_message.php 에 연결하여 출력
     * 1 : Response Method 가 XML 형식이 아닐 경우 브라우저에 최상단에 주석으로 표시
     **/
    define('__DEBUG_OUTPUT__', 0);

    /**
     * @brief 디버깅 메세지 출력
     * 0 : 디버그 메세지를 생성/ 출력하지 않음
     * 1 : 전체 실행 시간에 대해서만 메세지 생성/ 출력
     * 2 : 1 + DB 쿼리
     * 3 : 모든 로그
     **/
    define('__DEBUG__', 3);

    if(__DEBUG__) define('__StartTime__', getMicroTime());

    /**
     * @brief 기본적인 class 파일 include
     *
     * php5 기반으로 바꾸게 되면 _autoload를 이용할 수 있기에 제거 대상
     **/
    if(__DEBUG__) define('__ClassLosdStartTime__', getMicroTime());
    require_once("./classes/object/Object.class.php");
    require_once("./classes/handler/Handler.class.php");
    require_once("./classes/xml/XmlParser.class.php");
    require_once("./classes/context/Context.class.php");
    require_once("./classes/db/DB.class.php");
    require_once("./classes/file/FileHandler.class.php");
    require_once("./classes/plugin/PluginHandler.class.php");
    require_once("./classes/editor/EditorHandler.class.php");
    require_once("./classes/module/ModuleObject.class.php");
    require_once("./classes/module/ModuleHandler.class.php");
    require_once("./classes/display/DisplayHandler.class.php");
    if(__DEBUG__) $GLOBALS['__elapsed_class_load__'] = getMicroTime() - __ClassLosdStartTime__;

    /**
     * @brief 세션 설정
     **/
    session_cache_limiter('no-cache, must-revalidate');
    session_start();
?>
