<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file mobile.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 모바일XE 애드온
     *
     * 헤더정보를 가로채서 모바일에서의 접속일 경우 WAP 태그로 컨텐츠를 출력함
     *
     * 동작 시점
     *
     * before_module_proc > 모바일 처리를 위해 모듈의 일반 설정을 변경해야 할 경우 호출
     *
     * after_module_proc > 모바일 컨텐츠 출력
     * 동작 조건
     **/

    // 관리자 페이지는 무시
    if(Context::get('module')=='admin') return;

    // 동작 시점 관리
    if($called_position != 'before_module_proc' && $called_position != 'after_module_proc' ) return;

    // 모바일 브라우저가 아니라면 무시
    require_once(_XE_PATH_.'addons/mobile/classes/mobile.class.php');
    if(!mobileXE::getBrowserType()) return;

    // mobile instance 생성
    $oMobile = &mobileXE::getInstance();
    if(!$oMobile) return;

    // 애드온 설정에서 지정된 charset으로 지정
    $oMobile->setCharSet($addon_info->charset);

    // 모듈의 정보를 세팅
    $oMobile->setModuleInfo($this->module_info);

    // 현재 모듈 객체 등록
    $oMobile->setModuleInstance($this);

    // 네비게이트 모드이거나 WAP class가 있을 경우 미리 컨텐츠를 추출하여 출력/ 종료
    if($called_position == 'before_module_proc') {

        // 네비게이트 모드이면 네비게이션 컨텐츠 출력
        if($oMobile->isNavigationMode()) $oMobile->displayNavigationContent();

        // WAP class가 있으면 WAP class를 통해 컨텐츠 출력
        else $oMobile->displayModuleContent();

    // 네비게이트 모드가 아니고 WAP 클래스가 아니면 모듈의 결과를 출력
    } else if($called_position == 'after_module_proc')  {
        // 내용 준비
        $oMobile->displayContent();
    }
?>
