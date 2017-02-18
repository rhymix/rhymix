function content_widget_plus(obj,list_per_page){
	var page = 1;
	if(obj.is('table')) {
		var list = jQuery('>tbody>tr',obj);
	}else if(obj.is('ul')) {
		var list = jQuery('>li',obj);
	}

	var total_page = parseInt((list.size()-1) / list_per_page,10)+1;
	list.each(function(i){
		if(jQuery(this).css('display') !='none'){
			page = parseInt((i-1)/list_per_page,10) + 1;
		}
	});
	if(total_page === page+1)
	{
		if(obj.is('ul.simple_content_list'))
		{
			obj.next('div.simple_content_nav').css('display','none');
		}
	}
	if(total_page <= page) return;
	if(obj.is('ul.simple_content_list')) {
		jQuery('button.soo_cont_more>span.page_info',obj.next('div.simple_content_nav')).html(page+1 + '/' + total_page);
	}
	list.each(function(i){
		if(((page+1) * list_per_page) > i){
			jQuery(this).show();
		}else{
			jQuery(this).hide();
		}
	});
}

function content_widget_tab_show(tab,list,i){
	tab.parents('ul.widgetTab').children('li.active').removeClass('active');
	tab.parent('li').addClass('active');
	jQuery('>dd',list).each(function(j){
			if(j==i) jQuery(this).addClass('open');
			else jQuery(this).removeClass('open');
			});
}
