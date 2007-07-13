<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file google_adsense.addon.php
     * @author zero (zero@nzeo.com)
     * @brief google_adsense를 게시글의 상/하단에 출력할 수 있도록 한다.
     *
     * 모든 출력이 끝난후에 사용이 됨.
     **/

    // called_position이 before_module_init일때만 실행
    if($called_position != 'before_display_content' || Context::getResponseMethod()=="XMLRPC") return;

    require_once("./addons/google_adsense/google_adsense.lib.php");

    if($addon_info->position == "top") $pos_regx = "!<\!--BeforeDocument\(([0-9]+),([0-9]+)\)-->!is";
    else $pos_regx = "!<\!--AfterDocument\(([0-9]+),([0-9]+)\)-->!is";

    $GLOBALS['__g_addon_info__'] = $addon_info;

    $output = preg_replace_callback($pos_regx, matchDocument, $output);
?>
