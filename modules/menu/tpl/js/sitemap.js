/* NHN (developers@xpressengine.com) */
jQuery(function($){

$('form.siteMap')
	.delegate('li:not(.placeholder)', 'dropped.st', function() {
		var $this = $(this), $pkey, $mkey, is_child;

		$pkey = $this.find('>input._parent_key');
		is_child = !!$this.parent('ul').parent('li').length;

		if(is_child) {
			$pkey.val($this.parent('ul').parent('li').find('>input._item_key').val());
		} else {
			$pkey.val('0');
		}
	})

	var editForm = $('#editForm');
	var menuSrl = null;
	var menuForm = null;
	var menuUrl = null;

	$('a._edit').click(function(){
		var itemKey = $(this).parent().prevAll('._item_key').val();
		menuSrl = $(this).parents().prevAll('input[name=menu_srl]').val();
		menuForm = $('#menu_'+menuSrl);
		var menuItemSrl = null;

		menuItemSrl = itemKey;

		var params = new Array();
		var response_tags = new Array('menu_item');
		params['menu_item_srl'] = menuItemSrl;

		exec_xml("menu","getMenuAdminItemInfo", params, completeGetActList, response_tags);
	});

	function completeGetActList(obj)
	{
		var menuItem = obj.menu_item;
		menuUrl = menuItem.url;
		editForm.find('.h2').html('Edit Menu');
		editForm.find('input[name=menu_srl]').val(menuItem.menu_srl);
		editForm.find('input[name=menu_item_srl]').val(menuItem.menu_item_srl);
		editForm.find('input[name=parent_srl]').val(menuItem.parent_srl);
		editForm.find('input[name=menu_name_key]').val(menuItem.name_key);
		editForm.find('input[name=menu_name]').val(menuItem.name);

		var moduleType = menuItem.moduleType;
		if(menuItem.pageType) moduleType = menuItem.pageType;
		var inputCType = editForm.find('input[name=cType]');

		if(moduleType == 'url')
		{
			inputCType[2].checked = true;
			editForm.find('input[name=menu_url]').val(menuItem.url);
		}
		else
		{
			inputCType[1].checked = true;
			editForm.find('select[name=module_type]').val(moduleType);
			editForm.find('select[name=select_menu_url]').val(menuItem.url);
		}
		typeCheck();
		getModuleList();

		var openWindow = menuItem.open_window;
		var openWindowForm = editForm.find('input=[name=menu_open_window]');
		if(openWindow == 'Y') openWindowForm[1].checked = true;
		else openWindowForm[0].checked = true;

		// button image
		if(menuItem.normal_btn) $('#normal_btn_preview').html('<img src="'+menuItem.normal_btn+'" /><input type="checkbox" name="isNormalDelete" value="Y"> Delete');
		if(menuItem.hover_btn) $('#hover_btn_preview').html('<img src="'+menuItem.hover_btn+'" /><input type="checkbox" name="isHoverDelete" value="Y"> Delete');
		if(menuItem.active_btn) $('#active_btn_preview').html('<img src="'+menuItem.active_btn+'" /><input type="checkbox" name="isActiveDelete" value="Y"> Delete');

		var htmlBuffer = '';
		for(x in menuItem.groupList.item)
		{
			var groupObj = menuItem.groupList.item[x];

			htmlBuffer += '<input type="checkbox" name="group_srls[]" id="group_srls_'+groupObj.group_srl+'" value="'+groupObj.group_srl+'"';
			if(groupObj.isChecked) htmlBuffer += ' checked="checked" ';
			htmlBuffer += '/> <label for="group_srls_'+groupObj.group_srl+'">'+groupObj.title+'</label>'
		}
		$('#groupList').html(htmlBuffer);
	}

	$('a._delete').click(function() {
		if(confirmDelete())
		{
			menuSrl = $(this).parents().prevAll('input[name=menu_srl]').val();
			menuForm = $('#menu_'+menuSrl);

			var menu_item_srl = $(this).parent().prevAll('._item_key').val();
			menuForm.find('input[name=menu_item_srl]').val(menu_item_srl);
			menuForm.find('input[name=act]').val('procMenuAdminDeleteItem');
			menuForm.submit();
		}
	});

	var kindModuleLayer = $('#kindModule');
	var createModuleLayer = $('#createModule');
	var selectModuleLayer = $('#selectModule');
	var insertUrlLayer = $('#insertUrl');
	kindModuleLayer.hide();
	createModuleLayer.hide();
	selectModuleLayer.hide();
	insertUrlLayer.hide();

	$('a._add').click(function()
	{
		editForm.find('.h2').html('Add Menu');
		editForm.find('input[name=menu_srl]').val($(this).parents().prevAll('input[name=menu_srl]').val());
		editForm.find('input[name=menu_item_srl]').val('');
		editForm.find('input[name=parent_srl]').val(0);
		editForm.find('input[name=menu_name]').val('');
		editForm.find('input[name=cType]').attr('checked', false);
		editForm.find('input=[name=create_menu_url]').val('');
		editForm.find('input=[name=select_menu_url]').val('');
		editForm.find('input=[name=menu_url]').val('');
		editForm.find('input=[name=menu_open_window]')[0].checked = true;
		editForm.find('input=[name=group_srls\\[\\]]').attr('checked', false);
	});

	$('input._typeCheck').click(typeCheck);

	function typeCheck()
	{
		var inputTypeCheck = $('input._typeCheck');
		var checkedValue = null;
		for(var i=0; i<3; i++)
		{
			if(inputTypeCheck[i].checked)
			{
				checkedValue = inputTypeCheck[i].value;
				break;
			}
		}

		if(checkedValue == 'CREATE')
		{
			kindModuleLayer.show();
			createModuleLayer.show()
			selectModuleLayer.hide()
			insertUrlLayer.hide()
		}
		else if(checkedValue == 'SELECT')
		{
			kindModuleLayer.show();
			createModuleLayer.hide()
			selectModuleLayer.show()
			insertUrlLayer.hide()
		}
		// type is URL
		else
		{
			kindModuleLayer.hide();
			createModuleLayer.hide()
			selectModuleLayer.hide()
			insertUrlLayer.show()
		}
	}

	$('#kModule').change(getModuleList).change();
	function getModuleList()
	{
		var params = new Array();
		var response_tags = ['error', 'message', 'module_list'];

		exec_xml('module','procModuleAdminGetList',params, completeGetModuleList, response_tags);
	}

	function completeGetModuleList(ret_obj)
	{
		var module = $('#kModule').val();
		if(module == 'WIDGET' || module == 'ARTICLE' || module == 'OUTSIDE') module = 'page';

		var midList = ret_obj.module_list[module].list;
		var htmlBuffer = "";
		for(x in midList)
		{
			var midObject = midList[x];
			htmlBuffer += '<option value="'+midObject.mid+'"';
			if(menuUrl == midObject.mid) htmlBuffer += ' selected ';
			htmlBuffer += '>'+midObject.browser_title+'</option>';
		}
		selectModuleLayer.find('select').html(htmlBuffer);
	}

	$('a.tgMap').click(function() {
		var $this = $(this);

		$($this.attr('href')).slideToggle('fast');
		$this.closest('.siteMap').toggleClass('fold');

		return false;
	});
});

function confirmDelete()
{
	if(confirm(xe.lang.confirm_delete)) return true;
	return false;
}
