<?php
    /**
     * @file   modules/file/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com> 翻譯：royallin
     * @brief  附加檔案(file)模組語言
     **/

    $lang->file = '附加檔案';
    $lang->file_name = '檔案名稱';
    $lang->file_size = '檔案大小';
    $lang->download_count = '下載次數';
    $lang->status = '狀態';
    $lang->is_valid = '有效';
    $lang->is_stand_by = '等待';
    $lang->file_list = '檔案清單';
    $lang->allow_outlink = '外部檔案連結';
    $lang->allow_outlink_site = '允許的外連網站';
    $lang->allow_outlink_format = '允許外連的副檔名';
    $lang->allowed_filesize = '檔案大小限制';
    $lang->allowed_attach_size = '上傳限制';
    $lang->allowed_filetypes = '允許檔案類型';
    $lang->enable_download_group = '允許下載的用戶組';

    $lang->about_allow_outlink = '是否允許連結外部檔案。(*.wmv, *.mp3等影音檔案除外)';
    $lang->about_allow_outlink_format = '파일 외부 링크 설정에 관계 없이 허용하는 파일 확장자입니다. 여러개 입력시에 쉼표(,)을 이용해서 구분해주세요.<br />例)hwp,doc,zip,pdf';
    $lang->about_allow_outlink_site = '可設置允許外部檔案連結的網站名單。當數量太多時，可換行輸入。<br />例)http://www.zeroboard.com';
	$lang->about_allowed_filesize = '最大單一上傳檔案大小(管理員不受此限制)。';
    $lang->about_allowed_attach_size = '每個主題最大上傳檔案大小(管理員不受此限制)。';
    $lang->about_allowed_filetypes = '設定允許上傳的檔案類型。可以用"*.副檔名"來指定或用";"來區隔多個副檔名<br />例) *.* or *.jpg;*.gif;<br />(管理員不受此限制)';

    $lang->cmd_delete_checked_file = '刪除所選項目';
    $lang->cmd_move_to_document = '檢視原始主題';
    $lang->cmd_download = '下載';

    $lang->msg_not_permitted_download = '您不具備下載的權限。';
    $lang->msg_cart_is_null = ' 請選擇要刪除的檔案。';
    $lang->msg_checked_file_is_deleted = '已刪除%d個檔案！';
    $lang->msg_exceeds_limit_size = '已超過系統指定的檔案大小！';

    $lang->file_search_target_list = array(
        'filename' => '檔案名稱',
        'filesize' => '檔案大小 (byte, 以上)',
        'filesize_mega' => '檔案大小 (Mb, 以上)',
        'download_count' => '下載次數 (以上)',
        'user_id' => '帳號',
        'user_name' => '姓名',
        'nick_name' => '暱稱',
        'regdate' => '登錄日期',
        'ipaddress' => 'IP位址',
    );
?>
