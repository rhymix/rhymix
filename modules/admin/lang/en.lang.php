<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only basic words are included here)
     **/

    $lang->admin_info = '관리자 정보';
    $lang->admin_index = '관리자 초기 페이지';

    $lang->module_category_title = array(
        'service' => '서비스형 모듈',
        'manager' => '관리형 모듈',
        'utility' => '기능성 모듈',
        'accessory' => '부가 모듈',
        'base' => '기본 모듈',
    );

    $lang->newest_news = "Latest News";
    
    $lang->env_setup = "Setting";

    $lang->env_information = "Environment Information";
    $lang->current_version = "Current Version";
    $lang->current_path = "Installed Path";
    $lang->released_version = "Latest Version";
    $lang->about_download_link = "Newer version of Zerboard XE is available. To download the latest version, click download link.";
    
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

    $lang->welcome_to_zeroboard_xe = 'Welcome to the admin page of Zeroboard XE';
    $lang->about_admin_page = "Admin page is still being developing,\nWe will add essential contents by accepting many good suggestions during Closebeta.";
    $lang->about_lang_env = "To apply selected language set to users as default, click the [Save] button after changing it.";

    $lang->zeroboard_xe_user_links = 'Links for Users';
    $lang->zeroboard_xe_developer_links = 'Links for Developers';

    $lang->xe_user_links = array(
        'Official Website' => 'http://www.zeroboard.com',
        //'Close Beta website' => 'http://spring.zeroboard.com',
        //'Module morgue' => 'http://www.zeroboard.com',
        //'Addon morgue' => 'http://www.zeroboard.com',
        //'Widget morgue' => 'http://www.zeroboard.com',
        //'Module Skin morgue' => 'http://www.zeroboard.com',
        //'Widget Skin morgue' => 'http://www.zeroboard.com',
        //'Layout Skin morgue' => 'http://www.zeroboard.com',
    );

    $lang->xe_developer_links = array(
        //'Manual' => 'http://www.zeroboard.com/wiki/manual',
        "Developer's forum" => 'http://spring.zeroboard.com',
        'Issue Tracking' => 'http://trac.zeroboard.com',
        'SVN Repository' => 'http://svn.zeroboard.com',
        'doxygen document' => 'http://doc.zeroboard.com',
        'PDF Documentation' => 'http://doc.zeroboard.com/zeroboard_xe.pdf',
    );

    $lang->zeroboard_xe_usefulness_module = 'Useful Modules';
    $lang->xe_usefulness_modules = array(
        'dispEditorAdminIndex' => 'Editor Manager',
        'dispDocumentAdminList' => 'Article Manager',
        'dispCommentAdminList' => 'Comment Manager',
        'dispFileAdminList' => 'Attachment Manager',
        'dispPollAdminList' => 'Poll Manager',
        'dispSpamfilterAdminConfig' => 'Spam Filter Manager',
        'dispCounterAdminIndex' => 'Counter Log',

    );

    $lang->xe_license = 'Zeroboard XE complies with the GPL';
    $lang->about_shortcut = 'You may remove shortcuts of modules which are registered on frequently using module list';
?>
