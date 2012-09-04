/* After Login */
function completeLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    location.href = url;
}

jQuery(function($){
// Login
	// placeholder
	var $account = $('.account');
	$account 
		.unwrap().unwrap()
		.find('.idpw>input').each(function(){
			var idpw_placeholder = $(this).attr('title');
			$(this).attr('placeholder', idpw_placeholder);
		});
	// Toggle
	var $acTog = $account.find('>a[href="#acField"]:eq(0)');
	$acTog.click(function(){ 
		$(this).next('#acField').slideToggle(200, function(){
			var $user_id = $(this).find('input[name="user_id"]:eq(0)');
			if($user_id.is(':visible')){
				$user_id.focus();
			} else {
				$acTog.focus();
			}
		});
		return false;
	});
	// Close
	$account
		.find('>#acField')
			.append('<button type="button" class="x_close">&times;</button>')
		.find('>.x_close').click(function(){
			$(this).closest('#acField').slideUp(200, function(){
				$acTog.focus();
			});
			return false;
		});
	// Warning
	var $acWarning = $account.find('.warning');
	$('#keep_signed').change(function(){
		if($(this).is(':checked')){
			$acWarning.slideDown(200);
		} else {
			$acWarning.slideUp(200);
		}
	});
});