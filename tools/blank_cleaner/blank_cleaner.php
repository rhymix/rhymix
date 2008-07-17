<?php
    /**
     * @file tools/blank_cache.php
     * @author zero <zero@zeroboard.com>
     * @brief 첨부파일 디렉토리의 빈 디렉토리 삭제
     **/

    // 인증이 되지 않은 접근이면 종료
    if(!defined('__XE_TOOL_AUTH__') || !__XE_TOOL_AUTH__) exit();
    
    // 캐시 파일 제거
    FileHandler::removeBlankDir(_XE_PATH_.'files');
    $output = Context::getLang('success_deleted');

    Context::set('output', $output);
?>
