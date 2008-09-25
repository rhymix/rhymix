<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  網頁編輯器(editor) 模組 > naver地圖 (naver_map) 組件語言包
     **/

    $lang->map_width = "寬度";
    $lang->map_height = "高度";

    // 詞句
    $lang->about_address = "例) 餐廳, 公園";
    $lang->about_address_use = "在搜尋視窗搜尋要找的地址後，按『新增』按鈕即可把相關地圖插入到文章當中。";

    // 錯誤訊息
    $lang->msg_not_exists_addr = "找不到搜尋的目標";
    $lang->msg_fail_to_socket_open = "連結搜尋郵編主機失敗。";
    $lang->msg_no_result = "無搜尋結果";

    $lang->msg_no_apikey = "想要使用naver地圖，需要一個Open API key。\n 請選擇管理員 >  網頁編輯器 > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">naver地圖設置</a>後輸入Open API key。";
?>
