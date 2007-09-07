/**
 * @brief 회원 가입시나 정보 수정시 각 항목의 중복 검사등을 하는 기능을 구현
 * @author zero 
 **/

// 입력이 시작된 것과 입력후 정해진 시간동안 내용이 변하였을 경우 서버에 ajax로 체크를 하기 위한 변수 설정
var memberCheckObj = { target:null, value:null }

// onload시에 특정 필드들에 대해 이벤트를 걸어 놓음
xAddEventListener(window, 'load', memberSetEvent);

function memberSetEvent() {
    var fo_obj = xGetElementById('fo_insert_member');
    for(var node_name in fo_obj) {
        var obj = fo_obj[node_name];
        if(!obj || typeof(obj.nodeName)=="undefined" || obj.nodeName != "INPUT") continue;
        if(node_name != "user_id" && node_name != "nick_name" && node_name != "email_address") continue;

        xAddEventListener(obj, 'blur', memberCheckValue);
    }
}


// 실제 서버에 특정 필드의 value check를 요청하고 이상이 있으면 메세지를 뿌려주는 함수
function memberCheckValue(evt) {
    var e = new xEvent(evt);
    var obj = e.target;

    var name = obj.name;
    var value = obj.value;
    if(!name || !value) return;
    var params = new Array();
    params['name'] = name;
    params['value'] = value;

    var response_tags = new Array('error','message');

    exec_xml('member','procMemberCheckValue', params, completeMemberCheckValue, response_tags, e);

}

// 서버에서 응답이 올 경우 이상이 있으면 메세지를 출력
function completeMemberCheckValue(ret_obj, response_tags, e) {
    var obj = e.target;
    var name = obj.name;
    
    if(ret_obj['message']=='success') {
        var dummy_id = 'dummy_check_'+name;
        var dummy = xGetElementById(dummy_id);
        if(dummy) xInnerHtml(dummy,'');
        return;
    }

    var dummy_id = 'dummy_check_'+name;
    var dummy = null;
    if(! (dummy = xGetElementById(dummy_id)) ) {
        dummy = xCreateElement('DIV');
        dummy.id = dummy_id;
        dummy.style.display = "none";
        dummy.style.clear = 'both';
        dummy.style.marginTop = '10px';
        obj.parentNode.insertBefore(dummy, obj.nextChild);
    }

    xInnerHtml(dummy, ret_obj['message']);

    dummy.style.display = "block";

    obj.focus();

    // 3초 정도 후에 정리
    setTimeout(function() { removeMemberCheckValueOutput(dummy, obj); }, 3000);
}

// 결과 메세지를 정리하는 함수
function removeMemberCheckValueOutput(dummy, obj) {
    dummy.style.display = "none";
}
