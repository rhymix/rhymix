<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file autolink.addon.php
     * @author SOL군 (sol@ngleader.com)
     * @brief 
     **/



    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
        $config = Context::get('config');
        if($config->mid == 'planet'){
            $oPlanet = Context::get('planet');
            if($oPlanet->isMyPlanet()){
                Context::addJsFile('./addons/planet_tab/planet_tab.js');
            }
        }
    }
?>
