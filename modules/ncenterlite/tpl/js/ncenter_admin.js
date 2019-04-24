function doDisplaySkinColorset(sel, colorset)
{
	var skin = sel.options[sel.selectedIndex].value;

	var params = new Array();
	params["skin"] = skin;
	params["colorset"] = colorset;

	var response_tags = ['error', 'message', 'colorset_list'];

	exec_xml('ncenterlite', 'getColorsetList', params, completeGetSkinColorset, response_tags, params);
}

function completeGetSkinColorset(ret_obj, response_tags, params, fo_obj)
{
	var sel = get_by_id('fo_ncenterlite').colorset;
	var length = sel.options.length;
	var selected_colorset = params['colorset'];
	for(var i=0;i<length;i++) sel.remove(0);

	var colorset_list = ret_obj['colorset_list'].split("\n");
	var selected_index = 0;
	for(var i=0;i<colorset_list.length;i++) {
		var tmp = colorset_list[i].split('|@|');
		if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
		var opt = new Option(tmp[1], tmp[0], false, false);
		sel.options.add(opt);
	}

	sel.selectedIndex = selected_index;
}
