<?php
    /**
     * @file   modules/spamfilter/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  English Language Pack (basic)
     **/

    // action related
    $lang->cmd_denied_ip = "IP Address Blacklist";
    $lang->cmd_denied_word = "Word Blacklist";

    // general word
    $lang->spamfilter = "Spam filter";
    $lang->denied_ip = "IP to blacklist";
    $lang->interval = "Interval for spam filtering";
    $lang->limit_count = "No. of posting limitation";
    $lang->check_trackback = "Check trackbacks";
    $lang->word = "Word";

    // for description word
    $lang->about_interval = "All articles attempted for posting within the assigned time will be blocked.";
    $lang->about_limit_count = "If you exceed the posting limitation,\n your article will be recognized as a spam, and your IP address will be blacklisted.";
    $lang->about_denied_ip = "You can blacklist IP address range like 127.0.0.* by using *.";
    $lang->about_denied_word = "When you add a word to Word Blacklist,\n articles including that word will not be posted.";
    $lang->about_check_trackback = "Only the trackback by one IP per an article could be allowed.";

    // to post a message
    $lang->msg_alert_limited_by_config = 'Posting an article within %s second is not allowed.\n If you keep trying, your IP address may be blacklisted.';
    $lang->msg_alert_denied_word = 'The word "%s" is not allowed to be posted.';
    $lang->msg_alert_registered_denied_ip = 'Your IP address is blacklisted,\n so you may have limitations on normal using of this site.\n If you have any questions on that matter, please contact to the site administrator.'; 
    $lang->msg_alert_trackback_denied = 'Only one trackback per an article is allowed.';
?>
