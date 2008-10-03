<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/zh-TW.lang.php
     * @author zero <zero@zeroboard.com>
     * @brief  編輯器模組 >  創用CC授權組件
     **/

    $lang->ccl_default_title = '創用CC授權';
    $lang->ccl_default_message = '本文採用 <a rel="license" href="http://creativecommons.org/licenses/by%s%s%s%s" onclick="winopen(this.href);return false;">%s%s%s%s</a>';

    $lang->ccl_title = '標題';
    $lang->ccl_use_mark = '使用標章圖樣';
    $lang->ccl_allow_commercial = '允許商業性使用';
    $lang->ccl_allow_modification = '允許修改';

    $lang->ccl_allow = '允許';
    $lang->ccl_disallow = '不允許';
    $lang->ccl_sa = '相同方式分享';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-商業性', 'N'=>'-非商業性'),
        'ccl_allow_modification' => array('Y'=>'-允許', 'N'=>'-禁止', 'SA'=>'-相同方式分享'),
    );

    $lang->about_ccl_title = '顯示標題。留白顯示預設標題。';
    $lang->about_ccl_use_mark = '是否顯示圖案。(預設: 顯示)';
    $lang->about_ccl_allow_commercial = '是否允許商業使用。(預設: 不允許)';
    $lang->about_ccl_allow_modification = '是否允許修改。(預設: 允許)';
?>
