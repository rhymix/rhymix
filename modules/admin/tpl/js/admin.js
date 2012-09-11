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
// GNB Click
	var $xBody = $('.x>.body');
	var $xContent = $xBody.find('>.content');
	var $xGnb = $xBody.find('>.gnb');
	$xGnb.find('>ul>li>a').click(function(){
		var $xGnbReady = $xGnb.hasClass('ready');
		var $this = $(this);
		var $li = $xGnb.find('>ul>li');
		if($xGnbReady && $this.next('ul').length==1){
			$li.not($this.parent('li')).removeClass('open');
			$this.parent('li').toggleClass('open');
			return false;
		} else if(!$xGnbReady && $this.next('ul').length==1){
			return false;
		}
		if($this.attr('href')=='#gnb' && !$this.parent('li').hasClass('open')){
			$li.addClass('open');
			$xGnb.removeClass('ready');
		} else {
			$li.removeClass('open');
			$xGnb.addClass('ready');
		}
	});
// GNB Hover
	function contentBugFix(){
		$xContent.width('99.99%');
		setTimeout(function(){
			$xContent.removeAttr('style');
		}, 0);
	}
	$xGnb
		.bind('mouseenter', function(){
			if($xGnb.hasClass('ready')){
				$xBody.removeClass('wide');
				contentBugFix();
			}
		})
		.bind('mouseleave', function(){
			if($xGnb.hasClass('ready')){
				$xBody.addClass('wide');
				contentBugFix();
			}
		})
});