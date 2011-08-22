/* NHN (developers@xpressengine.com) */
jQuery(function($){
	// Constants
	var ESC_KEY = 27;

	// Overlapping label
	$('.form li').find('>input:text,>input:password,>textarea')
		.prev('label')
			.css({position:'absolute',top:'15px',left:'5px'})
			.next()
				.focus(function(){
					var $label = $(this).prev().stop().animate({opacity:0, left:'25px'},'fast',function(){ $label.css('visibility','hide') });
				})
				.blur(function(){
					var $this = $(this), $label;
					if($.trim($this.val()) == '') {
						$label = $this.prev().stop().css('visibility','visible').animate({opacity:1, left:'5px'},'fast');
					}
				})
				.filter('[value!=""]')
					.prev().css('visibility','hide').end()
				.end()
			.end()
			.parent()
				.css('position', 'relative');

	// Make selected checkbox elements bold
	var $rc_label = $('input:radio+label,input:checkbox+label'), $input_rc = $rc_label.prev('input');
	$input_rc
		.change(function(){
			var name = $(this).attr('name');
			$input_rc.filter(function(){ return this.name == name })
				.next('label').css('font-weight', 'normal').end()
				.filter(':checked')
					.next('label').css('font-weight', 'bold').end();
		})
		.change();

	// Toogle checkbox all
	$('.form th>input:checkbox')
		.change(function() {
			var $this = $(this), name = $this.data('name');

			$this.closest('table')
				.find('input:checkbox')
					.filter(function(){
						var $this = $(this);
						return ($this.attr('name') == name) || ($this.data('name') == name);
					})
						.prop('checked', $this.prop('checked'))
					.end()
				.end()
				.trigger('update.checkbox', name, this.checked);
		});

	// Global Navigation Bar
	var $menuitems = $('div.gnb')
		.removeClass('jx')
		.attr('role', 'navigation') // WAI-ARIA role
		.find('li')
			.attr('role', 'menuitem') // WAI-ARIA role
			.filter(':has(>ul)')
				.attr('aria-haspopup', 'true') // WAI-ARIA
				.find('>ul').hide().end()
				.mouseover(function(){
					var $this = $(this);
					if($this.css('float') == 'left') $this.find('>ul:hidden').prev('a').click();
				})
				.mouseleave(function(){
					var $this = $(this);
					if($this.css('float') == 'left') $this.find('>ul:visible').slideUp(100);
				})
				.find('>a')
					.focus(function(){ $(this).click() })
					.click(function(){
						$menuitems.removeClass('active');
					
						$(this)
							.next('ul').slideToggle(100).end()
							.parent().addClass('active');

						return false;
					})
				.end()
			.end()
			.find('>a')
				.blur(function(){
					var anchor = this;
					setTimeout(function(){
						var $a  = $(anchor), $ul = $a.closest('ul'), $focus = $ul.find('a:focus');

						if(!$focus.length || $focus.closest('ul').parent('div.gnb').length) {
							if($ul.parent('div.gnb').length) $ul = $a.next('ul');
							$ul.filter(':visible').slideUp(100);
						}
					}, 10);
				})
			.end()

	// Modal Window
	$('a.modalAnchor')
		.click(function(){
			var $this = $(this), $modal, disabled;

			// get and initialize modal window
			$modal = $( $this.attr('href') );
			$this.trigger('init.mw');

			// set the related anchor
			$modal.data('anchor', $this);

			if($modal.data('state') == 'showing') {
				$this.trigger('close.mw');
			} else {
				$this.trigger('open.mw');
			}

			return false;
		})
		.bind('init.mw', function(){
			var $this = $(this), $modal, $btnClose;

			$modal    = $( $this.attr('href') );
			$btnClose = $('<button type="button" class="modalClose" title="Close this layer">X</button>');
			$btnClose.click(function(){ $modal.data('anchor').trigger('close.mw') });

			$modal
				.prepend('<span class="bg"></span>')
				.append('<!--[if IE 6]><iframe class="ie6"></iframe><![endif]-->')
				.find('>.fg')
					.prepend($btnClose)
					.append($btnClose.clone(true))
				.end()
				.appendTo('body');

			// unbind create event
			$this.unbind('init.mw');
		})
		.bind('open.mw', function(){
			var $this = $(this), before_event, $modal, duration;

			// before event trigger
			before_event = $.Event('before-open.mw');
			$this.trigger(before_event);

			// is event canceled?
			if(before_event.isDefaultPrevented()) return false;

			// get modal window
			$modal = $( $this.attr('href') );

			// get duration
			duration = $this.data('duration') || 'fast';

			// set state : showing
			$modal.data('state', 'showing');

			// workaroud for IE6
			$('html,body').addClass('modalContainer');

			// after event trigger
			function after(){ $this.trigger('after-open.mw') };

			$(document).bind('keydown.mw', function(event){
				if(event.which == ESC_KEY) {
					$this.trigger('close.mw');
					return false;
				}
			});

			$modal
				.fadeIn(duration, after)
				.find('button.modalClose:first').focus();
		})
		.bind('close.mw', function(){
			var $this = $(this), before_event, $modal, duration;

			// before event trigger
			before_event = $.Event('before-close.mw');
			$this.trigger(before_event);

			// is event canceled?
			if(before_event.isDefaultPrevented()) return false;

			// get modal window
			$modal = $( $this.attr('href') );

			// get duration
			duration = $this.data('duration') || 'fast';

			// set state : hiding
			$modal.data('state', 'hiding');

			// workaroud for IE6
			$('html,body').removeClass('modalContainer');

			// after event trigger
			function after(){ $this.trigger('after-close.mw') };

			$modal.fadeOut(duration, after);
			$this.focus();
		});

	$('div.modal').hide();

	// pagination
	$('.pagination')
		.find('span.tgContent').css('whiteSpace', 'nowrap').end()
		.find('a.tgAnchor[href="#goTo"]')
			.each(function(idx){
				var $this = $(this);
				$this.after( $($this.attr('href')) );
			})
		.end();

	// Portlet Action
	$('.portlet .action')
		.css({display:'none',position:'absolute'})
		.parent()
			.mouseleave(function(){ $(this).find('>.action').fadeOut(100); })
			.mouseenter(function(){ $(this).find('>.action').fadeIn(100); })
			.focusin(function(){ $(this).mouseenter() })
			.focusout(function(){
				var $this = $(this), timer;
				
				clearTimeout($this.data('timer'));
				timer = setTimeout(function(){ if(!$this.find(':focus').length) $this.mouseleave() }, 10);

				$this.data('timer', timer);
			});

	// Display the dashboard in two column
	$('.dashboard>.portlet:odd').after('<br style="clear:both" />');

	// TODO: Site Map
// 	var siteMap = $('.siteMap');
// 	var siteItem = siteMap.find('li');
// 	siteItem
// 		.prepend('<button type="button" class="moveTo">Move to</button>')
// 		.append('<span class="vr"></span><span class="hr"></span>')
// 		.mouseover(function(){
// 			$(this).addClass('active');
// 			$('.vr').each(function(){
// 				var myHeight = $(this).parent('li').height();
// 				$(this).height(myHeight);
// 			});
// 			return false;
// 		})
// 		.mouseout(function(){
// 			$(this).removeClass('active');
// 		})
// 		.find('.moveTo+input').each(function(){
// 			$(this).width(this.value.length+'em');
// 		});
// 	siteMap.find('.moveTo')
// 		.focus(function(){
// 			$(this).parent('li').mouseover();
// 		})
// 		.blur(function(){
// 			$(this).mouseout();
// 		});
// 	siteMap.find('li:first-child').css('border','0');

	// Toggle Contents
	$('a.tgAnchor')
		.click(function(){
			var $this = $(this), $layer;

			// get content container
			$layer = $( $this.attr('href') );

			// set anchor object
			$layer.data('anchor', $this);

			if($layer.data('state') == 'showing') {
				$this.trigger('close.tc');
			} else {
				$this.trigger('open.tc');
			}

			return false;
		})
		.bind('open.tc', function(){
			var $this = $(this), $layer, effect, duration;

			// get content container
			$layer = $( $this.attr('href') );

			// get effeect
			effect = $this.data('effect');

			// get duration
			duration = $this.data('duration') || 'fast';

			// set state : showing
			$layer.data('state', 'showing');

			// before event trigger
			$this.trigger('before-open.tc');

			// When mouse button is down or when ESC key is pressed close this layer
			$(document)
				.unbind('mousedown.tc keydown.tc')
				.bind('mousedown.tc keydown.tc',
					function(event){
						if(event && (
							(event.type == 'keydown' && event.which != ESC_KEY) ||
							(event.type == 'mousedown' && ($(event.target).is('.tgAnchor,.tgContent') || $layer.has(event.target)[0]))
						)) return true;

						$this.trigger('close.tc');

						return false;
					}
				);

			// triggering after
			function trigger_after(){ $this.trigger('after-open.tc') }

			switch(effect) {
				case 'slide':
					$layer.slideDown(duration, trigger_after);
					break;
				case 'slide-h':
					var w = $layer.css({'overflow-x':'',width:''}).width();
					$layer
						.show()
						.css({'overflow-x':'hidden',width:'0px'})
						.animate({width:w}, duration, function(){ $layer.css({'overflow-x':'',width:''}); trigger_after(); });
					break;
				case 'fade':
					$layer.fadeIn(duration, trigger_after);
					break;
				default:
					$layer.show();
					$this.trigger('after-open.tc');
			}
		})
		.bind('close.tc', function(){
			var $this = $(this), $layer, effect, duration;

			// unbind document's event handlers
			$(document).unbind('mousedown.tc keydown.tc');

			// get content container
			$layer = $( $this.attr('href') );

			// get effeect
			effect = $this.data('effect');

			// get duration
			duration = $this.data('duration') || 'fast';

			// set state : hiding
			$layer.data('state', 'hiding');

			// before event trigger
			$this.trigger('before-close.tc');

			// triggering after
			function trigger_after(){ $this.trigger('after-close.tc') };

			// close this layer
			switch(effect) {
				case 'slide':
					$layer.slideUp(duration, trigger_after);
					break;
				case 'slide-h':
					$layer.animate({width:0}, duration, function(){ $layer.hide(); trigger_after(); });
					break;
				case 'fade':
					$layer.fadeOut(duration, trigger_after);
					break;
				default:
					$layer.hide();
					$this.trigger('after-close.tc');
			}
		});
	$('.tgContent')
		.hide()
		.focusout(function(event){
			var $this = $(this), $anchor = $this.data('anchor');
			setTimeout(function(){
				if(!$this.find(':focus').length && $this.data('state') == 'showing') $anchor.trigger('close.tc');
			}, 1);
		})

	// Popup list : 'Move to site' and 'Site map'
	$('.header>.siteTool>a.i')
		.bind('before-open.tc', function(){
			$(this)
				.addClass('active')
				.next('div.tgContent')
					.find('>.section:gt(0)').hide().end()
					.find('>.btnArea>button').show();
		})
		.bind('after-close.tc', function(){
			$(this).removeClass('active');
		})
		.next('#siteMapList')
			.find('>.section:last')
				.after('<p class="btnArea"><button type="button">&rsaquo; more</button></p>')
				.find('+p>button')
					.click(function(){
						// Display all sections then hide this button
						$(this).hide().parent().prevAll('.section').show();
					});
});
