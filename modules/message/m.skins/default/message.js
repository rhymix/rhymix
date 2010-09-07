/* 로그인 후 */
function completeMessageLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    location.href = url;
}
/* 오픈아이디 로그인 후 */
function completeMessageOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}
/* 로그인 폼 토글 */
$(function($){
	var gLogin = $('#gLogin');
	var oLogin = $('#oLogin');
	oLogin.hide();
	$('a[href=#oLogin]').click(function(){
		gLogin.hide();
		oLogin.show();
	});
	$('a[href=#gLogin]').click(function(){
		gLogin.show();
		oLogin.hide();
	});
});

