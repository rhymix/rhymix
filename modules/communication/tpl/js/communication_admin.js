/* 스킨 컬러셋 구해옴 */
function doGetSkinColorset(skin) {
    var params = new Array();
    params['skin'] = skin;

    var response_tags = new Array('error','message','tpl');
    exec_xml('communication', 'getCommunicationAdminColorset', params, doDisplaySkinColorset, response_tags);
}

function doDisplaySkinColorset(ret_obj) {
    var tpl = ret_obj["tpl"];
    jQuery('#communication_colorset').html(tpl);
}

