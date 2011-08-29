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
});
