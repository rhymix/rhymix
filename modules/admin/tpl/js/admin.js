/* NHN (developers@xpressengine.com) */
jQuery(function($){
// iSO mobile device toolbar remove
	window.scrollTo(0,0);
// Skip to content
	$('.x .skipNav>a').click(function(){
		$($(this).attr('href')).attr('tabindex','0').css('outline','0').focus();
	});
// TARGET toggle
	$(document.body).on('click', '.x [data-toggle]', function(){
		var $this = $(this);
		var $target = $($this.attr('data-toggle'));
		var focusable = 'a,input,button,textarea,select';
		$target.toggle();
		if($target.is(':visible') && !$target.find(focusable).length){
			$target.attr('tabindex','0').not(':disabled').focus();
		} else if($target.is(':visible') && $target.find(focusable).length) {
			$target.find(focusable).not(':disabled').eq(0).focus();
		} else {
			$this.focus();
		}
		return false;
	});
// TARGET show
	$(document.body).on('click', '.x [data-show]', function(){
		$($(this).attr('data-show')).show().attr('tabindex','0').focus();
		return false;
	});
// TARGET hide
	$(document.body).on('click', '.x [data-hide]', function(){
		var $this = $(this);
		$($this.attr('data-hide')).hide();
		$this.focus();
		return false;
	});
// Tab Navigation
	$.fn.xeTabbable = function(){
		$(this).each(function(){
			var $this = $(this);
			$this.find('>.x_nav-tabs>li>a').each(function(index){
				$(this).attr('data-index', index+1);
			});
			$this.find('>.x_tab-content>.x_tab-pane').each(function(index){
				$(this).attr('data-index', index+1);
			});
		});
		$('.x .x_tab-content>.x_tab-pane:not(".x_active")').hide();
	}
	$('.x .x_tabbable').xeTabbable();

	$(document.body).on('click', '.x .x_nav-tabs>li>a[href*="#"]', function(){
		var $this = $(this);
		$this.parent('li').addClass('x_active').siblings().removeClass('x_active');
		$this.closest('.x_nav-tabs').next('.x_tab-content').find('>.x_tab-pane').eq($this.attr('data-index')-1).addClass('x_active').show().siblings().removeClass('x_active').hide();
		return false;
	});
// GNB
	var $xBody = $('.x>.body');
	var $xContent = $xBody.children('#content.content');
	var $xGnb = $xBody.find('>.gnb');
	var $xGnb_li = $xGnb.find('>ul>li');
	// Add icon
	$xGnb_li.find('a').prepend('<i />');
	// Active Submenu Copy
	$xGnb_li.find('>ul>li.active_').clone().addClass('active').prependTo('#gnbNav');
	// GNB Hover toggle
	function reflow(){ // Chrome browser rendering bug fix
		$xContent.width('99.99%');
		setTimeout(function(){
			$xContent.removeAttr('style');
			if($xGnb.height() > $xContent.height()){
				$xContent.height($xGnb.height());
			}
		}, 100);
	}
	// GNB Click toggle
	$xGnb_li.find('ul').prev('a')
		.bind('click focus', function(){
			var $this = $(this);
			$this.parent('li').addClass('open').siblings('li').removeClass('open');
			$xBody.removeClass('wide');
			reflow();
			return false;
		});
	// GNB Mobile Toggle
	$xGnb.find('>a[href="#gnbNav"]').click(function(){
		$(this).parent('.gnb').toggleClass('open');
		$xBody.toggleClass('wide');
		reflow();
		return false;
	});
	// GNB Close
	$xGnb
		.prepend('<button type="button" class="close before" />')
		.append('<button type="button" class="close after" />');
	$xGnb.find('>.close').focus(function(){
		$xBody.addClass('wide');
		reflow();
	});
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
	$(document.body).on('click', '.x .x_pagination .x_disabled, .x .x_pagination .x_active', function(){
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
// Vertical Rule Style
	$('.x i').each(function(){
		var $this = $(this);
		if($this.text() == '|'){
			$this.addClass('vr');
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

			if($modal.data('state') == 'showing') {
				$this.trigger('close.mw');
			} else {
				$this.trigger('open.mw');
			}

			return false;
		})
		.bind('open.mw', function(){
			var $this = $(this), $modal, $btnClose, disabled, before_event, duration;
			
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
				.find('button.x_close:first').focus().end()
				.prev('.x_modal-backdrop').show();
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

			// after event trigger
			function after(){ $this.trigger('after-close.mw') };

			$modal.fadeOut(duration, after)
			.prev('.x_modal-backdrop').hide();
			$this.focus();
		});
	$('div.x_modal').addClass('x').hide();
};
$('a.modalAnchor').xeModalWindow();

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

var tmpCount = 0;
_xeModuleSearch = function(){
	var t = this;
	var $t = $(this);
	var is_multiple = $t.data('multiple');
	if(!is_multiple) is_multiple = '';
	var id = '__module_searcher_' + tmpCount;
	tmpCount++;

	// add html
	$.exec_json('module.getModuleAdminModuleSearcherHtml', {'id': id, 'is_multiple': is_multiple}, function(data){
		if(!data || !data.html) return;

		$t.after(data.html).addClass('tgAnchor').attr('href', '#' + id).xeContentToggler();

		var $moduleWindow = $t.next(".moduleWindow");
		var $siteListDiv = $moduleWindow.find('.siteList');
		var $moduleListDiv = $moduleWindow.find('.moduleList');
		var $instanceListDiv = $moduleWindow.find('.instanceList');
		var $siteList = $siteListDiv.find('>ul');
		var $moduleList = $moduleListDiv.find('>ul');
		var $instanceList = $instanceListDiv.find('>select');
		var $siteFinder = $moduleWindow.find('input.siteFinder');
		var aSiteListData;
		var MAX_LIST_HEIGHT = 280;

		function setListSize($UL, nHeight){
			var nWidth, $div;
			$UL.find('li div').width('');
			$UL.css('height', 'auto');
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
					sDomain
				).data('site_srl', list[i].site_srl).appendTo($li);
			}

			setListSize($siteList, MAX_LIST_HEIGHT - $siteFinder.parent("div").height());
		}

		$siteFinder.keyup(function(){
			setSiteList($siteFinder.val());
		});

		if(typeof console == 'undefined'){
			console={log:function(){}};
		}

		$t
			.not('.xe-module-search')
			.addClass('xe-module-search')
			.parent()
			.find('a.moduleTrigger')
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

						setSiteList($siteFinder.val());

						$siteFinder.focus();
					};

					$siteList.empty();
					$instanceList.empty();
					$moduleListDiv.hide();
					$instanceListDiv.hide();
					$.exec_json('admin.getSiteAllList', {domain:""}, on_complete);
				});
		$moduleWindow
			.find('.siteList>ul')
				.delegate('a','click',function(oEvent){
					var $this, $finder;

					$this    = $(this);
					$finder  = $this.closest('.moduleSearch');

					function on_complete(data) {

						var list = data.module_list, x;

						if(data.error || !list) return;

						for(x in list) {
							if(!list.hasOwnProperty(x)) continue;
							$li = $('<li />').appendTo($moduleList);
							$('<a>').attr('href', '#').html(
								list[x].title
							).data('moduleInstanceList', list[x].list).appendTo($li);
						}

						$moduleWindow.find('.moduleList').show();
						setListSize($moduleList, MAX_LIST_HEIGHT);

						$siteList.find('li').removeClass('x_active');
						$this.parent('li').addClass('x_active');
					};

					$moduleList.empty();
					$instanceListDiv.hide();

					$.exec_json('module.procModuleAdminGetList', {site_srl:$this.data('site_srl')}, on_complete);

					oEvent.preventDefault();
				})
			.end()
			.find('.moduleList>ul')
				.delegate('a', 'click', function(oEvent){
				
					var $this, $mid_select, val, list;

					$this = $(this);
					list = $this.data('moduleInstanceList');
					if(!list) return;

					t.sSelectedModuleType = $this.text();
					$instanceList.empty();

					for(var x in list) {
						if(!list.hasOwnProperty(x)) continue;

						$li = $('<option />').html(list[x].browser_title + ' (' + list[x].mid + ')').appendTo($instanceList).val(list[x].module_srl).data('mid', list[x].mid)
								.data('module_srl', list[x].module_srl).data('layout_srl', list[x].layout_srl).data('browser_title', list[x].browser_title);
					}

					$instanceListDiv.show();
					setListSize($instanceList, MAX_LIST_HEIGHT);

					$moduleList.find('li').removeClass('x_active');
					$this.parent('li').addClass('x_active');

					oEvent.preventDefault();
				})
			.end()
			.find('.moduleSearch_ok').click(function(oEvent){
					var aSelected = [];
					$instanceList.find('option:selected').each(function(){
						aSelected.push({
							'type' : t.sSelectedModuleType,
							'module_srl' : $(this).data('module_srl'),
							'layout_srl' : $(this).data('layout_srl'),
							'browser_title' : $(this).data('browser_title'),
							'mid' : $(this).data('mid')
						});
					});

					$t.trigger('moduleSelect', [aSelected]);
					$('a.moduleTrigger').trigger('close.tc');
					
					oEvent.preventDefault();
				});
	});

	return this;
};

