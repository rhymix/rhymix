<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file autolink.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief Automatic link add-on
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
        Context::addJsFile('./addons/autolink/autolink.js', false ,'', null, 'body');
    }
?>
