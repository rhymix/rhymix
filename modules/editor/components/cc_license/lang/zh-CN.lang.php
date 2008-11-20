<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/zh-CN.lang.php
     * @author zero <zero@zeroboard.com>
     * @brief  编辑器(editor) 模块 >  知识共享许可协议组件语言包
     **/

    $lang->ccl_default_title = '知识共享许可协议@中国大陆';
    $lang->ccl_default_message = '本主题采用<a rel="license" href="http://creativecommons.org/licenses/by%s%s%s%s" onclick="winopen(this.href);return false;">%s%s%s%s</a>授权。';

    $lang->ccl_title = '标题';
    $lang->ccl_use_mark = '使用图标';
    $lang->ccl_allow_commercial = '商业性使用';
    $lang->ccl_allow_modification = '允许修改作品';

    $lang->ccl_allow = '允许';
    $lang->ccl_disallow = '禁止';
    $lang->ccl_sa = '相同方式共享';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-商业', 'N'=>'-非商业'),
        'ccl_allow_modification' => array('Y'=>'-允许修改', 'N'=>'-禁止修改', 'SA'=>'-允许相同方式共享'),
    );

    $lang->about_ccl_title = '显示标题(留空为显示默认标题)。';
    $lang->about_ccl_use_mark = '设置图标显示与否(默认: 显示)。';
    $lang->about_ccl_allow_commercial = '设置商业性使用与否(默认: 禁止)。';
    $lang->about_ccl_allow_modification = '设置允许作品修改与否(默认:相同方式共享)。';
?>
