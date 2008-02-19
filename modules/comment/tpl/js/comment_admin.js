function doCheckAll(bToggle) {
    var fo_obj = xGetElementById('fo_list');
	if(typeof(bToggle) == "undefined") bToggle = false;
    for(var i=0;i<fo_obj.length;i++) {
        if(fo_obj[i].name == 'cart'){
			if( !fo_obj[i].checked || !bToggle) fo_obj[i].checked = true; else fo_obj[i].checked = false;
		}
    }
}

function doCancelDeclare() {
    var fo_obj = xGetElementById('fo_list');
    var comment_srl = new Array();
    if(fo_obj.cart.length) {
        for(var i=0;i<fo_obj.cart.length;i++) {
            if(fo_obj.cart[i].checked) comment_srl[comment_srl.length] = fo_obj.cart[i].value;
        }
    } else {
        if(fo_obj.cart.checked) comment_srl[comment_srl.length] = fo_obj.cart.value;
    }
    if(comment_srl.length<1) return;

    var params = new Array();
    params['comment_srl'] = comment_srl.join(',');

    exec_xml('comment','procCommentAdminCancelDeclare', params, completeCancelDeclare);
}

function completeCancelDeclare(ret_obj) {
    location.reload();
}
