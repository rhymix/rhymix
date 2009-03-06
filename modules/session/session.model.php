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
                if(!$oDB->isColumnExists("session","cur_mid")) $oDB->addColumn('session',"cur_mid","varchar",128);
                $output = executeQuery('session.getSession', $args);
            }

            // 세션 정보에서 cur_mid값이 없을 경우 테이블 생성 체크
            if(!isset($output->data->cur_mid)) {
                $oDB = &DB::getInstance();
                if(!$oDB->isColumnExists("session","cur_mid")) $oDB->addColumn('session',"cur_mid","varchar",128);
            }

            return $output->data->val;
        }

        /**
         * @brief 현재 접속중인 사용자의 목록을 구함
         * 여러개의 인자값을 필요로 해서 object를 인자로 받음
         * limit_count : 대상 수
         * page : 페이지 번호
         * period_time : 인자의 값을 n으로 하여 최근 n분 이내에 세션을 갱신한 대상을 추출함
         * mid : 특정 mid에 속한 사용자
         **/
        function getLoggedMembers($args) {
            if(!$args->site_srl) {
                $site_module_info = Context::get('site_module_info');
                $args->site_srl = (int)$site_module_info->site_srl;
            }
            if(!$args->list_count) $args->list_count = 20;
            if(!$args->page) $args->page = 1;
            if(!$args->period_time) $args->period_time = 3;
            $args->last_update = date("YmdHis", time() - $args->period_time*60);

            $output = executeQueryArray('session.getLoggedMembers', $args);
            if(!$output->toBool()) return $output;

            $member_srls = array();
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $member_srls[$key] = $val->member_srl;
                    $member_keys[$val->member_srl] = $key;
                }
            }

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if(!in_array($logged_info->member_srl, $member_srls)) {
                    $member_srls[0] = $logged_info->member_srl;
                    $member_keys[$logged_info->member_srl] = 0;
                }
            }

            if(!count($member_srls)) return $output;

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
