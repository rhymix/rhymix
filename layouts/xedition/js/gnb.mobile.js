(function($) {
	$(function(){
		var $gnb = $('.gnb');
		$("#mobile_menu_btn").on('click', function(){
			var isOpened = $(this);
			if(isOpened.hasClass('opened')){
				$("#gnb").find(">ul").slideUp(200);
			}else{
				$("#gnb").find(">ul:not(:animated)").slideDown(200);
			}
			isOpened.toggleClass('opened');
		});
	});
})(jQuery);
