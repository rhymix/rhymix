<?php
    /**
     * @file   modules/spamfilter/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English Language Pack (basic)
     **/

    // action related
    $lang->cmd_denied_ip = "IP Address Blacklist";
    $lang->cmd_denied_word = "Word Blacklist";

    // general word
    $lang->spamfilter = "Spam filter";
    $lang->denied_ip = "IP Address";
    $lang->interval = "Interval for spam filtering";
    $lang->limit_count = "No. of posting limitation";
    $lang->check_trackback = "Check Trackbacks";
    $lang->word = "Word";
    $lang->hit = 'Hit';
    $lang->latest_hit = 'Latest Hits';

    // for description word
    $lang->about_interval = "All articles attempted for posting within the assigned time will be blocked.";
    $lang->about_limit_count = "If exceeded the posting limitation,\n that IP will be regarded as a spam, thus will have limitations on posting articles, comments, and trackbacks.";
    $lang->about_denied_ip = "You can add IP address range like 127.0.0.* by using *.";
    $lang->about_denied_word = "When you add a word to Word Blacklist,\n articles including it will be blocked.";
    $lang->about_check_trackback = "A single IP per an article is allowed for trackbacks.";

    // to post a message
    $lang->msg_alert_limited_by_config = 'Posting an article within %s second is not allowed.\n If you keep trying, your IP address will be blacklisted.';
    $lang->msg_alert_denied_word = 'The word "%s" is not allowed.';
    $lang->msg_alert_registered_denied_ip = 'Your IP address is blacklisted,\n so you may have limitations on normal using of this site.\n If you have any questions on that matter, please contact to the site administrator.';
    $lang->msg_alert_trackback_denied = 'Only one trackback per an article is allowed.';
?>