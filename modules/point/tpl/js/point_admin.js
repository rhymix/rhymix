/**
 * @file   modules/point/js/point_admin.js
 * @author zero (zero@nzeo.com)
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