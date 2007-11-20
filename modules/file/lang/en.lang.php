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
    $lang->enable_download_group = 'Download allowed groups';

    $lang->about_allowed_filesize = 'You can assign file size limit for each file. (Excluding administrators)';
    $lang->about_allowed_attach_size = 'You can assign file size limit for each document. (Excluding administrators)';
    $lang->about_allowed_filetypes = 'Only allowed extentsions can be attached. To allow an extension, use "*.extention". To allow multiple extensions, use ";" between each extension.<br />ex) *.* or *.jpg;*.gif;<br />(Exclude Administrators)';

    $lang->cmd_delete_checked_file = 'Delete Selected';
    $lang->cmd_move_to_document = 'Move to document';
    $lang->cmd_download = 'Download';

    $lang->msg_not_permitted_download = 'You do not have any permission to download';
    $lang->msg_cart_is_null = 'Please select file(s) to delete';
    $lang->msg_checked_file_is_deleted = 'Total of %d attachment(s) was(were) deleted';
    $lang->msg_exceeds_limit_size = 'Attachedment failed due to the excess of file size';

    $lang->search_target_list = array(
        'filename' => 'File name',
        'filesize' => 'File size (byte, Over)',
        'download_count' => 'Downloads (Over)',
        'regdate' => 'Date',
        'ipaddress' => 'IP Address',
    );
?>
