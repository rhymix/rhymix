function doCheckAll(bToggle) {
    var fo_obj = jQuery('#fo_list')[0], el = null;
	if(typeof(bToggle) == "undefined") bToggle = false;
    for(var i=0; i<fo_obj.elements.length; i++) {
		el = fo_obj.elements[i];
        if(el.name == 'cart'){
			if(!el.checked || !bToggle) el.checked = true;
			else el.checked = false;
		}
    }
}

function insertSelectedModule(id, module_srl, mid, browser_title) {
    location.href = current_url.setQuery('module_srl',module_srl);
}
