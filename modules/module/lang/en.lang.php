<?php
    /**
     * @file   modules/module/lang/en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English language pack 
     **/

    $lang->module_list = "Module List";
    $lang->module_index = "Module List";
    $lang->module_category = "Module Category";
    $lang->module_info = "Info";
    $lang->add_shortcut = "Add Shortcuts";
    $lang->module_action = "Actions";
    $lang->module_maker = "Module Developer";
    $lang->module_history = "Update history";
    $lang->category_title = "Category Title";
    $lang->header_text = 'Header Text';
    $lang->footer_text = 'Footer Text';
    $lang->use_category = 'Enable Category';
    $lang->category_title = 'Category Title';
    $lang->checked_count = 'Number of checked article';
    $lang->skin_default_info = 'Default Skin Info';
    $lang->skin_maker = 'Skin Developer';
    $lang->skin_maker_homepage = "Skin Developer's Homepage";
    $lang->open_rss = 'Open RSS';
    $lang->open_rss_types = array(
        'Y' => 'Open all',
        'H' => 'Open summary',
        'N' => 'Not open',
    );
    $lang->module_copy = "Copy module";

    $lang->cmd_add_shortcut = "Add Shortcut";
    $lang->cmd_install = "Install";
    $lang->cmd_update = "Update";
    $lang->cmd_manage_category = 'Manage Categories';
    $lang->cmd_manage_grant = 'Manage Permission';
    $lang->cmd_manage_skin = 'Manage Skins';
    $lang->cmd_manage_document = 'Manage Articles';

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
    $lang->about_default = "If checked, the default will be shown when access to the site without no mid value(mid=NoValue).";
    $lang->about_module_category = "It enables you to manage it through module category.\n The URL for the module manager is <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manage module > Module category </a>.";
    $lang->about_description= 'It is the description only for a manager.';
    $lang->about_default = 'If checked, this module will be shown when users access to the site without mid value (mid=NoValue).';
    $lang->about_header_text = 'The contents will be shown on the top of the module.(html tags available)';
    $lang->about_footer_text = 'The contents will be shown on the bottom of the module.(html tags available)';
    $lang->about_skin = 'You may choose a module skin.';
    $lang->about_use_category = 'If checked, category function will be enabled.';
    $lang->about_list_count = 'You can set the number of limit to show article in a page.(default is 1)';
    $lang->about_page_count = 'You can set the number of page link to move pages in a bottom of page.(default is 10)';
    $lang->about_admin_id = 'You can grant a manager to have all permissions to the module.\n You can enter multiple IDs using <br />,(comma) \n(but the module manager cannot access the site admin page.)';
    $lang->about_grant = 'If you disable all objects having specific permissions, all members even not logined may have permission.';
    $lang->about_open_rss = 'You can select RSS on the current module to be open to the public.\nNo matter the view permission of article , RSS will be open to the public by its option.';
    $lang->about_module = "Zeroboard XE consists of modules except basic library.\n [Module Manage] module will show all installed modules and help you to manage them.\nThrough [Add Shortcuts] feature, you can manage frequently used modules easily.";

	$lang->about_extra_vars_default_value = 'If multiple default values are needed,	 you can link them with comma(,).';
?>
