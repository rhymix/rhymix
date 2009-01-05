<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Issuetracker模块语言包
     **/

     $lang->issuetracker = '问题跟踪';
     $lang->about_issuetracker = '可进行项目计划，查看代码，问题管理及发布项目等操作的问题跟踪模块。';

     $lang->cmd_project_list = '项目目录';
     $lang->cmd_view_info = '项目信息';
     $lang->cmd_project_setting = '项目设置';
     $lang->cmd_release_setting = '发布设置';
     $lang->cmd_insert_package = '添加程序包';
     $lang->cmd_insert_release = '添加发布';
     $lang->cmd_attach_file = '添加附件';
     $lang->cmd_display_item = '显示对象';

     $lang->cmd_resolve_as = '修改状态';
     $lang->cmd_reassign = '修改所有者';
     $lang->cmd_accept = '接受';

     $lang->svn_url = 'SVN地址';
     $lang->about_svn_url = '请输入项目的SVN地址。';
     $lang->svn_cmd = 'SVN应用程序位置';
     $lang->about_svn_cmd = '请输入svn clien应用程序位置。(ex: /usr/bin/svn)';
     $lang->diff_cmd = 'DIF应用程序位置';
     $lang->about_diff_cmd = '为比较SVN revision，请输入diff应用程序位置。 (ex: /usr/bin/diff)';
     $lang->svn_userid = 'SVN ID';
     $lang->about_svn_userid = 'If authentication is necessary, input userid for accessing the SVN repository';
     $lang->svn_passwd = 'SVN password';
     $lang->about_svn_passwd = 'If authentication is necessary, input password for accessing the SVN repository';

     $lang->issue = '问题';
     $lang->total_issue = '全部问题';
     $lang->milestone = $lang->milestone_srl = '计划';
     $lang->priority = $lang->priority_srl = '优先顺序';
     $lang->type = $lang->type_srl = '种类';
     $lang->component = $lang->component_srl = '构件';
     $lang->assignee = '所有者';
     $lang->status = '状态';
     $lang->action = '动作';

     $lang->history_format_not_source = '<span class="key">[key]</span>修改为<span class="target">[target]</span>';
     $lang->history_format = '<span class="key">[key]</span>，从<span class="source">[source]</span>修改为<span class="target">[target]</span>';

     $lang->project = '项目';

     $lang->deadline = '完成期限';
     $lang->name = '名称';
     $lang->complete = '完成';
     $lang->completed_date = '结束日';
     $lang->order = '顺序';
     $lang->package = $lang->package_srl = '程序包';
     $lang->release = $lang->release_srl = '发布版';
     $lang->release_note = '发布记录';
     $lang->release_changes = '更新日志';
     $lang->occured_version = $lang->occured_version_srl = '发生版本';
     $lang->attached_file = '附件';
     $lang->filename = '文件名';
     $lang->filesize = '文件大小';

     $lang->status_list = array(
             'new' => '新建',
             'reviewing' => '审查中',
             'assign' => '分配',
             'resolve' => '解决',
             'reopen' => '再发',
             'postponed' => '保留',
             'duplicated' => '重复',
             'invalid' => '不是问题',
    );

     $lang->about_milestone = '设置开发计划。';
     $lang->about_priority = '设置优先顺序。';
     $lang->about_type = '设置问题种类。 (ex. 问题, 改善项目)';
     $lang->about_component = '设置问题构件。';

     $lang->project_menus = array(
             'dispIssuetrackerViewIssue' => '查看问题',
             'dispIssuetrackerNewIssue' => '提交问题',
             'dispIssuetrackerViewMilestone' => '开发计划',
             'dispIssuetrackerViewSource' => '查看代码',
             'dispIssuetrackerDownload' => '下载',
             'dispIssuetrackerAdminProjectSetting' => '设置',
    );

    $lang->msg_not_attched = '请添加附件。';
    $lang->msg_attached = '文件已添加。';
    $lang->msg_no_releases = '没有被添加的发布版。';

    $lang->cmd_document_do = '将吧此问题.. ';
    $lang->not_assigned = '没有分配';
    $lang->not_assigned_description = '没被分配的问题目录';
?>
