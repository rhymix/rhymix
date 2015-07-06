 (function($){
    "use strict";
    $(function(){
        var $shrinkHeaderHeight = 300;
        var $fixedHeader = $('.fixed_header .header_wrap');
        var $gnb = $('.gnb');
        var $hoverEl = $('.hover');
        var $searchEl = $('.click > a');
        var $searchForm = $('.search_area');

        // Fixed header
        if($fixedHeader.length)
        {
            var fixedHeaderHeight = $fixedHeader.height();
            var hasClass = false;
            var $logoImg = $fixedHeader.find('.header h1 img');
            var logoDataSrc = $logoImg.data('logo');
            var logo = $logoImg.attr('src');

            $(window).scroll(function() {
                var scroll = $(this).scrollTop();

                if(scroll >= $shrinkHeaderHeight ) {
                    if(!hasClass)
                    {
                        $fixedHeader.addClass('shrink');
                        if(logoDataSrc) $logoImg.attr('src', logoDataSrc);
                        hasClass = true;
                    }
                } else {
                    if(hasClass)
                    {
                        $fixedHeader.removeClass('shrink');
                        if(logoDataSrc) $logoImg.attr('src', logo);
                        hasClass = false;
                    }
                }
            });
            $(window).triggerHandler('scroll');
        }
		// Search
        $searchEl.click(function(){
            if($searchForm.is(':hidden')){
                $searchForm.fadeIn().find('input').focus();
                if($('.magazine').length > 0){
                    $('.custom_area').css('opacity',0);
                    $('.side').css('opacity',0)
                } else{
                    $('.header').css('opacity',0)
                }
            }
            return false;
        });
        $('.btn_close').click(function(){
            var $this = $(this);
            $this.parent().fadeOut().find('input').val('');
            if($('.magazine').length > 0){
                $('.custom_area').css('opacity',1);
                $('.side').css('opacity',1)
            } else{
                $('.header').css('opacity',1)
            }
            $searchEl.focus();
            return false;
        });

        // slide
        if($.isFunction($.fn.camera) && $(".camera_wrap").length) {
            $(".camera_wrap").camera({
                height: "600px",
                pagination: true,
                thumbnails: false,
                playPause: false,
                loader: "none",
                fx: "simpleFade",
                time: 3000
            });
        }

        // Scroll to top
        var scrollToTop = function() {
            var link = $('.btn_top');
            var windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

            $(window).scroll(function() {
                if (($(this).scrollTop() > 150) && (windowW > 1000)) {
                    link.fadeIn(100);
                } else {
                    link.fadeOut(100);
                }
            });

            link.click(function() {
                $('html, body').animate({scrollTop: 0}, 400);
                return false;
            });
        };
        scrollToTop();

        // Sub Header Parallax
        $('.sub_type3 .bg_img').parallax('50%',0.4);
    })
})(jQuery);

(function($){
	 $(function(){
		$('.bg-holder').parallaxScroll({
		  friction: 0.2
		});
    });
})(jQuery);
