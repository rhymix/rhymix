<?php
    /**
     * @class  spamfilterModel
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 Model class
     **/

    class spamfilterModel extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 등록된 금지 IP의 목록을 return
         **/
        function getDeniedIPList() {
            $oDB = &DB::getInstance();
            $args->sort_index = "regdate";
            $args->page = Context::get('page')?Context::get('page'):1;
            $output = $oDB->executeQuery('spamfilter.getDeniedIPList', $args);
            if(!$output->data) return;
            if(!is_array($output->data)) return array($output->data);
            return $output->data;
        }

        /**
         * @brief 등록된 금지 Word 의 목록을 return
         **/
        function getDeniedWordList() {
            $oDB = &DB::getInstance();
            $args->sort_index = "regdate";
            $args->page = Context::get('page')?Context::get('page'):1;
            $output = $oDB->executeQuery('spamfilter.getDeniedWordList', $args);
            if(!$output->data) return;
            if(!is_array($output->data)) return array($output->data);
            return $output->data;
        }

        /**
         * @brief 지정된 IPaddress의 특정 시간대 내의 로그 수를 return
         **/
        function getLogCount($time = 3600, $ipaddress='') {
            if(!$ipaddress) $ipaddress = $_SERVER['REMOTE_ADDR'];

            $oDB = &DB::getInstance();
            $args->ipaddress = $ipaddress;
            $args->regdate = date("YmdHis", time()-$time);
            $output = $oDB->executeQuery('spamfilter.getLogCount');
            $count = $output->data->count;
            return $count;
        }


    }
?>
