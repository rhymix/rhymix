/* NHN (developers@xpressengine.com) */
jQuery(function($){
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
			.end()
			.parent()
				.css('position', 'relative');

	// Make selected checkbox elements bold
	var $rc_label = $('input:radio+label,input:checkbox+label'), $input_rc = $rc_label.prev('input');
	$input_rc
		.change(function(){
			var name = $(this).attr('name');
			$input_rc
				.filter('[name="'+name+'"]:not(:checked)')
					.next('label').css('font-weight', 'normal').end()
				.end()
				.next('label').css('font-weight', 'bold').end();
		})
		.change();

	// Toogle checkbox all
	$('.form th>:checkbox')
		.change(function() {
			var $this = $(this), self = this, name;

			name = $this.data('target');
			$this.closest('table').find('input:checkbox')
				.filter(function(){ return (this.name == name) })
					.prop('checked', $this.prop('checked'))
					.filter(function(){ return (this.parentNode.nodeName != 'TH') })
					.change();
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

	// TODO: Modal Window
// 	var htmlBody = $('html,body');
// 	var modalAnchor = $('.modalAnchor');
// 	var modal = $('.modal');
// 	var modalBg = modal.find('>.bg');
// 	var modalFg = modal.find('>.fg');
// 	var modalCloseHtml = '<button type="button" class="modalClose" title="Close this layer">X</button>';
// 	var modalBlurHtml = '<button type="button" class="modalBlur"></button>';
// 	var docHeight = $(document).height();
	$('.modal')
		.hide()
		.appendTo('body')
		.prepend('<span class="bg"></span>')
		.append('<!--[if IE 6]><iframe class="ie6"></iframe><![endif]-->');
// 	modalFg
// 		.prepend(modalCloseHtml)
// 		.prepend(modalBlurHtml);
// 	var modalClose = $('.modalClose');
// 	var modalBlur = $('.modalBlur');
// 	modalClose.eq(0).clone().appendTo(modalFg);
// 	modalBlur.eq(0).clone().appendTo(modalFg);
// 	modalAnchor
// 		.click(function(){
// 			if(typeof document.body.style.maxHeight == "undefined"){
// 				htmlBody.css({'width':'100%','height':'100%'});
// 			}
// 			var myTarget = $($(this).attr('href'));
// 			myTarget.fadeToggle(200).toggleClass('modalActive');
// 			myTarget.find('>.fg>.modalClose:first').focus();
// 			$(this).addClass('active');
// 		})
// 		.keypress(function(){
// 			if(event.keyCode != 32) return true;
// 			$(this).click();
// 			return false;
// 		});
// 	function closeModal() {
// 		if(typeof document.body.style.maxHeight == "undefined"){
// 			htmlBody.removeAttr('style');
// 		}
// 		modal.fadeOut(200).removeClass('modalActive');
// 		$('.modalAnchor.active').focus().removeClass('active');
// 		return false;
// 	}
// 	$(document).keydown(function(event){
// 		if(event.keyCode != 27) return true; // ESC
// 		if(modal.find('.tgContent:visible').length == 0) return closeModal();
// 	});
// 	$('.modal>.bg, .modalClose, .modal .cancel').click(closeModal);
// 	$('.modalBlur').focusin(function(event){
// 		modalClose.click();
// 	});

	// pagination
	$('.pagination')
		.find('span.gotopage')
			.attr('id', function(idx){ return 'gotopage-'+(idx+1) })
			.hide()
		.end()
		.find('a[href="#gotopage"]')
			.each(function(idx){
				var id = '#gotopage-'+(idx+1);
				$(this).attr('href', id).after($(id));
			})
			.click(function(){
				var $form, width, height, hidden, duration;
				console.log(this);

				$form  = $(this.getAttribute('href'));
				hidden = $form.is(':hidden');
				width  = $form.show().width();
				height = $form.height();
				duration = 100;

				$form.css('overflow', 'hidden').css('whiteSpace', 'nowrap');
				if(hidden) {
					$form
						.css('width', 0)
						.animate({width:width}, duration, function(){ $form.css({width:'',height:'',overflow:''}) })
						.find('input:text:first').focus();
				} else {
					$form
						.css('width', width)
						.animate({width:0}, duration, function(){ $form.hide().css({width:'',height:'',overflow:''}) });

					$(this).focus();
				}

				return false;
			})
		.end();

	// TODO: Portlet Action
// 	var action = $('.portlet .action');
// 	var action_li = action.parent('li');
// 	action.hide().css({'position':'absolute'});
// 	action_li.mouseleave(function(){
// 		action.fadeOut(100);
// 		return false;
// 	});
// 	action_li.mouseenter(function(){
// 		action_li.mouseleave();
// 		$(this).find('>.action').fadeIn(100);
// 		return false;
// 	});
// 	action_li.find('*:first-child').focusin(function(){
// 		$(this).parent('li').mouseenter();
// 	});

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

			// before event trigger
			$this.trigger('before-open.tc');

			// set state : showing
			$layer.data('state', 'showing');

			// When mouse button is down or when ESC key is pressed close this layer
			$(document)
				.unbind('mousedown.tc keydown.tc')
				.bind('mousedown.tc keydown.tc',
					function(event){
						if(event && (
							(event.type == 'keydown' && event.which != 27) || // '27' means ESC key
							(event.type == 'mousedown' && ($(event.target).is('.tgAnchor,.tgContent') || $layer.has(event.target)[0]))
						)) return true;

						$this.trigger('close.tc');

						return false;
					}
				);

			switch(effect) {
				case 'slide':
					$layer.slideDown(duration, function(){ $this.trigger('after-open.tc') });
					break;
				case 'fade':
					$layer.fadeIn(duration, function(){ $this.trigger('after-open.tc') });
					break;
				default:
					$layer.show();
					$this.trigger('after-open.tc');
			}
		})
		.bind('close.tc', function(){
			var $this = $(this), $layer, effect, duration;

			// get content container
			$layer = $( $this.attr('href') );

			// get effeect
			effect = $this.data('effect');

			// get duration
			duration = $this.data('duration') || 'fast';

			// before event trigger
			$this.trigger('before-close.tc');

			$(document).unbind('mousedown.tc keydown.tc');

			// set state : hiding
			$layer.data('state', 'hiding');

			// close this layer
			switch(effect) {
				case 'slide':
					$layer.slideUp(duration, function(){ $this.trigger('after-close.tc') });
					break;
				case 'fade':
					$layer.fadeOut(duration, function(){ $this.trigger('after-close.tc') });
					break;
				default:
					$layer.hide();
					$this.trigger('after-close.tc');
			}
		});
	$('.tgContent')
		.hide()
		.focusout(function(){
			var $this = $(this), $anchor = $this.data('anchor');
			setTimeout(function(){
				if(!$this.find(':focus').length) $anchor.trigger('close.tc');
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

	// TODO : Suggestion
// 	var suggestion = $('#suggestion');
// 	var snTarget = suggestion.prev('input[type=text]');
// 	suggestion.css('position','absolute');
// 	snTarget.keypress(function(){
// 		$(this).next('.tgContent').fadeIn(200);
// 		snTarget.css('background','url(./img/preLoader16.gif) no-repeat 268px center');
// 	});
// 	snTarget.keyup(function(){
// 		snTarget.css('background','');
// 	});
// 	suggestion
// 		.find('li:first-child>button').css('fontWeight','bold').end()
// 		.find('li:gt(0)>button').click(function(){
// 			var myValue = $(this).text();
// 			snTarget.val(myValue);
// 			return closeTg();
// 		});

	// TODO: FTP Suggestion
// 	var ftp_path = $('#ftp_path');
// 	var ftpSuggestion = $('#ftpSuggestion');
// 	ftpSuggestion.css('position','absolute').find('.tgBlur').eq(0).remove();
// 	ftpSuggestion.find('li:not(:first-child)>button').click(function(){
// 		var setValue = ftp_path.val();
// 		var myValue = $(this).text();
// 		ftp_path.val(setValue+myValue);
// 	});

	// TODO: Up-Down Dragable
// 	var uDrag = $('.uDrag');
// 	uDrag.find('>tr>td:first-child, >li').wrapInner('<div class="wrap"></div>');
// 	var uDragWrap = $('.uDrag .wrap');
// 	uDragWrap
// 		.prepend('<button type="button" class="dragBtn">Up/Down</button>')
// 		.each(function(){
// 			var t = $(this);
// 			var tHeight = t.parent().height();
// 			if(t.parent().is('td')){
// 				t.height(tHeight);
// 			}
// 		});
// 	var uDragItem = $('.uDrag>tr, .uDrag>li');
// 	uDragItem
// 		.mouseenter(function(){
// 			$(this).addClass('dragActive');
// 		})
// 		.mouseleave(function(){
// 			$(this).removeClass('dragActive');
// 		});
});
