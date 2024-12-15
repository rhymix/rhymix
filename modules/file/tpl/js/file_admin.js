function insertSelectedModule(id, module_srl, mid, browser_title) {
    location.href = current_url.setQuery('module_srl',module_srl);
}

function getFileList() {
	var fileListTable = jQuery('#fileListTable');
	var cartList = [];
	fileListTable.find(':checkbox[name=cart]').each(function(){
		if(this.checked) cartList.push(this.value);
	});

    var params = new Array();
	params["file_srls"] = cartList.join(",");

    exec_json('file.procFileGetList', params, completeGetFileList);
}

function completeGetFileList(ret_obj, response_tags)
{
	var htmlListBuffer = '';
	if(ret_obj['file_list'] == null)
	{
		htmlListBuffer = '<tr>' +
							'<td colspan="4" style="text-align:center;">'+ret_obj['message']+'</td>' +
						'</tr>';
	}
	else
	{
		var file_list = ret_obj['file_list']['item'] ? ret_obj['file_list']['item'] : ret_obj['file_list'];
		if(!jQuery.isArray(file_list)) file_list = [file_list];
		for(var x in file_list)
		{
			var objFile = file_list[x];
			htmlListBuffer += '<tr>' +
							'<td class="text">'+objFile.source_filename+'</td>' +
							'<td class="nowr">'+objFile.human_file_size+'</td>' +
							'<td class="nowr">'+objFile.validName+'</td>' +
						'</tr>' +
						'<input type="hidden" name="cart[]" value="'+objFile.file_srl+'" />';
		}
		jQuery('#selectedFileCount').html(file_list.length + ' (' + ret_obj['file_size_total_human'] + ')');
	}
	jQuery('#fileManageListTable>tbody').html(htmlListBuffer);
}

function checkSearch(form)
{
	if(form.search_target.value == '')
	{
		alert(xe.lang.msg_empty_search_target);
		return false;
	}
	/*
	if(form.search_keyword.value == '')
	{
		alert(xe.lang.msg_empty_search_keyword);
		return false;
	}
	*/
}

(function() {
	$(function() {
		$('.preset_size').on('click', function() {
			const preset_size = parseInt($(this).text(), 10);
			const width = parseInt($('input[name=original_width]').val(), 10);
			const height = parseInt($('input[name=original_height]').val(), 10);
			let new_width = 0;
			let new_height = 0;
			if (width > height) {
				new_width = preset_size;
				new_height = Math.round(preset_size * (height / width));
			} else {
				new_width = Math.round(preset_size * (width / height));
				new_height = preset_size;
			}
			$('input[name=new_width]').val(new_width);
			$('input[name=new_height]').val(new_height);
		});
	});
})(jQuery);
