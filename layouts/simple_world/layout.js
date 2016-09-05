$(function() {
        if($('.layout_menu>ul>li:first-child').width() > 50) {
                $('.layout_menu>ul>li:first-child .layout_dropdown-content, .layout_menu>ul>li:first-child .layout_dropdown-content a').css('width', $('.layout_menu>ul>li:first-child').width()).css('min-width', $('.layout_menu>ul>li:first-child').width());
        }
$( window ).resize(function() {
	if($('.layout_menu>ul>li:first-child').width() > 50) {
                $('.layout_menu>ul>li:first-child .layout_dropdown-content, .layout_menu>ul>li:first-child .layout_dropdown-content a').css('width', $('.layout_menu>ul>li:first-child').width()).css('min-width', $('.layout_menu>ul>li:first-child').width());
        }
});

	"use strict";

	var toggles = document.querySelectorAll(".layout_mobile_menu");

	for (var i = toggles.length - 1; i >= 0; i--) {
		var toggle = toggles[i];
		layout_toggleHandler(toggle);
	};

	function layout_toggleMenuOpener(obj) {
		if(obj.classList.contains("is-active") === true){
			var targetMenu = $(obj).attr('data-target');
			$('#' + targetMenu).slideUp('300', function() {
				$(this).css('display', '')
			});

			obj.classList.remove("is-active");
		}
		else {
			var targetMenu = $(obj).attr('data-target');
			$('#' + targetMenu).slideDown('300');

			obj.classList.add("is-active");
		}
	}

	function layout_toggleHandler(toggle) {
		toggle.addEventListener( "click", function(e) {
			e.preventDefault();
			layout_toggleMenuOpener(this);
		});
	}

});