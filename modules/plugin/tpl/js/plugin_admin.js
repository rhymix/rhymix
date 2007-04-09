/**
 * @file   modules/plugin/js/plugin_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  plugin 모듈의 관리자용 javascript
 **/

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

    window.close();
} 

/* 플러그인 코드 생성시 스킨을 고르면 컬러셋의 정보를 표시 */
function doDisplaySkinColorset(sel, colorset) {
    var skin = sel.options[sel.selectedIndex].value;
    if(!skin) {
        xGetElementById("colorset_area").style.display = "none";
        setFixedPopupSize();
        return;
    }

    var params = new Array();
    params["selected_plugin"] = xGetElementById("fo_plugin").selected_plugin.value;
    params["skin"] = skin;
    params["colorset"] = colorset;

    var response_tags = new Array("error","message","colorset_list");

    exec_xml("plugin", "procPluginGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

/* 서버에서 받아온 컬러셋을 표시 */
function completeGetSkinColorset(ret_obj, response_tags, params, fo_obj) {
    var sel = xGetElementById("fo_plugin").plugin_colorset;
    var length = sel.options.length;
    var selected_colorset = params["colorset"];
    for(var i=0;i<length;i++) sel.remove(0);

    var colorset_list = ret_obj["colorset_list"].split("\n");
    var selected_index = 0;
    for(var i=0;i<colorset_list.length;i++) {
        var tmp = colorset_list[i].split("|@|");
        if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
        var opt = new Option(tmp[1], tmp[0], false, false);
        sel.options.add(opt);
    }

    sel.selectedIndex = selected_index;

    xGetElementById("colorset_area").style.display = "block";
    setFixedPopupSize();
}

/* 페이지 모듈에서 내용의 플러그인을 더블클릭하여 수정하려고 할 경우 */
var selected_node = null;
function doFillPluginVars() {
    if(!opener || !opener.editorPrevNode || !opener.editorPrevNode.getAttribute("plugin")) return;

    selected_node = opener.editorPrevNode;

    // 스킨과 컬러셋은 기본
    var skin = selected_node.getAttribute("skin");
    var colorset = selected_node.getAttribute("colorset");

    var fo_obj = xGetElementById("fo_plugin");

    for(var name in fo_obj) {
        var node = fo_obj[name];
        if(!node || typeof(node)=="undefined") continue;

        var length = node.length;
        var type = node.type;
        if((typeof(type)=='undefined'||!type) && typeof(length)!='undefined' && typeof(node[0])!='undefined' && length>0) type = node[0].type;
        else length = 0;

        switch(type) {
            case "text" :
            case "textarea" :
                    var val = selected_node.getAttribute(name);
                    node.value = val;
                break;
            case "checkbox" :
                    var val = selected_node.getAttribute(name);
                    for(var i=0;i<fo_obj[name].length;i++) {
                        var v = fo_obj[name][i].value;
                        if(val.indexOf(v)!=-1) fo_obj[name][i].checked="true";
                    }
                break;
            case "select" :
            case "select-one" :
                    var val = selected_node.getAttribute(name);
                    var sel = fo_obj[name];
                    if(!val) break;
                    for(var i=0;i<sel.options.length;i++) {
                        if(sel.options[i].value == val) sel.options[i].selected = true;
                        else sel.options[i].selected = false;
                    }
                break;
        }

    }

    //  컬러셋 설정
    if(skin && xGetElementById("plugin_colorset").options.length<1 && colorset) {
        doDisplaySkinColorset(xGetElementById("plugin_skin"), colorset);
    }

}
