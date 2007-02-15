<?php
    /**
     * @class  memberModel
     * @author zero (zero@nzeo.com)
     * @brief  member module의 Model class
     **/

    class memberModel extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 로그인 되어 있는지에 대한 체크
         **/
        function isLogged() {
            if($_SESSION['is_logged']&&$_SESSION['ipaddress']==$_SERVER['REMOTE_ADDR']) return true;

            $_SESSION['is_logged'] = false;
            $_SESSION['logged_info'] = '';
            return false;
        }

        /**
         * @brief 인증된 사용자의 정보 return
         **/
        function getLoggedInfo() {
            // 로그인 되어 있고 세션 정보를 요청하면 세션 정보를 return
            if($this->isLogged()) return $_SESSION['logged_info'];
        }

        /**
         * @brief user_id에 해당하는 사용자 정보 return
         **/
        function getMemberInfoByUserID($user_id) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->user_id = $user_id;
            $output = $oDB->executeQuery('member.getMemberInfo', $args);
            if(!$output) return $output;

            $member_info = $output->data;
            $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

            return $member_info;
        }

        /**
         * @brief member_srl로 사용자 정보 return
         **/
        function getMemberInfoByMemberSrl($member_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->member_srl = $member_srl;
            $output = $oDB->executeQuery('member.getMemberInfoByMemberSrl', $args);
            if(!$output) return $output;

            $member_info = $output->data;
            $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

            return $member_info;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByUserID($user_id) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->user_id = $user_id;
            $output = $oDB->executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByEmailAddress($email_address) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->email_address = $email_address;
            $output = $oDB->executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByNickName($nick_name) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->nick_name = $nick_name;
            $output = $oDB->executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief 현재 접속자의 member_srl을 return
         **/
        function getLoggedMemberSrl() {
            if(!$this->isLogged()) return;
            return $_SESSION['member_srl'];
        }

        /**
         * @brief 현재 접속자의 user_id을 return
         **/
        function getLoggedUserID() {
            if(!$this->isLogged()) return;
            $logged_info = $_SESSION['logged_info'];
            return $logged_info->user_id;
        }

        /**
         * @brief member_srl이 속한 group 목록을 가져옴
         **/
        function getMemberGroups($member_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->member_srl = $member_srl;
            $output = $oDB->executeQuery('member.getMemberGroups', $args);
            if(!$output->data) return;

            $group_list = $output->data;
            if(!is_array($group_list)) $group_list = array($group_list);

            foreach($group_list as $group) {
                $result[$group->group_srl] = $group->title;
            }
            return $result;
        }

        /**
         * @brief 기본 그룹을 가져옴
         **/
        function getDefaultGroup() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $output = $odB->executeQuery('member.getDefaultGroup');
            return $output->data;
        }

        /**
         * @brief group_srl에 해당하는 그룹 정보 가져옴
         **/
        function getGroup($group_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->group_srl = $group_srl;
            $output = $oDB->executeQuery('member.getGroup', $args);
            return $output->data;
        }

        /**
         * @brief 그룹 목록을 가져옴
         **/
        function getGroups() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $output = $oDB->executeQuery('member.getGroups');
            if(!$output->data) return;

            $group_list = $output->data;
            if(!is_array($group_list)) $group_list = array($group_list);

            foreach($group_list as $val) {
                $result[$val->group_srl] = $val;
            }
            return $result;
        }

        /**
         * @brief 금지 아이디 목록 가져오기
         **/
        function getDeniedIDList() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->sort_index = "list_order";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;

            $output = $oDB->executeQuery('member.getDeniedIDList', $args);
            return $output;
        }

        /**
         * @brief 금지 아이디인지 확인
         **/
        function isDeniedID($user_id) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            $args->user_id = $user_id;
            $output = $oDB->executeQuery('member.chkDeniedID', $args);
            if($output->data->count) return true;
            return false;
        }

    }
?>
