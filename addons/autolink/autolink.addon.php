<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file autolink.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief 자동 링크 애드온
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
        Context::addJsFile('./addons/autolink/autolink.js', false ,'', null, 'body');
    }
?>
