jQuery(function($){
    // Global Navigation Bar
    var gMenu = $('#header>div.gnb');
    var gItem = gMenu.find('>ul>li');
    var ggItem = gMenu.find('>ul>li>ul>li');
    var lastEvent = null;
    gItem.find('>ul').hide();

	if(gItem.last().attr('class') != "on m1")
		gItem.last().attr('class','last');

	if(gItem.first().attr('class') != "home_on"){
		gItem.first().attr('class','home');
	}

	ggItem.mouseover(function(){
		old_class = $(this).parent('ul').parent('li').attr('class');
		if(old_class == "home first" || old_class =="home" || old_class == "home_on first" || old_class == "home_on")
			$(this).parent('ul').parent('li').attr('class','home_on');
		else
			$(this).parent('ul').parent('li').attr('class','on m1');

		$(this).mouseleave(function(){
			if(old_class == "home first" || old_class =="home" || old_class == "home_on first" || old_class == "home_on" )
				$(this).parent('ul').parent('li').attr('class',old_class);
			else if(old_class !="on m1" && old_class !="on m1 first")
				$(this).parent('ul').parent('li').attr('class','');
		});
	});

	gItem.filter(':first').addClass('first');
    function gMenuToggle(){
        var t = $(this);
        if (t.next('ul').is(':hidden') || t.next('ul').length == 0) {
            gItem.find('>ul').hide();
            gItem.find('a').removeClass('hover');
            t.next('ul').show();
            t.addClass('hover');            
        };

		if(t.next('ul').width()){
			if(t.next('ul').width()< t.parent().width()){
				t.next('ul').width(t.parent().width());
				t.next('ul').find('li').width(t.parent().width());
				t.next('ul').find('li').css('text-align','left');
			}else{
				t.next('ul').find('li').width(t.next('ul').width());
				t.next('ul').find('li').css('text-align','left');
			}
		}
		return false;
    };
    function gMenuOut(){
        gItem.find('ul').hide();
        gItem.find('a').removeClass('hover');
    };
    gItem.find('>a').mouseover(gMenuToggle).focus(gMenuToggle);
    gItem.mouseleave(gMenuOut);

	var maxHeight = 0;
	$('div.sitemap>ul>li').each(function(i){
		if($(this).height() > maxHeight)
			maxHeight = $(this).height();
	});
	$('div.sitemap>ul>li').height(maxHeight);
});