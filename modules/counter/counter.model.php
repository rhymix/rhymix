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
            $args->regdate = date("Ymd");
            $output = executeQuery('counter.getCounterLog', $args);
            return $output->data->count?true:false;
        }

        /**
         * @brief 오늘자 카운터 현황 row 있는지 체크
         **/
        function isInsertedTodayStatus() {
            $args->regdate = date("Ymd");
            $output = executeQuery('counter.getTodayStatus', $args);
            return $output->data->count?true:false;
        }

        /**
         * @brief 특정 일의 접속 통계를 가져옴
         **/
        function getStatus($regdate) {
            // 여러개의 날자 로그를 가져올 경우
            if(is_array($regdate)) {
                $date_count = count($regdate);
                $args->regdate = implode(',',$regdate);

            // 단일 날자의 로그를 가져올 경우
            } else {
                if(strlen($regdate)==8) $regdate = $regdate;
                $args->regdate = $regdate;
            }

            $output = executeQuery('counter.getCounterStatus', $args);
            $status = $output->data;

            if(!is_array($regdate)) return $status;

            if(!is_array($status)) $status = array($status);
            unset($output);

            foreach($status as $key => $val) {
                $output[substr($val->regdate,0,8)] = $val;
            }
            return $output;
        }

    }
?>
