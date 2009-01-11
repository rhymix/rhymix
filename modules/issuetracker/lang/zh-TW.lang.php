<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com) 翻譯：royallin
     * @brief  問題追蹤模組語言
     **/

     $lang->issuetracker = '問題追蹤';
     $lang->about_issuetracker = '版本管理，原始碼，問題與發佈等問題追蹤。';

     $lang->cmd_project_list = '專案清單';
     $lang->cmd_view_info = '專案資訊';
     $lang->cmd_project_setting = '專案設置';
     $lang->cmd_release_setting = '發佈設置';
     $lang->cmd_insert_package = '新增套裝包';
     $lang->cmd_insert_release = '新增發佈版';
     $lang->cmd_attach_file = '新增附加檔案';
     $lang->cmd_display_item = '顯示專案';

     $lang->cmd_resolve_as = '修改狀態';
     $lang->cmd_reassign = '修改所有者';
     $lang->cmd_accept = '接受';

     $lang->svn_url = 'SVN位址';
     $lang->about_svn_url = '請輸入專案的 SVN 位址。';
     $lang->svn_cmd = 'SVN應用程式位置';
     $lang->about_svn_cmd = '請輸入 SVN Client 應用程式位置。(ex: /usr/bin/svn)';
     $lang->diff_cmd = 'DIFF應用程式位置';
     $lang->about_diff_cmd = '為了比較 SVN 版本，請輸入 diff 應用程式位置。 (ex: /usr/bin/diff)';
     $lang->svn_userid = 'SVN帳號';
     $lang->about_svn_userid = '必須要驗證時，請輸入帳號來登入 SVN 檔案庫';
     $lang->svn_passwd = 'SVN密碼';
     $lang->about_svn_passwd = '必須要驗證時，請輸入密碼來登入 SVN 檔案庫';

     $lang->issue = '問題';
     $lang->total_issue = '所有問題';
     $lang->milestone = $lang->milestone_srl = '版本';
     $lang->priority = $lang->priority_srl = '優先順序';
     $lang->type = $lang->type_srl = '種類';
     $lang->component = $lang->component_srl = '組件';
     $lang->assignee = '所有者';
     $lang->status = '狀態';
     $lang->action = '動作';

     $lang->history_format_not_source = '<span class="key">[key]</span>修改為<span class="target">[target]</span>';
     $lang->history_format = '<span class="key">[key]</span>，從<span class="source">[source]</span>修改為<span class="target">[target]</span>';

     $lang->project = '專案';

     $lang->deadline = '完成期限';
     $lang->name = '名稱';
     $lang->complete = '完成';
     $lang->completed_date = '結束日期';
     $lang->order = '順序';
     $lang->package = $lang->package_srl = '套裝包';
     $lang->release = $lang->release_srl = '發佈版';
     $lang->release_note = '發佈記錄';
     $lang->release_changes = '更新日誌';
     $lang->occured_version = $lang->occured_version_srl = '目前版本';
     $lang->attached_file = '附加檔案';
     $lang->filename = '檔案名稱';
     $lang->filesize = '檔案大小';

     $lang->status_list = array(
             'new' => '新建',
             'reviewing' => '審查',
             'assign' => '分配',
             'resolve' => '解決',
             'reopen' => '重新開始',
             'postponed' => '保留',
             'duplicated' => '重複',
             'invalid' => '無效',
    );

     $lang->about_milestone = '設置開發計劃。';
     $lang->about_priority = '設置優先順序。';
     $lang->about_type = '設置問題種類。 (例如：問題，改善項目)';
     $lang->about_component = '設置問題組件。';

     $lang->project_menus = array(
             'dispIssuetrackerViewMilestone' => '版本開發',
             'dispIssuetrackerViewIssue' => '問題清單',
             'dispIssuetrackerNewIssue' => '發表問題',
             'dispIssuetrackerTimeline' => '時間軸',
             'dispIssuetrackerViewSource' => '檢視原始碼',
             'dispIssuetrackerDownload' => '下載',
             'dispIssuetrackerAdminProjectSetting' => '設置',
    );

    $lang->msg_not_attched = '請新增附檔。';
    $lang->msg_attached = '檔案已新增。';
    $lang->msg_no_releases = '尚未被新增的發佈版本。';

    $lang->cmd_document_do = '將此問題.. ';
    $lang->not_assigned = '尚未分配';
    $lang->not_assigned_description = '尚未被分配的問題清單';
    $lang->timeline_msg = array(
        'changed' => 'changed',
        'created' => 'created'
    );
    $lang->cmd_manage_issue = 'Manage issues';
?>
