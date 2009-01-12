<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file reaize_image.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 본문내 이미지 조절 애드온
     **/

    if($called_position == 'after_module_proc' && Context::getResponseMethod()=="HTML") {
        Context::loadJavascriptPlugin('ui');
        Context::addJsFile('./addons/resize_image/js/resize_image.js',false);
    }
?>
