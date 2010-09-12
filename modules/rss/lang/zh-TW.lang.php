<?php
    /**
     * @file   modules/rss/lang/zh-TW.lang.php
     * @author NHN (developers@xpressengine.com) 翻譯：royallin
     * @brief  RSS 模組正體中文語言
     **/

    // 一般語言
    $lang->feed = 'RSS Feed';
    $lang->total_feed = 'RSS';
    $lang->rss_disable = "關閉 RSS Feed";
    $lang->feed_copyright = '版權';
    $lang->feed_document_count = '每頁主題數';
    $lang->feed_image = 'RSS 圖片';
    $lang->rss_type = "RSS Feed 類型";
    $lang->open_rss = '公開程度';
    $lang->open_rss_types = array(
        'Y' => '全部公開',
        'H' => '公開摘要',
        'N' => '不公開',
    );
    $lang->open_feed_to_total = '是否使用';

    // 說明
    $lang->about_rss_disable = "隱藏 RSS Feed。";
    $lang->about_rss_type = "指定要顯示的 RSS Feed 類型。";
    $lang->about_open_rss = '選擇該模組 RSS Feed 的公開程度。公開 RSS Feed 將不受檢視內容權限的限制，隨公開 RSS Feed 的選項公開 RSS Feed。';
    $lang->about_feed_description = '請輸入簡介。 也可輸入相關管理使用說明。';
    $lang->about_feed_copyright = '請輸入 Feed 著作權資料。';
    $lang->about_part_feed_copyright = '著作權將會適用所有的 Feed 內容。';
    $lang->about_feed_document_count = '每頁要顯示的主題數。(預設: 15)';

    // 錯誤提示
    $lang->msg_rss_is_disabled = "RSS Feed 功能未開啟。";
    $lang->msg_rss_invalid_image_format = '錯誤的檔案格式，無法上傳。\n只允許上傳 JPEG, GIF, PNG 等檔案格式。';
?>
