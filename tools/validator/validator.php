<?php
    /**
     * @file tools/validator.php
     * @author zero <zero@zeroboard.com>
     * @brief 현재 설치된 버전에 해당하는 XE 파일 비교
     **/

    // 인증이 되지 않은 접근이면 종료
    if(!defined('__XE_TOOL_AUTH__') || !__XE_TOOL_AUTH__) exit();
    
    // 언어파일을 로드
    Context::loadLang(_XE_PATH_.'modules/admin/lang');

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

    if(!count($source)) $output = Context::getLang('msg_cannot_get_source_files');
    else {
        // 현재 설치된 디렉토리와 비교
        $avoid_path = array(_XE_PATH_.'files');
        getFiles(_XE_PATH_, _XE_PATH_, $avoid_path, $target);
        if(!count($target)) $output = Context::getLang('msg_cannot_get_target_files');
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

            // CSS 지정
            Context::addCssFile('./validator/style.css', false);

            // 결과물을 구함
            $oTemplate = &TemplateHandler::getInstance();
            $output = $oTemplate->compile('./tools/validator/','validator');
        }
    }
    Context::set('output', $output);

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
