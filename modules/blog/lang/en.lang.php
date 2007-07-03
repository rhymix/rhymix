<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  basic language pack for blog module
     **/

    // words used in button
    $lang->cmd_blog_list = 'Blog list';
    $lang->cmd_module_config = 'Common blog setup';
    $lang->cmd_view_info = 'Blog info';
    $lang->cmd_manage_menu = 'Menu management';
    $lang->cmd_make_child = 'Add child category';
    $lang->cmd_enable_move_category = "Change category position (Drag the menu on top after selection)";
    $lang->cmd_remake_cache = 'Rebuild cache file';
    $lang->cmd_layout_setup = 'Configure layout';
    $lang->cmd_layout_edit = 'Edit layout';

    // item
    $lang->parent_category_name = 'Parent category';
    $lang->category_name = 'Category';
    $lang->expand = 'Expand';
    $lang->category_group_srls = '그룹제한'; //context 이해 불가능;;
    $lang->search_result = 'Search result';

    // blah blah..
    $lang->about_category_name = 'Input category name';
    $lang->about_expand = 'By selecting this option, it will be always expanded';
    $lang->about_category_group_srls = 'Only the selected group will be able to see current categories. (Manually open xml file to expose)';
    $lang->about_layout_setup = 'You can manually modify blog layout code. Insert or manage the widget code whereever you want';
    $lang->about_blog_category = 'You can make blog categories.<br />When blog category is broken, try rebuilding the cache file manually.';
    $lang->about_blog = "This module is for creating and managing a blog.\nBlog module uses the skin that is included in the layout. So after creating one, always use category and skin management to decorate your blog.\nIf you want to connect other boards inside the blog, use the menu module to create a menu and then connect it with the skin manager";
?>
