<?php
    /**
     * @class  counterModel
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 Model class
     **/

    class counterModel extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 로그 검사
         **/
        function isLogged() {
            $output = executeQuery('counter.getCounterLog');
            return $output->data->count?true:false;
        }

        /**
         * @brief 특정 일의 접속 통계를 가져옴
         **/
        function getStatus($regdate) {
            $args->regdate = $regdate;
            $output = executeQuery('counter.getCounterStatus', $args);
            return $output->data;
        }

    }
?>
