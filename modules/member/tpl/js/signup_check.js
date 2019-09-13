/**
 * @brief 회원 가입시나 정보 수정시 각 항목의 중복 검사등을 하는 기능을 구현
 * @author NAVER (developer@xpressengine.com)
 **/

// 입력이 시작된 것과 입력후 정해진 시간동안 내용이 변하였을 경우 서버에 ajax로 체크를 하기 위한 변수 설정
var memberCheckObj = { target:null, value:null }

// domready시에 특정 필드들에 대해 이벤트를 걸어 놓음
jQuery(document).ready(memberSetEvent);

function memberSetEvent() {
	jQuery('#fo_insert_member :input')
		.filter('[name=user_id],[name=nick_name],[name=email_address]')
		.blur(memberCheckValue);
}


// 실제 서버에 특정 필드의 value check를 요청하고 이상이 있으면 메세지를 뿌려주는 함수
function memberCheckValue(event) {
	var field  = event.target;
	var _name  = field.name;
	var _value = field.value;
	if(!_name || !_value) return;

	var params = {name:_name, value:_value};
	var response_tags = ['error','message'];

	exec_xml('member','procMemberCheckValue', params, completeMemberCheckValue, response_tags, field);
}

// 서버에서 응답이 올 경우 이상이 있으면 메세지를 출력
function completeMemberCheckValue(ret_obj, response_tags, field) {
	var _id   = 'dummy_check'+field.name;
	var dummy = jQuery('#'+_id);
   
    if(ret_obj['message']=='success') {
        dummy.html('').hide();
        return;
    }

	if (!dummy.length) {
		dummy = jQuery('<p class="checkValue help-inline" style="color:red" />').attr('id', _id).appendTo(field.parentNode);
	}

	dummy.html(ret_obj['message']).show();
}

// 결과 메세지를 정리하는 함수
function removeMemberCheckValueOutput(dummy, obj) {
    dummy.style.display = "none";
}

// 문자 인증 처리
(function($) {
	$(function() {
		$('input.phone_number').on('keydown change', function() {
			$(this).siblings('button.verifySMS').show();
		});
		$('button.verifySMS').on('click', function(event) {
			event.preventDefault();
			$(this).attr('disabled', 'disabled');
			var phone_country = $(this).siblings('.phone_country').val();
			var phone_number = $(this).siblings('.phone_number').val();
			var that = $(this);
			exec_json('member.procMemberSendVerificationSMS', { phone_country: phone_country, phone_number: phone_number }, function(data) {
				alert(data.message);
				if (!data.error) {
					var input_area = that.siblings('.verifySMS_input_area');
					input_area.show().find('input').focus();
					that.hide();
				} else {
					that.removeAttr('disabled');
				}
			}, function() {
				that.removeAttr('disabled');
			});
		});
		$('button.verifySMS_input_button').on('click', function(event) {
			event.preventDefault();
			$(this).attr('disabled', 'disabled');
			var code = $(this).siblings('.verifySMS_input_number').val();
			var that = $(this);
			exec_json('member.procMemberConfirmVerificationSMS', { code: code }, function(data) {
				alert(data.message);
				if (!data.error) {
					var input_area = that.parents('.verifySMS_input_area');
					input_area.hide();
					input_area.siblings('.phone_number,.phone_country').attr('readonly', 'readonly');
				} else {
					that.removeAttr('disabled');
				}
			}, function() {
				that.removeAttr('disabled');
			});
		});
	});
})(jQuery);
