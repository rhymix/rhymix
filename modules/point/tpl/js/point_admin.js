/**
 * @file   modules/point/js/point_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  point 모듈의 관리자용 javascript
 **/

function exp_calc (form, reset) {
    var fo_obj = xGetElementById(form)
    var level = fo_obj.max_level.value
    var exp = fo_obj.expression
    var exp_default = "Math.pow(i, 2) * 90"

    if(reset || !exp.value) exp.value = exp_default

    for(i = 1; i <= level; i++) {
        point = eval("fo_obj.level_step_" + i)
        point.value = eval(exp.value);
    }
}

/**
 * @brief 포인트를 전부 체크하여 재계산하는 action 호출
 **/
function doPointRecal() {

    var params = new Array();
    var response_tags = new Array('error','message','total', 'position');

    exec_xml('point','procPointAdminReCal',params, completePointRecal, response_tags);
}

function completePointRecal(ret_obj) {
    var total = ret_obj['total'];
    var message = ret_obj['message'];
    var position = ret_obj['position'];

    if(position == total) {
        xInnerHtml('pointReCal', message);
        alert(message);
        location.reload();
    } else {
        xInnerHtml('pointReCal', message);

        var params = new Array();
        params['position'] = position;
        params['total'] = total;
        var response_tags = new Array('error','message','total', 'position');

        exec_xml('point','procPointAdminApplyPoint',params, completePointRecal, response_tags);
    }
}

function updatePoint(member_srl, action)
{
    var pointEl = jQuery("#point_"+member_srl);
    var e = jQuery("#update_member_srl").val(member_srl);
    e = jQuery("#update_action").val(action);
    e = jQuery("#update_point").val(pointEl.attr("value"));
    var hF = jQuery("#updateForm").get(0);
    procFilter(hF, update_point);
}


function doPointReset(module_srls) {
    var params = new Array();
    var response_tags = new Array('error','message');
    params['module_srls'] = module_srls;

    exec_xml('point','procPointAdminReset',params,function(ret_obj) {alert(ret_obj['message']);}, response_tags);
}
