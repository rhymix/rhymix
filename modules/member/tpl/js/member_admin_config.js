/* 멤버 스킨 컬러셋 구해옴 */
function doGetSkinColorset(skin) {
    var params = {skin:skin};
    var response_tags = ['error','message','tpl'];

	function on_complete(ret) {
		jQuery('#colorset').show();
		var $colorset = jQuery('#member_colorset'), old_h, new_h;

		old_h = $colorset.height();
		$colorset.html(ret.tpl);
		new_h = $colorset.height();

		try{ fixAdminLayoutFooter(new_h - old_h) }catch(e){ };
	}

    exec_xml(
		'member',
		'getMemberAdminColorset',
		{skin:skin},
		on_complete,
		['error','message','tpl']
	);
}

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

jQuery(function($){
	// hide form if enable_join is setted "No" 
	var suSetting = $('fieldset.suSetting'); // 회원가입 설정
	var suForm = $('fieldset.suForm'); // 회원가입 양식
	var isEnable = suSetting.find(':radio[name=enable_join]:checked').val();
	if (isEnable == 'N'){
		suSetting.find('>ul>li:gt(0)').hide();
		suForm.hide();
	}

	suSetting.find(':radio[name=enable_join]').change(function(){
		if($('#enable_join_yes').is(':checked')){ 
			// 회원 가입을 허용하지 않는 경우 불필요한 항목을 모두 감춘다
			suSetting.find('>ul>li:gt(0)').slideDown(200);
			suForm.slideDown(200);
		} else { 
			// 회원 가입을 허용하는 경우 필요한 항목을 모두 펼친다
			suSetting.find('>ul>li:gt(0)').slideUp(200);
			suForm.slideUp(200);
		}
	});
	suForm.find(':checkbox').each(function(){
		var $i = $(this);
		$i.change(function(){
			if($i.is(':checked')){
				$i.parent('td').next('td')
							   .find(':radio, :text')
									.removeAttr('disabled')
									.end()
							   .find(':radio[value=option]').attr('checked', 'checked');
				
			} else {
				$i.parent('td').next('td').find(':radio, :text').attr('disabled','disabled').removeAttr('checked').next('label').css('fontWeight','normal');
			}
		});
	});

	suForm.find('._imageType')
		.find('input:checkbox:not(:checked)').closest('tr')
			.find('._subItem').hide().end()
			.end()
		.end()
		.find('input:checkbox')
			.change(function(){
				var $subItem = $(this).closest('tr').find('._subItem');
				if($(this).is(':checked')) $subItem.show();
				else $subItem.hide();
			})
		.end();

		$('a.modalAnchor._extendFormEdit').bind('before-open.mw', function(event){
		var memberFormSrl = $(event.target).parent().attr('id');
		var checked = $(event.target).closest('tr').find('input:radio:checked').val();

		exec_xml(
			'member',
			'getMemberAdminInsertJoinForm',
			{member_join_form_srl:memberFormSrl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
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
			var uids = data.user_ids.split(',');
			for (var i=0; i<uids.length; i++){
				tag = '<li id="denied_'+uids[i]+'">'+uids[i]+' <a href="#" class="side" onclick="doUpdateDeniedID(\''+uids[i]+'\', \'delete\', \''+xe.lang.confirm_delete+'\');return false;">'+xe.lang.cmd_delete+'</a></li>';
				$('#deniedList').append($(tag));
			}
			$('#prohibited_id').val('');

			$('._deniedIDCount').html($('#deniedList li').length);
		}

		jQuery.exec_json('member.procMemberAdminInsertDeniedID', {'user_id': ids}, on_complete);

	});

	$('input[name=identifier]').change(function(){
		var $checkedTR = $('input[name=identifier]:checked').closest('tr');
		var $notCheckedTR = $('input[name=identifier]:not(:checked)').closest('tr');
		var name, notName;
		if (!$checkedTR.hasClass('sticky')){
			name = $checkedTR.find('input[name="list_order[]"]').val();
			if (!$checkedTR.find('input[type=hidden][name="usable_list[]"]').length) $('<input type="hidden" name="usable_list[]" value="'+name+'" />').insertBefore($checkedTR);
			if (!$checkedTR.find('input[type=hidden][name='+name+']').length) $('<input type="hidden" name="'+name+'" value="required" />').insertBefore($checkedTR);
			$checkedTR.find('th').html('<span class="_title" style="padding-left:20px" >'+$checkedTR.find('th ._title').html()+'</span>');
			$checkedTR.find('input[type=checkbox][name="usable_list[]"]').attr('checked', 'checked').attr('disabled', 'disabled');
			$checkedTR.find('input[type=radio][name='+name+'][value=required]').attr('checked', 'checked').attr('disabled', 'disabled');
			$checkedTR.find('input[type=radio][name='+name+'][value=option]').removeAttr('checked').attr('disabled', 'disabled');
			$checkedTR.addClass('sticky');
			$checkedTR.parent().prepend($checkedTR);

			notName = $notCheckedTR.find('input[name="list_order[]"]').val();
			if (notName == 'user_id'){
				if ($notCheckedTR.find('input[type=hidden][name="usable_list[]"]').length) $notCheckedTR.find('input[type=hidden][name="usable_list[]"]').remove();
				if ($notCheckedTR.find('input[type=hidden][name='+name+']').length) $notCheckedTR.find('input[type=hidden][name='+name+']').remove();
				$notCheckedTR.find('input[type=checkbox][name="usable_list[]"]').removeAttr('disabled');
				$notCheckedTR.find('input[type=radio][name='+notName+']').removeAttr('disabled');
			}
			$notCheckedTR.find('th').html('<div class="wrap"><button type="button" class="dragBtn">Move to</button><span class="_title" >'+$notCheckedTR.find('th ._title').html()+'</span></div>');
			$notCheckedTR.removeClass('sticky');

			// add sticky class 
		}
	});

});
