function moveHistory(fo_obj) {
    if(!fo_obj.entry.value) return;
    var url = current_url.setQuery('entry',fo_obj.entry.value);
    if(typeof(xeVid)!='undefined') url = url.setQuery('vid',xeVid);
    location.href=url;
}

function viewHistory(history_srl) {
    var zone = jQuery('#historyContent'+history_srl);
    if(zone.css('display')=='block') zone.css('display','none');
    else zone.css('display','block');
}
