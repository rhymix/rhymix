<?php
    /**
     * @file   modules/point/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Default language pack of point module
     **/

    $lang->point = "Point"; 
    $lang->level = "Level"; 

    $lang->about_point_module = "You can grant points on writing/adding comments/uploading/downloading.<br />But point module only configure settings, and the point will be accumulated only when point addon is activated";
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

    $lang->level_point_calc = 'Point Calculation per Point';
    $lang->expression = 'Please input Javascript formula by using level variable <b>i</b>. ex) Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = 'Calculate';
    $lang->cmd_exp_reset = 'Reset';

    $lang->cmd_point_recal = '포인트 초기화';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일/회원가입 점수만 이용하여 모든 포인트 점수를 초기화 합니다.<br />회원 가입 점수는 초기화 후 해당 회원이 활동을 하면 부여되고 그 전에는 부여되지 않습니다.<br />데이터 이전등을 하여 포인트를 완전히 초기화 해야 할 경우에만 사용하세요.';

    $lang->point_link_group = 'Group Change by Level';
    $lang->about_point_link_group = 'If you specify level for a specific group, users are assigned into the group when they adavnce to the level by getting points. When new group is assigned, the user is removed from the former assigned group.';

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


    $lang->cmd_point_config = 'Default Setting';
    $lang->cmd_point_module_config = 'Module Setting';
    $lang->cmd_point_act_config = 'Act Setting';
    $lang->cmd_point_member_list = 'Member Point List';

    $lang->msg_cannot_download = "You don't have enough point to download";

    $lang->point_recal_message = 'Adjusting Point. (%d / %d)';
    $lang->point_recal_finished = 'Point recalculation is finished.';
?>
