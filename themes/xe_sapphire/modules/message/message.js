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

function doLogin(o,filter){
	jQuery('input.iText',o).each(function(){
		var t = jQuery(this);
		if(t.attr('title').length>0 && t.attr('title') == t.val()) t.val('');
	});
	procFilter(o,filter);
	initLoginTitleMsg();
	return false;
}


function initLoginTitleMsg(){
	jQuery('.gLogin, .mLogin').find('input.iText').focus(function(){
		var t = jQuery(this);
		if(t.attr('title').length>0 && t.attr('title')==t.val()) t.val('');
	}).blur(function(){
		var t = jQuery(this);
		if(t.attr('title').length>0 && t.val()=='') t.val(t.attr('title'));
	}).focus().blur();
}

jQuery(function(){
	initLoginTitleMsg();
});


