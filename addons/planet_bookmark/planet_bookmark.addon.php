<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file planet_bookmark.addon.php
     * @author zero (zero@zeroboard.com)
     * @brief
     **/

    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::getResponseMethod()!="JSON") {
        $config = Context::get('config');
        if($config && $config->mid == 'planet'){
            $oPlanet = Context::get('planet');
            if(!is_object($oPlanet)) return;
            if($oPlanet->isMyPlanet()){
                $oPlanetModel = &getModel('planet');
                $countBookmark = $oPlanetModel->getTagSearchResultCount($oPlanet->getModuleSrl(),'bookmark');

                Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar planet_bookmark_count=".( (int)$countBookmark).";\n//]]></script>");
                Context::addJsFile('./addons/planet_bookmark/planet_bookmark.js');
            }
        }
    }
?>
