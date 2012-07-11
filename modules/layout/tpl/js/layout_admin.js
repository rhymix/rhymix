
/* 레이아웃 신규 생성시 완료 후 요청하는 함수 */
function completeInsertLayout(ret_obj) {
      var layout_srl = ret_obj['layout_srl'];
      var url = current_url.setQuery('act','dispLayoutAdminModify').setQuery('layout_srl',layout_srl);
      location.href = url;
}

/* 레이아웃 삭제 */
function doDeleteLayout(layout_srl) {
    var fo_obj = jQuery('#fo_layout').get(0);
    fo_obj.layout_srl.value = layout_srl;
    procFilter(fo_obj, delete_layout);
}

/* 메뉴 관리로 이동 */
function doMenuManagement(menu_id) {
    var menu_srl = jQuery('#fo_layout select[name='+menu_id+']').val();
    var url = '';
    // 선택된 메뉴가 없으면
    if(menu_srl == 0){
        url = current_url.setQuery('module','admin').setQuery('act','dispMenuAdminInsert');
    }else{
        url = current_url.setQuery('act','dispMenuAdminManagement').setQuery('menu_srl',menu_srl);
    }

    winopen(url);
}


function checkFile(f){
    var filename = jQuery('[name=user_layout_image]',f).val();
    if(/\.(gif|jpg|jpeg|gif|png|swf|flv)$/i.test(filename)){
        return true;
    }else{
        alert('only image and flash file');
        return false;
    }
}

function deleteFile(layout_srl,filename){
    var params ={
            "layout_srl":layout_srl
            ,"filename":filename
            };

    jQuery.exec_json('layout.procLayoutAdminUserImageDelete', params, function(data){
        document.location.reload();
    });
}

function addLayoutCopyInputbox()
{
	var html = '<tr>';
	html += '<td><input type="text" name="title[]" size="50" /></td>';
	html += '<td><span class="btn"><input type="button" value="'+addLang+'" onclick="addLayoutCopyInputbox()" /></span></td>';
	html += '</tr>';

	var it  = jQuery('#inputTable');
	it.append(html);
}

(function($){

/* preview layout */
function doPreviewLayoutCode(layout_srl) {
	var fo  = $('#fo_layout');
	var act = fo.find('input[name=act]:first').val();
	fo.attr('target', '_LayoutPreview').find('input[name=act]').val('dispLayoutAdminPreview');
	fo.submit();
	//.removeAttr('target').find('input[name=act]').val(act);
}
window.doPreviewLayoutCode = doPreviewLayoutCode;

/* restore layout code */
function doResetLayoutCode(layout_srl) {
    procFilter($('#fo_layout')[0], reset_layout_code);
}
window.doResetLayoutCode = doResetLayoutCode;

var validator = xe.getApp('validator')[0];
validator.cast('ADD_CALLBACK', ['update_layout_code', function(form) {
	if (form.act.value != 'procLayoutAdminCodeUpdate') return true;

	var params={},data=$(form).serializeArray();
	$.each(data, function(i,field){ params[field.name] = field.value });

	exec_xml('layout', 'procLayoutAdminCodeUpdate', params, filterAlertMessage, ['error','message'], params, form);
	return false;
}]);

})(jQuery);
