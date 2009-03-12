function toggleCategory(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(obj.nodeName != 'BUTTON') return;

    var node_srl = obj.className.replace(/^category_/,'');
    if(!node_srl) return;

    var li_obj = xGetElementById("category_parent_"+node_srl);
    if(!li_obj) return;
    var className = li_obj.className;

    if(/nav_tree_off/.test(className)) {
        xInnerHtml(obj,'-');
        li_obj.className = className.replace(/nav_tree_off/,'nav_tree_on');
    } else {
        xInnerHtml(obj,'+');
        li_obj.className = className.replace(/nav_tree_on/,'nav_tree_off');
    }
}

xAddEventListener(document, 'click', toggleCategory);
