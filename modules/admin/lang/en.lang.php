<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only basic words are included here)
     **/

    $lang->item_module = "Module List";
    $lang->item_addon  = "Addon List";
    $lang->item_widget = "Widget List";
    $lang->item_layout = "Layout List";

    $lang->module_name = "Module Name";
    $lang->addon_name = "Addon name";
    $lang->version = "Version";
    $lang->author = "Author";
    $lang->table_count = "The number of Table";
    $lang->installed_path = "Installing Path";

    $lang->cmd_shortcut_management = "Edit Menues";

    $lang->msg_is_not_administrator = 'Administrator only';
    $lang->msg_manage_module_cannot_delete = 'Cannot remove shortcuts for module, addon, layout, and widget module';
    $lang->msg_default_act_is_null = 'Cannot register the shortcut, since administrator\'s default action is not specified';

    $lang->welcome_to_zeroboard_xe = 'Welcome to the admin page of Zeroboard XE';
    $lang->about_admin_page = "Admin page is still incomplete,\nbut it\'ll be filled with required contents by accepting any good suggestion during Close Beta.";

    $lang->zeroboard_xe_user_links = 'Link for users';
    $lang->zeroboard_xe_developer_links = 'Link for Developers';

    $lang->xe_user_links = array(
        'Official website' => 'http://www.zeroboard.com',
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
        'Developer\'s forum' => 'http://spring.zeroboard.com',
        'Issue Tracking' => 'http://trac.zeroboard.com',
        'SVN Repository' => 'http://svn.zeroboard.com',
        'doxygen document' => 'http://doc.zeroboard.com',
        'PDF Documentation' => 'http://doc.zeroboard.com/zeroboard_xe.pdf',
    );

    $lang->zeroboard_xe_usefulness_module = 'Useful Modules';
    $lang->xe_usefulness_modules = array(
        'dispEditorAdminIndex' => 'Editor Manager',
        'dispDocumentAdminList' => 'Entry Manager',
        'dispCommentAdminList' => 'Comment Manager',
        'dispFileAdminList' => 'Attachment Manager',
        'dispPollAdminList' => 'Poll Manager',
        'dispSpamfilterAdminConfig' => 'Spam Filter Manager',
        'dispCounterAdminIndex' => 'Counter Log',

    );

    $lang->xe_license = 'Zeroboard XE complies with the GPL';
    $lang->about_shortcut = 'You could remove module shortcut that is registered on the module frequently used';
?>
