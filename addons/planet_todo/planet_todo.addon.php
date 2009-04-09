<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file planet_todo.addon.php
     * @author SOL군 (sol@ngleader.com)
     * @brief
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::getResponseMethod()!="JSON") {
        $planet = Context::get('planet');
        if(!$planet || !$planet->isMyPlanet()) return;

        $oPlanetModel = &getModel('planet');
        $countTodo = $oPlanetModel->getTagSearchResultCount($planet->getModuleSrl(),'todo');
        $countDone = $oPlanetModel->getTagSearchResultCount($planet->getModuleSrl(),'done');

        Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar planet_todo_count={todo:".$countTodo.",done:".$countDone."};\n//]]></script>");
        Context::addJsFile('./addons/planet_todo/planet_todo.js');
    }
?>
