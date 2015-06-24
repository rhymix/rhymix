 (function($){
	 $(function(){
		var $shrinkHeaderHeight = 300;
        var $fixedHeader = $('.fixed_header .header_wrap');
        var $gnb = $('.gnb');
        var $hoverEl = $('.hover');
        var $searchEl = $('.click > a');
        var $searchForm = $('.search_area');
        // Gnb
        $gnb.find('>ul>li>a')
        .mouseover(function(){
            $gnb.find('>ul>li>ul:visible').hide().parent('li').removeClass('on');
            $(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
        })
        .focus(function(){
            $(this).mouseover();
        })
        .end()
        .mouseleave(function(){
            $gnb.find('>ul>li>ul').hide().parent().removeClass('on')
        });

       $gnb.find('>ul>li>ul>li>a')
        .mouseover(function(){
            $gnb.find('>ul>li>ul>li>ul:visible').hide().parent('li').removeClass('on');
            $(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
        })
        .focus(function(){
            $(this).mouseover();
        })
        .end()
        .mouseleave(function(){
            $gnb.find('>ul>li>ul>li>ul').hide().parent().removeClass('on')
        });
    });
})(jQuery);
