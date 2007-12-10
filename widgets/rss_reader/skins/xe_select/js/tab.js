function tab_menu_select(tab_id, tabs, t, tab_obj){
    for(var i = 1; i <= tabs; i++) {
        eval("document.getElementById('tab"+tab_id+i+"')").style.display="none";
        if ( t == i ) {
            eval("document.getElementById('tab"+tab_id+i+"')").style.display="block";
        }
    }
    tab_obj.className="current"
}

/* 높이 조절 */
function resize_rss_tabcontent(tab_id, ms_height) {
    var obj = xGetElementById(tab_id)

    if(xHeight(obj) > ms_height) obj.style.height = ms_height + 'px'
    obj.style.overflow = "auto"
}
