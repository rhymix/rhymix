<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file point.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 포인트 레벨 아이콘 표시 애드온
     *
     * 포인트 시스템 사용중일때 사용자 이름 앞에 포인트 레벨 아이콘을 표시합니다.
     **/

    // before_display_content 가 아니면 return
    if($called_position != "before_display_content") return;

    $oPointController = &getController('point');
    $output = preg_replace_callback('!<(div|span)([^\>]*)member_([0-9\-]*)([^\>]*)>(.*?)\<\/(div|span)\>!is', array($oPointController, 'transLevelIcon'), $output);
?>
