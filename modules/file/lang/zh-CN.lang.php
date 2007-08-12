<?php
    /**
     * @file   modules/file/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  附件(file) 模块语言包
     **/

    $lang->file = '附件';
    $lang->file_name = '文件名';
    $lang->file_size = '文件大小';
    $lang->download_count = '下载次数';
    $lang->status = '状态';
    $lang->is_valid = '有效';
    $lang->is_stand_by = '等待';
    $lang->file_list = '附件目录';
    $lang->allowed_filesize = '文件大小限制';
    $lang->allowed_attach_size = '上传限制';
    $lang->allowed_filetypes = '可用扩展名';

    $lang->about_allowed_filesize = '最大单个上传文件大小(管理员不受此限制)。';
    $lang->about_allowed_attach_size = '每个主题最大上传文件大小(管理员不受此限制)。';
    $lang->about_allowed_filetypes = '只允许上传指定的扩展名。 可以用"*.扩展名"来指定或用 ";"来 区分多个扩展名<br />例) *.* or *.jpg;*.gif;<br />(管理员不受此限制)';

    $lang->cmd_delete_checked_file = '删除所选项目';
    $lang->cmd_move_to_document = '查看源主题';
    $lang->cmd_download = '下载';

    $lang->msg_cart_is_null = ' 请选择要删除的文件。';
    $lang->msg_checked_file_is_deleted = '已删除%d个文件！';
    $lang->msg_exceeds_limit_size = '已超过系统指定的上传文件大小！';

    $lang->search_target_list = array(
        'filename' => '文件名',
        'filesize' => '文件大小 (byte, 以上)',
        'download_count' => '下载次数 (以上)',
        'regdate' => '登录日期',
        'ipaddress' => 'IP地址',
    );
?>
