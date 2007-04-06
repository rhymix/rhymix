/* 설문 참여 함수 */
function doPoll(fo_obj) {

    var check_count = new Array();
    var item = new Array();

    for(var i=0;i<fo_obj.length;i++) {
        var obj = fo_obj[i];
        if(obj.nodeName != 'INPUT') continue;

        var name = obj.name;
        if(name.indexOf('checkcount')>-1) {
            var t = name.split('_');
            var poll_srl_index = parseInt(t[1],10);
            check_count[poll_srl_index] = obj.value;
            item[poll_srl_index] = new Array();

        } else if(name.indexOf('item_')>-1) {
            var t = name.split('_');
            var poll_srl = parseInt(t[1],10); 
            var poll_srl_index = parseInt(t[2],10); 
            if(obj.checked == true) item[poll_srl_index][item[poll_srl_index].length] = obj.value;
        }
    }

    var poll_srl_indexes = "";
    for(var poll_srl_index in check_count) {
        var count = check_count[poll_srl_index];
        var items = item[poll_srl_index];
        if(count > items.length) {
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
    alert(ret_obj['message']);
    var poll_srl = ret_obj['poll_srl'];
    var tpl = ret_obj['tpl'];
    var width = xWidth("poll_"+poll_srl);
    xInnerHtml("poll_"+poll_srl, tpl);
    xWidth("poll_"+poll_srl, width);
}
