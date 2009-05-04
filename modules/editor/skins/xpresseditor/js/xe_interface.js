if (!window.xe) xe = {};

xe.Editors = [];

function editorStart_xe(editor_sequence, primary_key, content_key, editor_height, colorset, content_style, content_font) {
    if(typeof(colorset)=='undefined') colorset = 'white';
    if(typeof(content_style)=='undefined') content_style = 'xeStyle';
    if(typeof(content_font)=='undefined') content_font= '';

    var target_src = request_uri+'modules/editor/styles/'+content_style+'/editor.html';

    var textarea = jQuery("#xpress-editor-"+editor_sequence);
    var iframe   = jQuery('<iframe id="editor_iframe_'+editor_sequence+'" allowTransparency="true" frameborder="0" src="'+target_src+'" scrolling="yes" style="width:100%;height:'+editor_height+'px">');
    var htmlsrc  = jQuery('<textarea rows="10" cols="20" class="input_syntax '+colorset+'" style="display:none"></textarea>');
    var form     = textarea.get(0).form;
    form.setAttribute('editor_sequence', editor_sequence);
    textarea.css("display","none");

    var saved_content = '';
    if(jQuery("input[name=content]",form).size()>0){
        saved_content=jQuery("input[name=content]",form).val().replace(/src=\"files\/attach/g,'src="'+request_uri+'files/attach'); //'
        jQuery("#xpress-editor-"+editor_sequence).val(saved_content);
    }

	/*
    // remove procFilter
    if(form.comment_srl){
        form.onsubmit=function(){
            var content = editorGetContent(editor_sequence);
            editorRelKeys[editor_sequence]['content'].value = content;
            insert_comment(form);
            return false;
        };
    }else{
        form.onsubmit=function(){
            var content = editorGetContent(editor_sequence);
            editorRelKeys[editor_sequence]['content'].value = content;
            insert(form);
            return false;
        };
    }
    */

    // hide textarea
    textarea.hide().css('width', '99%').before(iframe).after(htmlsrc);

    // create an editor
    var oEditor          = new xe.XpressCore();
    var oWYSIWYGIFrame   = iframe.get(0);
    var oIRTextarea      = textarea.get(0);
    var oHTMLSrcTextarea = htmlsrc.get(0);
    var elAppContainer   = jQuery('.xpress-editor', form).get(0);

    oEditor.getFrame = function(){ return oWYSIWYGIFrame;}

    var content = form[content_key].value;

    // src, href, url의 XE 상대경로를 http로 시작하는 full path로 변경
    content = content.replace(/(src=|href=|url\()("|\')*([^"\'\)]+)("|\'|\))*(\s|>)*/ig, function(m0,m1,m2,m3,m4,m5) {
        if(m1=="url(") { m2=''; m4=')'; } else { if(typeof(m2)=='undefined') m2 = '"'; if(typeof(m4)=='undefined') m4 = '"'; if(typeof(m5)=='undefined') m5 = ''; }
        var val = jQuery.trim(m3).replace(/^\.\//,'');
        if(/^(http|https|ftp|telnet|\/|\.\.)/i.test(val)) return m0;
        return m1+m2+request_uri+val+m4+m5;
    });

    form[content_key].value = content;
    jQuery("#xpress-editor-"+editor_sequence).val(content);

    // Set standard API
    editorRelKeys[editor_sequence] = new Array();
    editorRelKeys[editor_sequence]["primary"]   = form[primary_key];
    editorRelKeys[editor_sequence]["content"]   = form[content_key];
    editorRelKeys[editor_sequence]["func"]      = editorGetContentTextarea_xe;
    editorRelKeys[editor_sequence]["editor"]    = oEditor;
    editorRelKeys[editor_sequence]["pasteHTML"] = function(text){
        oEditor.exec('PASTE_HTML',[text]);
    }
    xe.Editors[editor_sequence] = oEditor;

    // register plugins
    oEditor.registerPlugin(new xe.CorePlugin(null));

    oEditor.registerPlugin(new xe.XE_PreservTemplate(jQuery("#xpress-editor-"+editor_sequence).val()));
    oEditor.registerPlugin(new xe.StringConverterManager());
    oEditor.registerPlugin(new xe.XE_EditingAreaManager("WYSIWYG", oIRTextarea, {nHeight:parseInt(editor_height), nMinHeight:205}, null, elAppContainer));
    oEditor.registerPlugin(new xe.XE_EditingArea_HTMLSrc(oHTMLSrcTextarea));
    oEditor.registerPlugin(new xe.XE_EditingAreaVerticalResizer(elAppContainer));
    oEditor.registerPlugin(new xe.Utils());
    oEditor.registerPlugin(new xe.DialogLayerManager());
    oEditor.registerPlugin(new xe.ActiveLayerManager());
    oEditor.registerPlugin(new xe.Hotkey());
    oEditor.registerPlugin(new xe.XE_WYSIWYGStyler());
    oEditor.registerPlugin(new xe.XE_WYSIWYGStyleGetter());
    oEditor.registerPlugin(new xe.MessageManager(oMessageMap));
    oEditor.registerPlugin(new xe.XE_Toolbar(elAppContainer));

    oEditor.registerPlugin(new xe.XE_XHTMLFormatter);
    oEditor.registerPlugin(new xe.XE_GET_WYSYWYG_MODE(editor_sequence));
    oEditor.registerPlugin(new xe.XE_GET_WYSYWYG_CONTENT());

    if(jQuery("ul.extra1").length) {
        oEditor.registerPlugin(new xe.XE_ColorPalette(elAppContainer));
        oEditor.registerPlugin(new xe.XE_FontColor(elAppContainer));
        oEditor.registerPlugin(new xe.XE_BGColor(elAppContainer));
        oEditor.registerPlugin(new xe.XE_Quote(elAppContainer));
        oEditor.registerPlugin(new xe.XE_FontNameWithSelectUI(elAppContainer));
        oEditor.registerPlugin(new xe.XE_FontSizeWithSelectUI(elAppContainer));
        oEditor.registerPlugin(new xe.XE_LineHeightWithSelectUI(elAppContainer));
        oEditor.registerPlugin(new xe.XE_UndoRedo());
        oEditor.registerPlugin(new xe.XE_Table(elAppContainer));
        oEditor.registerPlugin(new xe.XE_Hyperlink(elAppContainer));
        oEditor.registerPlugin(new xe.XE_FindReplacePlugin(elAppContainer));
        oEditor.registerPlugin(new xe.XE_FormatWithSelectUI(elAppContainer));
        oEditor.registerPlugin(new xe.XE_SCharacter(elAppContainer));
    }

    if(jQuery("ul.extra2").length) {
        oEditor.registerPlugin(new xe.XE_Extension(elAppContainer, editor_sequence));
    }

    if(jQuery("ul.extra3").length) {
        oEditor.registerPlugin(new xe.XE_EditingModeToggler(elAppContainer));
    }


    //oEditor.registerPlugin(new xe.XE_Preview(elAppContainer));

    if (!jQuery.browser.msie && !jQuery.browser.opera) {
        oEditor.registerPlugin(new xe.XE_WYSIWYGEnterKey(oWYSIWYGIFrame));
    }

    // 자동 저장 사용
    if (s=form._saved_doc_title) {
        oEditor.registerPlugin(new xe.XE_AutoSave(oIRTextarea, elAppContainer));
    }

    function load_proc() {
    	try {
    		var doc = oWYSIWYGIFrame.contentWindow.document, str;
    		if (doc.location == 'about:blank') throw 'blank';
    		
    		// get innerHTML
    		str = doc.body.innerHTML;
    		
    		// register plugin
    		oEditor.registerPlugin(new xe.XE_EditingArea_WYSIWYG(oWYSIWYGIFrame));
    		oEditor.registerPlugin(new xe.XpressRangeManager(oWYSIWYGIFrame));
    		oEditor.registerPlugin(new xe.XE_ExecCommand(oWYSIWYGIFrame));

            if(content_font && !doc.body.style.fontFamily) {
                doc.body.style.fontFamily = content_font;
            }
    		
    		// run
	    	oEditor.run();
    	} catch(e) {
    		setTimeout(load_proc, 0);
    	}
    }
    
    load_proc();

    return oEditor;
}

function editorGetContentTextarea_xe(editor_sequence) {
    var oEditor = xe.Editors[editor_sequence] || null;

    if (!oEditor) return '';

    return oEditor.getIR();
}

function editorGetIframe(srl) {
    return jQuery('iframe#editor_iframe_'+srl).get(0);
}

function editorReplaceHTML(iframe_obj, content) {
    // src, href, url의 XE 상대경로를 http로 시작하는 full path로 변경
    content = content.replace(/(src=|href=|url\()("|\')*([^"\'\)]+)("|\'|\))*(\s|>)*/ig, function(m0,m1,m2,m3,m4,m5) {
        if(m1=="url(") { m2=''; m4=')'; } else { if(typeof(m2)=='undefined') m2 = '"'; if(typeof(m4)=='undefined') m4 = '"'; if(typeof(m5)=='undefined') m5 = ''; }
        var val = jQuery.trim(m3).replace(/^\.\//,'');
        if(/^(http|https|ftp|telnet|\/|\.\.)/i.test(val)) return m0;
        return m1+m2+request_uri+val+m4+m5;
    });

    var srl = parseInt(iframe_obj.id.replace(/^.*_/,''),10);
    editorRelKeys[srl]["pasteHTML"](content);
}

// WYSIWYG 모드를 저장하는 확장기능
xe.XE_GET_WYSYWYG_MODE = jQuery.Class({
    name : "XE_GET_WYSYWYG_MODE",

    $init : function(editor_sequence) {
        this.editor_sequence = editor_sequence;
    },

    $ON_CHANGE_EDITING_MODE : function(mode) {
        editorMode[this.editor_sequence] = (mode =='HTMLSrc') ? 'html' : 'wysiwyg';
    }
});

// 이미지등의 상대경로를 절대경로로 바꾸는 플러그인 
xe.XE_GET_WYSYWYG_CONTENT = jQuery.Class({
    name : "XE_GET_WYSYWYG_CONTENT",

    $ON_MSG_APP_READY : function() {
        this.oApp.addConverter("IR_TO_WYSIWYG", this.TO_WYSIWYG_SET);
        this.oApp.addConverter("IR_TO_HTMLSrc", this.IR_TO_HTMLSrc);
    },

    TO_WYSIWYG_SET : function(content) {
        // src, href, url의 XE 상대경로를 http로 시작하는 full path로 변경
        content = content.replace(/(src=|href=|url\()("|\')*([^"\'\)]+)("|\'|\))*(\s|>)*/ig, function(m0,m1,m2,m3,m4,m5) {
            if(m1=="url(") { m2=''; m4=')'; } else { if(typeof(m2)=='undefined') m2 = '"'; if(typeof(m4)=='undefined') m4 = '"'; if(typeof(m5)=='undefined') m5 = ''; }
            var val = jQuery.trim(m3).replace(/^\.\//,'');
            if(/^(http|https|ftp|telnet|\/|\.\.)/i.test(val)) return m0;
            return m1+m2+request_uri+val+m4+m5;
        });

        return content;
    },

    IR_TO_HTMLSrc : function(content) {
        // src, href, url의 XE 상대경로를 http로 시작하는 full path로 변경
        content = content.replace(/(src=|href=|url\()("|\')*([^"\'\)]+)("|\'|\))*(\s|>|\))*/ig, function(m0,m1,m2,m3,m4,m5) {
            var uriReg = new RegExp('^'+request_uri.replace('\/','\\/'),'ig');
            if(m1=="url(") { m2=''; m4=')'; } else { if(typeof(m2)=='undefined') m2 = '"'; if(typeof(m4)=='undefined') m4 = '"'; if(typeof(m5)=='undefined') m5 = ''; }
            var val = jQuery.trim(m3);
            if(uriReg.test(val)) val = val.replace(uriReg,'');
            else val = m3;
            return m1+m2+val+m4+m5;
        });
        return content;

    }
});

// 서식 기본 내용을 보존
xe.XE_PreservTemplate = jQuery.Class({
    name : "XE_PreservTemplate",
    isRun : false,

    $BEFORE_SET_IR : function(content) {
        if(!this.isRun && !content) {
            this.isRun = true;
            return false;
        }
    }
});

// 미리보기 확장기능
xe.XE_Preview = jQuery.Class({
    name  : "XE_Preview",
    elPreviewButton : null,

    $init : function(elAppContainer) {
        this._assignHTMLObjects(elAppContainer);
    },

    _assignHTMLObjects : function(elAppContainer) {
        this.elPreviewButton = jQuery("BUTTON.xpress_xeditor_preview_button", elAppContainer);
    },

    $ON_MSG_APP_READY : function() {
        this.oApp.registerBrowserEvent(this.elPreviewButton.get(0), "click", "EVENT_PREVIEW", []);
    },

    $ON_EVENT_PREVIEW : function() {
        // TODO : 버튼이 눌렸을 때의 동작 정의
    }
});
