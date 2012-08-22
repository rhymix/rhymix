/* NHN (developers@xpressengine.com) */
jQuery(function($){
	// Constants
	var ESC_KEY = 27;

	// Overlapping label
	$('.form li').find('>input:text:not(".notmulti"),>input:password,>textarea')
 		.filter('input[value!=""],textarea:not(:empty)').prev('label').css('visibility','hidden').end().end()
		.prev('label')
			.addClass('overlap')
			.css({top:'15px',left:'5px'})
			.next()
				.focus(function(){
					var $label = $(this).prev().stop().animate({opacity:0, left:'25px'},'fast',function(){ $label.css('visibility','hidden') });
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
				.filter(function(){ return this.name == name })
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
						return !$this.prop('disabled') && (($this.attr('name') == name) || ($this.data('name') == name));
					})
						.prop('checked', $this.prop('checked'))
					.end()
				.end()
				.trigger('update.checkbox', [name, this.checked]);
		});

	// pagination
	$.fn.xePagination = function(){
		this
			.not('.xe-pagination')
			.addClass('xe-pagination')
			.find('span.tgContent').css('whiteSpace', 'nowrap').end()
			.find('a.tgAnchor')
				.each(function(idx){
					var $this = $(this);
					$this.after( $($this.attr('href')) );
				})
			.end();

		return this;
	};
	$('.pagination').xePagination();

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
	$(window).resize(function(){
		if($(document).width() < 1300){
			$('.dashboard>.section>br').remove();
			$('.dashboard>.section>.portlet:odd').after('<br style="clear:both" />');
		} else {
			$('.dashboard>.section>br').remove();
			$('.dashboard>.section>.portlet:eq(2),.dashboard>.section>.portlet:eq(5)').after('<br style="clear:both" />');
		}
	});
	$(window).resize();

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

	$.fn.xeMask = function(){
		this
			.each(function(){
				var $this = $(this), text = $this.text();
				var reg_mail = /^([\w\-\.]+?)@(([\w-]+\.)+[a-z]{2,})$/ig;

				if(reg_mail.test(text)) {
					$this
						.html(text.replace(/(@.+)$/, '<span class="ellipsis">...</span><span class="cover">$1</span>'))
						.find('>.ellipsis')
							.css({position:'absolute',zIndex:1})
							.hover(
								function(){ $(this).next('.cover').mouseover() },
								function(){ $(this).next('.cover').mouseout() }
							)
						.end()
						.find('>.cover')
							.css({zIndex:2,opacity:0})
							.hover(
								function(){ $(this).css('opacity',1).prev('span').css('visibility','hidden') },
								function(){ $(this).css('opacity',0).prev('span').css('visibility','visible') }
							)
						.end();
				}
			})
	};
	$('.masked').xeMask();
});

