<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file planet_todo.addon.php
     * @author SOL군 (sol@ngleader.com)
     * @brief
     **/



    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::getResponseMethod()!="JSON") {
        $config = Context::get('config');

    //getTagSearchResultCount
        if($config->mid == 'planet'){
            $oPlanet = Context::get('planet');
            if($oPlanet->isMyPlanet()){
                $oPlanetModel = &getModel('planet');
                $countTodo = $oPlanetModel->getTagSearchResultCount($this->module_srl,'todo');
                $countDone = $oPlanetModel->getTagSearchResultCount($this->module_srl,'done');

                Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar planet_todo_count={todo:".$countTodo.",done:".$countDone."};\n//]]></script>");
                Context::addJsFile('./addons/planet_todo/planet_todo.js');
            }
        }
    }
?>