$.fn.xeModuleSearch = function(){
	$(this).each(_xeModuleSearch);
};

$('.moduleTrigger').xeModuleSearch();

// Add html for .module_search
$.fn.xeModuleSearchHtml = function(){
	var tmpCount = 0;

	$(this).each(function(){
		var $this = $(this);
		var id = $this.attr('id');
		if(!id) id = '__module_search_' + tmpCount;
		tmpCount++;

		// add html
		var $btn = $('<a class="x_btn moduleTrigger">' + xe.cmd_find + '</a>');
		var $displayInput = $('<input type="text" readonly>');
		$this.after($btn).after('&nbsp;').after($displayInput).hide();
		$btn.xeModuleSearch();

		// on selected module
		$btn.bind('moduleSelect', function(e, selected){
			$displayInput.val(selected[0].browser_title + ' (' + selected[0].mid + ')');
			$this.val(selected[0].module_srl);
		});

		// get module info
		if($this.val()){
			$.exec_json('module.getModuleAdminModuleInfo', {'module_srl': $this.val()}, function(data){
				if(!data || !data.module_info) return;

				$displayInput.val(data.module_info.browser_title + ' (' + data.module_info.mid + ')');
			});
		}
	});

	return this;
}

$('.module_search').xeModuleSearchHtml();

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

