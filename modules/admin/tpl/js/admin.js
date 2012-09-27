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
// Alert Closer
	var $xAlert = $('.x .x_alert');
	$xAlert.prepend('<button type="button" class="x_close">&times;</button>');
	$xAlert.children('.x_close').click(function(){
		$(this).parent('.x_alert').hide();
	});	
// Desabled Buttons
	$('.x .x_btn').click(function(){
		if($(this).hasClass('x_disabled')){
			return false;
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

// Module finder
jQuery(function($){

$.fn.xeModuleFinder = function(){
	this
		.not('.xe-module-finder')
		.addClass('xe-module-finder')
		.find('a.tgAnchor.findsite')
			.bind('before-open.tc', function(){
				var $this, $ul, val;

				$this = $(this);
				$ul   = $($this.attr('href')).find('>ul');
				val   = $this.prev('input:text').val();

				function on_complete(data) {
					var $li, list = data.site_list, i, c;

					$ul.empty();
					$this.closest('.modulefinder').find('.moduleList,.moduleIdList').attr('disabled','disabled');

					if(data.error || !$.isArray(list)) {
						$this.trigger('close.tc');
						return;
					}

					for(i=0,c=list.length; i < c; i++) {
						$li = $('<li />').appendTo($ul);
						$('<button type="button" />').text(list[i].domain).data('site_srl', list[i].site_srl).appendTo($li);
					}
				};

				$.exec_json('admin.getSiteAllList', {domain:val}, on_complete);
			})
		.end()
		.find('.tgContent.suggestion')
			.delegate('button','click',function(){
				var $this, $finder;

				$this    = $(this);
				$finder  = $this.closest('.modulefinder');

				function on_complete(data) {
					var $mod_select, list = data.module_list, x;

					if(data.error || !list) return;

					$mod_select = $finder.find('.moduleList').data('module_list', list).removeAttr('disabled').empty();
					for(x in list) {
						if(!list.hasOwnProperty(x)) continue;
						$('<option />').attr('value', x).text(list[x].title).appendTo($mod_select);
					}
					$mod_select.prop('selectedIndex', 0).change().focus();

					if(!$mod_select.is(':visible')) {
						$mod_select
							.slideDown(100, function(){
								$finder.find('.moduleIdList:not(:visible)').slideDown(100).trigger('show');
							})
							.trigger('show');
					}
				};

				$finder.find('a.tgAnchor.findsite').trigger('close.tc');

				$.exec_json('module.procModuleAdminGetList', {site_srl:$this.data('site_srl')}, on_complete);
			})
		.end()
		.find('.moduleList,.moduleIdList').hide().end()
		.find('.moduleList')
			.change(function(){
				var $this, $mid_select, val, list;

				$this   = $(this);
				val     = $this.val();
				list    = $this.data('module_list');

				if(!list[val]) return;

				list = list[val].list;
				$mid_select = $this.closest('.modulefinder').find('.moduleIdList').removeAttr('disabled').empty();

				for(var x in list) {
					if(!list.hasOwnProperty(x)) continue;
					$('<option />').attr('value', list[x].module_srl).text(list[x].browser_title).appendTo($mid_select);
				}
				$mid_select.prop('selectedIndex', 0).change();
			});

	return this;
};
$('.modulefinder').xeModuleFinder();

});

// Module Search : A New Version Of Module Finder
jQuery(function($){

_xeModuleSearch = function(){
	var t = this;
	var $t = $(this);

	var $moduleSearchWindow = $t.find(".moduleSearchWindow");

	var $siteListDiv = $moduleSearchWindow.find('.siteList');
	var $moduleTypeListDiv = $moduleSearchWindow.find('.moduleTypeList');
	var $moduleInstanceListDiv = $moduleSearchWindow.find('.moduleInstanceList');

	var $siteList = $siteListDiv.find('UL');
	var $moduleTypeList = $moduleTypeListDiv.find('UL');
	var $moduleInstanceList = $moduleInstanceListDiv.find('SELECT');

	var $siteListSearchInput = $moduleSearchWindow.find('INPUT.siteListSearchInput');
	var aSiteListData;

	var MAX_LIST_HEIGHT = 280;
	
	function setListSize($UL, nHeight){
		var nWidth, $div;
		$UL.find('li div').width('');
		$UL.css('height', '');
		$UL.css('overflow-y', '');
		if($UL.height() > nHeight){
			$div = $UL.find('li div');
			$div.width($div.width()-20+'px');
			$UL.css('height', nHeight+'px');
			$UL.css('overflow-y', 'auto');
		}
	}

	function setSiteList(sFilter){
		var sDomain;
		var rxFilter = new RegExp(sFilter, "ig");
		var list = aSiteListData;

		$siteList.empty();
	
		for(i=0,c=list.length; i < c; i++) {
			sDomain = list[i].domain;
			if(sFilter){
				if(!sDomain.match(rxFilter)) continue;
				sDomain = sDomain.replace(rxFilter, function(sKeyword){
					return '<span class="highlight">'+sKeyword+'</span>';
				});
			}

			$li = $('<li />').appendTo($siteList);
			$('<a>').attr('href', '#').html(
				'<div>' + sDomain + '</div>' +
				'<span class="icon-circle-arrow-right" style="display:inline-block;float:right;width:16px;height:16px;"></span>'
			).data('site_srl', list[i].site_srl).appendTo($li);
		}

		setListSize($siteList, MAX_LIST_HEIGHT - $siteListSearchInput.parent("DIV").height());
	}

	$siteListSearchInput.keyup(function(){
		setSiteList($siteListSearchInput.val());
	});

	if(typeof console == 'undefined'){
		console={log:function(){}};
	}

	$t
		.not('.xe-module-search')
		.addClass('xe-module-search')
		.find('a.tgAnchor.moduleSearch')
			.bind('before-open.tc', function(){
				var $this;

				$this = $(this);

				function on_complete(data) {
					var $li, list = data.site_list, i, c;

					if(data.error || !$.isArray(list)) {
						$this.trigger('close.tc');
						return;
					}

					aSiteListData = list;

					setSiteList($siteListSearchInput.val());

					$siteListSearchInput.focus();
				};

				$siteList.empty();
				$moduleInstanceList.empty();
				$moduleTypeListDiv.hide();
				$moduleInstanceListDiv.hide();
				$.exec_json('admin.getSiteAllList', {domain:""}, on_complete);
			})
		.end()
		.find('.tgContent .siteListUL')
			.delegate('a','click',function(oEvent){
				var $this, $finder;

				$this    = $(this);
				$finder  = $this.closest('.modulefinder');

				function on_complete(data) {

					var list = data.module_list, x;

					if(data.error || !list) return;

					for(x in list) {
						if(!list.hasOwnProperty(x)) continue;
						$li = $('<li />').appendTo($moduleTypeList);
						$('<a>').attr('href', '#').html(
							'<div>'+list[x].title+'</div>' +
							'<span class="icon-circle-arrow-right" style="display:inline-block;float:right;width:16px;height:16px;"></span>'
						).data('moduleInstanceList', list[x].list).appendTo($li);
						//$('<option />').attr('value', x).text(list[x].title).appendTo($mod_select);
					}

					$moduleSearchWindow.find('.moduleTypeList').show();
					setListSize($moduleTypeList, MAX_LIST_HEIGHT);

					$siteList.find('li').removeClass('on');
					$this.parent('li').addClass('on');
				};

				//$finder.find('a.tgAnchor.findsite').trigger('close.tc');
				$moduleTypeList.empty();
				$moduleInstanceListDiv.hide();

				$.exec_json('module.procModuleAdminGetList', {site_srl:$this.data('site_srl')}, on_complete);

				oEvent.preventDefault();
			})
		.end()
		//.find('.moduleList,.moduleIdList').hide().end()
		.find('.moduleTypeListUL')
			.delegate('a', 'click', function(oEvent){
			
				var $this, $mid_select, val, list;

				$this = $(this);
				list = $this.data('moduleInstanceList');
				if(!list) return;

				t.sSelectedModuleType = $this.text();
				$moduleInstanceList.empty();

				for(var x in list) {
					if(!list.hasOwnProperty(x)) continue;

					$li = $('<option />').html(list[x].browser_title).appendTo($moduleInstanceList).val(list[x].module_srl).data('mid', list[x].module_srl)
							.data('module_srl', list[x].module_srl).data('layout_srl', list[x].layout_srl).data('browser_title', list[x].browser_title);
				}

				$moduleInstanceListDiv.show();
				setListSize($moduleInstanceList, MAX_LIST_HEIGHT);

				$moduleTypeList.find('li').removeClass('on');
				$this.parent('li').addClass('on');

				oEvent.preventDefault();
			})
		.end()
		.find('.moduleSearch_ok').click(function(oEvent){
				var aSelected = [];
				$t.find('.moduleInstanceListSelect option:selected').each(function(){
					aSelected.push({
						'type' : t.sSelectedModuleType,
						'module_srl' : $(this).data('module_srl'),
						'layout_srl' : $(this).data('layout_srl'),
						'browser_title' : $(this).data('browser_title')
					});
				});

				$t.trigger('moduleSelect', [aSelected]);
				$('.tgAnchor.moduleSearch').trigger('close.tc');
				
				oEvent.preventDefault();
			});
			

	return this;
};

$.fn.xeModuleSearch = function(){
	$(this).each(_xeModuleSearch);
};

$('.moduleSearch').xeModuleSearch();
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
