<?php
    /**
     * @file   en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English Language Pack (Only basic words are included here)
     **/

    $lang->admin_info = 'Administrator Info';
    $lang->admin_index = 'Index Admin Page';
    $lang->control_panel = 'Dashboard';
    $lang->start_module = 'Default Module';
    $lang->about_start_module = 'You can specify default module of the site.';

    $lang->module_category_title = array(
        'service' => 'Services',
        'member' => 'Members',
        'content' => 'Contents',
        'statistics' => 'Statistics',
        'construction' => 'Construction',
        'utility' => 'Utilities',
        'interlock' => 'Embedded',
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
    $lang->about_download_link = "New version of Zerboard XE is now available!\nPlease click the download link to get the latest version.";
    
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

    $lang->msg_is_not_administrator = 'Administrator Only';
    $lang->msg_manage_module_cannot_delete = 'Shortcuts of module, addon, layout, widget cannot be removed';
    $lang->msg_default_act_is_null = 'Shortcut could not be registered because default admin Action is not set';

    $lang->welcome_to_xe = 'Welcome to the admin page of XE';
    $lang->about_admin_page = "Admin page is still under development,\nWe will add essential contents by accepting many good suggestions during Closebeta.";
    $lang->about_lang_env = "To apply selected language as default language, click on the Save button.";

    $lang->xe_license = 'XE complies with the GPL';
    $lang->about_shortcut = 'You may remove shortcuts of modules which are registered on frequently using module list';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "Language";
    $lang->about_cmd_lang_select = "Only selected languages will be served.";
    $lang->about_recompile_cache = "You can delete useless or invalid cache files.";
    $lang->use_ssl = "Use SSL";
    $lang->ssl_options = array(
        'none' => "Never",
        'optional' => "Optional",
        'always' => "Always"
    );
    $lang->about_use_ssl = "In case of 'Optional', SSL will be used for actions such as signing up / changing information. And for 'Always', your site will be served only via https.";
    $lang->server_ports = "Server Port";
    $lang->about_server_ports = "If your web server does not use 80 for HTTP or 443 for HTTPS port, you should specify server ports";
    $lang->use_db_session = 'Use Session DB';
    $lang->about_db_session = 'It will use php session with DB when authenticating.<br/>Websites with infrequent usage of web server may expect faster response when this function is disabled.<br/>However session DB will make it unable to get current users, so you cannot use related functions.';
    $lang->sftp = "Use SFTP";
    $lang->ftp_get_list = "Get List";
    $lang->ftp_remove_info = 'Remove FTP Info.';
	$lang->msg_ftp_invalid_path = 'Failed to read the specified FTP Path.';
	$lang->msg_self_restart_cache_engine = 'Please restart Memcached or cache daemon.';
	$lang->mobile_view = 'Mobile View';
	$lang->about_mobile_view = 'Mobile View will display the best layout when accessing with smartphones.';
    $lang->autoinstall = 'EasyInstall';

    $lang->last_week = 'Last week';
    $lang->this_week = 'This week';
?>
