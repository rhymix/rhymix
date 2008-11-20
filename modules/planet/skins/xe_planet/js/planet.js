function completeInsertContent(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var document_srl = ret_obj['document_srl'];
    location.href = current_url;
}


function completeCreate(ret_obj, response_tags, params, fo_obj) {
    var mid_url = ret_obj['mid_url'];
    var mid = ret_obj['mid'];

    if(fo_obj.photo.value) {
        fo_obj.mid.value = mid;
        fo_obj.act.value = "procPlanetPhotoModify";
        fo_obj.submit();
    } else {
        location.href = mid_url;
    }
}


function completeUpdateContentTag(ret_obj) {
    var error = ret_obj['error'];
    //var message = ret_obj['message'];
    var document_srl = ret_obj['document_srl'];
    //alert(message);
    location.reload();
    location.href = current_url;
}


function completePlanetLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    if(typeof(fo_obj.return_act)!='undefined') {
        url =  url.setQuery('act',fo_obj.return_act.value);
    }
    location.href = url;
}

function completeInsertMemo(ret_obj, response_tags, params, fo_obj) {
    toggle('form');
    fo_obj.planet_memo.value = "";
    $('planetMemo').innerHTML = ret_obj['tpl'];
}

function doDeleteMemo(planet_memo_srl) {
    var params = new Array();
    params['planet_memo_srl'] = planet_memo_srl;
    var response_tags = new Array('error','message','tpl');
    exec_xml('planet', 'procPlanetDeleteMemo', params, completeDeleteMemo, response_tags);
}

function completeDeleteMemo(ret_obj, response_tags, params) {
    $('planetMemo').innerHTML = ret_obj['tpl'];
}

function memoPageMove(module_srl, page) {
    var params = new Array();
    params['target_module_srl'] = module_srl;
    params['page'] = page;
    var response_tags = new Array('error','message','tpl');
    exec_xml('planet', 'getPlanetMemoList', params, completePlanetPageMove, response_tags);
}

function completePlanetPageMove(ret_obj, response_tags, params) {
    $('planetMemo').innerHTML = ret_obj['tpl'];
}

function updatePlanetInfo(f,target){
    if(target == 'photo') {
        if(f.photo.value) {
            f.act.value = "procPlanetPhotoModify";
            f.submit();
        }
    } else {
        f.target.value = target;
        procFilter(f, modify_planet_info);
    }
}

function completeUpdatePlanetInfo(ret_obj){
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    location.reload();
}

function deletePlanetTag(f,deltag){
    $('planet_tag').value = $A($('planet_tag').value.trim().split(',')).refuse(deltag.trim())._array.join(',').trim();
    updatePlanetInfo(f,'planet_tag');
}


function doPlanetVoteContent(document_srl){
    var params = new Array();
    params['document_srl'] = document_srl;
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procPlanetVoteContent', params, completeVoteContent, response_tags, params);
}
function completeVoteContent(ret_obj,response_tags, params, fo_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var document_srl = params['document_srl'];
    if (error == '0') {
        $('content_voted:'+document_srl).innerHTML = parseInt($('content_voted:'+document_srl).innerHTML) + 1
    }
}

function completeInsertInterestTag(ret_obj) {
    $('myFavoriteTag').innerHTML = ret_obj['tpl'];
    toggle('myFavoriteTag');
}

function doDeleteInterestTags(tag) {
    var params = new Array();
    params['tag'] = tag;
    var response_tags = new Array('error','message','tpl');
    exec_xml('planet', 'procPlanetDeleteInterestTag', params, completeDeleteInterestTag, response_tags);
}

function completeDeleteInterestTag(ret_obj) {
    $('myFavoriteTag').innerHTML = ret_obj['tpl'];
    toggle('myFavoriteTag');
}

function doAddFavorite(module_srl) {
    var params = new Array();
    params['module_srl'] = module_srl;
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procPlanetInsertFavorite', params, completeAddFavorite, response_tags);
}

function completeAddFavorite(ret_obj) {
    $$('.addFavorite')[0].style.display = 'none';
}


function completeInsertReply(ret_obj, response_tags, params, fo_obj) {

    $('reply_count:'+ ret_obj['document_srl']).innerHTML = parseInt($('reply_count:'+ ret_obj['document_srl']).innerHTML)+1;
    showPlanetReply(ret_obj['document_srl'],'open');
}

function showPlanetReply(document_srl,f){
    if(toggleObject($('reply:'+document_srl),f) =='open'){
        if($('writeReply:'+document_srl)) $('writeReply:'+document_srl).planet_reply_content.focus();

        var params = new Array();
        params['document_srl'] = document_srl;
        var response_tags = new Array('error','message','tpl','document_srl');
        exec_xml('planet', 'getPlanetReplyList', params, completeShowPlanetReply, response_tags);
    }

}

function completeShowPlanetReply(ret_obj, response_tags, params, fo_obj) {
    if($('writeReply:'+ret_obj['document_srl'])) $('writeReply:'+ret_obj['document_srl']).reset();
    $('reply_content:'+ret_obj['document_srl']).innerHTML = ret_obj['tpl'] == null ?'':ret_obj['tpl'];
}

function doEnableRss() {
    var params = new Array();
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procPlanetEnableRss', params, function() { location.reload() }, response_tags);
}

function doDisableRss() {
    var params = new Array();
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procPlanetDisableRss', params, function() { location.reload() }, response_tags);
}

function planetPreview(obj) {
    if($('btn_preview').checked){
        $Element('preview').removeClass('off');
        var text = obj.value;
        text = text.replace(/</ig,'&lt;');
        text = text.replace(/>/ig,'&gt;');
        $('preview_text').innerHTML = text.replace(/"([^"]*)":(mms|http|ftp|https)([^ ]+)/ig,'<a href="$2$3">$1</a>');
    }else{
        $Element('preview').addClass('off');
        $('preview_text').innerHTML = '';
    }

}

function doUpdateColorset(obj) {
    var colorset = obj.parentNode.className;
    var params = new Array();
    params['colorset'] = colorset;
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procPlanetColorsetModify', params, completeUpdateColorset);
}

function completeUpdateColorset(ret_obj) {
    location.reload();
}

function completeMe2Api(ret_obj) {
    alert(ret_obj['message']);
    $Element('Me2ApiProtocol').toggleClass('open','close');
}
