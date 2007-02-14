<?php
    /**
     * @file  index.php
     * @author zero (zero@zeroboard.com)
     * @brief 시작 페이지
     *
     * Request Argument에서 mid, act로 module 객체를 찾아서 생성하고 \n
     * 모듈 정보를 세팅함
     *
     * @mainpage 첫페이지
     * @section intro 소개
     * zeroboard5는 오픈 프로젝트로 개발되는 오픈 소스입니다.\n
     * 자세한 내용은 아래 링크를 참조하세요.
     * - 공식홈페이지 : http://www.zeroboard.com
     * - 개발자 포험  : http://dev.zeroboard.com
     * - 이슈트래킹   : http://www.zeroboard.com/trac
     *
     **/

    /**
     * @brief 필요한 설정 파일들을 include
     **/
    require_once("./config/config.inc.php");

    /** 
     * @brief Context 객체를 생성하여 초기화\n
     *        모든 Request Argument/ 환경변수등을 세팅
     **/
    $oContext = &Context::getInstance(); 
    $oContext->init(); 

    /**
     * @brief ModuleHandler 객체를 생성
     **/
    $oModuleHandler = new ModuleHandler();

    /**
     * @brief ModuleHandler 객체를 를 실행하여 요청받은 모듈 객체를\n
     *        찾고 모듈 정보를 세팅하는 등의 역할을 한후 모듈 객체를\n
     *        return받음
     **/
    $oModule = $oModuleHandler->proc();

    /**
     * @brief DisplayHandler 객체를 생성하여 모듈의 처리 결과를 출력
     **/
    $oDisplayHandler = new DisplayHandler();
    $oDisplayHandler->printContent($oModule);
?>
