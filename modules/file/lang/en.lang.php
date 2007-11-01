<?php
    /**
     * @file   modules/file/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Attachment module's basic language pack
     **/

    $lang->file = 'Attachment';
    $lang->file_name = 'File name';
    $lang->file_size = 'File size';
    $lang->download_count = 'Number of downloads';
    $lang->status = 'Status';
    $lang->is_valid = 'Valid';
    $lang->is_stand_by = 'Stand by';
    $lang->file_list = 'Attachments list';
    $lang->allowed_filesize = 'File size limit';
    $lang->allowed_attach_size = 'Total size limit';
    $lang->allowed_filetypes = 'Allowed extensions';
    $lang->enable_download_group = '다운로드 가능 그룹';

    $lang->about_allowed_filesize = 'You can assign file size limit for each file. (Excluding administrators)';
    $lang->about_allowed_attach_size = 'You can assign file size limit for each document. (Excluding administrators)';
    $lang->about_allowed_filetypes = 'Only allowed extentsions can be attached. To allow an extention, use "*.extention". To allow multiple extentions, use ";" between each extentions.<br />ex) *.* or *.jpg;*.gif;<br />(Excludes Administrators)';

    $lang->cmd_delete_checked_file = 'Delete Selected';
    $lang->cmd_move_to_document = 'Move to document';
    $lang->cmd_download = 'Download';

    $lang->msg_not_permitted_download = '다운로드 할 수 있는 권한이 없습니다';
    $lang->msg_cart_is_null = 'Select the file you wish to delete';
    $lang->msg_checked_file_is_deleted = 'Total of %d attachments has been deleted';
    $lang->msg_exceeds_limit_size = 'Attachment faild; exceeded the file size limit';

    $lang->search_target_list = array(
        'filename' => 'File name',
        'filesize' => 'File size (byte, Above)',
        'download_count' => 'Downloads (Above)',
        'regdate' => 'Date',
        'ipaddress' => 'IP Address',
    );
?>
