function openWikiLinkDialog()
{
    var link = jQuery("#link");
    link.css('display', 'block');
    var target = xGetElementById('linktarget');
    target.value = "";
    try{
        link.dialog({height:100});
    }
    catch(e)
    {
        link.dialog("open");
    }
}

function setText() {
    var target = xGetElementById('linktarget');
    if(!target.value || target.value.trim() == '') return;
    var text = target.value;
    text.replace(/&/ig,'&amp;').replace(/</ig,'&lt;').replace(/>/ig,'&gt;');
    var url = request_uri.setQuery('mid',current_mid).setQuery('entry',text); 
    var link = "<a href=\""+url+"\" ";
    link += ">"+text+"</a>";

    var iframe_obj = editorGetIFrame(1)
    editorReplaceHTML(iframe_obj, link);
    jQuery("#link").dialog("close");
}

function addShortCutForWiki() 
{
    var iframe_obj = editorGetIFrame(1);
    if(jQuery.os.Mac)
    {
        jQuery(iframe_obj.contentWindow.document).bind('keydown', "ALT+SPACE", function(evt) { openWikiLinkDialog(); }); 
    }
    else
    {
        jQuery(iframe_obj.contentWindow.document).bind('keydown', "CTRL+SPACE", function(evt) { openWikiLinkDialog(); }); 
    }
    jQuery(document).bind('keydown',"CTRL+ALT+SPACE", function(evt) {} );
}

xAddEventListener(window, 'load', addShortCutForWiki);

