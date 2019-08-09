/* 금지아이디 관련 작업들 */
function doUpdateDeniedID(user_id, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    exec_xml(
		'member',
		'procMemberAdminUpdateDeniedID',
		{user_id:user_id, mode:mode},
		function(){
			if (mode == 'delete'){
				jQuery('#denied_'+user_id).remove();
				jQuery('._deniedIDCount').html(jQuery('#deniedList li').length);
			}
		},
		['error','message','tpl']
	);
}

/* prohibited nick name functions */
function doUpdateDeniedNickName(nick_name, mode, message)
{
    if(typeof(message)!='undefined' && !confirm(message)) return;

    exec_xml(
		'member',
		'procMemberAdminUpdateDeniedNickName',
		{nick_name:nick_name, mode:mode},
		function(){
			if (mode == 'delete'){
				jQuery('#denied_'+nick_name).remove();
				jQuery('._deniedNickNameCount').html(jQuery('#deniedNickNameList li').length);
			}
		},
		['error','message','tpl']
	);
}

/* managed E-mail Address functions */
function doUpdateManagedEmailHost(email_host, mode, message)
{
	if(typeof(message)!='undefined' && !confirm(message)) return;

	exec_xml(
		'member',
		'procMemberAdminUpdateManagedEmailHosts',
		{email_hosts:email_host, mode:mode, email_hosts_count:jQuery('#managedEmailHost li').length},
		function(){
			if (mode == 'delete'){
				jQuery('#managed_'+email_host.replace(/\./g,'\_\_')).remove();
				jQuery('._managededEmailHostCount').html(jQuery('#managedEmailHost li').length);
			}
		},
		['error','message','tpl']
	);
}

