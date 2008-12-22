<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file keyword_link.addon.php
     * @author sol (sol@ngleader.com)
     * @brief 키워드 링크 애드온
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC" && Context::getResponseMethod()!="JSON" ) {
        $json = array();
        for($i=1;$i<=5;$i++){
            $ii = sprintf("%02d",$i);
            $keyword = $addon_info->{"keyword".$ii};
            $url = $addon_info->{"url".$ii};
            if($keyword  && $url){
                $arg->url = $url;
                $keyword = explode(",",$keyword);
                for($j=0,$c=count($keyword);$j<$c;$j++){
                    if(trim($keyword[$j]) == "") continue;
                    $arg->keyword = trim($keyword[$j]);
                    $json[] = $arg;
                }
            }
        }

        if(count($json) > 0){
            $addon_keyword_link = json_encode2($json);
            Context::addHtmlHeader("<script type=\"text/javascript\">//<![CDATA[\nvar addon_keyword_link={$addon_keyword_link};\nvar addon_keyword_link_cssquery='{$addon_info->cssquery}';\nvar addon_keyword_link_reg_type='{$addon_info->reg_type}';\n//]]></script>");
            Context::addJsFile('./addons/keyword_link/keyword_link.js');
        }
    }
?>