<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/en.lang.php
     * @author zero <zero@zeroboard.com>
     * @brief  WYSIWYG module >  CCL display component
     **/

    $lang->ccl_default_title = 'Creative Commons Korea Copyrights';
    $lang->ccl_default_message = 'This component can be used by <a rel="license" href="http://creativecommons.org/licenses/by%s%s%s%s" onclick="winopen(this.href);return false;">%s%s%s%s</a>';

    $lang->ccl_title = 'Title';
    $lang->ccl_use_mark = 'Use Mark';
    $lang->ccl_allow_commercial = 'Allow Commercial Use';
    $lang->ccl_allow_modification = 'Allow Modification of Component';

    $lang->ccl_allow = 'Allow';
    $lang->ccl_disallow = 'Disallow';
    $lang->ccl_sa = 'Modify Identical Condition';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-Commertial', 'N'=>'-Noncommertial'),
        'ccl_allow_modification' => array('Y'=>'-Inhibit', 'N'=>'-Inhibit', 'SA'=>'-Under Identical Condition'),
    );

    $lang->about_ccl_title = 'Title will be displayed. Default message will be displayed when nothing is input.';
    $lang->about_ccl_use_mark = 'You may display or hide mark. (default: display)';
    $lang->about_ccl_allow_commercial = 'You may allow or disallow commertial use. (default: disallow)';
    $lang->about_ccl_allow_modification = 'You may allow or disallow modification of the work. (default: allow)';
?>
