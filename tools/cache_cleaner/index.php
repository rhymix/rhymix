<?php
    /**
     * @file tools/clear_cache.php
     * @author zero <zero@zeroboard.com>
     * @brief XE 캐시파일 및 불필요한 파일 정리
     **/
    
    /**
     * @brief 기본적인 상수 선언,  웹에서 직접 호출되는 것을 막기 위해 체크하는 상수 선언
     **/
    define('__ZBXE__', true);

    /**
     * @brief 필요한 설정 파일들을 include
     **/
    require_once('../../config/config.inc.php');

    // id/ password 구함
    $id = $_POST['id'];
    $pw = $_POST['pw'];

    // 저장되어 있는 비밀번호와 비교
    $oContext = &Context::getInstance();
    $oContext->init();
    $db_info = $oContext->getDBInfo();

    // install 모듈의 언어파일을 로드
    Context::loadLang(_XE_PATH_.'modules/install/lang');

    // 설치가 되어 있지 않을 경우 
    if(!Context::isInstalled()) {

        $msg = Context::getLang('msg_db_not_setted');

    // 인증 정보가 없을 경우
    } elseif(!isset($id) || !isset($pw)) {


    // 입력된 정보와 저장된 정보 비교
    } else if($id !== $db_info->db_userid || $pw !== $db_info->db_password) {

        if($id !== $db_info->db_userid) $msg = sprintf($lang->filter->equalto, Context::getLang('user_id'));
        else $msg = sprintf($lang->filter->equalto, Context::getLang('password'));

    // 캐시 파일 제거
    } else if($id === $db_info->db_userid && $pw === $db_info->db_password) {

        $oAdminController = &getAdminController('admin');
        $oAdminController->procAdminRecompileCacheFile();
        $msg = Context::getLang('success_reset');

    }

    Context::set('msg', $msg);

    $oTemplate = &TemplateHandler::getInstance();
    print $oTemplate->compile('./tools/cache_cleaner/','form');
?>
