<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file google_analytics.addon.php
     * @author zero (zero@nzeo.com)
     * @brief google analytics 코드를 사이트에 추가함
     **/

    // 관리자 모듈이면 패스~
    if(Context::get('module')=='admin') return; 

    // 한번만 출력시키기 위해 전역변수에 호출되었음을 체크해 놓음 (called position과 상관없음)
    if($GLOBALS['_called_ga_']) return;
    $GLOBALS['_called_ga_'] = true;

    $js_code = <<<EndOfCss
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "{$addon_info->uacct}";
urchinTracker();
</script>
EndOfCss;

    Context::addHtmlFooter($js_code);
?>
