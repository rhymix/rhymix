/*
 * jQuery Toolbar Plug-in
 *
 * @author Kim Taegon(gonom9@nhncorp.com)
 */

(function($){

$.fn.toolbar = function(settings) {	
	settings = $.extend({
		items : '.buttons button',
		fade  : false,
		click   : function(){},
		hover   : function(){},
		show    : function(){},
		hide    : function(){}
	}, settings);
	
	// get elements
	var items = this.find(settings.items);
	var menus = items.find('+ ul');
	var menuitems = menus.find('> li');
	
	// hover action - submenu
	menus.mouseout(
		function(event) {
			var el = $(event.relatedTarget).parents().add(event.relatedTarget);

			if ( el.index(this) < 0 && el.index($(this).prev()) < 0 ) hideMenu($(this), settings);
		}
	).click(
		function(event) {
			var item = $(event.target).parent();

			var data = createData(item);
			
			if ( !item.is('li') ) return;
			
			// radio button
			selectItem(data);
			
			// callback
			settings.click(data);
		}
	);
	
	menuitems.mouseover(
		function(event){
			var item = $(this);
			
			item.parent().find('> li').removeClass('tb-menu-item-hover');
			item.addClass('tb-menu-item-hover');
			
			// callback
			settings.hover(createData(item));
		}
	);
	
	// hover action - button
	items.hover(
		function(event) {
			showMenu($(this).find('+ ul'), settings);
		},
		function(event) {
			var menu = $(this).find('+ ul');
			var el   = $(event.relatedTarget).parents().add(event.relatedTarget);
			
			// hide menu
			if ( el.index(menu) < 0 && el.index(this) < 0 ) hideMenu(menu, settings);
		}
	);
	
	return this;
}

function hideMenu(menu, settings) {
	menu[settings.fade?'fadeOut':'hide'](settings.fade)
		.removeClass('tb-menu-active')
		.find('> li').removeClass('tb-menu-item-hover');
	
	menu.prev().removeClass('tb-btn-active');
	
	// hidemenu event
	settings.hide(menu);
}

function showMenu(menu, settings) {	
	menu[settings.fade?'fadeIn':'show'](settings.fade)
		.addClass('tb-menu-active')
		.css({position:'absolute',left:0,top:0});
	
	menu.prev().addClass('tb-btn-active');

	// positioning
	var btn = menu.prev();
	var btn_pos = btn.offset();
	var mnu_pos = menu.offset();
	
	menu.css({
		left : btn_pos.left - mnu_pos.left,
		top  : btn_pos.top  - mnu_pos.top + btn.height()
	})
	
	// showmenu event
	settings.show(menu);
}

function selectItem(data) {
	var item = data.element;
	
	switch(data.type){
		case 'radio':
			item.parent().find('> li').removeClass('tb-menu-item-selected');
			item.addClass('tb-menu-item-selected')
			data.checked = true;
			break;
		case 'checkbox':
			data.checked = !data.checked;
			if (data.checked) {
				item.addClass('tb-menu-item-selected');
			} else {
				item.removeClass('tb-menu-item-selected');
			}
			break;
		default:
			break;
	};
}

function createData(item) {
	return {
		element : item,
		type    : item.attr('tb:type'),
		arg     : item.attr('tb:arg'),
		checked : item.hasClass('tb-menu-item-selected')
	};
}

})(jQuery);