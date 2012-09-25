/* NHN (developers@xpressengine.com) */
jQuery(function($){
// iSO mobile device toolbar remove
	window.top.scrollTo(0,0);
// Skip to content
	$('.x .skipNav>a').click(function(){
		$($(this).attr('href')).attr('tabindex','0').css('outline','0').focus();
	});

// TARGET toggle
	$('.x [data-toggle]').click(function(){
		$($(this).attr('data-toggle')).toggle();
		return false;
	});
// TARGET show
	$('.x [data-show]').click(function(){
		$($(this).attr('data-show')).show();
		return false;
	});
// TARGET hide
	$('.x [data-hide]').click(function(){
		$($(this).attr('data-hide')).hide();
		return false;
	});

// Tab Navigation
	$('.x .x_tab-content>.x_tab-pane:not(".x_active")').hide();
	$('.x .x_nav-tabs').find('>li>a[href^="#"]').click(function(){
		var $this = $(this);
		$this.parent('li').addClass('x_active').siblings().removeClass('x_active');
		$this.closest('.x_nav-tabs').next('.x_tab-content').find($this.attr('href')).addClass('x_active').show().siblings().removeClass('x_active').hide();
		return false;
	});
// GNB Height 100%
	var $xBody = $('.x>.body');
	var $xContent = $xBody.find('>.content');
	var $xGnb = $xBody.find('>.gnb');
	var $xGnb_li = $xGnb.find('>ul>li');
	$(window).resize(function(){
		setTimeout(function(){
			if($(window).width() <= 980 || $(window).width() > 1240){
				$xBody.removeClass('wide');
			} else {
				$xBody.addClass('wide');
			}
		}, 100);
	}).resize();
// GNB Click toggle
	$xGnb_li.find('ul').prev('a')
		.bind('click focus', function(){
			var $this = $(this);
			// Submenu toggle
			$xGnb_li.not($this.parent('li')).removeClass('open');
			$(this).parent('li').toggleClass('open');
			$xGnb.trigger('mouseenter'); // GNB Hover
			return false;
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
			if($(window).width() >= 980){
				$xBody.removeClass('wide');
				contentBugFix();
			}
		})
		.mouseleave(function(){ // Mouseleave
			if($(window).width() >= 980 && $(window).width() < 1240){
				$xBody.addClass('wide');
				contentBugFix();
			}
		});
// GNB Mobile Toggle
	$xGnb.find('>a[href="#gnbNav"]').click(function(){
		$(this).parent('.gnb').toggleClass('open');
		return false;
	});
// GNB Close
	$xGnb
		.prepend('<button type="button" class="close before" />')
		.append('<button type="button" class="close after" />');
	$xGnb.find('>.close').focus(function(){
		$xBody.addClass('wide');
		contentBugFix();
	});
// Multilingual
	var $mlCheck = $('.x .multilingual>label>input[type="checkbox"]');
	function multilingual(){
		$mlCheck.each(function(event){
			var $this = $(this);
			var $label = $this.parent('label'); // Checkbox label
			var $input = $label.siblings('input[type="text"]:first');
			var $select = $label.siblings('select:first');
			var $fieldset = $this.closest('.multilingual').siblings('.multilingual_item:first'); // Multilingual list
			if($this.is(':checked')){
				$input.hide();
				$select.show();
				$label.addClass('checked'); 
				$fieldset.show();
			} else {
				$input.show();
				$select.hide();
				$label.removeClass('checked');
				$fieldset.hide();
			}
		});
	}
	multilingual();
	$mlCheck.change(multilingual);
// Check All
	$('.x th>input[type="checkbox"]').change(function(){
		var $this =$(this);
		var $target = $this.closest('table').find('th>input[type="checkbox"], td>input[type="checkbox"]');
		if($this.is(':checked')){
			$target.attr('checked','checked');
		} else {
			$target.removeAttr('checked');
		}
		
	});
// Pagination
	$('.x .x_pagination .x_disabled, .x .x_pagination .x_active').click(function(){
		return false;
	});
// Section Toggle
	$('.x .section>h1').append('<button type="button" class="snToggle x_icon-chevron-up">Toggle this section</button>');
	$('.x .section>h1>.snToggle').click(function(){
		var $this = $(this);
		var $section = $this.closest('.section');
		if(!$section.hasClass('collapse')){
			$section.addClass('collapse').children('h1:first').siblings().hide();
			$this.removeClass('x_icon-chevron-up').addClass('x_icon-chevron-down');
		} else {
			$section.removeClass('collapse').children('h1:first').siblings().show();
			$this.removeClass('x_icon-chevron-down').addClass('x_icon-chevron-up');
		}
	});
// Close Button
	$('.x_close').click(function(){
		$(this).parent().hide();
	});
// Modal Window
	var $modal = $('.x_modal');
	if($modal.length >= 1){
		$('body').append('<div class="x_modal-backdrop"></div>').append($modal); // append background
		$modal.prepend('<button type="button" class="x_close">&times;</button>'); // prepend close button
	}
	// Set close button 'data-hide' attribute
	$modal.children('.x_close').each(function(){
		var $this = $(this);
		$this.attr('data-hide', '#' + $this.parent().attr('id'));
	});
	// Modal Open
	var $modalBack = $('.x_modal-backdrop');
	$('.x a').click(function(){
		var $target = $($(this).attr('href'));
		if($target.hasClass('x_modal')){
			$modalBack.show();
			$target.show();
		}
	});
	// Modal Close 
	function modalClose(){
		$modal.hide();
		$modalBack.hide();
	}
	$modalBack.click(modalClose); // $modalBack click
	$(document).keydown(function(event){ // ESC keydown
		if(event.keyCode != 27) return true;
		return modalClose();
	});
	$('[data-hide]').click(function(){ // [data-hide] click
		if($($(this).attr('data-hide')).hasClass('x_modal')){
			modalClose();
		}
	});
});