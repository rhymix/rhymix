if (!window.xe) xe = {};

xe.Editors = [];

function editorStart_xe(editor_sequence, primary_key, content_key, editor_height) {

    var textarea = jQuery("#xpress-editor-"+editor_sequence);
    var iframe   = jQuery('<iframe id="editor_iframe_'+editor_sequence+'"frameborder="0" src="'+editor_path+'/blank.html" scrolling="yes" style="width:100%;height:'+editor_height+'px">');
    var htmlsrc  = jQuery('<textarea rows="10" cols="20" class="input_syntax" style="display:none"></textarea>');
    var form     = textarea.get(0).form;
    form.setAttribute('editor_sequence', editor_sequence);

    var saved_content = '';
    if(jQuery("input[name=content]",form).size()>0){
        saved_content=jQuery("input[name=content]",form).val().replace(/src=\"files\/attach/g,'src="'+request_uri+'files/attach');
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

    oEditor.registerPlugin(new xe.StringConverterManager());
    oEditor.registerPlugin(new xe.XE_EditingAreaManager("WYSIWYG", oIRTextarea, {nHeight:parseInt(editor_height), nMinHeight:205}, null, elAppContainer));
    oEditor.registerPlugin(new xe.XE_EditingArea_WYSIWYG(oWYSIWYGIFrame));
    oEditor.registerPlugin(new xe.XE_EditingArea_HTMLSrc(oHTMLSrcTextarea));
    oEditor.registerPlugin(new xe.XE_EditingAreaVerticalResizer(elAppContainer));
    oEditor.registerPlugin(new xe.Utils());
    oEditor.registerPlugin(new xe.DialogLayerManager());
    oEditor.registerPlugin(new xe.ActiveLayerManager());
    oEditor.registerPlugin(new xe.XpressRangeManager(oWYSIWYGIFrame));
    oEditor.registerPlugin(new xe.Hotkey());
    oEditor.registerPlugin(new xe.XE_WYSIWYGStyler());
    oEditor.registerPlugin(new xe.XE_WYSIWYGStyleGetter());
    oEditor.registerPlugin(new xe.XE_Toolbar(elAppContainer));
    oEditor.registerPlugin(new xe.XE_ExecCommand(oWYSIWYGIFrame));
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
    oEditor.registerPlugin(new xe.XE_EditingModeToggler(elAppContainer));
    oEditor.registerPlugin(new xe.MessageManager(oMessageMap));
    oEditor.registerPlugin(new xe.XE_SCharacter(elAppContainer));
    oEditor.registerPlugin(new xe.XE_FindReplacePlugin(elAppContainer));
    oEditor.registerPlugin(new xe.XE_XHTMLConverter);
    oEditor.registerPlugin(new xe.XE_Preview(elAppContainer));
    oEditor.registerPlugin(new xe.XE_GET_WYSYWYG_MODE(editor_sequence));
    oEditor.registerPlugin(new xe.XE_Extension(elAppContainer, editor_sequence));
    oEditor.registerPlugin(new xe.XE_FormatWithSelectUI(elAppContainer));

    if (!jQuery.browser.msie && !jQuery.browser.opera) {
        oEditor.registerPlugin(new xe.XE_WYSIWYGEnterKey(oWYSIWYGIFrame));
    }

    // 자동 저장 사용?
    if (s=form._saved_doc_title) {
        oEditor.registerPlugin(new xe.XE_AutoSave(oIRTextarea, elAppContainer));
    }

    // run
    oEditor.run();

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

function editorReplaceHTML(iframe_obj, text) {
    var srl = parseInt(iframe_obj.id.replace(/^.*_/,''),10);
    editorRelKeys[srl]["pasteHTML"](text);

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
