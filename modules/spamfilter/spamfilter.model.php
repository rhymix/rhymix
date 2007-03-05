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
         * @brief 스팸필터 모듈의 사용자 설정 값 return
         **/
        function getConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            return $oModuleModel->getModuleConfig('spamfilter');
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
         * @brief 인자로 넘겨진 ipaddress가 금지 ip인지 체크하여 return
         **/
        function isDeniedIP($ipaddress) {
            $oDB = &DB::getInstance();
            $args->ipaddress = $ipaddress;
            $output = $oDB->executeQuery('spamfilter.isDeniedIP', $args);
            if($output->data->count>0) return true;
            return false;
        }

        /**
         * @brief 등록된 금지 Word 의 목록을 return
         **/
        function getDeniedWordList() {
            $oDB = &DB::getInstance();
            $args->sort_index = "regdate";
            $output = $oDB->executeQuery('spamfilter.getDeniedWordList', $args);
            if(!$output->data) return;
            if(!is_array($output->data)) return array($output->data);
            return $output->data;
        }

        /**
         * @brief 지정된 IPaddress의 특정 시간대 내의 로그 수를 return
         **/
        function getLogCount($time = 60, $ipaddress='') {
            if(!$ipaddress) $ipaddress = $_SERVER['REMOTE_ADDR'];

            $oDB = &DB::getInstance();
            $args->ipaddress = $ipaddress;
            $args->regdate = date("YmdHis", time()-$time);
            $output = $oDB->executeQuery('spamfilter.getLogCount', $args);
            $count = $output->data->count;
            return $count;
        }


    }
?>
