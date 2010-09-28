<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file counter.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief 카운터 애드온
     **/
    // called_position가 before_display_content 일 경우 실행
    if(Context::isInstalled() && $called_position == 'before_module_init' && Context::get('module')!='admin' && Context::getResponseMethod() == 'HTML') {
        $oCounterController = &getController('counter');
        $oCounterController->procCounterExecute();
    }
?>
