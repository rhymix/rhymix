jQuery(function ($){
	
	$('a.modalAnchor._member').bind('before-open.mw', function(){
		var $memberList = $('.memberList input[name=user]:checked');
		if ($memberList.length == 0){
			alert(xe.lang.msg_select_user);
			return false;
		}
		var memberInfo, memberSrl;
		var memberTag = "";
		$('input[name="groups[]"]:checked').removeAttr('checked');
		$('#message').val('');
		for (var i = 0; i<$memberList.length; i++){
			memberInfo = $memberList.eq(i).val().split('\t');
			memberSrl = memberInfo.shift();
			memberTag += '<tr><td>'+memberInfo.join("</td><td>")+'</td><td><input type="checkbox" name="member_srls[]" value="'+memberSrl+'" checked="checked" /></td></tr>' 
		}
		$('#popupBody').empty().html(memberTag);
	});
});
