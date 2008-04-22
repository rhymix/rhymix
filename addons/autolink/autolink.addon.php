<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file autolink.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 자동 링크 애드온
     **/

    if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
        Context::loadLang("./addons/autolink/lang");
        $open_cur_window = Context::getLang('open_cur_window');
        $open_new_window = Context::getLang('open_new_window');

        $script_code = <<<EndOfScript

    <script type="text/javascript"> function addUrlLink() { var objs = xGetElementsByClassName('xe_content'); if(objs.length<1) return; var rep_list = new Array(); var url_regx = new RegExp("(http|https|ftp|news)://([-/.a-zA-Z0-9_~#%$?&=:200-377()]+)","gi"); for(var i=0;i<objs.length;i++) { var as = xGetElementsByTagName('A', objs[i]); for(var j=0;j<as.length;j++) { xInnerHtml(as[j], xInnerHtml(as[j]).replace('://',';//')); as[j].setAttribute('href', as[j].getAttribute('href').replace('://',';//')); } xInnerHtml(objs[i], xInnerHtml(objs[i]).replace(url_regx, "<a href=\"$1://$2\" onclick=\"window.open(this.href); return false;\">$1://$2</a>")); for(var j=0;j<as.length;j++) { xInnerHtml(as[j], xInnerHtml(as[j]).replace(';//','://')); as[j].setAttribute('href', as[j].getAttribute('href').replace(';//','://')); } xAddEventListener(objs[i], 'mouseover', showUrlOpener); } } function showUrlOpener(e) { var evt = new xEvent(e); var obj = evt.target; var layer = xGetElementById('zbXEUrlOpener'); if(!layer) { layer = xCreateElement('div'); layer.style.position = 'absolute'; layer.style.border = '3px solid #DDDDDD'; layer.style.backgroundColor = '#FFFFFF'; layer.style.padding = '5px'; layer.style.visibility = 'hidden'; layer.style.lineHeight = '1.6'; layer.setAttribute('id','zbXEUrlOpener'); document.body.appendChild(layer); } if(obj && obj.nodeName == 'A' && obj.getAttribute('href') && !/#/.test(obj.getAttribute('href'))) { var href = obj.getAttribute('href'); if(href.length>40) href = href.substr(0,40)+'...'; var html = ''+ '<a href="'+obj.getAttribute('href')+'" style="text-decoration:none; color:#555555;">'+href+'</a> [{$open_cur_window}]<br />'+ '<a href="'+obj.getAttribute('href')+'" onclick="window.open(this.href); return false;" style="text-decoration:none; color:#555555;">'+href+'</a> [{$open_new_window}]'; xInnerHtml(layer, html); xLeft(layer, evt.pageX-20); xTop(layer, evt.pageY-10); layer.style.visibility = 'visible'; } else { layer.style.visibility = 'hidden'; } } xAddEventListener(window,'load', addUrlLink); </script>

EndOfScript;
        Context::addHtmlHeader($script_code);
    }
?>
