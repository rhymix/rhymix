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
    $lang->menu_url = '链接l';
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
    $lang->cmd_remake_cache = "缓冲文件再生成";
    $lang->cmd_move_to_installed_list = "查看生成目录";
    $lang->cmd_enable_move_menu = "移动菜单 （选择后用鼠标拖动）";
    $lang->cmd_search_mid = "查找 mid";

    $lang->msg_cannot_delete_for_child = '有下级菜单的菜单不能删除。';

    $lang->about_title = '请输入链接模块时容易区分的标题';
    $lang->about_menu_management = "菜单管理是将构成在选择布局里使用的菜单.\n可以构成指定的层级菜单，点击菜单时可以输入详细信息。\n点击文件夹图标可以扩展菜单\n偶尔不能正常显示菜单时请按\"缓冲文件再生成\"按钮更新信息。\n* 不能正常显示指定层级以上的菜单。";
    $lang->about_menu_name = '不是管理及图片按钮的情况将显示标题名的菜单';
    $lang->about_menu_url = "选择菜单时移动的URL。<br />链接其他模块时只输入id值。<br />没有内容时选择菜单也没有反映。";
    $lang->about_menu_open_window = '选择菜单时决定是否开启新窗口';
    $lang->about_menu_expand = '使用树型（tree_menu.js）菜单时总是展开状态';
    $lang->about_menu_img_btn = '登录图片按钮时在布局里自动替换显示图片按钮。';
    $lang->about_menu_group_srls = '选择组只有本用户组能够查看此菜单。 —（打开xml会文件会显示）';

    $lang->about_menu = "菜单模块是不需要复杂的手作业把生成的模块通过便捷的菜单管理整理帮助建设一个完整的网站。\n菜单是只有链接模块和布局的同时通过布局显示多种形态的菜单。";
?>