function editorGetForm_xq(element) {
    var fo_obj = element.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    if(fo_obj.nodeName == 'FORM') return fo_obj;
    return;
}   

function getAdditionalAutocompletions() {
    return [
	{
	    id:'link',
	    criteria: /"[^"]+":http:\/\/[^ ]+$/i,
	    handler: function(xed, rdom, block, wrapper, text) {
		var sp = text.split("\"");
		var node = rdom.createElement('A');
		node.innerHTML = sp[1];
		node.href = sp[2].substr(1);

		wrapper.innerHTML = "";
		wrapper.appendChild(node);
	    }
	}
    ];
}

function editorGetContent_xq(editor_sequence) {
    var editor = editorRelKeys[editor_sequence]['editor'];
    return editor.getCurrentContent(true);
}

function editorStart_xq(editor, element, editor_sequence, content_key, editor_height, primary_key) {
    editor = new xq.Editor(element);
    var additionalAttributes = ['editor_component', 'poll_srl','multimedia_src', 'auto_start', 'link_url', 'editor_sequence', 'use_folder', 'folder_opener', 'folder_closer', 'color', 'border_thickness', 'border_color', 'bg_color', 'border_style', 'margin', 'padding', 'bold', 'nx', 'ny', 'gx', 'gy', 'address', 'reg_sinpic', 'language'];
    var additionalTags = ['embed', 'param', 'object'];
    additionalAttributes.each( function (item, index) {
	editor.config.allowedAttributes.push(item); } );
    additionalTags.each( function (item, index) { editor.config.allowedTags.push(item); } );

    editorRelKeys[editor_sequence] = new Array();
    editorRelKeys[editor_sequence]['editor'] = editor; 
    editorRelKeys[editor_sequence]['func'] = editorGetContent_xq;
    editorMode[editor_sequence] = null;
    var fo_obj = editorGetForm_xq(element);
    fo_obj.setAttribute('editor_sequence', editor_sequence);
    editorRelKeys[editor_sequence]['content'] = fo_obj[content_key];
    editorRelKeys[editor_sequence]['primary'] = fo_obj[primary_key];

    // saved document(자동저장 문서)에 대한 확인
    if(typeof(fo_obj._saved_doc_title)!="undefined" ) { ///<< _saved_doc_title field가 없으면 자동저장 하지 않음

        var saved_title = fo_obj._saved_doc_title.value;
        var saved_content = fo_obj._saved_doc_content.value;

        if(saved_title || saved_content) {
            // 자동저장된 문서 활용여부를 물은 후 사용하지 않는다면 자동저장된 문서 삭제
            if(confirm(fo_obj._saved_doc_message.value)) {
                if(typeof(fo_obj.title)!='undefined') fo_obj.title.value = saved_title;
                editorRelKeys[editor_sequence]['content'].value = saved_content;
            } else {
                editorRemoveSavedDoc();
            }
        }
    }

    editor.setStaticContent(fo_obj[content_key].value);
    editor.setEditMode('wysiwyg');
    editor.loadStylesheet(request_uri+editor_path+"/examples/css/xq_contents.css");
    editor.getFrame().style.width = "100%";
    editor.getFrame().parentNode.style.height = editor_height;
    editor.getBody().setAttribute('editor_sequence', editor_sequence);
    editor.addAutocompletions(getAdditionalAutocompletions());

    // 위젯 감시를 위한 더블클릭 이벤트 걸기 
    try {
        xAddEventListener(editor.getFrame().contentWindow.document,'dblclick',editorSearchComponent);
    } catch(e) {
    }

    if(typeof(fo_obj._saved_doc_title)!="undefined" ) editorEnableAutoSave(fo_obj, editor_sequence);
}

xq.Editor.prototype.insertHTML = function (html) {
    this.rdom.insertHtml(html);
}

xq.ui_templates.basicLangSelectDialog='<form action="#" class="xqFormDialog xqBasicLangSelectDialog">\n	<div>\n		 	<select name="lang">\n			<option value="Php">PHP</option>\n			<option value="Css">CSS</option>\n			<option value="JScript">Javascript</option>\n			<option value="Xml">XML</option>\n			<option value="Cpp">C++</option>\n			<option value="CSharp">C#</option>\n			 <option value="Vb">VB</option>\n			 <option value="Java">Java</option>\n			 <option value="Delphi">Delphi</option>\n			 <option value="Python">Python</option>\n			 <option value="Ruby">Ruby</option>\n			 <option value="Sql">SQL</option>\n		 </select>\n		<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\	</div>\n</form>';

xq.Editor.prototype.handleList = function (type, selected) {
    if(type == "CODE" && selected == undefined)
    {
	var dialog = new xq.controls.FormDialog(
	    this,
	    xq.ui_templates.basicLangSelectDialog,
	    function(dialog) {
	    },
	    function(data) {
		this.focus();
		if(!data) return;
		this.handleList("CODE", data.lang);
	    }.bind(this)
	);

	dialog.show({position: 'centerOfEditor'});

	return true;
    }

    if(this.rdom.hasSelection()) {
	var blocks = this.rdom.getBlockElementsAtSelectionEdge(true, true);
	if(blocks.first() != blocks.last()) {
	    blocks = this.rdom.applyLists(blocks.first(), blocks.last(), type);
	} else {
	    blocks[0] = blocks[1] = this.rdom.applyList(blocks.first(), type);
	}
	this.rdom.selectBlocksBetween(blocks.first(), blocks.last());
    } else {
	var block = this.rdom.applyList(this.rdom.getCurrentBlockElement(), type);
	this.rdom.placeCaretAtStartOf(block);
	if(selected != undefined) {
		block.parentNode.setAttribute("language", selected);
	}
    }
    var historyAdded = this.editHistory.onCommand();
    this._fireOnCurrentContentChanged(this);

    return true;
}

function editor_insert_file_xq(editor_sequence) {
    if(editorMode[editor_sequence]=='html') return;
    var obj = xGetElementById('uploaded_file_list_'+editor_sequence);
    if(obj.options.length<1) return;
    
    var editor = editorRelKeys[editor_sequence]['editor'];
    editor.focus();

    for(var i=0;i<obj.options.length;i++) {
        var sel_obj = obj.options[i];
        if(!sel_obj.selected) continue;

        var file_srl = sel_obj.value;
        if(!file_srl) continue;

        var file_obj = uploaded_files[file_srl];
        var filename = file_obj.filename;
        var sid = file_obj.sid;
        var url = file_obj.uploaded_filename.replace(request_uri,'');

        if(url.indexOf("binaries")==-1) {
            // 이미지 파일의 경우 image_link 컴포넌트 열결
            if(/\.(jpg|jpeg|png|gif)$/i.test(url)) {
                var text = "<img editor_component=\"image_link\" src=\""+url+"\" alt=\""+file_obj.filename+"\" />";
		editor.insertHTML(text);
            // 이미지외의 경우는 multimedia_link 컴포넌트 연결
            } else {
                var text = "<img src=\"./common/tpl/images/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+url+"\" width=\"400\" height=\"320\" style=\"display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\"false\" alt=\"\" />";
		editor.insertHTML(text);
            }

            // binary파일의 경우 url_link 컴포넌트 연결 
        } else {
            var mid = fo_obj.mid.value;
            var url = request_uri+"/?module=file&amp;act=procFileDownload&amp;file_srl="+file_srl+"&amp;sid="+sid;
            var text = "<a href=\""+url+"\">"+filename+"</a><br />\n";
	    editor.insertHTML(text);
        } 
    }
}

