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
	$('#openid_login').hide();
	$('#keepid').click(function(){ if(this.checked) return confirm(keep_signed_msg) });

	/* focus on login form */
	if (!$(document).scrollTop()) $('#fo_login_widget input[name=user_id]').focus();
	
	// show/hide openid form
	$('#use_open_id,#use_open_id_2').click(function(){
		if($('#login').toggle().is(':visible')) {
			$('#openid_login').hide();
			$('#use_open_id,#use_open_id_2').removeAttr('checked');
		} else {
			$('#openid_login').show();
			$('#use_open_id_2').attr('checked', 'checked');
		}
	});
});