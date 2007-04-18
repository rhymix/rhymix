<?php
    /**
     * @class  counterController
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 controller class
     **/

    class counterController extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 로그 등록 
         **/
        function insertLog() {
            return executeQuery('counter.insertCounterLog');
        }

        /**
         * @brief 현황 등록
         **/
        function insertStatus() {
            return executeQuery('counter.insertCounterStatus');
        }

    }
?>
