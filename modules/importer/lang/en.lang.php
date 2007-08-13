<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Importer module's basic language pack
     **/

    // words for button
    $lang->cmd_sync_member = 'Synchronize';
    $lang->cmd_continue = 'Continue';

    // items
    $lang->importer = 'Transfer zeroboard datas';
    $lang->source_type = 'Previous target';
    $lang->type_member = 'Member data';
    $lang->type_module = 'Articles data';
    $lang->type_syncmember = 'Synchronize member data';
    $lang->target_module = 'Target module';
    $lang->xml_file = 'XML file';

    $lang->import_step_title = array(
        1 => 'Step 1. Select previous target',
        12 => 'Step 1-2. Select target module',
        13 => 'Step 1-3. Select target category',
        2 => 'Step 2. Upload XML file',
        3 => 'Step 2. Synchronize member data and article data',
    );

    $lang->import_step_desc = array(
        1 => 'Please select the XML file\'s type you wish to transfer.',
        12 => 'Please select the module you wish to transfer datas.',
        13 => 'Please select the target category you wish to tranfer datas.',
        2 => "Please input the XML file's location you wish to tranfer datas.\nIf it is located in the same account, input absolute/relative path. If not, input the url starting with http://..",
        3 => 'The member data and article data may not be correct after the transferation. If that is the case, synchronize to repair it based on user_id.',
    );

    // guide/alert
    $lang->msg_sync_member = 'Member and article data synchronization will begin by clicking the synchronize button.';
    $lang->msg_no_xml_file = 'Could not find XML file. Please check the path again';
    $lang->msg_invalid_xml_file = 'Invalid type of XML file.';
    $lang->msg_importing = 'Writing %d datas of %d. (If it keeps being frozen, click the button "Continue")';
    $lang->msg_import_finished = '%d datas were inputted completely. Depending on the situation, there might be some datas which couldn\'t be inputted.';
    $lang->msg_sync_completed = 'Completed synchronzing member article and comments.';

    // blah blah..
    $lang->about_type_member = 'If you are transfering the member information, select this option';
    $lang->about_type_module = 'If you are transfering the board or articles information, select this option';
    $lang->about_type_syncmember = 'If you are trying to synchronize the member information after transfering member and article information, select this option';
    $lang->about_importer = "You can transfer Zeroboard4, Zeroboard5 Beta or other program's data into ZeroboardXE's data.\nIn order to tranfer, you have to use <a href=\"#\" onclick=\"winopen('');return false;\">XML Exporter</a> to convert the data you want into XML File then upload it.";

    $lang->about_target_path = "To get attachments from Zeroboard4, please input the address where Zeroboard4 is installed.\nIf it is located in the same server, input Zeroboard4's path such as /home/USERID/public_html/bbs\nIf it is not located in the same server, input the address where Zeroboard4 is installed. ex. http://Domain/bbs";
?>
