<?php
    /**
     * @file   modules/rss/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    // 一般用語
    $lang->rss_disable = "RSS機能ロック";
    $lang->rss_type = "出力するRSSタイプ";
    $lang->open_rss = 'RSS配信';
    $lang->open_rss_types = array(
        'Y' => '全文配信 ',
        'H' => '要約配信',
        'N' => '配信しない',
    );

    // 説明文
    $lang->about_rss_disable = "チェックするとRSSの出力を行いません。";
    $lang->about_rss_type = "出力するRSSタイプを指定することができます。";
    $lang->about_open_rss = '現在のモジュールに対して「RSS配信」を選択することができます。書き込みの内容が読める権限とは関係なくオプションによってRSSが配信されます。';

    // エラーメッセージ
    $lang->msg_rss_is_disabled = "RSS機能がロックされています。";
?>
