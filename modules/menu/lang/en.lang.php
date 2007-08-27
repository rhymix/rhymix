<?php
    /**
     * @file   modules/menu/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Menu module's basic language pack
     **/

    $lang->cmd_menu_insert = 'Create menu';
    $lang->cmd_menu_management = 'Menu management';

    $lang->menu = 'Menu'; 
    $lang->menu_count = 'No. of menu';
    $lang->menu_management = 'Menu management';
    $lang->depth = 'Step';
    $lang->parent_menu_name = 'Parent menu name';
    $lang->menu_name = 'Menu name';
    $lang->menu_srl = 'Menu SRL';
    $lang->menu_id = 'Menu ID';
    $lang->menu_url = 'Menu URL';
    $lang->menu_open_window = 'Open a new window';
    $lang->menu_expand = 'Expand';
    $lang->menu_img_btn = 'Image button';
    $lang->menu_normal_btn = 'Normal';
    $lang->menu_hover_btn = 'Mouse over';
    $lang->menu_active_btn = 'When selected';
    $lang->menu_group_srls = 'Accessable groups';
    $lang->layout_maker = "Layout maker";
    $lang->layout_history = "Update history ";
    $lang->layout_info = "Layout info";
    $lang->layout_list = 'Layout list';
    $lang->downloaded_list = 'Downloaded list';
    $lang->limit_menu_depth = 'Display enabled';

    $lang->cmd_make_child = 'Add a child menu';
    $lang->cmd_move_to_installed_list = "View created lists";
    $lang->cmd_enable_move_menu = "Move menu (Drag the top menu after selecting)";
    $lang->cmd_search_mid = "Search mid";

    $lang->msg_cannot_delete_for_child = 'Cannot delete a menu having a child menu.';

    $lang->about_title = 'Please input the title that is easy to verify when connecting to module.';
    $lang->about_menu_management = "Menu management enables you to consist menu in the selected layout.\nYou can create menu upto setted depth and can enter information in details by clicking the menu.\nMenu will be expanded by cliking the folder image.\nIf menu is not shown normally, refresh the information by clicking the button \"Re-create cache file\".\n* Menu created over the depth limit may not be shown properly.";
    $lang->about_menu_name = 'The name will be shown as a menu name if it is not an admin or image button.';
    $lang->about_menu_url = "It is the menu URL when select the menu.<br />You may enter only id value to link to other module.<br />If no contents exist, nothing will happen even though you click the menu.";
    $lang->about_menu_open_window = 'You can assign it to open a page in a new window when the menu clicked.';
    $lang->about_menu_expand = 'It enables the menu to remain expanded when the tree menu(tree_menu.js) is used.';
    $lang->about_menu_img_btn = 'If you register an image button, the image button will automatically replace the text button, and it will be shown in the layout.';
    $lang->about_menu_group_srls = 'If you select a group, only the group members can see the menu. (if xml file is directly opened, it will be shown.)';

    $lang->about_menu = "Menu module will help you to create a complete site through the convenient menu management which arranges created modules and links to layouts without any manual works..\nMenu is not a site manager, but it just has information which can link to modules and layouts so you can express different types of menu.";
?>
