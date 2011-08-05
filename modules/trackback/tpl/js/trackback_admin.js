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

function addCart(trackback_srl) {
    var params = new Array();
    var response_tags = ['error','message'];
    params['trackback_srl'] = trackback_srl;

    exec_xml('trackback','procTrackbackAdminAddCart',params, completeAddCart, response_tags);
}

function completeAddCart(ret_obj, response_tags)
{
}

function getTrackbackList()
{
    var params = new Array();
    var response_tags = ['error','message', 'trackback_list'];

    exec_xml('trackback','procTrackbackGetList',params, completeGetTrackbackList, response_tags);
}

function completeGetTrackbackList(ret_obj, response_tags)
{
	var htmlListBuffer = '';
	var statusNameList = {"N":"Public", "Y":"Secret"};
	console.log(ret_obj);

	if(ret_obj['trackback_list'] == null)
	{
		htmlListBuffer = '<tr>' +
							'<td colspan="2" style="text-align:center;">'+ret_obj['message']+'</td>' +
						'</tr>';
	}
	else
	{
		var trackback_list = ret_obj['trackback_list']['item'];
		if(!jQuery.isArray(trackback_list)) trackback_list = [trackback_list];
		for(var x in trackback_list)
		{
			var objTrackback = trackback_list[x];
			htmlListBuffer += '<tr>' +
							'<tr>' +
								'<td class="text"> <strong>'+ objTrackback.title +'</strong>' +
									'<p>'+ objTrackback.excerpt +'</p>' +
								'</td>' +
								'<td>'+ objTrackback.blog_name +'</td>' +
							'</tr>' +
							'<input type="hidden" name="cart[]" value="'+objTrackback.trackback_srl+'" />';
		}
		jQuery('#selectedTrackbackCount').html(trackback_list.length);
	}
	jQuery('#trackbackManageListTable>tbody').html(htmlListBuffer);
}
