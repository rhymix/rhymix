<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  匯入模組語言
     **/

    // 按鈕上使用的語言
    $lang->cmd_sync_member = '同步';
    $lang->cmd_continue = '繼續進行';
    $lang->preprocessing = '準備匯入資料...';

    // 項目
    $lang->importer = '匯入';
    $lang->source_type = '匯入目標';
    $lang->type_member = '會員資料';
    $lang->type_message = '短訊息(MemoBox)';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = '討論版資料';
    $lang->type_syncmember = '同步會員資料';
    $lang->target_module = '目標模組';
    $lang->xml_file = 'XML檔案';

    $lang->import_step_title = array(
        1 => 'Step 1. 選擇匯入目標',
        12 => 'Step 1-2. 選擇目標模組',
        13 => 'Step 1-3. 選擇目標分類',
        2 => 'Step 2. XML檔案',
        3 => 'Step 2. 同步會員資料與文章',
        99 => '匯入資料',
    );

    $lang->import_step_desc = array(
        1 => '請選擇要匯入的XML檔案類型。',
        12 => '請選擇要匯入的目標模組。',
        13 => '請選擇要匯入的目標分類。',
        2 => "請輸入要匯入的XML檔案位置。\n可輸入相對或絕對路徑。",
        3 => '資料匯入後，可能會導致會員資料和文章內容產生誤差。請以user_id進行同步即可解決。',
        99 => '資料匯入中...',
    );

    // 訊息/提示
    $lang->msg_sync_member = '按同步按鈕，即可開始進行會員資料和文章的同步。';
    $lang->msg_no_xml_file = '找不到XML檔案，請重新確認路徑。';
    $lang->msg_invalid_xml_file = 'XML檔案格式錯誤！';
    $lang->msg_importing = '%d個的資料中正在輸入 %d個。 （長時間沒有回應時，請按「繼續進行」按鈕）';
    $lang->msg_import_finished = '已完成輸入%d/%d個資料。根據情況的不同，可能會出現沒有被匯入的資料。';
    $lang->msg_sync_completed = '已完成會員和文章，評論的同步。';

    // 說明
    $lang->about_type_member = '資料匯入目標為會員資料時，請選擇此項。';
    $lang->about_type_message = '資料匯入目標為短信息(MemoBox)時，請選擇此項。';
    $lang->about_type_ttxml = '資料匯入目標為TTXML(textcube系列)時，請選擇此項。';
    $lang->about_ttxml_user_id = '請輸入匯入TTXML資料時，指定為主題發表者的ID(必須是已註冊會員)。';
    $lang->about_type_module = '資料匯入目標為討論版主題時，請選擇此項。';
    $lang->about_type_syncmember = '匯入會員和文章資料後，需要同步會員資料時，請選擇此項。';
    $lang->about_importer = "不僅可以匯入Zeroboard 4，Zb5beta的資料，也能夠把其他程式資料匯入到Zeroboard XE當中。\n匯入資料時，請利用 <a href=\"http://svn.zeroboard.com/zeroboard_xe/migration_tools/\" onclick=\"winopen(this.href);return false;\">XML Exporter</a>建立XML檔案後再上傳。";
    $lang->about_target_path = "為了下載附件請輸入Zeroboard 4的安裝位置。\n位置在同一個主機時，請輸入如 /home/id/public_html/bbs的路徑,在不同主機時，請輸入如 http://域名/bbs的url地址。";
?>
