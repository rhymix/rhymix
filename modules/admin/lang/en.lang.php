<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only basic words are included here)
     **/

    $lang->admin_info = 'Administrator Info';
    $lang->admin_index = 'Index Admin Page';
    $lang->control_panel = 'Control panel';

    $lang->module_category_title = array(
        'service' => 'Service Setting',
        'member' => 'Member Setting',
        'content' => 'Content Setting',
        'statistics' => 'Statistics',
        'construction' => 'Construction',
        'utility' => 'Utility Setting',
        'interlock' => 'Interlock Setting',
        'accessory' => 'Accessories',
        'migration' => 'Data Migration',
        'system' => 'System Setting',
    );

    $lang->newest_news = "Latest News";
    
    $lang->env_setup = "Setting";
    $lang->default_url = "Default URL";
    $lang->about_default_url = "If you use a virtual site feature (e.g., cafeXE), input default URL (parent-site's address), then SSO would be enabled, thus connection to documents/modules works properly. ";

    $lang->env_information = "Environment Information";
    $lang->current_version = "Current Version";
    $lang->current_path = "Installed Path";
    $lang->released_version = "Latest Version";
    $lang->about_download_link = "New version of Zerboard XE is now available.\nClick the download link to get the latest version.";
    
    $lang->item_module = "Module List";
    $lang->item_addon  = "Addon List";
    $lang->item_widget = "Widget List";
    $lang->item_layout = "Layout List";

    $lang->module_name = "Module Name";
    $lang->addon_name = "Addon Name";
    $lang->version = "Version";
    $lang->author = "Developer";
    $lang->table_count = "Number of Table";
    $lang->installed_path = "Installed Path";

    $lang->cmd_shortcut_management = "Edit Menu";

    $lang->msg_is_not_administrator = 'Administrator only';
    $lang->msg_manage_module_cannot_delete = 'Shortcuts of module, addon, layout, widget cannot be removed';
    $lang->msg_default_act_is_null = 'Shortcut could not be registered because default admin Action is not set';

    $lang->welcome_to_xe = 'Welcome to the admin page of XE';
    $lang->about_admin_page = "Admin page is still being developing,\nWe will add essential contents by accepting many good suggestions during Closebeta.";
    $lang->about_lang_env = "To apply selected language as default language, click the [Save] button.";

    $lang->xe_license = 'XE complies with the GPL';
    $lang->about_shortcut = 'You may remove shortcuts of modules which are registered on frequently using module list';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "Language";
    $lang->about_cmd_lang_select = "Selected languages only will be serviced";
    $lang->about_recompile_cache = "You can arrange useless or invalid cache files";
    $lang->use_ssl = "Use SSL";
    $lang->ssl_options = array(
        'none' => "Not use",
        'optional' => "optional",
        'always' => "always"
    );
    $lang->about_use_ssl = "If you choose 'optional', SSL will be used for actions such as sign up / changing information. And for 'always', your site will be served only via https.";
    $lang->server_ports = "Server port";
    $lang->about_server_ports = "If your web-server uses other than 80 for HTTP, 443 for HTTPS, you should specify server ports";
    $lang->use_db_session = '인증 세션 DB 사용';
    $lang->about_db_session = '인증시 사용되는 PHP 세션을 DB로 사용하는 기능입니다.<br/>웹서버의 사용율이 낮은 사이트에서는 비활성화시 사이트 응답 속도가 향상될 수 있습니다';
?>
