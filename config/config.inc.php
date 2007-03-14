<?php
    /**
     * @file   config/config.inc.php
     * @author zero (zero@nzeo.com)
     * @brief  기본적으로 사용하는 class파일의 include 및 환경 설정을 함
     **/

    /**
     * @brief 기본적인 상수 선언,  웹에서 직접 호출되는 것을 막기 위해 체크하는 상수 선언
     **/
    define('__ZB5__', true);

    /**
     * @brief 간단하게 사용하기 위한 함수 정의한 파일 require
     **/
    require_once("./config/func.inc.php");

    /**
     * @brief debug mode = true 일때 files/_debug_message.php 에 디버그 내용이 쌓임
     **/
    define('__DEBUG__', true);
    if(__DEBUG__) {
        
        // php5이상이면 error handling을 handleError() 로 set
        if (version_compare(phpversion(), '5.0') > 0) {
            set_error_handler("handleError");
        }

        // 여기서부터 시작 시간으로 설정
        define('__StartTime__', getMicroTime());
    }

    /**
     * @brief 세션 설정
     **/
    @session_cache_limiter('no-cache, must-revalidate');
    @session_start();

    /**
     * @brief 기본적인 class 파일 include
     *
     * php5 기반으로 바꾸게 되면 _autoload를 이용할 수 있기에 제거 대상
     **/
    if(__DEBUG__) define('__RequireClassStartTime__', getMicroTime());
    require_once("./classes/handler/Handler.class.php");
    require_once("./classes/xml/XmlParser.class.php");
    require_once("./classes/context/Context.class.php");
    require_once("./classes/db/DB.class.php");
    require_once("./classes/file/FileHandler.class.php");
    require_once("./classes/object/Object.class.php");
    require_once("./classes/plugin/Plugin.class.php");
    require_once("./classes/module/ModuleObject.class.php");
    require_once("./classes/module/ModuleHandler.class.php");
    require_once("./classes/display/DisplayHandler.class.php");
    if(__DEBUG__) define('__RequireClassEndTime__', getMicroTime());
?>
