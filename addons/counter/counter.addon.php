<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file counter.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 카운터 애드온
     **/
    // called_position가 before_display_content 일 경우 실행
    if(Context::isInstalled() && $called_position == 'before_module_init' && Context::get('module')!='admin') {
        $oCounterController = &getController('counter');
        $oCounterController->procCounterExecute();
    }
?>
