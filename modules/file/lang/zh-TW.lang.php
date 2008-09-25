<?php
    /**
     * @file   modules/file/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  附件(file) 模組語言包
     **/

    $lang->file = '附件';
    $lang->file_name = '檔案名';
    $lang->file_size = '檔案大小';
    $lang->download_count = '下載次數';
    $lang->status = '狀態';
    $lang->is_valid = '有效';
    $lang->is_stand_by = '等待';
    $lang->file_list = '附件目錄';
    $lang->allowed_filesize = '檔案大小限制';
    $lang->allowed_attach_size = '上傳限制';
    $lang->allowed_filetypes = '允許檔案類型';
    $lang->enable_download_group = '允許下載的用戶組';

    $lang->about_allowed_filesize = '最大單個上傳檔案大小(管理員不受此限制)。';
    $lang->about_allowed_attach_size = '每個主題最大上傳檔案大小(管理員不受此限制)。';
    $lang->about_allowed_filetypes = '只允許上傳指定的檔案類型。 可以用"*.副檔名"來指定或用 ";"來 區分多個副檔名<br />例) *.* or *.jpg;*.gif;<br />(管理員不受此限制)';

    $lang->cmd_delete_checked_file = '刪除所選項目';
    $lang->cmd_move_to_document = '檢視原主題';
    $lang->cmd_download = '下載';

    $lang->msg_not_permitted_download = '您不具備下載的權限。';
    $lang->msg_cart_is_null = ' 請選擇要刪除的檔案。';
    $lang->msg_checked_file_is_deleted = '已刪除%d個檔案！';
    $lang->msg_exceeds_limit_size = '已超過系統指定的上傳檔案大小！';

    $lang->search_target_list = array(
        'filename' => '檔案名稱',
        'filesize' => '檔案大小 (byte, 以上)',
        'download_count' => '下載次數 (以上)',
        'regdate' => '登錄日期',
        'ipaddress' => 'IP地址',
    );
?>
