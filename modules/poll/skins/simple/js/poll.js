/* 설문 참여 함수 */
function doPoll(fo_obj) {

    var checkcount = new Array();
    var item = new Array();

    for(var i=0;i<fo_obj.length;i++) {
        var obj = fo_obj[i];
        if(obj.nodeName != 'INPUT') continue;

        var name = obj.name;
        if(name.indexOf('checkcount')>-1) {
            var t = name.split('_');
            var poll_srl_index = parseInt(t[1],10);
            checkcount[poll_srl_index] = obj.value;
            item[poll_srl_index] = new Array();

        } else if(name.indexOf('item_')>-1) {
            var t = name.split('_');
            var poll_srl = parseInt(t[1],10); 
            var poll_srl_index = parseInt(t[2],10); 
            if(obj.checked == true) item[poll_srl_index][item[poll_srl_index].length] = obj.value;
        }
    }

    var poll_srl_indexes = "";
    for(var poll_srl_index in checkcount) {
	if(!checkcount.hasOwnProperty(poll_srl_index)) continue;
        var count = checkcount[poll_srl_index];
        var items = item[poll_srl_index];
        if(items.length < 1 || count < items.length) {
            alert(poll_alert_lang);
            return false;
        }

        poll_srl_indexes += items.join(',')+',';
    }
    fo_obj.poll_srl_indexes.value = poll_srl_indexes;

    procFilter(fo_obj, poll);
    return false;
}

/* 설문 조사후 내용을 바꿀 함수 */
function completePoll(ret_obj) {
    var poll_srl = ret_obj['poll_srl'];
    var tpl = ret_obj['tpl'];
    var width = xWidth("poll_"+poll_srl);
    xInnerHtml("poll_"+poll_srl, tpl);
    xWidth("poll_"+poll_srl, width);
}

/* 설문 미리 보기 */
function doPollViewResult(poll_srl, skin) {
    var params = new Array();
    params['poll_srl'] = poll_srl;

    if(typeof(skin)=='undefined') skin = 'default';
    params['skin'] = skin;

    var response_tags = new Array('error','message','poll_srl', 'tpl');

    exec_xml('poll','procPollViewResult', params, completePoll, response_tags);
}
