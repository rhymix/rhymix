<?php
    /**
     * @file tools/clear_cache.php
     * @author zero <zero@zeroboard.com>
     * @brief XE 캐시파일 및 불필요한 파일 정리
     **/

    // 인증이 되지 않은 접근이면 종료
    if(!defined('__XE_TOOL_AUTH__') || !__XE_TOOL_AUTH__) exit();
    
    // 캐시 파일 제거
    $oAdminController = &getAdminController('admin');
    $oAdminController->procAdminRecompileCacheFile();
    $output = Context::getLang('success_reset');

    Context::set('output', $output);
?>
