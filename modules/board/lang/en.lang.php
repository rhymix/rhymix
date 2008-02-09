<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  board modules's basic language pack
     **/

    $lang->board = "Board"; 

    $lang->except_notice = "Exclude Notices";

    $lang->cmd_manage_menu = 'Menu management';
    $lang->cmd_make_child = 'Add child category';
    $lang->cmd_enable_move_category = "Change category position (Drag the top menu after selection)";

    // Item
    $lang->parent_category_title = 'Parent category';
    $lang->category_title = 'Category';
    $lang->expand = 'Expand';
    $lang->category_group_srls = 'Accessable Group';
    $lang->search_result = 'Search result';

    // words used in button
    $lang->cmd_board_list = 'Board list';
    $lang->cmd_module_config = 'Common board setting';
    $lang->cmd_view_info = 'Board info';

    // blah blah..
    $lang->about_category_title = 'Please input category name';
    $lang->about_expand = 'By selecting this option, it will be always expanded';
    $lang->about_category_group_srls = 'Only the selected group will be able to see current categories. (Manually open xml file to expose)';
    $lang->about_layout_setup = 'You can manually modify board layout code. Insert or manage the widget code anywhere you want';
    $lang->about_board_category = 'You can make board categories.<br />When board category is broken, try rebuilding the cache file manually.';
    $lang->about_except_notice = "Notice articles will not be displayed on normal list.";
    $lang->about_board = "This module is for creating and managing boards.\nYou may select the module name from the list after creating one to configure specifically.\nPlease be careful with board's module name, since it will be the url. (ex : http://domain/zb/?mid=modulename)"; 
?>
