<?php
    if(!defined("__ZBXE__")) exit();

    /**
    * @file naver_search_addon.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 네이버 검색 연동 애드온 
    *
    * 네이버 검색 연동 애드온은 모듈이 실행된 후에 동작을 한다.
    * board 모듈의 procInsertDocument, procDeleteDocument action일 때만 특정 서버로 발송을 한다.
    **/

    // called_position이 before일때만 실행
    if($called_position != 'after_module_proc') return;

    if($this->module != 'board' && ($this->act != 'procInsertDocument' || $this->act != 'procDeleteDocument')) return;

    // 검색 서버로 발송할 url을 구함
    $url = sprintf('%s?document_srl=%s',Context::getRequestUri(), Context::get('document_srl'));

    // URL을 네이버 검색 서버로 발송
?>
