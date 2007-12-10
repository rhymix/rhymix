<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file referer.addon.php 
     * @author haneul (haneul0318@gmail.com)
     **/

    // called_position가 before_module_init 이고 module이 admin이 아닐 경우
    if($called_position == 'before_module_init' && !$GLOBALS['__referer_addon_called__']) {
	$oController = &getController('referer');
	$oController->procRefererExecute();
        $GLOBALS['__referer_addon_called__'] = true;
    }
?>
