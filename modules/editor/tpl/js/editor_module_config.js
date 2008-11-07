function getEditorSkinColorList(skin_name,selected_colorset,type){
    if(skin_name.length>0){
        type = type || 'board';
        var response_tags = new Array('error','message','colorset');
        exec_xml('editor','dispEditorAdminSkinColorset',{skin:skin_name},resultGetEditorSkinColorList,response_tags,{'selected_colorset':selected_colorset,'type':type});
    }
}

function resultGetEditorSkinColorList(ret_obj,response_tags, params) {

    var selectbox = null;
    if(params.type == 'board'){
        selectbox = xGetElementById("sel_editor_colorset");
    }else{
        selectbox = xGetElementById("sel_comment_editor_colorset");
    }

    if(ret_obj['error'] == 0 && ret_obj.colorset){
        var it = new Array();
        var items = ret_obj['colorset']['item'];
        if(typeof(items[0]) == 'undefined'){
            it[0] = items;
        }else{
            it = items;
        }
        var sel = 0;
        for(var i=0,c=it.length;i<c;i++){
            selectbox.options[i]=new Option(it[i].title,it[i].name);
            if(params.selected_colorset && params.selected_colorset == it[i].name) sel = i;
        }
        selectbox.options[sel].selected = true;
        selectbox.style.display="";
    }else{
        selectbox.style.display="none";
        selectbox.innerHTML="";
    }
}
