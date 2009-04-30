function openWikiLinkDialog()
{
    var wikiLink = jQuery('#wikiLink');
    if ( wikiLink.length < 1 ) {
        try{
            jQuery('<div id="wikiLink">Link Target : <input type="text" id="linktarget" class="inputTypeText" style="width:200px;" /></div>')
            .appendTo('body')
            .dialog({
                title:'wiki Link', width:300, height:60, resizable:false,
                modal: false, overlay: { opacity: 1, background: "#fff" },
                buttons: { "add link": function() { setText(); jQuery(this).dialog("close"); }, "cancel": function() { jQuery(this).dialog("close"); } },
                show: 'drop' /* , hide: 'explode' */
            });
        } catch(e){
        }
    } else {
        wikiLink.dialog('open');
    }
}

function setText() {
    var target = xGetElementById('linktarget');
    if(!target.value || target.value.trim() == '') return;
    var text = target.value;
    text.replace(/&/ig,'&amp;').replace(/</ig,'&lt;').replace(/>/ig,'&gt;');
    var url = request_uri.setQuery('mid',current_mid).setQuery('entry',text); 
    if(typeof(xeVid)!='undefined') url = url.setQuery('vid', xeVid);
    var link = "<a href=\""+url+"\" ";
    link += ">"+text+"</a>";

    var iframe_obj = editorGetIFrame(1)
    editorReplaceHTML(iframe_obj, link);
    jQuery("#link").dialog("close");
}

function addShortCutForWiki() 
{
    var iframe_obj = editorGetIFrame(1);
    jQuery(iframe_obj.contentWindow.document).bind('keydown', "CTRL+SHIFT+SPACE", function(evt) { openWikiLinkDialog(); }); 
    if(jQuery.os.Mac) {
        jQuery(iframe_obj.contentWindow.document).bind('keydown', "ALT+SPACE", function(evt) { openWikiLinkDialog(); }); 
    } else {
        jQuery(iframe_obj.contentWindow.document).bind('keydown', "CTRL+SPACE", function(evt) { openWikiLinkDialog(); }); 
    }
    jQuery(document).bind('keydown',"CTRL+SHIFT+SPACE", function(evt) {} );
}

jQuery(window).load( function() { addShortCutForWiki() } );

