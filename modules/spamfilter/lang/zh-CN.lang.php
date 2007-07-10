<?php
    /**
     * @file   modules/spamfilter/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  简体中文语言包（只收录基本内容）
     **/

    // action 相关
    $lang->cmd_denied_ip = "禁止IP 目录";
    $lang->cmd_denied_word = "禁止词语目录";

    // 一般用语
    $lang->spamfilter = "Spam过滤";
    $lang->denied_ip = "禁止 IP";
    $lang->interval = "Spam处理间隔";
    $lang->limit_count = "限制数";
    $lang->check_trackback = "引用检查";
    $lang->word = "词语";

    // 说明文
    $lang->about_interval = "指定的时间内不能发表新文章。";
    $lang->about_limit_count = "在指定的时间内试图多次发表新文章，系统默认为Spam后自动禁止对方的IP。";
    $lang->about_denied_ip = "禁止 IP （通配符 \"*\" 如 \"127.0.*.*\"）";
    $lang->about_denied_word = "登录为禁止单词会不能发表包含该词语的文章";
    $lang->about_check_trackback = "在一个文章只允许一个IP引用";

    // 提示信息
    $lang->msg_alert_limited_by_config = '%s 秒之内不能连续发表新文章。试图继续发表系统会自动禁止您的IP';
    $lang->msg_alert_denied_word = '"%s"是禁用词语';
    $lang->msg_alert_registered_denied_ip = '您的IP被禁止，请联系网站管理员咨询';
    $lang->msg_alert_trackback_denied = '一个文章只允许一个引用';
?>
