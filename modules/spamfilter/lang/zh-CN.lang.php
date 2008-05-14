<?php
    /**
     * @file   modules/spamfilter/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  简体中文语言包（只收录基本内容）
     **/

    // action 相关
    $lang->cmd_denied_ip = "禁止IP目录";
    $lang->cmd_denied_word = "敏感词目录";

    // 一般用语
    $lang->spamfilter = "垃圾过滤";
    $lang->denied_ip = "禁止IP";
    $lang->interval = "处理垃圾间隔";
    $lang->limit_count = "限制数";
    $lang->check_trackback = "检查引用";
    $lang->word = "单词";

    // 说明文
    $lang->about_interval = "指定的时间内禁止发表新主题。";
    $lang->about_limit_count = "在指定时间内发表的新主题超过限制数时，系统将把它认为是垃圾主题，将自动禁止对方的IP。";
    $lang->about_denied_ip = "禁止IP可以使用通配符。(如：如 \"127.0.*.*\"）";
    $lang->about_denied_word = "登录为敏感词，可以对要发表的主题进行检测并禁止含有敏感词的主题发表。";
    $lang->about_check_trackback = "对一个主题只允许一个IP引用。";

    // 提示信息
    $lang->msg_alert_limited_by_config = '%s秒之内不能连续发表新主题。如您继续再试系统将自动禁止您的IP。';
    $lang->msg_alert_denied_word = '"%s"是敏感词！';
    $lang->msg_alert_registered_denied_ip = '您的IP已被禁止，详情请联系网站管理员。';
    $lang->msg_alert_trackback_denied = '一个主题只允许一个引用。';
?>
