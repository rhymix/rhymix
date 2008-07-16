<?php
    /**
     * @file tools/validator.php
     * @author zero <zero@zeroboard.com>
     * @brief 현재 설치된 버전에 해당하는 XE 파일 비교
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
    Context::loadLang(_XE_PATH_.'modules/admin/lang');
    Context::loadLang(_XE_PATH_.'tools/validator/lang');

    // 설치가 되어 있지 않을 경우 
    if(!Context::isInstalled()) {

        $msg = Context::getLang('msg_db_not_setted');

    // 인증 정보가 없을 경우
    } elseif(!isset($id) || !isset($pw)) {


    // 입력된 정보와 저장된 정보 비교
    } else if($id !== $db_info->db_userid || $pw !== $db_info->db_password) {

        if($id !== $db_info->db_userid) $msg = sprintf($lang->filter->equalto, Context::getLang('user_id'));
        else $msg = sprintf($lang->filter->equalto, Context::getLang('password'));

    // 출력
    } else if($id === $db_info->db_userid && $pw === $db_info->db_password) {
        
        // 현재 버전을 구함
        $ver = __ZBXE_VERSION__;

        // 현재 버전에 맞는 배포 정보를 구함
        $header = "GET /validatorLogs/{$ver}.log HTTP/1.0\r\nHost: news.zeroboard.com\r\n\r\n";
        $is_started = false;
        $f = fsockopen('news.zeroboard.com', 80);
        fputs($f, $header);
        while($buff = fgets($f, 1024)) {
            if(!trim($buff)) $is_started = true;
            if($is_started && trim($buff)) {
                $buff = trim($buff);
                $pos = strpos($buff,',');
                $size = (int)substr($buff,0,$pos);
                $filename = substr($buff,$pos+1);
                if($filename && $size) $source[$filename] = $size;
            }
        }
        fclose($f);

        if(!count($source)) $msg = Context::getLang('msg_cannot_get_source_files');
        else {

            // 현재 설치된 디렉토리와 비교
            $avoid_path = array(_XE_PATH_.'files');
            getFiles(_XE_PATH_, _XE_PATH_, $avoid_path, $target);
            if(!count($target)) $msg = Context::getLang('msg_cannot_get_target_files');
            else {
                // 파일 수를 지정
                Context::set('source_cnt', count($source));
                Context::set('target_cnt', count($target));

                // 최신 버전 정보 구함
                $oAdminView = &getAdminView('admin');
                $oAdminView->dispAdminIndex();

                // 누락된 파일 구함
                $leaveouts = array();
                foreach($source as $key => $val) {
                    if(!isset($target[$key])) $leaveouts[] = $key;
                }
                Context::set('leaveouts', $leaveouts);

                // 수정된 파일 구함
                $modified = array();
                foreach($source as $key => $val) {
                    if(isset($target[$key]) && $val!=$target[$key]) $modified[] = $key;
                }
                Context::set('modified', $modified);

                // 추가된 파일 구함
                $added = array();
                foreach($target as $key => $val) {
                    if(!isset($source[$key])) $added[] = $key;
                }
                Context::set('added', $added);
            }
        }
    }

    Context::set('msg', $msg);

    $oTemplate = &TemplateHandler::getInstance();
    print $oTemplate->compile('./tools/validator/','validator');

    // recursive하게 돌면서 파일 정보 수집
    function getFiles($path, $base_path, $avoid_path, &$buff) {
        if(substr($path,-1)=='/') $path = substr($path,0,-1);
        if(substr($base_path,-1)=='/') $base_path = substr($base_path,0,-1);
        if(in_array($path, $avoid_path)) return;

        $oDir = dir($path);
        while($item = $oDir->read()) {
            if(substr($item,0,1)=='.' && $item != '.htaccess' ) continue;
            $new_path = $path.'/'.$item;
            if(!is_dir($new_path)) {
                $filesize = filesize($new_path);
                $filename = substr($new_path, strlen($base_path)+1);
                $buff[$filename] = $filesize;
            } else {
                getFiles($new_path, $base_path, $avoid_path, $buff);
            }
        }
        $oDir->close();
    }
?>
