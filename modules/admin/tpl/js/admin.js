/* NHN (developers@xpressengine.com) */
jQuery(function($){
// iSO mobile device toolbar remove
	window.top.scrollTo(0,0);
// Skip to content
	$('.x .skipNav>a').click(function(){
		$($(this).attr('href')).attr('tabindex','0').css('outline','0').focus();
	});
// Content Toggle
	$('.x [data-toggle^="#"]').click(function(){
		$($(this).attr('data-toggle')).toggle();
		return false;
	});
// Close Layer
	$('.x [data-close^="#"]').click(function(){
		$($(this).attr('data-close')).hide();
		return false;
	});
// GNB Height 100%
	var $xBody = $('.x>.body');
	var $xContent = $xBody.find('>.content');
	var $xGnb = $xBody.find('>.gnb');
	var $xGnb_li = $xGnb.find('>ul>li');
	$(window).resize(function(){
		setTimeout(function(){
			if($(window).width() >= 980){ // Over than 1024px
				$xGnb.height('auto').height($xBody.height());
			} else { // Less than 1024
				$xGnb.height('auto');
			}
		}, 100);
	}).resize();
// GNB Click toggle
	// Add virtual class
	$xGnb_li.find('>ul').prev('a').addClass('virtual');
	// Virtual click
	$xGnb_li.find('>a.virtual')
		.bind('click focus', function(){
			var $this = $(this);
			// Submenu toggle
			if(!$xGnb.hasClass('all')) { 
				$xGnb_li.not($this.parent('li')).removeClass('open');
				$(this).parent('li').toggleClass('open');
			} 
			return false;
		});
	// Toggle all
	$xGnb_li.find('>a[href="#gnb"]')
		.click(function(){
			if(!$xGnb.hasClass('all')){ // Open All
				$xGnb_li.addClass('open');
				$xGnb.addClass('all');
			} else { // Close All
				$xGnb_li.removeClass('open');
				$xGnb.removeClass('all');
			}
		});
});