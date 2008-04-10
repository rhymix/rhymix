<?php
    /**
     * @file   modules/menu/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Menu module's basic language pack
     **/

    $lang->cmd_menu_insert = 'Create Menu';
    $lang->cmd_menu_management = 'Menu Management';

    $lang->menu = 'Menu'; 
    $lang->menu_count = 'No. of menu';
    $lang->menu_management = 'Menu Management';
    $lang->depth = 'Step';
    $lang->parent_menu_name = 'Parent Menu Name';
    $lang->menu_name = 'Menu Name';
    $lang->menu_srl = 'Menu Serial Number';
    $lang->menu_id = 'Menu ID';
    $lang->menu_url = 'Menu URL';
    $lang->menu_open_window = 'Open a new window';
    $lang->menu_expand = 'Expand';
    $lang->menu_img_btn = 'Image button';
    $lang->menu_normal_btn = 'Normal';
    $lang->menu_hover_btn = 'Mouse over';
    $lang->menu_active_btn = 'When selected';
    $lang->menu_group_srls = 'Accessable Groups';
    $lang->layout_maker = "Layout Maker";
    $lang->layout_history = "Update History ";
    $lang->layout_info = "Layout Info";
    $lang->layout_list = 'Layouts List';
    $lang->downloaded_list = 'Downloads List';
    $lang->limit_menu_depth = 'Display Enabled';

    $lang->cmd_make_child = 'Add a Child Menu';
    $lang->cmd_move_to_installed_list = "View Created List";
    $lang->cmd_enable_move_menu = "Move Menu (Drag the top menu after selecting)";
    $lang->cmd_search_mid = "Search mid";

    $lang->msg_cannot_delete_for_child = 'A menu with child menus cannot be deleted.';

    $lang->about_title = 'Please input the title that is easy to verify when connecting to module.';
    $lang->about_menu_management = "Menu management enables you to consist menu in the selected layout.\nYou can create menu upto setted depth and can enter information in details by clicking the menu.\nMenu will be expanded by cliking the folder image.\nIf menu is not shown normally, refresh the information by clicking the button \"Re-create cache file\".\n* Menu created over the depth limit may not be shown properly.";
    $lang->about_menu_name = 'The name will be shown as a menu name if it is not an admin or image button.';
    $lang->about_menu_url = "It is the menu URL when select the menu.<br />You may enter only id value to link to other module.<br />If no contents exist, nothing will happen even though you click the menu.";
    $lang->about_menu_open_window = 'You can assign it to open a page in a new window when the menu clicked.';
    $lang->about_menu_expand = 'It enables the menu to remain expanded when the tree menu(tree_menu.js) is used.';
    $lang->about_menu_img_btn = 'If you register an image button, the image button will automatically replace the text button, and it will be shown in the layout.';
    $lang->about_menu_group_srls = 'If you select a group, only the group members can see the menu. (if xml file is directly opened, it will be shown.)';

    $lang->about_menu = "Menu module will help you to create a complete site through the convenient menu management which arranges created modules and links to layouts without any manual works..\nMenu is not a site manager, but it just has information which can link to modules and layouts so you can express different types of menu.";

    $lang->alert_image_only = "Only image files can be registered.";
?>
