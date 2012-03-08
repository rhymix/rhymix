/* NHN (developers@xpressengine.com) */
jQuery(function($){

// get add/edit menu title
var $lang = $('#editMenu h2:first span');
xe.lang.add_menu  = $lang.eq(0).text();
xe.lang.edit_menu = $lang.eq(1).text();
$lang.empty();

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
	});

	var editForm = $('#editForm');
	var menuSrl = null;
	var menuForm = null;
	var menuUrl = null;

	$('a._edit').click(function(){
		resetEditForm();
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
		var successReturnUrl = editForm.find('input[name=success_return_url]').val() + menuItem.menu_srl;
		editForm.find('.h2').text(xe.lang.edit_menu);
		editForm.find('input[name=menu_srl]').val(menuItem.menu_srl);
		editForm.find('input[name=menu_item_srl]').val(menuItem.menu_item_srl);
		editForm.find('input[name=parent_srl]').val(menuItem.parent_srl);
		editForm.find('input[name=menu_name_key]').val(menuItem.name_key);
		editForm.find('input[name=menu_name]').val(menuItem.name);
		editForm.find('input[name=success_return_url]').val(successReturnUrl);

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

		// reset label
		var checked_labels = [];
		editForm.find('label').css('font-weight', '');
		editForm.find('input:checked').each(function(){
			editForm.find('label[for='+this.id+']').css('font-weight', 'bold');
		});
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

	function resetEditForm()
	{
		kindModuleLayer.hide();
		createModuleLayer.hide()
		selectModuleLayer.hide()
		insertUrlLayer.hide()

		editForm.find('input[name=menu_item_srl]').val('');
		editForm.find('input[name=parent_srl]').val(0);
		editForm.find('input[name=menu_name]').val('');
		editForm.find('input[name=cType]').attr('checked', false);
		editForm.find('input[name=create_menu_url]').val('');
		editForm.find('input[name=select_menu_url]').val('');
		editForm.find('input[name=menu_url]').val('');
		editForm.find('input[name=menu_open_window]')[0].checked = true;
		editForm.find('input[name=group_srls\\[\\]]').attr('checked', false);
		editForm.find('label').css('font-weight', '');
	}

	$('a._add').click(function()
	{
		var $this = $(this);

		resetEditForm();
		
		editForm.find('.h2').text(xe.lang.add_menu);
		editForm.find('input[name=menu_srl]').val($this.closest('form').find('input[name=menu_srl]:first').val());
		editForm.find('input[name=parent_srl]').val($this.parent().prevAll('input._item_key').val());
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

		var htmlBuffer = "";
		if(ret_obj.module_list[module] != undefined)
		{
			var midList = ret_obj.module_list[module].list;
			for(x in midList)
			{
				var midObject = midList[x];
				htmlBuffer += '<option value="'+midObject.mid+'"';
				if(menuUrl == midObject.mid) htmlBuffer += ' selected ';
				htmlBuffer += '>'+midObject.mid+'('+midObject.browser_title+')</option>';
			}
		}
		else htmlBuffer = '';

		selectModuleLayer.find('select').html(htmlBuffer);
	}
	
	function tgMapBtn(){
		$('.x .siteMap>ul:visible').next('.btnArea').slideDown(50);
		$('.x .siteMap>ul:hidden').next('.btnArea').slideUp(50);
	}
	tgMapBtn();
	$('a.tgMap').click(function() {
		var $this = $(this);
		var curToggleStatus = getCookie('sitemap_toggle_'+$this.attr('href'));
		var toggleStatus = curToggleStatus == 1 ? '0' : 1;

		$($this.attr('href')).slideToggle('fast');
		$this.closest('.siteMap').toggleClass('fold');
		setCookie('sitemap_toggle_'+$this.attr('href'), toggleStatus);
		setTimeout(function(){ tgMapBtn(); }, 250);
		
		return false;
	});
});

function confirmDelete()
{
	if(confirm(xe.lang.confirm_delete)) return true;
	return false;
}
