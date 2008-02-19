<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/ko.lang.php
     * @author zero <zero@zeroboard.com> 翻訳：RisaPapa
     * @brief  ウィジウィグエディターモジュール >  CCL 表示エディターコンポーネント
     **/

    $lang->ccl_default_title = 'クリエイティブコモンズジャパン著作者表示';
    $lang->ccl_default_message = 'この著作物は<a rel="license" href="http://creativecommons.org/licenses/by%s%s%s%s" onclick="winopen(this.href);return false;">%s%s%s%s</a>に従って利用することができます。';

    $lang->ccl_title = 'タイトル';
    $lang->ccl_use_mark = 'マーク使用';
    $lang->ccl_allow_commercial = '営利目的許可';
    $lang->ccl_allow_modification = '著作物変更許';

    $lang->ccl_allow = '許可';
    $lang->ccl_disallow = '禁止';
    $lang->ccl_sa = '同一条件変更';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-営利', 'N'=>'-非営利'),
        'ccl_allow_modification' => array('Y'=>'-変更許可', 'N'=>'-変更禁止', 'SA'=>'-同一条件変更許可'),
    );

    $lang->about_ccl_title = 'タイトルを表示します。空欄の場合はデフォルトのメッセージが表示されます。';
    $lang->about_ccl_use_mark = 'マークを表示するかどうかが選択できます（デフォルト：表示）。';
    $lang->about_ccl_allow_commercial = '営利目的での利用を許可するかどうかが選択できます（デフォルト：禁止）';
    $lang->about_ccl_allow_modification = '著作権の変更ができるかがどうかが許可できます（デフォルト：同一条件変更）。';
?>
