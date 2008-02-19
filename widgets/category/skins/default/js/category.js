function toggleCategory(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var srl = null;
    if(obj.nodeName != 'DIV') return;

    if(obj.id && /^category_parent_/.test(obj.id)) {
        srl = obj.id.replace(/^category_parent_/,'');
    } else if(obj.id && /^category_/.test(obj.id)) {
        srl = obj.id.replace(/^category_parent_/,'');
    } else if(obj.className && /item/.test(obj.className)) {
        var pObj = obj.parentNode;
        srl = pObj.id.replace(/^category_parent_/,'');
    }

    if(!srl) return;
    var obj = xGetElementById("category_"+srl);
    if(!obj) return;

    var selObj = xGetElementById("category_parent_"+srl);
    if(!selObj) return;

    if(!obj.style.display || obj.style.display == 'block') {
        obj.style.display = 'none';
        selObj.className = selObj.className.replace('minus','plus');
    } else {
        obj.style.display = 'block';
        selObj.className = selObj.className.replace('plus','minus');
    }
}

xAddEventListener(document, 'click', toggleCategory);
