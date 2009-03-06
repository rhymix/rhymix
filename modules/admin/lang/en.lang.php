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
    $lang->default_url = "기본 URL";
    $lang->about_default_url = "XE 가상 사이트(cafeXE등)의 기능을 사용할때 기본 URL을 입력해 주셔야 가상 사이트간 인증 연동이 되고 게시글/모듈등의 연결이 정상적으로 이루어집니다. (ex: http://도메인/설치경로)";

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
?>
