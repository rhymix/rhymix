<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file autolink.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 자동 링크 애드온
     **/
    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
        $script_code = <<<EndOfScript

    <script type="text/javascript"> 
    // <![CDATA[
        var url_regx = new RegExp("(http|https|ftp|news)://([^ \\r\\n\"\'\>\<]*)","i"); function replaceHrefLink(target_obj) {  var obj_list = new Array(); var obj = target_obj; while(obj) { obj_list[obj_list.length] = obj; obj = obj.nextSibling; } for(var i=0;i<obj_list.length;i++) { var obj = obj_list[i]; var pObj = obj.parentNode; if(!pObj) continue; var pN = pObj.nodeName.toLowerCase(); if(pN == 'a' || pN == 'pre' || pN == 'xml' || pN == 'textarea' || pN == 'input') continue; if(obj.nodeType == 3 && obj.data && url_regx.test(obj.data) ) {  var html = obj.nodeValue.split('<'); for(var i=0;i<html.length;i++) { var html2 = html[i].split('>'); for(var j=0;j<html2.length;j++) { html2[j] = html2[j].replace(url_regx,"<a href=\"$1://$2\" onclick=\"window.open(this.href); return false;\">$1://$2</a>"); } html[i] = html2.join('&gt;'); } var output = html.join('&lt;'); var dummy = xCreateElement('span'); xInnerHtml(dummy, output); pObj.insertBefore(dummy, obj); pObj.removeChild(obj); } else if(obj.nodeType == 1 && obj.firstChild) { replaceHrefLink(obj.firstChild); } } } function addUrlLink() { var objs = xGetElementsByClassName('xe_content'); if(objs.length<1) return; for(var i=0;i<objs.length;i++) { if(url_regx.test(xInnerHtml(objs[i]))) replaceHrefLink(objs[i].firstChild); } } xAddEventListener(window,'load', addUrlLink);
    // ]]>
    </script>

EndOfScript;
        Context::addHtmlHeader($script_code);
    }
?>
