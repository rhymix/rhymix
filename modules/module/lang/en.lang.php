<?php
    /**
     * @file   modules/module/lang/en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English language pack 
     **/

    $lang->module_list = "Module list";
    $lang->module_index = "Module list";
    $lang->module_category = "Module category";
    $lang->module_info = "Module info";
    $lang->add_shortcut = "Add to admin menu";
    $lang->module_action = "Action";
    $lang->module_maker = "Module maker";
    $lang->module_history = "Update history";
    $lang->category_title = "Category title";
    $lang->header_text = 'Header text';
    $lang->footer_text = 'Footer text';
    $lang->use_category = 'Enable category';
    $lang->category_title = 'Category title';
    $lang->checked_count = 'No. of checked article';
    $lang->skin_default_info = 'Default skin info';
    $lang->skin_maker = 'Skin maker';
    $lang->skin_maker_homepage = 'Skin maker homepage';
    $lang->open_rss = 'Open RSS';
    $lang->open_rss_types = array(
        'Y' => 'Open all',
        'H' => 'Open summary',
        'N' => 'Not open',
    );

    $lang->cmd_add_shortcut = "Add shortcut";
    $lang->cmd_install = "Install";
    $lang->cmd_update = "Update";
    $lang->cmd_manage_category = 'Manage categories';
    $lang->cmd_manage_grant = 'Manage grant';
    $lang->cmd_manage_skin = 'Manage skins';
    $lang->cmd_manage_document = 'Manage articles';

    $lang->msg_new_module = "Create new module";
    $lang->msg_update_module = "Modify module";
    $lang->msg_module_name_exists = "The name already exists. Please try other name.";
    $lang->msg_category_is_null = 'No registered category exists.';
    $lang->msg_grant_is_null = 'No list exists for grant.';
    $lang->msg_no_checked_document = 'No checked articles exist.';
    $lang->msg_move_failed = 'Failed to move';
    $lang->msg_cannot_delete_for_child = 'Cannot delete a category having child categories.';

    $lang->about_browser_title = "It will be shown in the browser title. It will be also used in a RSS/Trackback.";
    $lang->about_mid = "The module name will be used like http://address/?mid=ModuleName.\n(only english alphabet,numbers, and underscore(_) are allowed)";
    $lang->about_default = "If cheched, the default will be shown when access to the site without no mid value(mid=NoValue).";
    $lang->about_module_category = "It enables you to manage it through module category.\n The URL for the module manager is <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manage module > Module category </a>.";
    $lang->about_description= 'It is the description only for a manager.';
    $lang->about_default = 'If cheched, the default will be shown when access to the site without no mid value(mid=NoValue).';
    $lang->about_header_text = 'The contents will be shown on the top of the module.(html tags available)';
    $lang->about_footer_text = 'The contents will be shown on the bottom of the module.(html tags available)';
    $lang->about_skin = 'You can choose a module skin.';
    $lang->about_use_category = 'If checked, category function will be enabled.';
    $lang->about_list_count = 'You can set the number of limit to show article in a page.(default is 1)';
    $lang->about_page_count = 'You can set the number of page link to move pages in a bottom of page.(default is 10)';
    $lang->about_admin_id = 'You can grant a manager to have all permissions to the module.\n You can enter multiple IDs using <br />,(comma) \n(but the module manager cannot access the site admin page.)';
    $lang->about_grant = 'If you disable all objects having specific permissions, all members even not logined may have permission.';
    $lang->about_open_rss = 'You can select RSS on the current module to be open to the public.\nNo matter the view permission of article , RSS will be open to the public by its option.';
    $lang->about_module = "All of Zeroboard XE except the basic library consist of module.\n [Manage module] module will show all installed modules and help you to manage them.\nThrough [Add shortcut] feature, you can manage frequently used modules easily.";

    $lang->about_extra_vars_default_value = '다중/단일 선택등 기본값이 여러개가 필요한 경우 , (콤마)로 연결하시면 됩니다';
?>
