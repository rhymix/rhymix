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
    $lang->allowed_filesize = 'Maximum File Size';
    $lang->allowed_attach_size = 'Maximum Attachments';
    $lang->allowed_filetypes = 'Allowed Extensions';
    $lang->enable_download_group = 'Download Allowed Groups';

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

    $lang->search_target_list = array(
        'filename' => 'File Name',
        'filesize' => 'File Size (byte, over)',
        'download_count' => 'Downloads (over)',
        'regdate' => 'Registered Date',
        'ipaddress' => 'IP Address',
    );
?>
