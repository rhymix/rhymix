<?php
    /**
     * @class  krzipController
     * @author zero (zero@nzeo.com)
     * @brief  krzip 모듈의 controller class
     **/

    class krzipController extends krzip {

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
            $args = Context::gets('krzip_server_hostname','krzip_server_port','krzip_server_query');
            if(!$args->krzip_server_hostname) $args->krzip_server_hostname = $this->hostname;
            if(!$args->krzip_server_port) $args->krzip_server_port = $this->port;
            if(!$args->krzip_server_query) $args->krzip_server_query = $this->query;

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('krzip',$args);
            return $output;
        }
        
    }
?>
