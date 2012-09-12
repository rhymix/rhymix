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
// GNB Height 100%
	var $xBody = $('.x>.body');
	var $xContent = $xBody.find('>.content');
	var $xGnb = $xBody.find('>.gnb');
	var $xGnb_li = $xGnb.find('>ul>li');
	$(window).resize(function(){
		setTimeout(function(){
			if($(window).width() >= 1024){ // Over than 1024px
				$xGnb.height('auto').height($xBody.height());
			} else { // Less than 1024
				$xGnb.height('auto');
				$xBody.removeClass('wide');
			}
		},100);
	}).resize();
	// Trigger for GNB height resize
	$($xBody, $xContent, $xGnb).bind('click mouseenter mouseleave focusin focusout', function(){ 
		$(window).resize();
	});
// GNB Click toggle
	// Add virtual class
	$xGnb_li.find('>ul').prev('a').addClass('virtual');
	// Virtual click
	$xGnb_li.find('>a.virtual')
		.bind('click focus', function(event){
			var $this = $(this);
			// Submenu toggle
			if(!$xGnb.hasClass('all')) { 
				$xGnb_li.not($this.parent('li')).removeClass('open');
				$(this).parent('li').toggleClass('open');
			} 
			// GNB Hover
			$xGnb.trigger('mouseenter');
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
		})
		.focus(function(){
			// GNB Hover
			$xGnb.trigger('mouseenter');
		});
// GNB Hover toggle
	function contentBugFix(){ // Chrome browser rendering bug fix
		$xContent.width('99.99%');
		setTimeout(function(){
			$xContent.removeAttr('style');
		}, 0);
	}
	$xGnb
		.mouseenter(function(){ // Mouseenter
			if($(window).width() >= 1024){
				setTimeout(function(){
					$xBody.removeClass('wide');
					contentBugFix();
				}, 200);
			}
		})
		.mouseleave(function(){ // Mouseleave
			if($(window).width() >= 1024){
				$xBody.addClass('wide');
				contentBugFix();
			}
		});
// GNB Close
	$xGnb
		.prepend('<button type="button" class="close before" />')
		.append('<button type="button" class="close after" />');
	$xGnb.find('>.before, >.after').focus(function(){
		$xBody.addClass('wide');
		contentBugFix();
	});
});