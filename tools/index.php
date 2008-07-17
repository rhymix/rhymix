<?php
    /**
     * @file tools/index.php
     * @author zero <zero@zeroboard.com>
     * @brief 각종 tools 목록을 보여주기 인증 시도
     **/
    
    /**
     * @brief 기본적인 상수 선언,  웹에서 직접 호출되는 것을 막기 위해 체크하는 상수 선언
     **/
    define('__ZBXE__', true);

    /**
     * @brief 필요한 설정 파일들을 include
     **/
    require_once('../config/config.inc.php');

    // id/ password/ tool 구함
    $id = $_POST['id'];
    $pw = $_POST['pw'];
    $tool = $_POST['tool'];

    // 저장되어 있는 비밀번호와 비교
    $oContext = &Context::getInstance();
    $oContext->init();
    $db_info = $oContext->getDBInfo();

    // 툴 목록을 구하고 언어파일 로그, 변수 설정
    Context::loadLang(_XE_PATH_.'modules/install/lang');
    $list = FileHandler::readDir(_XE_PATH_.'tools/');
    $filters = array('index.php','style.css','form.html');
    for($i=0;$i<count($list);$i++) {
        if(in_array($list[$i], $filters)) continue;
        Context::loadLang(_XE_PATH_.'tools/'.$list[$i].'/lang');
        $tools[$list[$i]] = Context::getLang($list[$i]);
    }
    Context::set('tools', $tools);
    
    // 설치가 되어 있지 않을 경우 
    if(!Context::isInstalled()) {

        $msg = Context::getLang('msg_db_not_setted');

    // 인증 정보가 없을 경우
    } elseif(!isset($id) || !isset($pw)) {


    // 입력된 정보와 저장된 정보 비교
    } else if($id !== $db_info->db_userid || $pw !== $db_info->db_password) {

        if(!$tool) $msg = Context::getLang('msg_not_founded');
        elseif($id !== $db_info->db_userid) $msg = sprintf($lang->filter->equalto, Context::getLang('user_id'));
        else $msg = sprintf($lang->filter->equalto, Context::getLang('password'));

    // tool 실행
    } else if($id === $db_info->db_userid && $pw === $db_info->db_password) {

        define('__XE_TOOL_AUTH__', true);
        include(_XE_PATH_.'tools/'.$tool.'/'.$tool.'.php'); 
    }

    Context::set('msg', $msg);

    $oTemplate = &TemplateHandler::getInstance();
    print $oTemplate->compile('./tools','form');
?>
