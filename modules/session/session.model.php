<?php
    /**
     * @class  sessionModel
     * @author zero (zero@nzeo.com)
     * @brief  session 모듈의 Model class
     **/

    class sessionModel extends session {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function getLifeTime() {
            return $this->lifetime;
        }

        function read($session_key) {
            if(!$session_key || !$this->session_started) return;

            $args->session_key = $session_key;
            $output = executeQuery('session.getSession', $args);

            // 읽기 오류 발생시 테이블 생성 유무 확인
            if(!$output->toBool()) {
                $oDB = &DB::getInstance();
                if(!$oDB->isTableExists('session')) $oDB->createTableByXmlFile($this->module_path.'schemas/session.xml');
            }

            return $output->data->val;
        }

        /**
         * @brief 현재 접속중인 사용자의 목록을 구함
         * period_time 인자의 값을 n으로 하여 최근 n분 이내에 세션을 갱신한 대상을 추출함
         **/
        function getLoggedMembers($limit_count = 20, $page = 1, $period_time = 3) {
            $args->last_update = date("YmdHis", time() - $period_time*60);
            $args->page = $page;

            $output = executeQueryArray('session.getLoggedMembers', $args);
            if(!$output->toBool() || !$output->data) return $output;

            $member_srls = array();
            foreach($output->data as $key => $val) {
                $member_srls[$key] = $val->member_srl;
                $member_keys[$val->member_srl] = $key;
            }

            $member_args->member_srl = implode(',',$member_srls);
            $member_output = executeQueryArray('member.getMembers', $member_args);
            if($member_output->data) {
                foreach($member_output->data as $key => $val) {
                    $output->data[$member_keys[$val->member_srl]] = $val;
                }
            }

            return $output;
        }
    }
?>
