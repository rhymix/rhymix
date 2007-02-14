<?php
    /**
     * @file   ./trackback.php
     * @author zero (zero@nzeo.com)
     * @brief  트랙백을 받기 위한 파일. 파일이름때문에.. index.php와 거의 동일하나 act를 procReceiveTrackback로 고정
     **/

    /**
     * @brief 필요한 설정 파일들을 include
     **/
    require_once("./config/config.inc.php");

    /**
     * @brief Request Method와 설정값들을 세팅
     **/
    $oContext = &Context::getInstance();
    $oContext->init();

    /**
     * @brief act값을 procReceiveTrackback로 강제 설정 
     *
     * 각 모듈마다 procReceiveTrackback가 필수적으로 있어야 함
     **/
    Context::set('act', 'procReceiveTrackback');

    /**
     * @brief ModuleHandler 호출하여 content 출력
     **/
    $oModuleHandler = new ModuleHandler();
    $oModule = $oModuleHandler->proc();
?>
