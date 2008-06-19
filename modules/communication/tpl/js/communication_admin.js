/* 스킨 컬러셋 구해옴 */
function doGetSkinColorset(skin) {
    var params = new Array();
    params['skin'] = skin;

    var response_tags = new Array('error','message','tpl');
    exec_xml('communication', 'getCommunicationAdminColorset', params, doDisplaySkinColorset, response_tags);
}

function doDisplaySkinColorset(ret_obj) {
    var tpl = ret_obj["tpl"];
    var old_height = xHeight("communication_colorset");
    xInnerHtml("communication_colorset", tpl);
    var new_height = xHeight("communication_colorset");
}

