<?php
    /**
     * @class  spamfilterController
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 controller class
     **/

    class spamfilterController extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief IP 등록
         * 등록된 IP는 스패머로 간주
         **/
        function insertIP($ipaddress) {
            $args->ipaddress = $ipaddress;
            return executeQuery('spamfilter.insertDeniedIP', $args);
        }

        /**
         * @brief 로그 등록
         * 현 접속 IP를 로그에 등록, 로그의 간격이 특정 시간 이내일 경우 도배로 간주하여
         * 스패머로 등록할 수 있음
         **/
        function insertLog() {
            $output = executeQuery('spamfilter.insertLog');
            return $output;
        }
    }
?>
