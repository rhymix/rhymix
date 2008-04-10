/**
 * @file   modules/referer/js/referer_admin.js
 * @author haneul 
 * @brief  referer 모듈의 관리자용 javascript
 **/

/* stat 삭제 후 */
function completeDeleteStat(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispRefererAdminIndex').setQuery('host','');
    location.href = url;
}
