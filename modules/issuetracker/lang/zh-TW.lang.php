<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Issuetracker模組語言包
     **/

     $lang->issuetracker = '問題追蹤';
     $lang->about_issuetracker = '里程碑管理，代碼，問題與發佈等問題追蹤。';

     $lang->cmd_project_list = '專案目錄';
     $lang->cmd_view_info = '專案資訊';
     $lang->cmd_project_setting = '專案設置';
     $lang->cmd_release_setting = '發佈設置';
     $lang->cmd_insert_package = '新增套裝包';
     $lang->cmd_insert_release = '新增發佈版';
     $lang->cmd_attach_file = '新增附件';
     $lang->cmd_display_item = '顯示專案';

     $lang->cmd_resolve_as = '修改狀態';
     $lang->cmd_reassign = '修改所有者';
     $lang->cmd_accept = '接受';

     $lang->svn_url = 'SVN地址';
     $lang->about_svn_url = '請輸入專案的SVN地址。';
     $lang->svn_cmd = 'SVN應用程式位置';
     $lang->about_svn_cmd = '請輸入svn client應用程式位置。(ex: /usr/bin/svn)';
     $lang->diff_cmd = 'DIF應用程式位置';
     $lang->about_diff_cmd = '為了比較 SVN revisions，請輸入diff應用程式位置。 (ex: /usr/bin/diff)';

     $lang->issue = '問題';
     $lang->total_issue = '全部問題';
     $lang->milestone = $lang->milestone_srl = '里程碑';
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
     $lang->completed_date = '結束日';
     $lang->order = '順序';
     $lang->package = $lang->package_srl = '套裝包';
     $lang->release = $lang->release_srl = '發佈版';
     $lang->release_note = '發佈記錄';
     $lang->release_changes = '更新日誌';
     $lang->occured_version = $lang->occured_version_srl = '目前版本';
     $lang->attached_file = '附件';
     $lang->filename = '檔案名稱';
     $lang->filesize = '檔案大小';

     $lang->status_list = array(
             'new' => '新建',
             'reviewing' => '審查中',
             'assign' => '分配',
             'resolve' => '解決',
             'reopen' => '重新開始',
             'postponed' => '保留',
             'duplicated' => '重複',
             'invalid' => '無效',
    );

     $lang->about_milestone = '設置開發計劃。';
     $lang->about_priority = '設置優先順序。';
     $lang->about_type = '設置問題種類。 (ex. 問題, 改善項目)';
     $lang->about_component = '設置問題組件。';

     $lang->project_menus = array(
             'dispIssuetrackerViewIssue' => '檢視問題',
             'dispIssuetrackerNewIssue' => '提交問題',
             'dispIssuetrackerViewMilestone' => '開發計劃',
             'dispIssuetrackerViewSource' => '檢視代碼',
             'dispIssuetrackerDownload' => '下載',
             'dispIssuetrackerAdminProjectSetting' => '設置',
    );

    $lang->msg_not_attched = '請新增附件。';
    $lang->msg_attached = '檔案已新增。';
    $lang->msg_no_releases = '沒有被新增的發佈版。';

    $lang->cmd_document_do = '將把此問題.. ';
    $lang->not_assigned = '沒有分配';
    $lang->not_assigned_description = '沒被分配的問題目錄';
?>
