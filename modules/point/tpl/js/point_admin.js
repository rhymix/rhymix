/**
 * @file   modules/point/js/point_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  point 모듈의 관리자용 javascript
 **/

function exp_calc (form, reset) {
    var fo_obj = get_by_id(form);
    var level = fo_obj.max_level.value;
    var exp = fo_obj.expression;
    var exp_default = "Math.pow(i, 2) * 90";

    if(reset || !exp.value) exp.value = exp_default;

    for(i = 1; i <= level; i++) {
        point = eval("fo_obj.level_step_" + i);
        point.value = eval(exp.value);
    }
}

/**
 * @brief 포인트를 전부 체크하여 재계산하는 action 호출
 **/
function doPointRecal() {
	var resp, $recal;

	function on_complete(ret) {
		if(!$recal) $recal = jQuery('#pointReCal');

		$recal.html(ret.message);

		if(ret.position == ret.total) {
			alert(message);
			location.reload();
		} else {
			exec_xml(
				'point',
				'procPointAdminApplyPoint',
				{position : ret.position, total : ret.total},
				on_complete,
				resp
			);
		}
	}

    exec_xml(
		'point', // module
		'procPointAdminReCal', // procedure
		{}, // parameters
		on_complete, // callback
		resp=['error','message','total','position'] // response tags
	);
}

function updatePoint(member_srl)
{
	var $point = jQuery('#point_'+member_srl);
	get_by_id('update_member_srl').value = member_srl;
	get_by_id('update_point').value = $point.val();

    var hF = get_by_id('updateForm');
	hF.submit();
}


function doPointReset(module_srls) {
    exec_xml(
		'point',
		'procPointAdminReset',
		{module_srls : module_srls},
		function(ret_obj){alert(ret_obj['message']);},
		['error','message']
	);
}
