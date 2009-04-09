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
        $countBookmark = $oPlanetModel->getTagSearchResultCount($planet->getModuleSrl(),'bookmark');
        Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar planet_bookmark_count=".( (int)$countBookmark).";\n//]]></script>");
        Context::addJsFile('./addons/planet_bookmark/planet_bookmark.js');
    }
?>
