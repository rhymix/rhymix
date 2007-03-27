/* 생성된 코드를 textarea에 출력 */
function completeGenerateCode(ret_obj) {
    var plugin_code = ret_obj["plugin_code"];

    var zone = xGetElementById("plugin_code");
    zone.value = plugin_code;
} 

/* 생성된 코드를 에디터에 출력 */
function completeGenerateCodeInPage(ret_obj,response_tags,params,fo_obj) {
    var plugin_code = ret_obj["plugin_code"];
    var module_srl = fo_obj.module_srl.value;

    if(!opener || !plugin_code || !module_srl) {
        window.close(); 
        return;
    }

    opener.editorFocus(module_srl);
    var iframe_obj = opener.editorGetIFrame(module_srl);
    opener.editorReplaceHTML(iframe_obj, plugin_code);
    opener.editorFocus(module_srl);
} 

/* 플러그인 코드 생성시 스킨을 고르면 컬러셋의 정보를 표시 */
function doDisplaySkinColorset(sel) {
    var skin = sel.options[sel.selectedIndex].value;
    if(!skin) {
        xGetElementById("colorset_area").style.display = "none";
        setFixedPopupSize();
        return;
    }

    var params = new Array();
    params["selected_plugin"] = xGetElementById("fo_plugin").selected_plugin.value;
    params["skin"] = skin;

    var response_tags = new Array("error","message","colorset_list");

    exec_xml("plugin", "procGetColorsetList", params, completeGetSkinColorset, response_tags);
}

// 서버에서 받아온 컬러셋을 표시
function completeGetSkinColorset(ret_obj) {
    var sel = xGetElementById("fo_plugin").colorset;
    var length = sel.options.length;
    for(var i=0;i<length;i++) sel.remove(0);

    var colorset_list = ret_obj["colorset_list"].split("\n");
    for(var i=0;i<colorset_list.length;i++) {
        var tmp = colorset_list[i].split("|@|");
        var opt = new Option(tmp[1], tmp[0], false, false);
        sel.options.add(opt);
    }

    xGetElementById("colorset_area").style.display = "block";
    setFixedPopupSize();
}
