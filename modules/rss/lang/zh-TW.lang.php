<?php
    /**
     * @file   modules/rss/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com> 翻譯：royallin
     * @brief  RSS模組正體中文語言 (包含基本內容)
     **/

    // 一般語言
    $lang->feed = '發佈RSS Feed';
    $lang->total_feed = '所有RSS';
    $lang->rss_disable = "關閉RSS Feed";
    $lang->feed_copyright = '版權';
    $lang->feed_document_count = '每頁主題數';
    $lang->feed_image = 'RSS圖片';
    $lang->rss_type = "RSS Feed類型";
    $lang->open_rss = '公開RSS Feed';
    $lang->open_rss_types = array(
        'Y' => '全部公開',
        'H' => '公開摘要',
        'N' => '不公開',
    );
    $lang->open_feed_to_total = '包含所有RSS Feed';

    // 說明
    $lang->about_rss_disable = "不顯示RSS Feed。";
    $lang->about_rss_type = "指定要顯示的RSS Feed類型。";
    $lang->about_open_rss = '選擇該模組的RSS Feed公開程度。公開RSS Feed將不受檢視內容權限的限制，隨公開RSS Feed的選項公開RSS Feed。';
    $lang->about_feed_description = '현재 모듈에 대한 간단한 설명을 쓸 수 있습니다. 설명을 입력하지 않으실 경우, 해당 모듈에 설정된 관리용 설명이 포함됩니다.';
    $lang->about_feed_copyright = '현재 모듈에서 Feed로 발행되는 글에 대한 저작권 정보입니다.';
    $lang->about_part_feed_copyright = '입력하지 않으면 전체 피드 저작권 설정과 동일하게 적용됩니다.';
    $lang->about_feed_document_count = '每頁要顯示的主題數。(預設: 15)';

    // 錯誤提示
    $lang->msg_rss_is_disabled = "RSS Feed功能未開啟。";
    $lang->msg_rss_invalid_image_format = '錯誤的檔案格式，無法上傳。\n只允許上傳JPEG, GIF, PNG等檔案格式。';
?>
