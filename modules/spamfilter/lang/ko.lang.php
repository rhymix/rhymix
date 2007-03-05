<?php
    /**
     * @file   : modules/spamfilter/lang/ko.lang.php
     * @author : zero <zero@nzeo.com>
     * @desc   : 한국어 언어팩 (기본적인 내용만 수록)
     **/

    // action 관련
    $lang->cmd_denied_ip = "금지IP 목록";
    $lang->cmd_denied_word = "금지단어 목록";

    // 일반 단어
    $lang->denied_ip = "금지 IP";
    $lang->interval = "스팸 처리 간격";
    $lang->word = "단어";

    // 설명문
    $lang->about_interval = "지정된 시간내에 다시 글이 등록이 되면 스팸으로 간주가 됩니다";
    $lang->about_denied_ip = "127.0.0.* 와 같이 * 로 정해진 패턴의 IP 대역을 모두 금지 시킬 수 있습니다";
    $lang->about_denied_word = "금지 단어로 등록되면 해당 단어가 있는 글은 등록을 금지 시킬 수 있습니다";

    // 메세지 출력용
    $lang->msg_alert_registered_spamer = '스패머로 등록되셨습니다';
?>
