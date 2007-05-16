<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Importer(importer) 모듈의 기본 언어팩
     **/

    // 버튼에 사용되는 언어
    $lang->cmd_sync_member = '동기화';
    $lang->cmd_continue = '계속진행';

    // 항목
    $lang->source_type = '이전 대상';
    $lang->type_member = '회원 정보';
    $lang->type_module = '게시물 정보';
    $lang->type_syncmember = '회원정보 동기화';
    $lang->target_module = '대상 모듈';
    $lang->xml_file = 'XML 파일';

    $lang->import_step_title = array(
        1 => 'Step 1. 이전 대상 선택',
        12 => 'Step 1-2. 대상 모듈 선택',
        13 => 'Step 1-3. 대상 분류 선택',
        2 => 'Step 2. XML파일 업로드',
        3 => 'Step 2. 회원정보와 게시물의 정보 동기화',
    );

    $lang->import_step_desc = array(
        1 => '이전을 하려는 XML파일의 종류를 선택해주세요.',
        12 => '데이터 이전을 할 대상 모듈을 선택해주세요.',
        13 => '데이터 이전을 할 대상 분류를 선택해주세요.',
        2 => "데이터 이전을 할 XML파일의 위치를 입력해주세요.\n같은 계정일 경우 상대 또는 절대 경로를, 다른 서버에 업로드 되어 있으면 http://주소.. 를 입력해주세요",
        3 => '회원정보와 게시물의 정보가 이전후에 맞지 않을 수 있습니다. 이 때 동기화를 하시면 user_id를 기반으로 올바르게 동작하도록 합니다.',
    );

    // 안내/경고
    $lang->msg_sync_member = '동기화 버튼을 클릭하시면 회원정보와 게시물정보의 동기화를 시작합니다.';
    $lang->msg_no_xml_file = 'XML파일을 찾을 수 없습니다. 경로를 다시 확인해주세요';
    $lang->msg_invalid_xml_file = '잘못된 형식의 XML파일입니다';
    $lang->msg_importing = '데이터를 입력중입니다. 혹시 아래 숫자가 변하지 않는다면 계속진행 버튼을 클릭해주세요.'; 
    $lang->msg_import_finished = '%d개의 데이터 입력이 완료되었습니다. 상황에 따라 입력되지 못한 데이터가 있을 수 있습니다.';

    // 주절 주절..
    $lang->about_type_member = '데이터 이전 대상이 회원정보일 경우 선택해주세요';
    $lang->about_type_module = '데이터 이전 대상이 게시판등의 게시물 정보일 경우 선택해주세요';
    $lang->about_type_syncmember = '회원정보와 게시물정보등을 이전후 회원정보 동기화 해야 할때 선택해주세요';
    $lang->about_importer = "제로보드4, zb5beta 또는 다른 프로그램의 데이터를 제로보드XE 데이터로 이전할 수 있습니다.\n이전을 위해서는 <a href=\"#\" onclick=\"winopen('');return false;\">XML Exporter</a>를 이용해서 원하는 데이터를 XML파일로 생성후 업로드해주셔야 합니다.";
?>
