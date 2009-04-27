function moveHistory(fo_obj) {
    if(!fo_obj.entry.value) return;
    var url = current_url.setQuery('entry',fo_obj.entry.value);
    if(typeof(xeVid)!='undefined') url = url.setQuery('vid',xeVid);
    location.href=url;
}