// Global Navigation Bar
jQuery(function($){

	$.fn.xeMenu = function(){
		this
			.attr('role', 'navigation') // WAI-ARIA role
			.find('>.nav-gnb>li')
				.attr('role', 'menuitem') // WAI-ARIA role
				.filter(':has(>ul)')
					.attr('aria-haspopup', 'true') // WAI-ARIA
				.end()
			.end()
			.find('>.nav-gnb')
			.mouseover(function(){
				$(this)
					.parent('.gnb').addClass('active').end()
					.find('>li>ul').css('height','auto').end()
			})
			.mouseleave(function(){
				$(this)
					.parent('.gnb').removeClass('active').end()
					.find('>li>ul').css('height','0').end()
			})
			.focusout(function(){
				var $this = $(this);
				setTimeout(function(){
					if(!$this.find(':focus').length) {
						$this.mouseleave();
					}
				}, 1);
			})
			.delegate('a', {
				focus : function(){
					$(this).mouseover();
				}
			});
	};
	
	$('div.gnb').xeMenu();

	$('.gnb>.mnv').change(function(){
		window.location.href=$(this).find('option:selected').val();
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
				.find('>.bg').height($(document).height()).end()
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
};
$('a.modalAnchor').xeModalWindow();
$('div.modal').addClass('x').hide();

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
	var $moduleInstanceList = $moduleInstanceListDiv.find('UL');

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
			$('<a href="#" >').html(
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
						$('<a href="#" >').html(
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
					
					$li = $('<li />').appendTo($moduleInstanceList);
					$('<a href="#" >').html(
						'<div>'+list[x].browser_title+'</div>'
					).data('module_srl', list[x].module_srl).appendTo($li);
					
				}

				$moduleInstanceListDiv.show();
				setListSize($moduleInstanceList, MAX_LIST_HEIGHT);

				$moduleTypeList.find('li').removeClass('on');
				$this.parent('li').addClass('on');

				oEvent.preventDefault();
			})
		.end()
		.find('.moduleInstanceListUL')
			.delegate('a','click',function(oEvent){
				$this = $(this);

				t.sSelectedModuleInstanceName = $this.text();
				t.sSelectedModuleSrl = $this.data('module_srl');
				
				$t.trigger('moduleSelect', [t.sSelectedModuleType, t.sSelectedModuleInstanceName, t.sSelectedModuleSrl]);
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

// Language selector
jQuery(function($){

var w_timer = null, r_timer = null, r_idx = 0, f_timer = null, skip_textchange=false, keep_showing=false, $suggest;
var ESC=27, UP=38, DOWN=40, ENTER=13;

$('.multiLangEdit')
	.delegate('input.vLang:text,textarea.vLang', {
		textchange : function(){
			var $this = $(this), val = $.trim($this.val()), $ul, $container;

			if(r_timer) {
				clearTimeout(r_timer);
				r_timer = null;
			}

			$container = $this.data('mle-container');
			$ul        = $suggest.find('>ul');

			if(!val || skip_textchange) {
				skip_textchange = false;
				$ul.parent().hide();
				$this.prev('input.vLang').val('');
				return;
			}

			// remove lagnauge key
			$this.data('mle-langkey').val('');
			
			// copy current string to hidden input
			$this.prev('input.vLang').val(val);

			function request() {
				$this.addClass('loading');

				if($ul.parent().is(':visible')) $ul.parent().hide();

				show_waiting_message = false;
				$.exec_json(
					'module.getLangListByLangcodeForAutoComplete',
					{search_keyword:val},
					(function(i){ return function(data){ on_complete(data,i) } })(r_idx++)
				);
				show_waiting_message = true;
			};

			function on_complete(data, idx){
				var results = data.results, $btn, i, c;

				if(data.error || !results || (r_idx != idx+1)) return;

				$this.removeClass('loading');

				$ul.empty();
				for(i=0,c=results.length; i < c; i++) {
					$btn = $('<button type="button" class="_btnLang" />').data('langkey', results[i].name).text(results[i].value);
					$('<li />').append($btn).appendTo($ul);
				}
				$suggest.trigger('show');
			};

			r_timer = setTimeout(request, 100);
		},
		keydown : function(event){
			var $this, $ul, $active, $after, key = event.which;

			$this = $(this);
			$ul   = $suggest.find('>ul');

			if(!$suggest.is(':visible') || $.inArray(key, [UP,DOWN,ENTER,ESC]) < 0) return true;

			if(key == ESC) {
				$suggest.trigger('hide');
				return false;
			}

			$active = $ul.find('button.active');

			if(key == ENTER) {
				$active.click();
				return false;
			}

			if(!$active.length) {
				$ul.find('li>button:first').addClass('active');
				return false;
			}

			// Move the cursor
			if(key == UP) {
				$after = $active.parent().prev('li').find('>button');
				if(!$after.length) $after = $ul.find('>li:last>button');
			} else if(key == DOWN) {
				$after = $active.parent().next('li').find('>button');
				if(!$after.length) $after = $ul.find('>li:first>button');
			}

			$active.removeClass('active');
			$after.addClass('active');

			return false;
		},
		focus : function(){
			var $this = $(this), oldValue = $.trim($this.val()), $mle = $this.closest('.multiLangEdit');

			$this.after($suggest);
			if(!$this.data('mle-container')) $this.data('mle-container', $mle);
			if(!$this.data('mle-langkey')) $this.data('mle-langkey', $mle.find('input.vLang:first'));

			(function(){
				var value = $.trim($this.val());

				if(value != oldValue) {
					oldValue = value;
					$this.trigger('textchange');
				}
				w_timer = setTimeout(arguments.callee, 50);
			})();
		},
		blur : function(){
			clearTimeout(w_timer);
			w_timer = null;

			$(this).closest('.multiLangEdit').focusout();
		},
		focusout : function(){
			var $this = $(this);

			clearTimeout(f_timer);
			f_timer = setTimeout(function(){
				if(keep_showing) {
					keep_showing = false;
					return;
				}
				if(!$this.find(':focus').is('.vLang,button._btnLang')) $suggest.trigger('hide');
			}, 10);
		}
	})
	.delegate('a.tgAnchor.editUserLang', {
		'before-open.tc' : function(){
			var $this, $layer, $mle, $vlang, key, text, api, params;

			$this  = $(this);
			$layer = $($this.attr('href')).insertBefore($this);
			$mle   = $this.closest('.multiLangEdit');
			$vlang = $mle.find('input.vLang,textarea.vLang');

			key  = $vlang.eq(0).val();
			text = $vlang.eq(1).val();

			// initialize the layer
			initLayer($layer);

			// reset
			$layer.trigger('multilang-reset').removeClass('showChild').find('.langList').empty().end();
			$layer.find('.langInput li.'+xe.current_lang).find('>input:text,>textarea').val(text).prev('label').css('visibility','hidden');

			// hide suggestion layer
			$suggest.trigger('hide');

			if(/^\$user_lang->(.+)$/.test(key)) {
				api = 'module.getModuleAdminLangListByName';
				params = {lang_name:RegExp.$1};
			} else {
				api = 'module.getModuleAdminLangListByValue';
				params = {value:text};
			}

			function on_complete(data) {
				var list = data.lang_list, obj, i, c, name, $langlist;

				if(data.error || !list) return;

				list = extractList(list);

				// set data
				$layer.data('multilang-list', list);

				// make language list
				$langlist = $layer.find('.langList');
				$.each(list, function(key){
					var $li = $('<li />').appendTo($langlist);

					var anchor_id = $layer.data('layer_index');

					var lang_text = this[xe.current_lang];
					if(!lang_text) {
						for(lang_code in this) {
							lang_text = this[lang_code];
							break;
						}
					}

					$('<a href="#langInput_'+anchor_id+'" class="langItem" />')
						.text(lang_text)
						.data('multilang-name', key)
						.appendTo($li);
				});

				if(count(list) > 1) $layer.addClass('showChild');

				$layer.find('.langList>li>a:first').click();

			};

			show_waiting_message = false;
			$.exec_json(api, params, on_complete);
			show_waiting_message = true;
		}
	})
	.delegate('button._btnLang', {
		click : function(){
			var $this = $(this);

			skip_textchange = true;

			$suggest.trigger('hide');

			$this.closest('.multiLangEdit')
				.find('input.vLang,textarea.vLang')
					.eq(0).val($this.data('langkey')).end()
					.eq(1).val($this.text()).end();

			return false;
		},
		mousedown : function(){ keep_showing = true },
		focus : function(){
			$(this).mouseover();
		},
		mouseover : function(){
			$(this).closest('ul').find('button.active').removeClass('active');
		}
	});

$suggest = $('<div class="suggestion"><ul></ul></div>')
	.bind('show', function(){
		$(this).show();
	})
	.bind('hide', function(){
		$(this).hide();
	});

var layer_index = 0;
function initLayer($layer) {
	var $submit, $input, value='', current_status = 0, mode, cmd_add, cmd_edit, status_texts=[];
	var USE = 0, UPDATE_AND_USE = 1, MODE_SAVE = 0, MODE_UPDATE = 1;

	if($layer.data('init-multilang-editor')) return;

	$layer
		.data('init-multilang-editor', true)
		.bind('multilang-reset', function(){
			$layer
				.data('multilang-current-name', '')
				.find('.langInput li').find('>input:text,>textarea').val('').prev('label').css('visibility','visible');

			mode = MODE_SAVE;
			setTitleText();
		})
		.find('h2 a')
			.click(function(){
				mode = !mode;
				setTitleText();

				return false;
			})
		.end()
		.delegate('a.langItem', 'click', function(){
			var $this = $(this), $controls, list, name, i, c;

			list = $layer.data('multilang-list');
			name = $this.data('multilang-name');

			if(!list || !list[name]) return;
			list = list[name];

			$controls = $layer.find('.langInput');

			$layer
				.trigger('multilang-reset') // reset
				.find('.langList li.active').removeClass('active').end()
				.data('multilang-current-name', name);

			$this.parent('li').addClass('active');

			for(var code in list) {
				if(!list.hasOwnProperty(code)) continue;
				$controls.find('li.'+code).find('>input:text,>textarea').data('multilang-value',list[code]).val(list[code]).prev('label').css('visibility','hidden');
			}

			value = get_value();

			current_status = 0;
			$submit.val(status_texts[current_status]);

			mode = MODE_UPDATE;
			setTitleText();

			return false;
		})
		.data('layer_index', layer_index)
		.find('.langInput').attr('id', 'langInput_' + layer_index++);

	cmd_edit = $layer.find('h2 strong').text();
	cmd_add  = $layer.find('h2 a').text();

	$input = $layer.find('input:text,textarea')
		.change(function(){
			var status = (get_value() == value)?USE:UPDATE_AND_USE;

			if(status != current_status) {
				$submit.val(status_texts[current_status=status]);
			}
		});

	function get_value() {
		var text = [];
		$input.each(function(){ text.push(this.value); });
		return text.join('\n');
	};

	function setTitleText() {
		if(!$layer.data('multilang-current-name')) {
			$layer.find('h2').find('strong').text(cmd_add).end().find('a').hide();
		} else {
			$layer.find('h2')
				.find('strong').text(mode==MODE_SAVE?cmd_add:cmd_edit).end()
				.find('a').text(mode==MODE_SAVE?cmd_edit:cmd_add).show().end();
		}
	};

	// process the submit button
	$submit = $layer.find('input[type=submit]')
		.click(function(){
			var name = $layer.data('multilang-current-name');

			function use_lang() {
				$layer.hide().closest('.multiLangEdit').find('.vLang')
					.eq(0).val('$user_lang->'+name).end()
					.eq(1).val($layer.find('.langInput li.'+xe.current_lang).find('>input:text,>textarea').val()).end();
			};

			function save_lang() {
				var params = {};
 				if(name && mode == MODE_UPDATE) params.lang_name = name;

 				$input.each(function(k,v){ var $this = $(this); params[$this.parent('li').attr('class')] = $this.val() });

 				$.exec_json('module.procModuleAdminInsertLang', params, on_complete);
			};

			function on_complete(data) {
				if(!data || data.error || !data.name) return;

				name = data.name;
				use_lang();
			};

			(get_value() == value) ? use_lang() : save_lang();


			return false;
		});
	status_texts = $submit.val().split('|');
	$submit.val(status_texts[USE]);
};

function extractList(list) {
	var i, c, obj={}, item;

	for(i=0,c=list.length; i < c; i++) {
		item = list[i];

		if(!obj[item.name]) obj[item.name] = {};

		obj[item.name][item.lang_code] = item.value;
	}

	return obj;
};

function count(obj) {
	var size = 0;
	for(var x in obj) {
		if(obj.hasOwnProperty(x)) size++;
	}
	return size;
};

});

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

			$list.find('.lined .select')
				.bind('click', function(event){
					var selectedImages = $('input.select_checkbox:checked');
					if(selectedImages.length == 0) {
						var selectedImgSrc = $(this).parent().find('img.filebox_item').attr('src');
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

			$list.find('.pagination')
				.find('a')
				.filter(function(){
					if ($(this).hasClass('tgAnchor')) return false;
					return true;
				})
				.bind('click', function(){
					var page = $(this).attr('page');

					$.exec_json('module.getFileBoxListHtml', {'page': page}, on_complete);
					$(window).scrollTop($(anchor).find('.modalClose').offset().top);
					return false;
				});

			$('#FileBoxGoTo')
				.find('button')
				.bind('click', function(){
					var page = $(this).prev('input').val();

					$.exec_json('module.getFileBoxListHtml', {'page': page}, on_complete);
					$(window).scrollTop($(anchor).find('.modalClose').offset().top);
					return false;
				});
		}

		$.exec_json('module.getFileBoxListHtml', {'page': '1'}, on_complete);
	});

});

// insert fog layer
function showWaitingFogLayer() {
	var $bg = jQuery('<span class="bg"></span>');
	$bg.height(jQuery(window).height());
	jQuery('.wfsr')
		.wrap('<div class="wfsr_fog" />')
		.before($bg);
}

function hideWaitingFogLayer() {
	jQuery('.wfsr').prev('span').remove();
	jQuery('.wfsr').unwrap();
}


// install module
function doInstallModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminInstall',params, completeInstallModule);
	showWaitingFogLayer();
}

// upgrade module
function doUpdateModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminUpdate',params, completeInstallModule);
	showWaitingFogLayer();
}

