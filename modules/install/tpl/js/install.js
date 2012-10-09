jQuery(function($){
// TARGET toggle
	$(document.body).on('click', '.x [data-toggle]', function(){
		var $this = $(this);
		var $target = $($this.attr('data-toggle'));
		$target.toggle();
		if($target.is(':visible') && !$target.find('a,input,button,textarea,select').length){
			$target.attr('tabindex','0').focus();
		} else if($target.is(':visible') && $target.find('a,input,button,textarea,select').length) {
			$target.find('a,input,button,textarea,select').eq(0).focus();
		} else {
			$this.focus();
		}
		return false;
	});
// SUBMIT disabled 
	$('input[required]').change(function(){
		var invalid = $('input[required]').is('[value=""], [value=" "], [value="  "], [value="   "]');
		var $submit = $('[type="submit"]');
		if(!invalid){
			$submit.removeClass('x_disabled');
		} else {
			$submit.addClass('x_disabled');
		}
	});
});
