jQuery(function($){
	$('.tgContent ul').bind('click', function(){
		$('#sitefind_addBtn').css('display','');
	});
	if ($("#object_cache_type").size()) {
		$("#object_cache_type").on("change", function() {
			if ($(this).val().match(/memcache|redis/)) {
				$("#object_cache_additional_config").show();
				if (!$("#object_cache_host").val()) {
					$("#object_cache_host").val('127.0.0.1');
				}
				if (!$("#object_cache_port").val()) {
					$("#object_cache_port").val($(this).val().match(/memcache/) ? '11211' : '6379');
				}
				if ($(this).val().match(/memcache/)) {
					if ($("#object_cache_port").val() == '6379') {
						$("#object_cache_port").val('11211');
					}
					$("#object_cache_dbnum").parents("label").hide();
				}
				if ($(this).val().match(/redis/)) {
					if ($("#object_cache_port").val() == '11211') {
						$("#object_cache_port").val('6379');
					}
					$("#object_cache_dbnum").parents("label").show();
				}
			} else {
				$("#object_cache_additional_config").hide();
			}
		}).triggerHandler("change");
	}
});

function setStartModule(){
	var target_module = jQuery('.moduleIdList option:selected').text();
	var index_module_srl = jQuery('.moduleIdList').val();
	jQuery('#_target_module').val(target_module);
	jQuery('#index_module_srl').val(index_module_srl);
	jQuery('.moduleList,.moduleIdList, .site_keyword_search, #sitefind_addBtn').css('display','none');
}

function viewSiteSearch(){
	jQuery(".site_keyword_search").css("display","");
}

var icon = null;
function deleteIcon(iconname){
	var params = [];
	params.iconname = iconname;
	exec_xml('admin', 'procAdminRemoveIcons', params, iconDeleteMessage, ['error', 'message'], params);
	icon = iconname;
}
function iconDeleteMessage(ret_obj){
	alert(ret_obj.message);

	if (ret_obj.error == '0')
	{
		if (icon == 'favicon.ico'){
			jQuery('.faviconPreview img').attr('src', 'modules/admin/tpl/img/faviconSample.png');
		}else if (icon == 'mobicon.png'){
			jQuery('.mobiconPreview img').attr('src', 'modules/admin/tpl/img/mobiconSample.png');
		}
	}
}
function doRecompileCacheFile() {
	if (!confirm(xe.lang.confirm_run)) return;
	var params = [];
	exec_xml("admin","procAdminRecompileCacheFile", params, completeCacheMessage);
}
function completeCacheMessage(ret_obj) {
	alert(ret_obj.message);
}

function doResetAdminMenu() {
	if (!confirm(xe.lang.confirm_reset_admin_menu)) return;
	var params = [];
	params.menu_srl = admin_menu_srl;
	exec_xml("admin","procAdminMenuReset", params, completeResetAdminMenu);
}
function completeResetAdminMenu(ret_obj) {
	document.location.reload();
}

