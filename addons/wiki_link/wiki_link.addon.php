<?php
    if(!defined("__ZBXE__")) exit();

    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::get('act')=='dispWikiEditPage' ) {
        $module_info = Context::get('module_info');
        if(!$module_info->module) return;
        if($module_info->module != 'wiki') return;
        Context::loadJavascriptPlugin('hotkeys');
        Context::addJsFile('./addons/wiki_link/wikilink.js');
        Context::addCssFile('./addons/wiki_link/wikilink.css');
        $footer = "<div id='link'> <div class='linkForm'>Link Target: <input type='text' id='linktarget' class='inputTypeText w100' /><a href='#' onclick='setText(); return false;'>AddLink</a></div></div>";
        Context::addHtmlFooter($footer);
    }
?>
