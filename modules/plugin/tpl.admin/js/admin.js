/* 생성된 코드를 textarea에 출력 */
function completeGenerateCode(ret_obj) {
    var plugin_code = ret_obj["plugin_code"];

    var zone = xGetElementById("plugin_code");
    zone.value = plugin_code;
} 
