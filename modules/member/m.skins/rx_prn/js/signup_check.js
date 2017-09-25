/**
 * @brief 회원 가입시나 정보 수정시 각 항목의 중복 검사하고 화면에 바로 나타냄
 * @author misol <misol.kr@gmail.com>
 * @author NAVER (developer@xpressengine.com)
 **/
// body 에서 불러오면 가능
$('#rx_insert_member :input').filter('[name=user_id],[name=nick_name],[name=email_address]').blur(rxMemberCheckValue);

// 실제 서버에 특정 필드의 value check를 요청하고 이상이 있으면 메세지를 뿌려주는 함수
function rxMemberCheckValue(event) {
	var field  = event.target;
	var _name  = field.name;
	var _value = field.value;
	if(!_name || !_value) return;

	var params = {name:_name, value:_value};
	var response_tags = ['error','message','message_type'];

	exec_xml('member','procMemberCheckValue', params, dispMemberValueCheck, response_tags, field);
}

// 서버에서 응답이 올 경우 이상이 있으면 메세지를 출력
function dispMemberValueCheck(response, response_tags, field) {
	var _id   = 'rx_sw_dummy-'+field.name;
	var dummy = $('#'+_id);

	if(response['message']=='success') {
		dummy.html('').hide();
		return;
	}

	if (!dummy.length) {
		dummy = $('<p class="rx_member-notice error" />').attr('id', _id)
		$(field).after(dummy);
	}

	dummy.html(response['message']).show();
}

$(document).ready(function(){
	// label for setup
	$('.control-label[for]').each(function(){
		var $this = $(this);
		if($this.attr('for') == ''){
			$this.attr('for', $this.next().children(':visible:first').attr('id'));
		}
	});
	// image input
	var html = '';
	if(prn_profile_image.exists !== false)
	{
		html = '<button type="button" onclick="doDeleteProfileImage(' + prn_member_srl + ');return false;" title="' + xe.lang.cmd_delete + '">x</button>';
	}
	else
	{
		html = '<label for="profile_image" class="prn_button" title="' + xe.lang.cmd_upload + '">+</label>';
	}
	$('#profile_image')
		.after('<div class="control-group"><label for="profile_image"><span id="prn_profile_imagetag"><img src="' + prn_profile_image.src + '" onerror="this.src=\'./modules/member/m.skins/rx_prn/images/member.png\'" width="'+prn_profile_image.width+'" height="'+prn_profile_image.height+'">'+html+'</span></label></div>')
		.css('display','none')
		.change(function() {
			if (this.files && this.files[0]) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$('#prn_profile_imagetag img').attr('src', e.target.result);
				}
				reader.readAsDataURL(this.files[0]);
			} else {
				$('#prn_profile_imagetag img').attr('src', './modules/member/m.skin/rx_prn/images/member.svg').attr('width', prn_profile_image.width).attr('height', prn_profile_image.height);
			}
		});
	// label for profile images
	$('#profile_imagetag').each(function() {
		$(this).html(function(i, oldhtml) {
			$(this).attr('id', '');
			return '';
		});
	});
});