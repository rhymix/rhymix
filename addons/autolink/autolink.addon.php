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

    <script type="text/javascript"> 
        var url_regx = new RegExp("(http|https|ftp|news)://([-/.a-zA-Z0-9_~#%$?&=:200-377()]+)","gi");  function replaceHrefLink(obj) {  while(obj) {  if(obj.nodeType == 3) {  var text = obj.data;  if(url_regx.test(text)) {  var html = text.replace(url_regx,"<a href=\"$1://$2\" onclick=\"window.open(this.href); return false;\">$1://$2</a>");  var dummy = xCreateElement('span');  xInnerHtml(dummy, html);  obj.parentNode.insertBefore(dummy, obj);  obj.parentNode.removeChild(obj);  }  }  if(obj.firstChild) replaceHrefLink(obj.firstChild);  obj = obj.nextSibling;  }  } function addUrlLink() {   var objs = xGetElementsByClassName('xe_content');  if(objs.length<1) return;  for(var i=0;i<objs.length;i++) {   replaceHrefLink(objs[i].firstChild);  xAddEventListener(objs[i], 'mouseover', showUrlOpener);  }  }  function showUrlOpener(e) {   var evt = new xEvent(e);  var obj = evt.target;  var layer = xGetElementById('zbXEUrlOpener');  if(!layer) {   layer = xCreateElement('div');  layer.style.position = 'absolute';  layer.style.border = '3px solid #DDDDDD';  layer.style.backgroundColor = '#FFFFFF';  layer.style.padding = '5px';  layer.style.visibility = 'hidden';  layer.style.lineHeight = '1.6';  layer.setAttribute('id','zbXEUrlOpener');  document.body.appendChild(layer);  }  if(obj && obj.nodeName == 'A' && obj.getAttribute('href') && !/#/.test(obj.getAttribute('href'))) {   var href = obj.getAttribute('href');  if(href.length>40) href = href.substr(0,40)+'...';  var html = ''+   '<a href="'+obj.getAttribute('href')+'" onclick="window.open(this.href); return false;" style="text-decoration:none; color:#555555;">'+href+'</a> [{$open_new_window}]<br />'+  '<a href="'+obj.getAttribute('href')+'" style="text-decoration:none; color:#555555;">'+href+'</a> [{$open_cur_window}]'+   '';  xInnerHtml(layer, html);  xLeft(layer, evt.pageX-20);  xTop(layer, evt.pageY-10);  layer.style.visibility = 'visible';  } else {   layer.style.visibility = 'hidden';  }  }   xAddEventListener(window,'load', addUrlLink);   
    </script>

EndOfScript;
        Context::addHtmlHeader($script_code);
    }
?>
