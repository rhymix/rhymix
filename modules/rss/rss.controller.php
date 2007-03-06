<?php
    /**
     * @class  rssController
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssController extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정
         **/
        function procInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('rss_disable', 'rss_type');
            if($args->rss_disable!='Y') $args->rss_disable = 'N';
            if(!$args->rss_type) $args->rss_type = "rss20";

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('rss',$args);
            return $output;
        }
    }
?>
