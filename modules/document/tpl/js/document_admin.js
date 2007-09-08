function doToggleAll() {
    var fo_obj = xGetElementById('fo_list');
    for(var i=0;i<fo_obj.length;i++) {
        if(fo_obj[i].name == 'cart'){
			if( fo_obj[i].checked == true ){
				fo_obj[i].checked = false;
			} else {
				fo_obj[i].checked = true;
			}
		}
    }
}

function doCheckAll() {
    var fo_obj = xGetElementById('fo_list');
    for(var i=0;i<fo_obj.length;i++) {
        if(fo_obj[i].name == 'cart') fo_obj[i].checked = true;
    }
}
