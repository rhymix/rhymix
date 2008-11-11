<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file planet_todo.addon.php
     * @author SOL군 (sol@ngleader.com)
     * @brief
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::getResponseMethod()!="JSON") {
        $config = Context::get('config');

        if($config && $config->mid == 'planet'){
            $oPlanet = Context::get('planet');
            if(!is_object($oPlanet)) return;
            if($oPlanet->isMyPlanet()){
                $oPlanetModel = &getModel('planet');
                $countTodo = $oPlanetModel->getTagSearchResultCount($oPlanet->getModuleSrl(),'todo');
                $countDone = $oPlanetModel->getTagSearchResultCount($oPlanet->getModuleSrl(),'done');

                Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar planet_todo_count={todo:".$countTodo.",done:".$countDone."};\n//]]></script>");
                Context::addJsFile('./addons/planet_todo/planet_todo.js');
            }
        }
    }
?>