jQuery(function($){
	// hide form if enable_join is setted "No"
	var suForm = $('table.__join_form'); // 회원가입 양식

	function changeTable($i)
	{
			if($i.is(':checked')){
				$i.parent('td').next('td').next('td')
					.find('>._subItem').show().end()
					.find(':radio, [type="number"]')
						.removeAttr('disabled')
						.end()
					.find(':radio.item_optional').prop('checked', true)
						.end()
					.prev('td')
					.find(':input[value=Y]').removeAttr('disabled').prop('checked', true);
			} else {
				$i.parent('td').next('td').next('td')
					.find('>._subItem').hide().end()
					.find(':radio.item_required, :radio.item_optional, [type="number"]').attr('disabled','disabled').prop('checked', false)
						.next('label').css('fontWeight','normal').end()
						.end()
					.prev('td')
					.find(':input[value=Y]').attr('disabled', 'disabled').prop('checked', false);
			}

	}

	suForm.find(':checkbox[name="usable_list[]"]').each(function(){
		var $i = $(this);
		if($i.val() == 'find_account_question') return;

		$i.change(function(){
			changeTable($i);
		});
	});

	$('a.modalAnchor._extendFormEdit').bind('before-open.mw', function(event){
		var memberFormSrl = $(event.target).parent().attr('id');
		var checked = $(event.target).closest('tr').find('input:radio:checked').val();

		exec_xml(
			'member',
			'getMemberAdminInsertJoinForm',
			{member_join_form_srl:memberFormSrl},
			function(ret){
				var tpl = ret.tpl.replace(/\|@\|/g, '\n');
				$('#extendForm').html(tpl);

				if (checked)$('#extendForm #radio_'+checked).attr('checked', 'checked');
			},
			['error','message','tpl']
		);

	});
	
	$('a._extendFormDelete').click(function(event){
		event.preventDefault();
		if (!confirm(xe.lang.msg_delete_extend_form)) return;

		var memberFormSrl = $(event.target).parent().attr('id');
		var targetTR = $(event.target).closest('tr');

		exec_xml(
			'member',
			'procMemberAdminDeleteJoinForm',
			{member_join_form_srl:memberFormSrl},
			function(ret){
				targetTR.remove();
			},
			['error','message','tpl']
		);
	});

	$('button._addDeniedID').click(function(){
		var ids = $('#prohibited_id').val();
		if(ids == ''){
			alert(xe.lang.msg_null_prohibited_id);
			$('#prohibited_id').focus();
			return;
		}
		

		ids = ids.replace(/\n/g, ',');

		var tag;
		function on_complete(data){
			var userIds = $.trim(data.user_ids);
			if(userIds == '') return;
			var uids = userIds.split(',');
			for (var i=0; i<uids.length; i++){
				tag = '<li id="denied_'+uids[i]+'">'+uids[i]+' <button type="button" class="x_icon-remove" onclick="doUpdateDeniedID(\''+uids[i]+'\',\'delete\',\''+xe.lang.confirm_delete+'\');return false;">'+xe.lang.cmd_delete+'</button></li>';
				$('#deniedList').append($(tag));
			}
			$('#prohibited_id').val('');

			$('._deniedIDCount').html($('#deniedList li').length);
		}

		jQuery.exec_json('member.procMemberAdminInsertDeniedID', {'user_id': ids}, on_complete);

	});
	$('button._addManagedEmailHost').click(function(){
		var hosts = $('#manage_email_host').val();
		if(hosts == ''){
			alert(xe.lang.msg_null_managed_emailhost);
			$('#manage_email_host').focus();
			return;
		}

		var tag;
		function on_complete(data)
		{
			$('#manage_email_host').val('');

			var hosts = $.trim(data.email_hosts);
			if(hosts == '') return;
			var uids = hosts.split("\n");
			for (var i=0; i<uids.length; i++)
			{
				uids[i] = $.trim(uids[i]);
				tag = '<li id="managed_'+uids[i].replace(/\./g,'\_\_')+'">'+uids[i]+' <button type="button" class="x_icon-remove" onclick="doUpdateManagedEmailHost(\''+uids[i]+'\',\'delete\',\''+xe.lang.confirm_delete+'\');return false;">'+xe.lang.cmd_delete+'</button></li>';
				$('#managedEmailHost').append($(tag));
			}

			$('._managededEmailHostCount').html($('#managedEmailHost li').length);
		}

		$.exec_json('member.procMemberAdminUpdateManagedEmailHosts', {'email_hosts': hosts}, on_complete);

	});

	$('button._addDeniedNickName').click(function(){
		var ids = $('#prohibited_nick_name').val();
		if(ids == ''){
			alert(xe.lang.msg_null_prohibited_nick_name);
			$('#prohibited_nick_name').focus();
			return;
		}
		

		ids = ids.replace(/\n/g, ',');

		var tag;
		function on_complete(data)
		{
			$('#prohibited_nick_name').val('');

			var nickNames = $.trim(data.nick_names);
			if(nickNames == '') return;
			var uids = nickNames.split(',');
			for (var i=0; i<uids.length; i++)
			{
				tag = '<li id="denied_'+uids[i]+'">'+uids[i]+' <button type="button" class="x_icon-remove" onclick="doUpdateDeniedNickName(\''+uids[i]+'\',\'delete\',\''+xe.lang.confirm_delete+'\');return false;">'+xe.lang.cmd_delete+'</button></li>';
				$('#deniedNickNameList').append($(tag));
			}

			$('._deniedNickNameCount').html($('#deniedNickNameList li').length);
		}

		jQuery.exec_json('member.procMemberAdminUpdateDeniedNickName', {'nick_name': ids}, on_complete);

	});
	
	$('#userDefine').submit(function(e) {
		var id_list = $(this).find('input[name=join_form_id_list]').val();
		var id_list_arr = id_list.split(',');

		var column_id = $(this).find('input[name=column_id]').val();
		var old_column_id = $(this).find('input[name=old_column_id]').val();
		if($.inArray(column_id, id_list_arr) > -1 && column_id != old_column_id) {
			alert(xe.lang.msg_exists_user_id);
			return false;
		}
		else return true;
	});

	$('.__redirect_url_btn').click(function(e){
		$(this).parent().find('input[name=redirect_url]').val('');
		$(this).parent().find('input[type=text]').val('');
	});
});
