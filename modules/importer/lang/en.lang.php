<?php
    /**
     * @file   en.lang.php
     * @author nicetwo (supershop@naver.com)
     * @brief basic english language pack of Importer
     **/

    // words for button
    $lang->cmd_sync_member = 'Synchronize';
    $lang->cmd_continue = 'Continue';
    $lang->preprocessing = 'Zeroboard is preparing for importing.';

    // items
    $lang->importer = 'Zeroboard Data Importer';
    $lang->source_type = 'Migration Target';
    $lang->type_member = 'Member Data';
    $lang->type_message = 'Message Data';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Article Data';
    $lang->type_syncmember = 'Synchronize Member Data';
    $lang->target_module = 'Target Module';
    $lang->xml_file = 'XML File';

    $lang->import_step_title = array(
        1 => 'Step 1. Migration Target',
        12 => 'Step 1-2. Target Module',
        13 => 'Step 1-3. Target Category',
        2 => 'Step 2. XML File Location',
        3 => 'Step 2. Synchronize Member and Article Data',
        99 => 'Importing Data',
    );

    $lang->import_step_desc = array(
        1 => 'Please select the XML file\'s type you wish to migrate.',
        12 => 'Please select the module you wish to import data.',
        121 => 'Posts:',
        122 => 'Guestbook:',
        13 => 'Please select the category you wish to import data.',
        2 => "Please input the XML file's location that contains data to import.\nYou may input both the absolute and relative path.",
        3 => 'Member and article data may not be corrected after the import. If so, please synchronize to recover them with user_id.',
        99 => 'Importing...',
    );
	$lang->xml_path = 'XML 파일의 경로를 입력하세요.';
	$lang->path_info = '상대 경로와 절대 경로 모두 입력 가능합니다.';
	$lang->data_destination = '데이터의 목적지를 선택하세요.';
	$lang->document_destination = '글 데이터의 목적지를 선택하세요.';
	$lang->guestbook_destination = '방명록 데이터의 목적지를 선택하세요.';
    // guide/alert
    $lang->msg_sync_member = 'Please click on Synchronize button to start data synchronization.';
    $lang->msg_no_xml_file = 'Could not find the XML file. Please check the path again';
    $lang->msg_invalid_xml_file = 'Invalid type of XML file.';
    $lang->msg_importing = 'Import %d items out of %d. (if process is stopped, click on Continue button)';
    $lang->msg_import_finished = '%d/%d items were imported completely. There could be some items that were imported improperly.';
    $lang->msg_sync_completed = 'Completed synchronzing member article and comments.';

    // blah blah..
    $lang->about_type_member = 'If you are to import a member data, please select this option';
    $lang->about_type_message = 'If you are to import a message data, please select this option';
    $lang->about_type_ttxml = 'If you are to import a TTXML (textcube) data, please select this option';
	$lang->about_ttxml_user_id = 'Please input a user ID to set as author of the TTXML data. (user ID must be already signed up.)';
    $lang->about_type_module = 'If you are to import an article data, please select this option';
    $lang->about_type_syncmember = 'If you are to import and synchronize member and article data, please select this option.';
    $lang->about_importer = "Data Importer will help you import Zeroboard4, Zeroboard5 Beta or other program's data into XE.\nIn order to import, you first have to use <a href=\"http://svn.xpressengine.net/migration/\" onclick=\"winopen(this.href);return false;\">XML Exporter</a> to convert the data you want into XML File.";

    $lang->about_target_path = "To get attachments from Zeroboard4, please input the path where Zeroboard4 is installed.\nIf it is located in the same server, please input Zeroboard4's path such as /home/USERID/public_html/bbs\nIf not, please input the address where Zeroboard4 is installed. ex. http://Domain/bbs";
?>
