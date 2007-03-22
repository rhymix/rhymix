var selected_node = null;
function getSlideShow() {
    // 부모창이 있는지 체크 
    if(typeof(opener)=="undefined") return;

    // 부모창의 업로드 이미지 목록을 모두 가져와서 세팅 
    var fo = xGetElementById("fo");
    var upload_target_srl = fo.upload_target_srl.value;
    var parent_list_obj = opener.xGetElementById("uploaded_file_list_"+upload_target_srl);
    var list_obj = xGetElementById("image_list");

    var length = parent_list_obj.length;
    for(var i=0;i<length;i++) {
      var opt = new Option(parent_list_obj[i].text, parent_list_obj[i].value, false, false);
      list_obj.options.add(opt);
    }

    // 부모 위지윅 에디터에서 선택된 영역이 있으면 처리
    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "DIV") {
        return;
    }
    selected_node = node;
}

function insertSlideShow(obj) {
    if(typeof(opener)=="undefined") return;

    window.close();
}

xAddEventListener(window, "load", getSlideShow);
