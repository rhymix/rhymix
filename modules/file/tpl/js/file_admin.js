function insertSelectedModule(id, module_srl, mid, browser_title) {
    location.href = current_url.setQuery('module_srl',module_srl);
}

function addCart(file_srl) {
    var params = new Array();
    var response_tags = ['error','message'];
    params['file_srl'] = file_srl;

    exec_xml('file','procFileAdminAddCart',params, completeAddCart, response_tags);
}

function completeAddCart(ret_obj, response_tags)
{
}

function getFileList() {
    var params = new Array();
    var response_tags = ['error','message', 'file_list'];

    exec_xml('file','procFileGetList',params, completeGetFileList, response_tags);
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
		var file_list = ret_obj['file_list']['item'];
		if(!jQuery.isArray(file_list)) file_list = [file_list];
		for(var x in file_list)
		{
			var objFile = file_list[x];
			console.log(objFile);
			htmlListBuffer += '<tr>' +
							'<td class="text">'+objFile.source_filename+'</td>' +
							'<td>'+objFile.human_file_size+'</td>' +
							'<td></td>' +
							'<td>'+objFile.validName+'</td>' +
						'</tr>' +
						'<input type="hidden" name="cart[]" value="'+objFile.file_srl+'" />';
		}
		jQuery('#selectedFileCount').html(file_list.length);
	}
	jQuery('#fileManageListTable>tbody').html(htmlListBuffer);
}
