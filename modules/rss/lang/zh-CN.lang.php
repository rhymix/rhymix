<?php
    /**
     * @file   modules/rss/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>　翻译：guny
     * @brief  RSS模块简体中文语言包
     **/

    // 一般单词
    $lang->feed = 'RSS发布';
    $lang->total_feed = '整站RSS';
    $lang->rss_disable = "关闭RSS";
    $lang->feed_copyright = '版权';
    $lang->feed_document_count = '每页主题数';
    $lang->feed_image = 'RSS图片';
    $lang->rss_type = "将显示的RSS形式";
    $lang->open_rss = 'RSS公开';
    $lang->open_rss_types = array(
        'Y' => '公开全文',
        'H' => '公开摘要',
        'N' => '不公开',
    );
    $lang->open_feed_to_total = '包含到整站RSS';
    
    // 说明
    $lang->about_rss_disable = "选此项不显示RSS。";
    $lang->about_rss_type = "可以指定要显示的RSS形式。";
    $lang->about_open_rss = '可以指定RSS公开程度,RSS公开不受查看内容权限的限制。';
    $lang->about_feed_description = '可以输入简单说明,留空输出该模块的说明。';
    $lang->about_feed_copyright = 'RSS Feed版权信息。';
    $lang->about_part_feed_copyright = '留空版权信息参照整站RSS的版权信息。';
    $lang->about_feed_document_count = '每页要显示的主题数(默认: 15)。';

    // 错误提示
    $lang->msg_rss_is_disabled = "RSS功能处于锁定状态。";
    $lang->msg_rss_invalid_image_format = '上传的文件格式错误！\n只允许上传JPEG, GIF, PNG图片文件。';
?>
