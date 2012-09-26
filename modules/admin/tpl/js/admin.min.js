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
		var $this = $(this);
		var $target = $($this.attr('data-toggle'));
		$target.toggle();
		if($target.is(':visible') && !$target.find('a,input,button,textarea,select').length){
			$target.attr('tabindex','0').focus();
		} else if($target.is(':visible') && $target.find('a,input,button,textarea,select').length) {
			$target.find('a,input,button,textarea,select').eq(0).focus();
		} else {
			$this.focus();
		}
		return false;
	});
// TARGET show
	$('.x [data-show]').click(function(){
		$($(this).attr('data-show')).show().attr('tabindex','0').focus();
		return false;
	});
// TARGET hide
	$('.x [data-hide]').click(function(){
		var $this = $(this);
		$($this.attr('data-hide')).hide();
		$this.focus();
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
	$('.x th>input[type="checkbox"]')
		.change(function() {
			var $this = $(this), name = $this.data('name');

			$this.closest('table')
				.find('input:checkbox')
					.filter(function(){
						var $this = $(this);
						return !$this.prop('disabled') && (($this.attr('name') == name) || ($this.data('name') == name));
					})
						.prop('checked', $this.prop('checked'))
					.end()
				.end()
				.trigger('update.checkbox', [name, this.checked]);
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
});

// Modal Window
jQuery(function($){

var ESC = 27;

$.fn.xeModalWindow = function(){
	this
		.not('.xe-modal-window')
		.addClass('xe-modal-window')
		.each(function(){
			$( $(this).attr('href') ).addClass('x').hide();
		})
		.click(function(){
			var $this = $(this), $modal, $btnClose, disabled;

			// get and initialize modal window
			$modal = $( $this.attr('href') );
			if(!$modal.parent('body').length) {
				$btnClose = $('<button type="button" class="x_close">&times;</button>');
				$btnClose.click(function(){ $modal.data('anchor').trigger('close.mw') });
				$modal.find('[data-hide]').click(function(){ $modal.data('anchor').trigger('close.mw') });
				$('body').append('<div class="x_modal-backdrop"></div>').append($modal); // append background
				$modal.prepend($btnClose); // prepend close button
			}

			// set the related anchor
			$modal.data('anchor', $this);

			if($modal.data('state') == 'showing') {
				$this.trigger('close.mw');
			} else {
				$this.trigger('open.mw');
			}

			return false;
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
				if(event.which == ESC) {
					$this.trigger('close.mw');
					return false;
				}
			});

			$modal
				.fadeIn(duration, after)
				.find('button.x_close:first').focus();
			$('.x_modal-backdrop').show();
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
			$('.x_modal-backdrop').hide();
			$this.focus();
		});
};
$('a.modalAnchor').xeModalWindow();
$('div.x_modal').addClass('x').hide();

});

