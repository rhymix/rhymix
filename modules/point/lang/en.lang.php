<?php
    /**
     * @file   modules/point/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Default language pack of point module
     **/

    $lang->point = "Point"; 
    $lang->level = "Level"; 

    $lang->about_point_module = "You can grant points on writing/adding comments/uploading/downloading.";
    $lang->about_act_config = "Each module like board/blog has its own actions such as \"writing/deleting/adding comments/deleting comments\".<br />You can just add act values to link modules with point system except board/blog.<br />Comma(,) will distinguish multiple values."; 

    $lang->max_level = 'Max Level';
    $lang->about_max_level = 'You may set the max level. Level icons should be considered and the level of 1000 is the maximum value you can set'; 

    $lang->level_icon = 'Level Icon';
    $lang->about_level_icon = 'Path of level icon is "./module/point/icons/[level].gif" and max level could be different with icon set. So please be careful'; 

    $lang->point_name = 'Point Name';
    $lang->about_point_name = 'You may give a name or unit for point'; 

    $lang->level_point = 'Level Point';
    $lang->about_level_point = 'Level will be adjusted when point gets to each level point or drops under each level point'; 

    $lang->disable_download = 'Prohibit Downloads';
    $lang->about_disable_download = "This will prohibit downloads when there are not enough points. (Exclude image files)"; 
    $lang->disable_read_document = 'Prohibit Readings';
    $lang->about_disable_read_document = 'Users will be unable to read articles when they do not have enough points';

    $lang->level_point_calc = 'Point Calculation per Point';
    $lang->expression = 'Please input Javascript formula by using level variable <b>i</b>. ex) Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = 'Calculate';
    $lang->cmd_exp_reset = 'Reset';

    $lang->cmd_point_recal = 'Reset Point';
	$lang->about_cmd_point_recal = 'All point will be initialized only with articles/comments/attachments/join points.<br />Only members who do website activities will get signup points after reset.<br />Please use this function when complete initialization is required in case of data transferring or other situations.';

    $lang->point_link_group = 'Group Change by Level';
    $lang->point_group_reset_and_add = 'Reset adjusted groups and add new groups';
    $lang->point_group_add_only = 'Only to new groups';
    $lang->about_point_link_group = 'If you specify level for a specific group, users are assigned into the group when they adavnce to the level by getting points.';

    $lang->about_module_point = "You can set point for each module and modules which don't have any value will use default point.<br />All point will be restored on acting reverse.";

    $lang->point_signup = 'Signup';
    $lang->point_insert_document = 'On Writing';
    $lang->point_delete_document = 'On Deleting';
    $lang->point_insert_comment = 'On Adding Comments';
    $lang->point_delete_comment = 'On Deleting Comments';
    $lang->point_upload_file = 'On Uploading';
    $lang->point_delete_file = 'On Deleting Files';
    $lang->point_download_file = 'On Downloading Files (Exclude images)';
    $lang->point_read_document = 'On Reading';
    $lang->point_voted = 'On Recommended';
    $lang->point_blamed = 'On Accused';


    $lang->cmd_point_config = 'Default Setting';
    $lang->cmd_point_module_config = 'Module Setting';
    $lang->cmd_point_act_config = 'Act Setting';
    $lang->cmd_point_member_list = 'Member Point List';

    $lang->msg_cannot_download = "You don't have enough point to download";
    $lang->msg_disallow_by_point = "You need more points to read the article (You need %d, but you have %d)";

    $lang->point_recal_message = 'Adjusting Point. (%d / %d)';
    $lang->point_recal_finished = 'Point recalculation is finished.';
?>
