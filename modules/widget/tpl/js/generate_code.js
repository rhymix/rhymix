function doDisplaySkinColorset()
{
	var skin = jQuery('select[name=skin]').val();
	if(!skin) {
		doHideSkinColorset();
		return;
	}

	var params = new Array();
	params["selected_widget"] = jQuery('input[name=selected_widget]').val();
	params["skin"] = skin;
	params["colorset"] = jQuery('select[name=colorset]').val();

	var response_tags = new Array("error","message","colorset_list");

	exec_xml("widget", "procWidgetGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

function completeGetSkinColorset(ret_obj)
{
	var sel = jQuery("select[name=colorset]").get(0);
	var length = sel.options.length;
	var selected_colorset = jQuery('select[name=colorset]').val();
	for(var i=0;i<length;i++) sel.remove(0);

	if(!ret_obj["colorset_list"]) return;

	var colorset_list = ret_obj["colorset_list"].split("\n");
	var selected_index = 0;
	for(var i=0;i<colorset_list.length;i++) {
		var tmp = colorset_list[i].split("|@|");
		if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
		var opt = new Option(tmp[1], tmp[0], false, false);
		sel.options.add(opt);
	}

	sel.selectedIndex = selected_index;

	doShowSkinColorset();
}

function doHideSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').hide();
}

function doShowSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').show();
}

jQuery(document).ready(function($){
	$('select[name=skin]').next('span').children().bind('click', function(e){
		doDisplaySkinColorset();
	});
	doHideSkinColorset();
});