function completeInstallModule(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

jQuery(function($){
	$('body').ajaxComplete(function(){ hideWaitingFogLayer() });
// admin single column layout
	if($('.x>.body .lnb').length == 0){ // When it have no lnb
		$('.x>.body').addClass('single'); // Add class single
	}
// Details toggle in admin table
	var viewBtn = $('.x .dsTg span.side>button.text');
	var tdTitle = $('.x .dsTg td.title');
	tdTitle.each(function(){
		var $t = $(this)
		if($t.find('p.update').length==0){
			$t.addClass('tg').find('>p:not(:first-child)').hide();
		} else {
			$t.addClass('up');
		}
	});
	var details = $('.x .dsTg td.tg>p:not(:first-child)');
	viewBtn.click(function(){
		viewBtn.toggleClass('details');
		details.slideToggle(200);
	});
// Toggle Content
	var $h2h3 = $('.x .content .h2, .x .content .h3').not('.portlet .h2, .modal .h2');
	$h2h3.each(function(){
		var $sTog = $('<button type="button" class="sTog" title="Open/Close"><i class="icon-chevron-up"></i></button>');
		$(this).append($sTog);
		$sTog.click(function(){
			var $t = $(this);
			var $sTogObj = $t.parent().nextUntil('.h2, .h3').not('.langEdit, .tgContent, .modal');
			if($t.parent().next().is(':hidden')){
				$t.find('i').attr('class','icon-chevron-up');
				$sTogObj.slideDown(200);
			} else {
				$t.find('i').attr('class','icon-chevron-down');
				$sTogObj.slideUp(200);
			}
		});
	});
});
