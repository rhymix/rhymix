<?php
    /**
     * @file   modules/spamfilter/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com> 翻譯：royallin
     * @brief  垃圾過濾(spamfilter)模組正體中文語言(包含基本內容)
     **/

    // action 相關
    $lang->cmd_denied_ip = "禁止IP列表";
    $lang->cmd_denied_word = "敏感詞清單";

    // 一般用語
    $lang->spamfilter = "垃圾過濾";
    $lang->denied_ip = "禁止IP";
    $lang->interval = "處理垃圾間隔";
    $lang->limit_count = "限制數";
    $lang->check_trackback = "檢查引用";
    $lang->word = "單字";
    $lang->hit = '히트';
    $lang->latest_hit = '최근 히트';

    // 說明
    $lang->about_interval = "指定的時間內禁止發表新主題。";
    $lang->about_limit_count = "在指定時間內發表的新主題超過限制數時，系統將會認為是垃圾主題，並自動禁止對方的IP。";
    $lang->about_denied_ip = "禁止IP可以使用通配符。(如：如 \"127.0.*.*\"）";
    $lang->about_denied_word = "登錄為敏感詞，可以對要發表的主題進行檢測並禁止含有敏感詞的主題發表。";
    $lang->about_check_trackback = "對一個主題只允許一個IP引用。";

    // 提示訊息
    $lang->msg_alert_limited_by_config = '%s秒之內不能連續發表新主題。如您繼續再試系統將自動禁止您的IP。';
    $lang->msg_alert_denied_word = '"%s"是敏感詞！';
    $lang->msg_alert_registered_denied_ip = '您的IP已被禁止，詳情請聯繫網站管理員。';
    $lang->msg_alert_trackback_denied = '一個主題只允許一個引用。';
?>