<?php
    /**
     * @file   modules/spamfilter/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  English Language Pack (basic)
     **/

    // action related
    $lang->cmd_denied_ip = "Black listed IPs";
    $lang->cmd_denied_word = "Black listed words";

    // general word
    $lang->spamfilter = "Spam filter";
    $lang->denied_ip = "IP blacklist";
    $lang->interval = "Interval for spam process";
    $lang->limit_count = "No. of limit";
    $lang->check_trackback = "Check trackback";
    $lang->word = "word";

    // for description word
    $lang->about_interval = "It is a time setting to block posting an article within the time.";
    $lang->about_limit_count = "If you try to post an article more times over the limit within the setted time,\n it will be recognized as a spam, and your IP will be blocked.";
    $lang->about_denied_ip = "Using *, You can block all IP addresses of 127.0.0.* patterned address.";
    $lang->about_denied_word = "When you add a word to Black Listed Words,\n you can block an article contained the word not to be posted.";
    $lang->about_check_trackback = "Only the trackback by one IP per an article could be allowed.";

    // to post a message
    $lang->msg_alert_limited_by_config = 'Posting an article within %s second is not allowed.\n If you try it again and again, your IP may be listed as a IP blacklist.';
    $lang->msg_alert_denied_word = 'The word "%s" is not allowed to be used.';
    $lang->msg_alert_registered_denied_ip = 'Your IP was listed as a IP blacklist,\n so you may have limitations on normal use of this site.\n If you have any questions on that matter, please contact to the site administrator.'; 
    $lang->msg_alert_trackback_denied = 'Only one trackback per an article is allowed.';
?>
