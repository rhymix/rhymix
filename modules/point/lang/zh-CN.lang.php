<?php
    /**
     * @file   modules/point/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  积分 (point) 模块简体中文语言包
     **/

    $lang->point = "积分"; 
    $lang->level = "级别"; 

    $lang->about_point_module = "积分系统可以在发表/删除新帖，发表/删除评论，上传/下载/删除/文件等动作时，付与其相应的积分。<br />积分系统模块只能设置各项积分，不能记录积分。只有激活积分插件后才可以正常记录相关积分。";
    $lang->about_act_config = "版面，博客等模块都有发表/删除新帖，发表/删除评论等动作。 <br />要想与版面/博客之外的模块关联积分功能时，添加与其各模块功能相适合的act值即可。";

    $lang->max_level = '最高级别';
    $lang->about_max_level = '可以指定最高级别。级别共设1000级，因此制作级别图标时要好好考虑一下。';

    $lang->level_icon = '级别图标';
    $lang->about_level_icon = '级别图标要以 ./modules/point/icons/级别.gif形式指定，有时出现最高级别的图标跟您指定的最高级别图标不同的现象，敬请注意。';

    $lang->point_name = '积分名';
    $lang->about_point_name = '可以指定积分名或积分单位。';

    $lang->level_point = '级别积分';
    $lang->about_level_point = '积分达到或减少到下列各级别所设置的积分值时，将会自动调节相应级别。';

    $lang->disable_download = '禁止下载';
    $lang->about_disable_download = '没有积分时，将禁止下载。 (图片除外)';

    $lang->level_point_calc = '计算级别积分';
    $lang->expression = '使用级别变数<b>"i"</b>输入JS数学函数。例: Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = '计算';
    $lang->cmd_exp_reset = '初始化';

    $lang->cmd_point_recal = '重新计算积分';
    $lang->about_cmd_point_recal = '文章/评论/附件等从新检查后按相应设置从新计算积分。';

    $lang->point_link_group = '그룹 연동';
    $lang->about_point_link_group = '그룹에 원하는 레벨을 지정하면 해당 레벨에 도달할때 그룹이 변경됩니다. 단 새로운 그룹으로 변경될때 이전에 자동 등록된 그룹은 제거됩니다.';

    $lang->about_module_point = '可以分别对各模块进行积分设置，没有被设置的模块将使用默认值。<br />所有积分在相反动作下恢复原始值。即：发表新帖后再删除得到的积分为0分。';

    $lang->point_signup = '注册';
    $lang->point_insert_document = '发表新帖';
    $lang->point_delete_document = '删除主题';
    $lang->point_insert_comment = '发表评论';
    $lang->point_delete_comment = '删除评论';
    $lang->point_upload_file = '上传文件';
    $lang->point_delete_file = '删除文件';
    $lang->point_download_file = '下载文件 (图片除外)';
    $lang->point_read_document = '게시글 조회';


    $lang->cmd_point_config = '基本设置';
    $lang->cmd_point_module_config = '对象模块设置';
    $lang->cmd_point_act_config = '功能act设置';
    $lang->cmd_point_member_list = '会员积分目录';

    $lang->msg_cannot_download = '积分不足无法下载！';

    $lang->point_recal_message = '计算中. (%d / %d)';
    $lang->point_recal_finished = '所有会员积分从新计算完毕。';
?>