// Content Toggler
jQuery(function($){

var dont_close_this_time = false;
var ESC = 27;

$.fn.xeContentToggler = function(){
	this
		.not('.xe-content-toggler')
		.addClass('xe-content-toggler')
		.each(function(){
			var $anchor = $(this); $layer = $($anchor.attr('href'));

			$layer.hide()
				.not('.xe-toggling-content')
				.addClass('xe-toggling-content')
				.mousedown(function(event){ dont_close_this_time = true })
				.focusout(function(event){
					setTimeout(function(){
						if(!dont_close_this_time && !$layer.find(':focus').length && $layer.data('state') == 'showing') $anchor.trigger('close.tc');
						dont_close_this_time = false;
					}, 1);
				});
		})
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

			dont_close_this_time = false;

			// When mouse button is down or when ESC key is pressed close this layer
			$(document)
				.unbind('mousedown.tc keydown.tc')
				.bind('mousedown.tc keydown.tc',
					function(event){
						if(event) {
							if(event.type == 'keydown' && event.which != ESC) return true;
							if(event.type == 'mousedown') {
								var $t = $(event.target);
								if($t.is('html,.tgAnchor,.tgContent') || $layer.has($t).length) return true;
							}
						}

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

	return this;
};

$('a.tgAnchor').xeContentToggler();

});
// Sortable table
jQuery(function($){

var
	dragging = false,
	$holder  = $('<tr class="placeholder"><td>&nbsp;</td></tr>');

$.fn.xeSortableTable = function(){
	this
		.not('.xe-sortable-table')
		.addClass('xe-sortable-table')
		.delegate('button.dragBtn', 'mousedown.st', function(event){
			var $this, $tr, $table, $th, height, width, offset, position, offsets, i, dropzone, cols, ofspar;

			if(event.which != 1) return;

			$this  = $(this);
			$tr    = $this.closest('tr');
			$table = $this.closest('table');
			ofspar = $table.get(0).offsetParent;
			height = $tr.height();
			width  = $tr.width();

			// before event trigger
			before_event = $.Event('before-drag.st');
			$table.trigger(before_event);

			// is event canceled?
			if(before_event.isDefaultPrevented()) return false;

			position = {x:event.pageX, y:event.pageY};
			offset   = getOffset($tr.get(0), ofspar);

			$clone = $tr.attr('target', true).clone(true).appendTo($table);

			// get colspan
			cols = ($th=$table.find('thead th')).length;
			$th.filter('[colspan]').attr('colspan', function(idx,attr){ cols += attr - 1; });
			$holder.find('td').attr('colspan', cols);

			// get offsets of all list-item elements
			offsets = [];
			$table.find('tbody>tr:not([target],.sticky,:hidden)').each(function() {
				var $this = $(this), o;

				o = getOffset(this, ofspar);
				offsets.push({top:o.top, bottom:o.top+$this.height(), $item:$this});
			});

			$clone
				.addClass('draggable')
				.css({
					position: 'absolute',
					opacity : .6,
					width   : width,
					height  : height,
					left    : offset.left,
					top     : offset.top,
					zIndex  : 100
				});

			// Set a place holder
			$holder
				.css({
					position:'absolute',
					opacity : .6,
					width   : width,
					height  : '10px',
					left    : offset.left,
					top     : offset.top,
					backgroundColor : '#bbb',
					overflow: 'hidden',
					zIndex  : 99
				})
				.appendTo($table);

			$tr.css('opacity', .6);

			$(document)
				.unbind('mousedown.st mouseup.st')
				.bind('mousemove.st', function(event) {
					var diff, nTop, item, i, c, o;

					dropzone = null;

					diff = {x:position.x-event.pageX, y:position.y-event.pageY};
					nTop = offset.top - diff.y;

					for(i=0,c=offsets.length; i < c; i++) {
						o = offsets[i];
						if( (i && o.top > nTop) || ((i < c-1) && o.bottom < nTop)) continue;

						dropzone = {element:o.$item};
						if(o.top > nTop - 12) {
							dropzone.state = 'before';
							$holder.css('top', o.top-5);
						} else {
							dropzone.state = 'after';
							$holder.css('top', o.bottom-5);
						}
					}

					$clone.css({top:nTop});
				})
				.bind('mouseup.st', function(event) {
					var $dropzone;

					dragging = false;

					$(document).unbind('mousemove.st mouseup.st');
					$tr.removeAttr('target').css('opacity', '');
					$clone.remove();
					$holder.remove();

					if(!dropzone) return;
					$dropzone = $(dropzone.element);

					// use the clone for animation
					$dropzone[dropzone.state]($tr);

					$table.trigger('after-drag.st');
				});
		})

	return this;
};
$('table.sortable').xeSortableTable();


function getOffset(elem, offsetParent) {
	var top = 0, left = 0;

	while(elem && elem != offsetParent) {
		top  += elem.offsetTop;
		left += elem.offsetLeft;

		elem = elem.offsetParent;
	}

	return {top:top, left:left};
}

});
