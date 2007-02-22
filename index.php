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
     * - pdf 문서     : http://dev.zeroboard.com/doc/zeroboard.pdf
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
     * 객체를 생성하고 기본 정보를 setting 해줌\n
     * ModuleHandler는 이 외에도 설치가 되어 있는지에 대한 체크를\n
     * 하여 미설치시 Install 모듈을 실행하도록 한다\n
     * 그리고 해당 모듈을 실행후 모듈 객체를 return한다
     * 이 모듈 객체는 DisplayHandler에 의해 content 출력시 사용된다.
     **/
    $oModuleHandler = new ModuleHandler();
    $oModuleHandler->init();
    $oModule = &$oModuleHandler->procModule();

    /**
     * @brief DisplayHandler 객체를 생성하여 모듈의 처리 결과를 출력
     *
     * ModuleHandler에 의해 주어진 모듈 객체는 Object 클래스의 상속을 받으므로\n
     * RequestMethod의 종류(GET/POST/XML)에 따라 적절한 헤더 정보를 발송하고\n
     * XML 데이터 혹은 HTML 데이터를 출력한다
     **/
    $oDisplayHandler = new DisplayHandler();
    $oDisplayHandler->printContent($oModule);

    /**
     * @brief Context::close()를 통해서 DB및 기타 사용된 자원들의 처리
     **/
    $oContext->close();
?>
