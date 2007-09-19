<?php
    /**
     * @file   modules/menu/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  菜单(menu) 模块的基本语言包
     **/

    $lang->cmd_menu_insert = '生成菜单';
    $lang->cmd_menu_management = '菜单设置';

    $lang->menu = '菜单'; 
    $lang->menu_count = '菜单数';
    $lang->menu_management = '菜单管理';
    $lang->depth = '层级';
    $lang->parent_menu_name = '上级菜单名';
    $lang->menu_name = '菜单名';
    $lang->menu_srl = '菜单固有编号';
    $lang->menu_id = '菜单名称';
    $lang->menu_url = '链接';
    $lang->menu_open_window = '新窗口';
    $lang->menu_expand = '展开';
    $lang->menu_img_btn = '图片按钮';
    $lang->menu_normal_btn = '普通';
    $lang->menu_hover_btn = '鼠标滑过';
    $lang->menu_active_btn = '激活链接';
    $lang->menu_group_srls = '用户组';
    $lang->layout_maker = "布局作者";
    $lang->layout_history = "更新列表 ";
    $lang->layout_info = "布局信息";
    $lang->layout_list = '布局目录';
    $lang->downloaded_list = '下载目录';
    $lang->limit_menu_depth = '可显示';

    $lang->cmd_make_child = '添加下级菜单';
    $lang->cmd_move_to_installed_list = "查看生成目录";
    $lang->cmd_enable_move_menu = "移动菜单 （选择后用鼠标拖动）";
    $lang->cmd_search_mid = "查找 mid";

    $lang->msg_cannot_delete_for_child = '有下级菜单的菜单不能删除。';

    $lang->about_title = '请输入连接模块时容易区分的标题。';
    $lang->about_menu_management = "菜单管理可以构筑被选布局中使用的菜单。\n不仅可以构筑指定级(层级)菜单，点击输入的菜单项即可对其详细设置。\n点击菜单前置图标可以展开菜单。\n偶尔不能正常显示菜单时，请按\"缓冲文件再生成\"按钮更新信息。\n* 可能不能正常显示指定级(层级)以上的菜单。";
    $lang->about_menu_name = '输入不是图片按钮时显示为菜单名标题。';
    $lang->about_menu_url = "点击菜单时要移动的URL。<br />连接其他模块时只输入id值即可。<br />没有内容时点击菜单也不会有什么动作。";
    $lang->about_menu_open_window = '可以设置点击菜单时是否要在新窗口中打开。';
    $lang->about_menu_expand = '使用树型（tree_menu.js）菜单时总是呈展开状态。';
    $lang->about_menu_img_btn = '如登录图片按钮，在布局中自动替换显示为图片按钮。';
    $lang->about_menu_group_srls = '如选择用户组，只有所属组用户才能看到此菜单。 —（xml文件中不能隐藏）';

    $lang->about_menu = "菜单模块可以通过菜单管理器整理已生成的模块并同布局相连接来轻松建设一个完整的网站。\n菜单模块虽然具有连接模块和布局并通过布局来显示多种形态菜单的信息，但它不具备管理网站的功能。";
?>