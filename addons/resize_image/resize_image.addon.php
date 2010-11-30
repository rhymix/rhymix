<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file resize_image.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief 본문내 이미지 조절 애드온
     **/

    if($called_position == 'after_module_proc' && Context::getResponseMethod()=="HTML") {
        Context::loadJavascriptPlugin('ui');
        Context::addJsFile('./addons/resize_image/js/resize_image.min.js',false, '',null, 'body');
    }
?>
