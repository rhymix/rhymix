<?php
    @error_reporting(E_ALL ^ E_NOTICE);

    /**
     * @file   config/config.inc.php
     * @author zero (zero@nzeo.com)
     * @brief  기본적으로 사용하는 class파일의 include 및 환경 설정을 함
     **/

    if(!defined('__ZBXE__')) exit();

    /**
     * @brief 제로보드XE의 전체 버전 표기
     * 이 내용은 제로보드XE의 버전을 관리자 페이지에 표시하기 위한 용도이며
     * config.inc.php의 수정이 없더라도 공식 릴리즈시에 수정되어 함께 배포되어야 함
     **/
    define('__ZBXE_VERSION__', '1.0.1');

    /**
     * @brief 디버깅 메세지 출력
     * 0 : 디버그 메세지를 생성/ 출력하지 않음
     * 1 : 전체 실행 시간에 대해서만 메세지 생성/ 출력
     * 2 : 1 + DB 쿼리
     * 3 : 모든 로그
     **/
    define('__DEBUG__', 0);

    /**
     * @brief 디버그 메세지의 출력 장소
     * 0 : files/_debug_message.php 에 연결하여 출력
     * 1 : Response Method 가 XML 형식이 아닐 경우 브라우저에 최상단에 주석으로 표시
     **/
    define('__DEBUG_OUTPUT__', 0);

    /**
     * @brief DB 오류 메세지 출력 정의
     * 0 : 출력하지 않음
     * 1 : files/_debug_db_query.php 에 연결하여 출력
     **/
    define('__DEBUG_DB_OUTPUT__', 0);

    /**
     * @brief DB 쿼리중 정해진 시간을 넘기는 쿼리의 로그 남김
     * 0 : 로그를 남기지 않음
     * 0 이상 : 단위를 초로 하여 지정된 초 이상의 실행시간이 걸린 쿼리를 로그로 남김
     * 로그파일은 ./files/_db_slow_query.php 파일로 저장됨
     **/
    define('__LOG_SLOW_QUERY__', 0);

    /**
     * @brief ob_gzhandler를 이용한 압축 기능을 강제로 사용하거나 끄는 옵션
     * 0 : 사용하지 않음
     * 1 : 사용함
     * 대부분의 서버에서는 문제가 없는데 특정 서버군에서 압축전송시 IE에서 오동작을 일으키는경우가 있음
     **/
    define('__OB_GZHANDLER_ENABLE__', 1);

    /**
     * @brief 간단하게 사용하기 위한 함수 정의한 파일 require
     **/
    require_once('./config/func.inc.php');


    if(__DEBUG__) define('__StartTime__', getMicroTime());

    /**
     * @brief 기본적인 class 파일 include
     *
     * php5 기반으로 바꾸게 되면 _autoload를 이용할 수 있기에 제거 대상
     **/
    if(__DEBUG__) define('__ClassLosdStartTime__', getMicroTime());
    require_once('./classes/object/Object.class.php');
    require_once('./classes/handler/Handler.class.php');
    require_once('./classes/xml/XmlParser.class.php');
    require_once('./classes/context/Context.class.php');
    require_once('./classes/db/DB.class.php');
    require_once('./classes/file/FileHandler.class.php');
    require_once('./classes/widget/WidgetHandler.class.php');
    require_once('./classes/editor/EditorHandler.class.php');
    require_once('./classes/module/ModuleObject.class.php');
    require_once('./classes/module/ModuleHandler.class.php');
    require_once('./classes/display/DisplayHandler.class.php');
    require_once('./classes/template/TemplateHandler.class.php');
    require_once('./classes/mail/Mail.class.php');
    if(__DEBUG__) $GLOBALS['__elapsed_class_load__'] = getMicroTime() - __ClassLosdStartTime__;

    /**
     * @brief 세션 설정
     * 세션의 파기 시간을 5시간으로 하고 세션 저장 경로를 files/session 으로 변경
     **/
    if(!ini_get('session.auto_start')) {
        session_cache_limiter('no-cache, must-revalidate');
        ini_set('session.gc_maxlifetime', '18000');
        if(!is_dir('./files/sessions')) {
            FileHandler::makeDir('./files/sessions');
            @chmod('./files/sessions',  0777);
        }
        if(is_dir('./files/sessions')) session_save_path(realpath('.').'/files/sessions/');
        session_start();
    }
?>