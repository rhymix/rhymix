<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Default Language Pack of Issuetracker
     **/

     $lang->issuetracker = 'Issue Tracker';
	 $lang->about_issuetracker = 'Issue Tracker manages milestones, codes, issues and releases';

     $lang->cmd_project_list = 'Project List';
     $lang->cmd_view_info = 'Project Info';
     $lang->cmd_project_setting = 'Project Setting';
     $lang->cmd_release_setting = 'Release Setting';
     $lang->cmd_insert_package = 'Add Package';
     $lang->cmd_insert_release = 'Add Release';
     $lang->cmd_attach_file = 'Attach File';
     $lang->cmd_display_item = 'Display Item';

     $lang->cmd_resolve_as = 'Modify Status';
     $lang->cmd_reassign = 'Modify Assignee';
     $lang->cmd_accept = 'Accept';

     $lang->svn_url = 'SVN URL';
     $lang->about_svn_url = "Please input SVN URL where project's version is managed";
     $lang->svn_cmd = 'SVN Command Location';
     $lang->about_svn_cmd = 'Please input the location of svn client to link with SVN. (ex: /usr/bin/svn)';
     $lang->diff_cmd = 'DIFF Command Location';
     $lang->about_diff_cmd = 'Please input the location of diff to compare SVN revisions. (ex: /usr/bin/diff)';

     $lang->issue = 'Issue';
     $lang->total_issue = 'All Issues';
     $lang->milestone = $lang->milestone_srl = 'Milestone';
     $lang->priority = $lang->priority_srl = 'Priority';
     $lang->type = $lang->type_srl = 'Type';
     $lang->component = $lang->component_srl = 'Components';
     $lang->assignee = 'Assignee';
     $lang->status = 'Status';
     $lang->action = 'Action';

     $lang->history_format_not_source = '<span class="key">[key]</span> Modify to <span class="target">[target]</span>';
     $lang->history_format = '<span class="key">[key]</span> Modify from <span class="source">[source]</span> to <span class="target">[target]</span>';

     $lang->project = 'Project';

     $lang->deadline = 'Deadline';
     $lang->name = 'Name';
     $lang->complete = 'Complete';
     $lang->completed_date = 'Completed Date';
     $lang->order = 'Order';
     $lang->package = $lang->package_srl = 'Package';
     $lang->release = $lang->release_srl = 'Release';
     $lang->release_note = 'Release Note';
     $lang->release_changes = 'Release Changes';
     $lang->occured_version = $lang->occured_version_srl = 'Occured Version';
     $lang->attached_file = 'Attached File';
     $lang->filename = 'File Name';
     $lang->filesize = 'File Size';

     $lang->status_list = array(
             'new' => 'New',
             'reviewing' => 'Reviewing',
             'assign' => 'Assign',
             'resolve' => 'Resolve',
             'reopen' => 'Reopen',
             'postponed' => 'Postponed',
             'duplicated' => 'Duplicated',
             'invalid' => 'Invalid',
    );

     $lang->about_milestone = 'This sets milestones.';
     $lang->about_priority = 'This sets priority.';
     $lang->about_type = 'This selects type of issues (ex. issue, development)';
     $lang->about_component = 'This sets components of issues';

     $lang->project_menus = array(
             'dispIssuetrackerViewIssue' => 'View Issue',
             'dispIssuetrackerNewIssue' => 'New Issue',
             'dispIssuetrackerViewMilestone' => 'Milestone',
             'dispIssuetrackerViewSource' => 'View Source',
             'dispIssuetrackerDownload' => 'Download',
             'dispIssuetrackerAdminProjectSetting' => 'Settings',
    );

    $lang->msg_not_attched = 'No file is attached';
    $lang->msg_attached = 'File has been attached';
    $lang->msg_no_releases = 'No release is registered';

    $lang->cmd_document_do = 'You would...';
    $lang->not_assigned = 'Unassigned';
    $lang->not_assigned_description = 'List of unassigned issues.';
?>
