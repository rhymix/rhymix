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
            $args->sort_index = "regdate";
            $args->page = Context::get('page')?Context::get('page'):1;
            $output = executeQuery('spamfilter.getDeniedIPList', $args);
            if(!$output->data) return;
            if(!is_array($output->data)) return array($output->data);
            return $output->data;
        }

        /**
         * @brief 인자로 넘겨진 ipaddress가 금지 ip인지 체크하여 return
         **/
        function isDeniedIP() {
            $ipaddress = $_SERVER['REMOTE_ADDR'];

            $ip_list = $this->getDeniedIPList();
            if(!count($ip_list)) return new Object();

            $count = count($ip_list);
            $patterns = array();
            for($i=0;$i<$count;$i++) {
                $ip = str_replace('*','',$ip_list[$i]->ipaddress);
                $patterns[] = preg_quote($ip);
            }

            $pattern = '/^('.implode($patterns,'|').')/';

            if(preg_match($pattern, $ipaddress, $matches)) return new Object(-1,'msg_alert_registered_denied_ip');
             
            return new Object();
        }

        /**
         * @brief 등록된 금지 Word 의 목록을 return
         **/
        function getDeniedWordList() {
            $args->sort_index = "regdate";
            $output = executeQuery('spamfilter.getDeniedWordList', $args);
            if(!$output->data) return;
            if(!is_array($output->data)) return array($output->data);
            return $output->data;
        }

        /**
         * @brief 넘어온 text에 금지 단어가 있는지 확인
         **/
        function isDeniedWord($text) {
            $word_list = $this->getDeniedWordList();
            if(!count($word_list)) return new Object();

            $count = count($word_list);
            for($i=0;$i<$count;$i++) {
                $word = $word_list[$i]->word;
                if(preg_match('/'.preg_quote($word,'/').'/is', $text)) return new Object(-1,sprintf(Context::getLang('msg_alert_denied_word'), $word));
            }

            return new Object();
        }

        /**
         * @brief 지정된 시간을 체크
         **/
        function checkLimited() {
            $config = $this->getConfig();
            $limit_count = $config->limit_count?$config->limit_count:5;
            $interval = $config->interval;
            if(!$interval) return new Object();

            $count = $this->getLogCount($interval);

            $ipaddress = $_SERVER['REMOTE_ADDR'];

            // 정해진 시간보다 클 경우 금지 ip로 등록
            if($count>=$limit_count) {
                $oSpamFilterController = &getController('spamfilter');
                $oSpamFilterController->insertIP($ipaddress);
                return new Object(-1, 'msg_alert_registered_denied_ip');
            }

            // 제한 글수까지는 아니지만 정해진 시간내에 글 작성을 계속 할때
            if($count) {
                $message = sprintf(Context::getLang('msg_alert_limited_by_config'), $interval);

                $oSpamFilterController = &getController('spamfilter');
                $oSpamFilterController->insertLog();

                return new Object(-1, $message);
            }

            return new Object();
        }

        /**
         * @brief 특정 글에 이미 엮인글이 등록되어 있는지 확인
         **/
        function isInsertedTrackback($document_srl) {
            $config = $this->getConfig();
            $check_trackback = $config->check_trackback=='Y'?true:false;
            if(!$check_trackback) return new Object();

            $oTrackbackModel = &getModel('trackback');
            $count = $oTrackbackModel->getTrackbackCountByIPAddress($document_srl, $_SERVER['REMOTE_ADDR']);
            if($count>0) return new Object(-1, 'msg_alert_trackback_denied');

            return new Object();
        }

        /**
         * @brief 지정된 IPaddress의 특정 시간대 내의 로그 수를 return
         **/
        function getLogCount($time = 60, $ipaddress='') {
            if(!$ipaddress) $ipaddress = $_SERVER['REMOTE_ADDR'];

            $args->ipaddress = $ipaddress;
            $args->regdate = date("YmdHis", time()-$time);
            $output = executeQuery('spamfilter.getLogCount', $args);
            $count = $output->data->count;
            return $count;
        }


    }
?>
