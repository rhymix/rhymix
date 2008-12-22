(function($){
$(function(){
    if(addon_keyword_link.length > 0){
        if(!addon_keyword_link_cssquery) addon_keyword_link_cssquery= 'div.contentBody > div, div.replyContent > div';
        $(addon_keyword_link_cssquery).each(function(){
            var content = $(this).html();
            for(var i=0,c=addon_keyword_link.length;i<c;i++){
                var re = new RegExp(addon_keyword_link[i].keyword + '(?! *<\/a)',addon_keyword_link_reg_type);
                content = content.replace(re,'<a href="'+addon_keyword_link[i].url+'">' + addon_keyword_link[i].keyword + '</a>');
            }
            $(this).html(content);
        });
    }
});
})(jQuery);