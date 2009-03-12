<?php
    /**
     * @file   modules/file/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Attachment module's basic language pack
     **/

    $lang->file = 'Attachment';
    $lang->file_name = 'File Name';
    $lang->file_size = 'File Size';
    $lang->download_count = 'Number of Downloads';
    $lang->status = 'Status';
    $lang->is_valid = 'Valid';
    $lang->is_stand_by = 'Stand by';
    $lang->file_list = 'Attachments List';
    $lang->allow_outlink = '파일 외부 링크';
    $lang->allow_outlink_site = '파일 외부 허용 사이트';
    $lang->allow_outlink_format = '파일 외부 링크 허용 확장자';
    $lang->allowed_filesize = 'Maximum File Size';
    $lang->allowed_attach_size = 'Maximum Attachments';
    $lang->allowed_filetypes = 'Allowed Extensions';
    $lang->enable_download_group = 'Download Allowed Groups';

    $lang->about_allow_outlink = '리퍼러에 따라 파일 외부 링크를 차단할 수 있습니다.(*.wmv, *.mp3등 미디어 파일 제외)';
    $lang->about_allow_outlink_format = '파일 외부 링크 설정에 관계 없이 허용하는 파일 확장자입니다. 여러개 입력시에 쉼표(,)을 이용해서 구분해주세요.<br />eg)hwp,doc,zip,pdf';
    $lang->about_allow_outlink_site = '파일 외부 링크 설정에 관계 없이 허용하는 사이트 주소입니다. 여러개 입력시에 줄을 바꿔서 구분해주세요.<br />ex)http://www.zeroboard.com';
	$lang->about_allowed_filesize = 'You can assign file size limit for each file. (Exclude administrators)';
    $lang->about_allowed_attach_size = 'You can assign file size limit for each document. (Exclude administrators)';
    $lang->about_allowed_filetypes = 'Only allowed extentsions can be attached. To allow an extension, use "*.[extention]". To allow multiple extensions, use ";" between each extension.<br />ex) *.* or *.jpg;*.gif;<br />(Exclude Administrators)';

    $lang->cmd_delete_checked_file = 'Delete Selected Item(s)';
    $lang->cmd_move_to_document = 'Move to Document';
    $lang->cmd_download = 'Download';

    $lang->msg_not_permitted_download = 'You do not have permission to download';
    $lang->msg_cart_is_null = 'Please select file(s) to delete';
    $lang->msg_checked_file_is_deleted = '%d attachment(s) was(were) deleted';
    $lang->msg_exceeds_limit_size = 'File size of attachment is bigger than allowed size.';


    $lang->file_search_target_list = array(
        'filename' => 'File Name',
        'filesize' => 'File Size (byte, over)',
        'filesize_mega' => 'File Size (mbyte, over)',
        'download_count' => 'Downloads (over)',
        'regdate' => 'Registered Date',
        'user_id' => 'User UD',
        'user_name' => 'User Name',
        'nick_name' => 'Nickname',
        'ipaddress' => 'IP Address',
    );
?>
