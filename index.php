<?php
    /**
     * @file  index.php
     * @author zero (zero@zeroboard.com)
     * @brief 시작 페이지
     *
     * zeroboard XE의 시작 페이지
     *
     * Request Argument에서 mid, act로 module 객체를 찾아서 생성하고 \n
     * 모듈 정보를 세팅함
     *
     * @mainpage 첫페이지
     * @section intro 소개
     * zeroboard XE 는 오픈 프로젝트로 개발되는 오픈 소스입니다.\n
     * 자세한 내용은 아래 링크를 참조하세요.
     * - 공식홈페이지   : http://www.zeroboard.com
     * - 개발자 포험    : http://www.zeroboard.com/dev_forum
     * - 이슈트래킹     : http://trac.zeroboard.com
     * - SVN Repository : http://svn.zeroboard.com
     * - document       : http://doc.zeroboard.com
     * - pdf 문서       : http://doc.zeroboard.com/zeroboard_xe.pdf
     *
     **/

    /**
     * @brief 필요한 설정 파일들을 include
     **/
    require_once("./config/config.inc.php");

    /** 
     * @brief Context 객체를 생성하여 초기화
     * 모든 Request Argument/ 환경변수등을 세팅
     **/
    $oContext = &Context::getInstance(); 
    $oContext->init(); 

    /**
     * @brief ModuleHandler 객체를 생성/ 실행
     *
     * 모듈 핸들러는 Request Argument를 바탕으로 모듈을 찾아서\n
     * 객체를 생성하고 기본 정보를 setting 해준다.\n
     * ModuleHandler는 이 외에도 설치가 되어 있는지에 대한 체크를\n
     * 하여 미설치시 Install 모듈을 실행하도록 한다\n
     * 그리고 해당 모듈을 실행후 컨텐츠를 출력한다\n
     **/
    $oModuleHandler = new ModuleHandler();
    $oModuleHandler->init();
    $oModule = &$oModuleHandler->procModule();
    $oModuleHandler->displayContent($oModule);
?>
