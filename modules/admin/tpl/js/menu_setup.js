jQuery(function($){
	var menuList;
	var parentSrl;
	var editForm = $('#editForm');
	var listForm = $('#listForm');

	$('a._add').click(function(){
		parentSrl = $(this).parent().prevAll('._parent_key').val();
		editForm.find('input[name=parent_srl]').val(parentSrl);
		if(!menuList)
		{
			var params = new Array();
			var response_tags = new Array('menuList');
			exec_xml("menu","procMenuAdminAllActList", params, completeGetActList, response_tags);
		}
	});

	function completeGetActList(obj)
	{
		moduleList = obj.menuList;
		if(moduleList)
		{
 			var menuNameList = $('#menuNameList');
			for(var x in moduleList)
			{
				var menuList = moduleList[x];
				for(var y in menuList)
				{
					var menu = menuList[y];
					menuNameList.append('<option value="'+x+':'+y+'">'+menu.title+'</option>');
				}
			}
		}
	}

	$('a._parent_delete').click(function() {
		var menu_item_srl = $(this).parent().prevAll('._parent_key').val();
		listForm.find('input[name=menu_item_srl]').val(menu_item_srl);
		listForm.submit();
	});

	$('a._child_delete').click(function() {
		var menu_item_srl = $(this).parents('li').prevAll('._child_key').val();
		listForm.find('input[name=menu_item_srl]').val(menu_item_srl);
		listForm.submit();
	});
});

