/* 로그인 후 */
function completeLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    location.href = url;
}

/* 오픈아이디 로그인 후 */
function completeOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}

jQuery(function($){
	// keep signed?
	$('#keep_signed').click(function(){ if(this.checked) return confirm(xe.lang.about_keep_signed) });

	// toggle login form
	var $chk_openid =
	$('#use_open_id,#use_open_id_2').click(function(){
		$('#login').toggle().is(':hidden')?
			$chk_openid.attr('checked','checked') : 
			$chk_openid.removeAttr('checked');

		$('#openid_login').toggle();
	});

	// hide openid login form
	$('#openid_login').hide();

	// focus userid input box
	if (!$(document).scrollTop()) {
		try {
			$('#fo_login_widget > input[name=user_id]').focus();
		} catch(e){};
	}
});
