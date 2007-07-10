<?php
    /**
     * @file   modules/spamfilter/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    // action関連
    $lang->cmd_denied_ip = "禁止IPリスト";
    $lang->cmd_denied_word = "禁止ワードリスト";

    // 一般用語
    $lang->spamfilter = "スパムフィルター";
    $lang->denied_ip = "禁止IP";
    $lang->interval = "スパム処理間隔";
    $lang->limit_count = "制限数";
    $lang->check_trackback = "トラックバック検査";
    $lang->word = "ワード";

    // 説明文
    $lang->about_interval = "指定された時間内に書き込みが行えないようにします。";
    $lang->about_limit_count = "指定された時間内に制限数を超える書き込みが行われるとスパムとして認識し、該当するＩＰを禁止します。";
    $lang->about_denied_ip = "「127.0.0.* 」のように「*」で、「127.0.0」以下ののIP帯域をすべて禁止することができます。";
    $lang->about_denied_word = "禁止ワードとして登録されると該当するワードが存在する書き込みを禁することができます。";
    $lang->about_check_trackback = "一つのＩＰからのみトラックバックを受信するようにします。";

    // メッセージ出力用
    $lang->msg_alert_limited_by_config = '%s秒以内の書き込みは禁止されます。続けて行うとスパムとして認識され禁止ＩＰに登録されます。';
    $lang->msg_alert_denied_word = '"%s"は使用が禁止されたワードです。';
    $lang->msg_alert_registered_denied_ip = '禁止IPに登録され、サイト内で正常な活動が制限されています。管理者にお問い合わせください。'; 
    $lang->msg_alert_trackback_denied = '一つの書き込みには１つのトラックバックしか受け取れません。';
?>
