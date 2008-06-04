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
        var url_regx = new RegExp("(http|https|ftp|news)://([^ \\r\\n]*)","gim"); function replaceHrefLink(obj) {  var obj_list = new Array(); while(obj) { obj_list[obj_list.length] = obj; obj = obj.nextSibling; } for(i=0;i<obj_list.length;i++) { obj = obj_list[i]; var pObj = obj.parentNode; if(!pObj) continue; if(obj.firstChild) { replaceHrefLink(obj.firstChild); } else if(obj.nodeType == 3) {  var html = obj.data.replace(url_regx,"<a href=\"$1://$2\" onclick=\"window.open(this.href); return false;\">$1://$2</a>");  var dummy = xCreateElement('span');  xInnerHtml(dummy, html);  pObj.insertBefore(dummy, obj); pObj.removeChild(obj); } } } function addUrlLink() { var objs = xGetElementsByClassName('xe_content'); if(objs.length<1) return; for(var i=0;i<objs.length;i++) { if(url_regx.test(xInnerHtml(objs[i]))) replaceHrefLink(objs[i].firstChild); } } xAddEventListener(window,'load', addUrlLink);
    // ]]>
    </script>

EndOfScript;
        Context::addHtmlHeader($script_code);
    }
?>
