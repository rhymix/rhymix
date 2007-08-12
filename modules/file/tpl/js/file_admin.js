function doCheckAll() {
    var fo_obj = xGetElementById('fo_list');
    for(var i=0;i<fo_obj.length;i++) {
        if(fo_obj[i].name == 'cart') fo_obj[i].checked = true;
    }
}
