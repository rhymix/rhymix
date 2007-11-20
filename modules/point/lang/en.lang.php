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

    $lang->level_point_calc = '레벨별 포인트 계산';
    $lang->expression = '레벨 변수 <b>i</b>를 사용하여 자바스크립트 수식을 입력하세요. 예: Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = '계산';
    $lang->cmd_exp_reset = '초기화';

    $lang->about_module_point = "You can set point for each module and modules which don't have any value will use default point.<br />All point will be restored on acting reverse.";

    $lang->point_signup = 'Signup';
    $lang->point_insert_document = 'On Writing';
    $lang->point_delete_document = 'On Deleting';
    $lang->point_insert_comment = 'On Adding Comments';
    $lang->point_delete_comment = 'On Deleting Comments';
    $lang->point_upload_file = 'On Uploading';
    $lang->point_delete_file = 'On Deleting Files';
    $lang->point_download_file = 'On Downloading Files (Exclude images)';


    $lang->cmd_point_config = 'Default Setting';
    $lang->cmd_point_module_config = 'Module Setting';
    $lang->cmd_point_act_config = 'Act Setting';
    $lang->cmd_point_member_list = 'Member Point List';

    $lang->msg_cannot_download = "You don't have enough point to download";
?>
