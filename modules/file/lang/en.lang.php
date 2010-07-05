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
    $lang->allow_outlink = 'External Link';
    $lang->allow_outlink_site = 'Allowed Websites';
    $lang->allow_outlink_format = 'Allowed Formats';
    $lang->allowed_filesize = 'Maximum File Size';
    $lang->allowed_attach_size = 'Maximum Attachments';
    $lang->allowed_filetypes = 'Allowed Extensions';
    $lang->enable_download_group = 'Download Allowed Groups';

    $lang->about_allow_outlink = 'You can shut external links according to referers. (except media files like *.wmv, *.mp3)';
    $lang->about_allow_outlink_format = 'These formats will always be allowed. Please use comma(,) for multiple input.<br />eg)hwp,doc,zip,pdf';
    $lang->about_allow_outlink_site = 'These websites will alyways be allowed. Please use new line for multiple input.<br />ex)http://www.zeroboard.com';
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
    $lang->msg_file_not_found = 'Could not find requested file.';


    $lang->file_search_target_list = array(
        'filename' => 'File Name',
        'filesize_more' => 'File Size (byte, more)',
        'filesize_mega_more' => 'File Size (mbyte, more)',
		'filesize_less' => 'File Size (byte, less)',
		'filesize_mega_less' => 'File Size (Mb, less)',
        'download_count' => 'Downloads (more)',
        'regdate' => 'Registered Date',
        'user_id' => 'User UD',
        'user_name' => 'User Name',
        'nick_name' => 'Nickname',
        'ipaddress' => 'IP Address',
    );
	$lang->msg_not_allowed_outlink = 'It is not allowed to download files not from this site.'; 
    $lang->msg_not_permitted_create = '파일 또는 디렉토리를 생성할 수 없습니다.';
	$lang->msg_file_upload_error = '파일 업로드 중 에러가 발생하였습니다.';

?>
