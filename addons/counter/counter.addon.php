<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file counter.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 카운터 애드온
     *
     * 카운터 애드온은 제로보드XE의 기본 카운터(counter)모듈을 이용하여 로그를 남깁니다.
     * 검색 로봇이나 기타 툴의 접속을 방지하고 부하를 줄이기 위해서 페이지가 로드된 후에 javascript로 다시 로그를 남기도록 합니다.
     * 따라서 이 카운터 애드온은 카운터를 수집하게 하는 javascript 파일을 추가하는 동작만 하며 기본 카운터 모듈의 호출은 해당 javascript
     * 파일내에서 이루어집니다.
     **/

    // called_position가 before_module_init 이고 module이 admin이 아닐 경우
    if($called_position == 'before_module_init' && !$GLOBALS['__counter_addon_called__']) {
        if($this->module != 'admin') Context::addJsFile('./modules/counter/tpl/js/counter.js');
        $GLOBALS['__counter_addon_called__'] = true;
    }
?>
