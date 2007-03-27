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
