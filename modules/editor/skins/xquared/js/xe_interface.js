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

function editorSync_xq(editor_sequence) {
    var editor = editorRelKeys[editor_sequence]['editor'];
    editorRelKeys[editor_sequence]['content'].value = editor.getCurrentContent(true);
    return;
}

function editorStart_xq(editor, element, editor_sequence, content_key, editor_height, primary_key) {
    editor = new xq.Editor(element);

    editorRelKeys[editor_sequence] = new Array();
    editorRelKeys[editor_sequence]['editor'] = editor; 
    editorRelKeys[editor_sequence]['func'] = editorSync_xq;
    editorMode[editor_sequence] = null;
    var fo_obj = editorGetForm_xq(element);
    fo_obj.setAttribute('editor_sequence', editor_sequence);
    editor.setStaticContent(fo_obj[content_key].value);
    editorRelKeys[editor_sequence]['content'] = fo_obj[content_key];
    editorRelKeys[editor_sequence]['primary'] = fo_obj[primary_key];
    editor.setEditMode('wysiwyg');
    editor.loadStylesheet(request_uri+editor_path+"/examples/css/xq_contents.css");
    editor.getFrame().style.width = "100%";
    editor.getFrame().parentNode.style.height = editor_height;
    editor.addAutocompletions(getAdditionalAutocompletions());
}

xq.Editor.prototype.insertHTML = function (html) {
    this.rdom.insertHtml(html);
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

