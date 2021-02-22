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
					$("#object_cache_redis_config").hide();
				}
				if ($(this).val().match(/redis/)) {
					if ($("#object_cache_port").val() == '11211') {
						$("#object_cache_port").val('6379');
					}
					$("#object_cache_redis_config").show();
				}
			} else {
				$("#object_cache_additional_config").hide();
				$("#object_cache_redis_config").hide();
			}
		}).triggerHandler("change");
	}
	
	// Disable rewrite level 2 if test AJAX request fails.
	if ($('#use_rewrite_2').size() && !$('#use_rewrite_2').is(':checked')) {
		var testval = 1000 + Math.floor(Math.random() * 9000);
		$.ajax({
			url: request_uri + 'common/rewrite/test/' + testval,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data.result != testval * 42) {
					$('#use_rewrite_2').prop('disabled', true);
				}
			},
			error: function() {
				$('#use_rewrite_2').prop('disabled', true);
			}
		});
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
	exec_json('admin.procAdminRecompileCacheFile', {}, function(data) {
		alert(data.message);
	});
}

function doResetAdminMenu() {
	if (!confirm(xe.lang.confirm_reset_admin_menu)) return;
	exec_json('admin.procAdminMenuReset', {}, function() {
		window.location.reload();
	});
}
