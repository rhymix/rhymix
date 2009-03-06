<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->communication = '커뮤니케이션';
    $lang->about_communication = '회원간의 쪽지나 친구 관리등 커뮤니케이션 기능을 수행하는 모듈입니다';

    $lang->allow_message = '쪽지 수신 허용';
    $lang->allow_message_type = array(
             'Y' => '전체 수신',
             'N' => '거부',
             'F' => '친구만 허용',
        );

    $lang->message_box = array(
        'R' => '받은 쪽지함',
        'S' => '보낸 쪽지함',
        'T' => '보관함',
    );

    $lang->readed_date = '읽은 시간'; 

    $lang->sender = '보낸이';
    $lang->receiver = '받는이';
    $lang->friend_group = '친구 그룹';
    $lang->default_friend_group = '그룹 미지정';

    $lang->cmd_send_message = '쪽지 보내기';
    $lang->cmd_reply_message = '쪽지 답장';
    $lang->cmd_view_friend = '친구 보기';
    $lang->cmd_add_friend = '친구 등록';
    $lang->cmd_view_message_box = '쪽지함 보기';
    $lang->cmd_store = '보관';
    $lang->cmd_add_friend_group = '친구 그룹 추가';
    $lang->cmd_rename_friend_group = '친구 그룹 이름 변경';

    $lang->msg_no_message = '쪽지가 없습니다';
    $lang->message_received = '쪽지가 왔습니다';

    $lang->msg_title_is_null = '쪽지 제목을 입력해주세요';
    $lang->msg_content_is_null = '내용을 입력해주세요';
    $lang->msg_allow_message_to_friend = '친구에게만 쪽지 수신을 허용한 사용자라서 쪽지 발송을 하지 못했습니다';
    $lang->msg_disallow_message = '쪽지 수신을 거부한 사용자라서 쪽지 발송을 하지 못했습니다';
    $lang->about_allow_message = '쪽지 수신 여부를 결정할 수 있습니다';
?>
