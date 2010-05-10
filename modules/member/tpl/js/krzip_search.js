/* 한국 우편 번호 관련 */
function doHideKrZipList(column_name) {
	var $j = jQuery;
	$j('#zone_address_list_'+column_name).hide();
	$j('#zone_address_search_'+column_name).show();
	$j('#zone_address_1_'+column_name).hide();

	var form = $j('#fo_insert_member');
	form.find('select[name=_tmp_address_list_'+column_name+']').focus();
	form.find('input[name='+column_name+']').eq(0).val('');
}

function doSelectKrZip(column_name) {
	var $j = jQuery;
	$j('#zone_address_list_'+column_name).hide();
	$j('#zone_address_search_'+column_name).hide();
	$j('#zone_address_1_'+column_name).show();

	var form = $j('#fo_insert_member');
	var val  = form.find('select[name=_tmp_address_list_'+column_name+']').val();
	var addr = form.find('input[name='+column_name+']');

	addr.eq(0).val(val);
	addr.eq(1).focus();
}

function doSearchKrZip(column_name) {
	var field = jQuery('#fo_insert_member input[name=_tmp_address_search_'+column_name+']');
	var _addr = field.val();
	if(!_addr) return;

	var params = {
		addr : _addr,
		column_name : column_name
	};

	var response_tags = ['error','message','address_list'];

	exec_xml('krzip', 'getKrzipCodeList', params, completeSearchKrZip, response_tags, params);
}

function completeSearchKrZip(ret_obj, response_tags, callback_args) {
	if(!ret_obj['address_list']) {
		alert(alert_msg['address']);
		return;
	}

	var address_list = ret_obj['address_list'].split('\n');
	var column_name  = callback_args['column_name'];

	var $j = jQuery;
	
	address_list = $j.map(address_list, function(addr){ return '<option value="'+addr+'">'+addr+'</option>'; });

	$j('#zone_address_list_'+column_name).show();
	$j('#zone_address_search_'+column_name).hide();
	$j('#zone_address_1_'+column_name).hide();
	$j('#fo_insert_member select[name=_tmp_address_list_'+column_name+']').html(address_list.join('')).get(0).selectedIndex = 0;
}