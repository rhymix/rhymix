jQuery(function($){
    var lang = $('.link .lang');
    var lang_lst = $('.lang .lang_lst')
    lang.click(function(){
        if(lang.hasClass('on')){
            lang.removeClass('on').addClass('off');
            lang_lst.hide();
        }else if(lang.hasClass('off')){
            lang.removeClass('off').addClass('on');
            lang_lst.show();
        }
    })
   var lo_foot = $('.lo_foot');
   var lo_head = $('.lo_head');
   var ct = $('.ct');
    function footPosition(){
        if((lo_head.outerHeight() + ct.outerHeight() + 71) > $(window).height()){
            lo_foot.removeClass('fixed').addClass('static');
        }else if((lo_head.outerHeight() + ct.outerHeight() + 71) < $(window).height()){
            lo_foot.removeClass('static').addClass('fixed');
        }
    }
     footPosition();
    ct.resize(footPosition);
    
}); // end of ready