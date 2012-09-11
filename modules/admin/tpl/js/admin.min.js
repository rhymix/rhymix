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
// GNB
	$('.x .gnb>ul>li>a').click(function(){
		var $t = $(this);
		var $gnb = $('.gnb');
		var $li = $('.x .gnb>ul>li');
		if($gnb.hasClass('able') && $t.next('ul').length==1){
			$li.not($t.parent('li')).removeClass('open');
			$t.parent('li').toggleClass('open');
			return false;
		} else if(!$gnb.hasClass('able') && $t.next('ul').length==1){
			return false;
		}
		if($t.attr('href')=='#gnb' && !$t.parent('li').hasClass('open')){
			$li.addClass('open');
			$('.x .gnb').removeClass('able');
		} else {
			$li.removeClass('open');
			$('.x .gnb').addClass('able');
		}
	});
});