jQuery(function($){
	var menuList;
	var parentSrl;
	var editForm = $('#editForm');
	var listForm = $('#listForm');

	$('button._add').click(function(){
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
		menuList = obj.menuList;
		if(menuList)
		{
 			var menuNameList = $('#menuNameList');
			for(var x in menuList)
			{
				var menu = menuList[x];
				menuNameList.append('<option value="'+x+'">'+menu.title+'</option>');
			}
		}
	}

	$('button._parent_delete').click(function() {
		var menu_item_srl = $(this).parent().prevAll('._parent_key').val();
		listForm.find('input[name=menu_item_srl]').val(menu_item_srl);
		listForm.submit();
	});

	$('button._child_delete').click(function() {
		var menu_item_srl = $(this).parents('li').prevAll('._child_key').val();
		listForm.find('input[name=menu_item_srl]').val(menu_item_srl);
		listForm.submit();
	});
});

