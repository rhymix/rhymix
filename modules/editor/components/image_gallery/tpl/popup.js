var selected_node = null;
function getSlideShow() {
    // 부모창이 있는지 체크 
    if(typeof(opener)=="undefined") return;

    // 부모 위지윅 에디터에서 선택된 영역이 있으면 처리
    var node = opener.editorPrevNode;
    var selected_images = "";
    if(node && node.nodeName == "DIV") {
        selected_node = node;

        var width = xWidth(selected_node)-6;
        var height = xHeight(selected_node)-6;
        var make_thumbnail = selected_node.getAttribute("make_thumbnail");
        xGetElementById("width").value = width; 
        xGetElementById("height").value = height; 
        if(make_thumbnail=="Y") xGetElementById("make_thumbnail").checked = true;
        else xGetElementById("make_thumbnail").checked = false;

        selected_images = xInnerHtml(selected_node);
    }

    // 부모창의 업로드된 파일중 이미지 목록을 모두 가져와서 세팅 
    var fo = xGetElementById("fo");
    var upload_target_srl = fo.upload_target_srl.value;

    var parent_list_obj = opener.xGetElementById("uploaded_file_list_"+upload_target_srl);

    var list_obj = xGetElementById("image_list");

    for(var i=0;i<parent_list_obj.length;i++) {
        var opt = parent_list_obj.options[i];
        var file_srl = opt.value;
        var file_obj = opener.uploaded_files[file_srl];
        var filename = file_obj.uploaded_filename;
        if((/(jpg|jpeg|gif|png)$/).test(filename)) {
            var selected = false;
            if(selected_images.indexOf(filename)!=-1) selected = true;
            var opt = new Option(opt.text, opt.value, false, selected);
            list_obj.options.add(opt);
        }
    }
}

function insertSlideShow() {
    if(typeof(opener)=="undefined") return;

    var list = new Array();
    var list_obj = xGetElementById("image_list");
    for(var i=0;i<list_obj.length;i++) {
        var opt = list_obj.options[i];
        if(opt.selected) {
            var file_srl = opt.value;
            var file_obj = opener.uploaded_files[file_srl];
            var filename = file_obj.uploaded_filename;
            list[list.length] = filename;
        }
    }

    if(!list.length) {
        window.close();
        return;
    }

    var width = xGetElementById("width").value;
    var height = xGetElementById("height").value;
    var make_thumbnail = "N";
    if(xGetElementById("make_thumbnail").checked) make_thumbnail = "Y";
    else make_thumbnail = "N";

    var images_list = "";
    for(var i=0; i<list.length;i++) {
        images_list += list[i]+"\n";
    }

    if(selected_node) {
        selected_node.setAttribute("width", width);
        selected_node.setAttribute("height", height);
        selected_node.setAttribute("make_thumbnail", make_thumbnail);
        selected_node.style.width = width+"px";
        selected_node.style.height = height+"px";
        xInnerHtml(selected_node, images_list);
    } else {
        var text = "<div editor_component=\"image_gallery\" class=\"editor_component_output\" make_thumbnail=\""+make_thumbnail+"\" width=\""+width+"\" height=\""+height+"\" style=\"width:"+width+"px;height:"+height+"px;\" >"+images_list+"</div>";
        opener.editorFocus(opener.editorPrevSrl);
        var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)
        opener.editorReplaceHTML(iframe_obj, text);
    }

    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

xAddEventListener(window, "load", getSlideShow);
