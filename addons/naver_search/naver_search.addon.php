<?php
    if(!__ZBXE__) exit();

    /**
    * @file naver_search_addon.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 네이버 검색 연동 애드온 
    *
    * addOn은 ModuleObject 에서 모듈이 불러지기 전/후에 include되는 것으로 실행을 한다.
    * 즉 별도의 interface가 필요한 것이 아니고 모듈의 일부라고 판단하여 코드를 작성하면 된다.
    **/

    // called_position이 before일때만 실행
    if($called_position != 'after_module_proc') return;

    // 이 애드온이 동작할 대상 (이 부분은 특별히 정해진 규약이 없다)
    $effecived_target = array(
        'board' => array('procInsertDocument', 'procDeleteDocument'),
    );

    // spam filter모듈이 적용될 module+act를 체크
    if(!in_array($this->act, $effecived_target[$this->module])) return;

    // 해당 글의 URL을 구함
    $url = sprintf('%s?document_srl=%s',Context::getRequestUri(), Context::get('document_srl'));

    // URL을 네이버 검색 서버로 발송
    //@todo 차후 개발
?>
