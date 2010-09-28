<?php
    /**
     * @file   modules/document/lang/zh-CN.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  文章(document)模块语言包
     **/

    $lang->document_list = '主题列表';
    $lang->thumbnail_type = '缩略图生成方式';
    $lang->thumbnail_crop = '裁减(按指定大小裁剪图片)';
    $lang->thumbnail_ratio = '比例(按原图比例缩略处理)';
    $lang->cmd_delete_all_thumbnail = '删除全部缩略图';
    $lang->title_bold = '粗标题';
    $lang->title_color = '标题颜色';
    $lang->new_document_count = '新帖';

    $lang->parent_category_title = '上级分类名';
    $lang->category_title = '分类名';
    $lang->category_color = '分类颜色';
    $lang->expand = '展开';
    $lang->category_group_srls = '用户组';
    
    $lang->cmd_make_child = '添加下级分类';
    $lang->cmd_enable_move_category = "分类顺序(勾选后用鼠标拖动分类项)";
    
    $lang->about_category_title = '请输入分类名。';
    $lang->about_expand = '选择此项将维持展开状态。';
    $lang->about_category_group_srls = '所选用户组才可以查看此分类。';
    $lang->about_category_color = '请指定分类颜色（必须带#符号）。ex）#ff0000';

    $lang->cmd_search_next = '继续搜索';

    $lang->cmd_temp_save = '临时保存';

    $lang->cmd_toggle_checked_document = '反选';
    $lang->cmd_delete_checked_document = '删除所选';
    $lang->cmd_document_do = '将把此主题..';

    $lang->msg_cart_is_null = '请选择要删除的文章。';
    $lang->msg_category_not_moved = '不能移动！';
    $lang->msg_is_secret = '这是密帖！';
    $lang->msg_checked_document_is_deleted = '删除了%d个文章。';

    $lang->move_target_module = '目标模块';

    // 管理页面查找的对象
    $lang->search_target_list = array(
        'title' => '标题',
        'content' => '内容',
        'user_id' => 'I D',
        'member_srl' => '会员编号',
        'user_name' => '姓名',
        'nick_name' => '昵称',
        'email_address' => '电子邮件',
        'homepage' => '主页',
        'is_notice' => '公告',
        'is_secret' => '密帖',
        'tags' => '标签',
        'readed_count' => '查看数（以上）',
        'voted_count' => '推荐数（以上）',
        'comment_count ' => '评论数（以上）',
        'trackback_count ' => '引用数（以上）',
        'uploaded_count ' => '上传附件数（以上）',
        'regdate' => '登录日期',
        'last_update' => '最近更新日期',
        'ipaddress' => 'IP 地址',
    );

    $lang->alias = "Alias";
    $lang->history = "历史版本功能";
    $lang->about_use_history = "启用历史版本功能它将记录主题修改版本，并还可以复原到之前版本。";
    $lang->trace_only = "只留痕迹";

    $lang->cmd_trash = "回收箱";
    $lang->cmd_restore = "复原";
    $lang->cmd_restore_all = "全部复原";

    $lang->in_trash = "回收箱";
    $lang->trash_nick_name = "操作人昵称";
    $lang->trash_date = "删除日期";
    $lang->trash_description = "说明";

    // 管理页面回收箱搜索对象
    $lang->search_target_trash_list = array(
        'title' => '标题',
        'content' => '内容',
        'user_id' => '用户名',
        'member_srl' => '会员编号',
        'user_name' => '姓名',
        'nick_name' => '昵称',
        'trash_member_srl' => '操作人会员编号',
        'trash_user_name' => '操作人用户名',
        'trash_nick_name' => '操作人昵称',
        'trash_date' => '删除日期',
        'trash_ipaddress' => '操作人IP地址',
    );

    $lang->success_trashed = '已成功移除到回收箱。';
    $lang->msg_not_selected_document = '선택된 문서가 없습니다.';
?>