// filebox
jQuery(function($){

$('.filebox')
	.bind('before-open.mw', function(){
		var $this, $list, $parentObj;
		var anchor;

		$this = $(this);
		anchor = $this.attr('href');

		$list = $(anchor).find('.filebox_list');

		function on_complete(data){
			$list.html(data.html);

			$list.find('.select')
				.bind('click', function(event){
					var selectedImages = $('input.select_checkbox:checked');
					if(selectedImages.length == 0) {
						var selectedImgSrc = $(this).closest('tr').find('img.filebox_item').attr('src');
						if(!selectedImgSrc){
							alert("None selected!");
						}else{
							$this.trigger('filebox.selected', [selectedImgSrc]);
							$this.trigger('close.mw');
						}
					}else {
						$this.trigger('filebox.selected', [selectedImages]);
						$this.trigger('close.mw');
					}
					return false;
				});

			$list.find('.x_pagination')
				.find('a')
				.filter(function(){
					if ($(this).data('toggle')) return false;
					if ($(this).parent().hasClass('x_disabled')) return false;
					if ($(this).parent().hasClass('x_active')) return false;
					return true;
				})
				.bind('click', function(){
					var page = $(this).attr('page');

					$.exec_json('module.getFileBoxListHtml', {'page': page}, on_complete);
					return false;
				});

			$('#goToFileBox')
				.find('button')
				.bind('click', function(){
					var page = $(this).prev('input').val();

					$.exec_json('module.getFileBoxListHtml', {'page': page}, on_complete);
					return false;
				});

			$list.closest('.x_modal-body').scrollTop(0);
		}

		$.exec_json('module.getFileBoxListHtml', {'page': '1'}, on_complete);
	});
// Details toggle in admin table
	var simpleBtn = $('.x .dsTg .__simple');
	var detailBtn = $('.x .dsTg .__detail');
	var tdTitle = $('.x .dsTg td.title');
	tdTitle.each(function(){
		var $t = $(this)
		if($t.find('p.update').length==0){
			$t.addClass('tg').find('>*:not(:first-child)').hide();
		} else {
			$t.addClass('up');
		}
	});
	var details = $('.x .dsTg td.tg>*:not(:first-child)');
	simpleBtn.click(function(){
		details.slideUp(200);
		detailBtn.removeClass('x_active');
		simpleBtn.addClass('x_active');
	});
	detailBtn.click(function(){
		details.slideDown(200);
		detailBtn.addClass('x_active');
		simpleBtn.removeClass('x_active');
	});

// Multilingual
	var $multilinguals_v15 = $('.vLang[type="hidden"]');
	var $multilinguals = $('.lang_code');

	if($multilinguals_v15.length || $multilinguals.length){
		function on_complete(data){
			var $content = $('.x #content');
			$content.append(data.html);

			var tmpCount = 0;

			$multilinguals_v15.each(function(){
				var $this = $(this);

				$this.removeClass('vLang').addClass('lang_code');
				$this.parent().find('.editUserLang').remove();
			});

			$multilinguals = $('.lang_code');

			$multilinguals.each(function(){
				var $this = $(this);
				var id = $this.attr('id');
				if(!id){
					id = '__lang_code_' + tmpCount;
					tmpCount++;
					$this.attr('id', id);
				}

				if(this.tagName == 'TEXTAREA' || $this.next('textarea.vLang').length){
					var $displayInput = $('<textarea id="lang_' + id + '" class="displayInput" style="width:179px">').data('lang-id', id);
				}else{
					var $displayInput = $('<input type="text" id="lang_' + id + '" class="displayInput" style="width:179px">').data('lang-id', id);
				}
				var $remover = $('<button type="button" class="x_add-on remover" title="' + xe.cmd_remove_multilingual_text + '"><i class="x_icon-remove"></i>' + xe.cmd_remove_multilingual_text + '</button>').data('lang-target', id);
				var $setter = $('<a href="#g11n" class="x_add-on modalAnchor" title="' + xe.cmd_set_multilingual_text + '"><i class="x_icon-globe"></i>' + xe.cmd_set_multilingual_text + '</a>').data('lang-target', id);

				$this.parent().addClass('g11n').addClass('x_input-append');
				$this.after($displayInput, $remover, $setter);
				$this.parent().find('.vLang').remove();
				$this.hide();
				$setter.attr('href', '#g11n').xeModalWindow();

				// text change
				var $hiddenInput = $this;
				$displayInput.bind('change, keyup', function(){
					$this = $(this);

					if($this.closest('.g11n').hasClass('active')) return;

					$hiddenInput.val($this.val());
				});

				// load value
				$displayInput.val($hiddenInput.val());
				var pattern = /^\$user_lang->/;
				if(pattern.test($displayInput.val())){
					function on_complete2(data){
						if(!data || !data.langs) return;

						$displayInput.closest('.g11n').addClass('active');
						$displayInput.val(data.langs[xe.current_lang]).attr('disabled', 'disabled').width(135);
					}

					$.exec_json('module.getModuleAdminLangCode', {'name': $displayInput.val().replace('$user_lang->', '')}, on_complete2);
				}
			});

			var $g11n_set = $('.x .g11n'); // set container
			var $g11n_anchor = $g11n_set.children('.modalAnchor');
			var $g11n_get = $('.x #g11n'); // get container
			var $g11n_create = $g11n_get.find('#lang_create'); // create section
			var $g11n_search = $g11n_get.find('#lang_search'); // search section
			var is_create_changed = false;

			// tabbable
			$g11n_get.find('.x_tabbable').xeTabbable();

			// check create change
			$g11n_create.find('.editMode textarea').change(function(){
				is_create_changed = true;
			});

			// use lang code
			function g11n_use_lang_code($this, code, value){
				var $displayInput = $('#lang_' + $this.closest('.x_modal').data('lang-target'));

				$displayInput
					.width(135)
					.attr('disabled', 'disabled')
					.val(value)
					.parent('.g11n').addClass('active');
				$displayInput.siblings('#' + $displayInput.data('lang-id')).val('$user_lang->' + code);

				is_create_changed = false;
				$displayInput.siblings('[href="#g11n"]').trigger('close.mw');

			}

			// get list
			function g11n_get_list(page, search_keyword, name){
				if(!page) page = 1;
				if(!search_keyword) search_keyword = '';
				if(!name) name = '';

				$.exec_json('module.getModuleAdminLangListHtml', {'page': page, 'search_keyword': search_keyword, 'name': name}, on_complete);

				function on_complete(data){
					if(!data || !data.html) return;

					$('#lang_search').html(data.html);

					// page
					$('#lang_search .x_pagination a').click(function(){
						var page = $(this).data('page');
						var search_keyword = $(this).data('search_keyword');

						if(!page) return;

						g11n_get_list(page, search_keyword);
						return false;
					});

					$('#lang_search .x_pagination').submit(function(){
						var page = $(this).find('[name="page"]').val();
						var search_keyword = $(this).data('search_keyword');

						if(!page) return false;

						g11n_get_list(page, search_keyword);
						return false;
					});

					// search
					$('#lang_search .search').submit(function(){
						var search_keyword = $(this).find('[name="search_keyword"]').val();

						g11n_get_list(1, search_keyword);
						return false;
					});

					$('#lang_search #search_cancel').click(function(){
						g11n_get_list(1, '');
					});

					// text click
					$('#lang_search').find('.set').append('<i class="x_icon-chevron-down"></i>').click(function(){
						var $this = $(this);
						var lang_code = $this.data('lang_code');

						g11n_search_save_confirm();

						// Fieldset close/open display
						var up = 'x_icon-chevron-up';
						var down = 'x_icon-chevron-down';
						if($this.next('fieldset').is(':visible')){
							$this.children('i').removeClass(up).addClass(down);
						}else{
							$this.parent('.item').siblings('.item').find('a > i').removeClass(up).addClass(down).end().children('fieldset').hide();
							$this.children('i').removeClass(down).addClass(up);
						}

						if(typeof $this.data('is_loaded') != 'undefined') return;

						$.exec_json('module.getModuleAdminLangCode', {'name': lang_code}, on_complete);

						function on_complete(data){
							var $textareas = $this.next('fieldset').find('textarea');

							$textareas.each(function(){
								var $this = $(this);
								var value = data.langs[$this.data('lang')];
								var pattern = /^\$user_lang->/;

								if(pattern.test(value)){
									$this.val('').data('value', '');
								}else{
									$this.val(value).data('value', value);
								}
							});

							$this.data('is_loaded', true);
						}


					});

					if(name){
						$('#lang_search').find('[href^="#lang-"]').trigger('click');
					}

					// Modify click
					$('#lang_search').find('.modify').click(function(){
						$(this).closest('fieldset').addClass('editMode').find('textarea').removeAttr('disabled');
					});

					// Cancel Click
					$('#lang_search').find('.cancel').click(function(){
						$(this).closest('fieldset').removeClass('editMode').find('textarea').attr('disabled', 'disabled').each(function(){
							var $this = $(this);

							$this.val($this.data('value'));
						});

						return false;
					});

					// Save Click
					$('#lang_search').find('.item').submit(function(){
						var $this = $(this);
						var $textareas = $this.find('.editMode').children('textarea');
						var $anchor = $this.find('[href^="#lang-"]');
						var params = {};
						var current_lang_value = null;

						// create lang list
						$textareas.each(function(){
							var $this = $(this);
							params[$this.attr('class')] = $this.val();
							$this.data('tmp_value', $this.val());
							if(xe.current_lang == $this.attr('class')){
								current_lang_value = $this.val();
							}
						});

						params.lang_name = $anchor.data('lang_code');

						// submit
						$.exec_json('module.procModuleAdminInsertLang', params, on_complete);

						function on_complete(data){
							if(!data || data.error || !data.name) return;

							$textareas.each(function(){
								var $this = $(this);
								$this.data('value', $this.data('tmp_value'));
							});
							$anchor.children('span').html(current_lang_value);

							$('#lang_search').find('.cancel').trigger('click');
							$this.find('.useit').trigger('click');
						}

						return false;
					});

					// Useit click
					$('#lang_search').find('.useit').click(function(){
						var $this = $(this);
						var $anchor = $this.closest('.item').find('[href^="#lang-"]');
						var name = $anchor.data('lang_code');
						var value = $anchor.children('span').text();

						g11n_use_lang_code($this, name, value);
					});
				}
			}

			// #lang_create confirm
			function g11n_create_save_confirm(){
				if($g11n_create.is(':visible') && is_create_changed){
					if(confirm(xe.msg_confirm_save_and_use_multilingual)){
						$g11n_create.find('.save-useit').trigger('click');
					}
				}

				return true;
			}

			// #lang_search confirm
			function g11n_search_save_confirm(){
				if($g11n_search.is(':visible') && $g11n_search.find('.editMode').length){
					var $search_item = $g11n_search.find('form.item');
					if(confirm(xe.msg_confirm_save_and_use_multilingual)){
						$search_item.find('.save').trigger('click').end().find('textarea').attr('disabled', 'disabled');
					}else{
						$search_item.find('.cancel').trigger('click');
					}
				}

				return true;
			}

			// #g11n Reset to default
			function g11n_reset_default(){
				$g11n_search.find('.item > fieldset').hide().prev('a').children('i').removeClass('x_icon-chevrom-up').addClass('x_icon-chevron-down');
				$g11n_get.find('[href="#lang_create"]').trigger('click');
				$g11n_create.find('.editMode').children('textarea').val('');
				is_create_changed = false;

				return true;
			}

			// Save-Useit click
			$g11n_create.submit(function(){
				var $this = $(this);
				var params = {};
				var current_lang_value = null;

				// create lang list
				$this.find('.editMode').children('textarea').each(function(){
					var $this = $(this);
					params[$this.attr('class')] = $this.val();
					if(xe.current_lang == $this.attr('class')){
						current_lang_value = $this.val();
					}
				});

				if(!current_lang_value){
					alert(xe.msg_empty_multilingual);
					return false;
				}

				// submit
				$.exec_json('module.procModuleAdminInsertLang', params, on_complete);

				function on_complete(data){
					if(!data || data.error || !data.name) return;

					g11n_use_lang_code($this, data.name, current_lang_value);
				}

				return false;

			});

			// Remover click
			$g11n_set.children('.remover').click(function(){
				var $this = $(this);
				var $g11n_set_input = $('#lang_' + $this.data('lang-target'));
				$g11n_set_input.val('').removeAttr('disabled')
					.width(179)
					.parent('.g11n').removeClass('active');
				$this.siblings('.lang_code').val('');
			});

			// Close click
			$g11n_anchor.bind('before-close.mw', function(){
				if(!g11n_create_save_confirm()) return false;
				if(!g11n_search_save_confirm()) return false;
				if(!g11n_reset_default()) return false;
			});

			// .modalAnchor click
			$g11n_anchor.bind('open.mw',function(){
				var $this = $(this);
				var $displayInput = $this.siblings('.displayInput');

				if($this.closest('.g11n').hasClass('active')){
					g11n_get_list(1, '', $displayInput.prev('.lang_code').val().replace('$user_lang->', ''));
					$($this.attr('href')).find('[href="#lang_search"]').trigger('click');
				}else{
					g11n_get_list();
				}

				$($this.attr('href')).data('lang-target', $this.data('lang-target'));
			});
		}
		$.exec_json('module.getModuleAdminMultilingualHtml', {}, on_complete);
	}

});

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
