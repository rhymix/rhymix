<?php
    /**
     * @class  memberController
     * @author zero (zero@nzeo.com)
     * @brief  member module의 Controller class
     **/

    class memberController extends member {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief user_id, password를 체크하여 로그인 시킴
         **/
        function procLogin($user_id, $password) {
            // 변수 정리
            $user_id = trim($user_id);
            $password = trim($password);

            // 이메일 주소나 비밀번호가 없을때 오류 return
            if(!$user_id) return new Object(-1,'null_user_id');
            if(!$password) return new Object(-1,'null_password');

            // member model 객체 생성
            $oMemberModel = &getModel('member');

            // user_id 에 따른 정보 가져옴
            $member_info = $oMemberModel->getMemberInfoByUserID($user_id);

            // return 값이 없거나 비밀번호가 틀릴 경우
            if($member_info->user_id != $user_id) return new Object(-1, 'invalid_user_id');
            if($member_info->password != md5($password)) return new Object(-1, 'invalid_password');

            // 로그인 처리
            $_SESSION['is_logged'] = true;
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];

            unset($member_info->password);

            // 세션에 로그인 사용자 정보 저장
            $_SESSION['member_srl'] = $member_info->member_srl;
            $_SESSION['logged_info'] = $member_info;

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 사용자 정보의 최근 로그인 시간을 기록
            $args->member_srl = $member_info->member_srl;
            $output = $oDB->executeQuery('member.updateLastLogin', $args);

            return $output;
        }

        /**
         * @brief 로그아웃
         **/
        function procLogout() {
            $_SESSION['is_logged'] = false;
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['logged_info'] = NULL;
            return new Object();
        }

        /**
         * @brief 사용자 추가 (관리자용)
         **/
        function procInsert() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $args = Context::gets('member_srl','user_id','user_name','nick_name','email_address','password','allow_mailing','denied','is_admin','signature','profile_image','image_nick','image_mark','description','group_srl_list');
            $all_args = Context::getRequestVars();
            unset($all_args->page);
            unset($all_args->module);
            unset($all_args->act);

            // 모든 request argument에서 필수 정보만 제외 한 후 추가 데이터로 입력
            $extra_vars = delObjectVars($all_args, $args);
            $args->extra_vars = serialize($extra_vars);

            // member_srl이 넘어오면 원 회원이 있는지 확인
            if($args->member_srl) {
                // 멤버 모델 객체 생성
                $oMemberModel = &getModel('member');

                // 회원 정보 구하기
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

                // 만약 원래 회원이 없으면 새로 입력하기 위한 처리
                if($member_info->member_srl != $args->member_srl) unset($args->member_srl);
            }

            // member_srl의 값에 따라 insert/update
            if(!$args->member_srl) {
                $output = $this->insertMember($args);
                $msg_code = 'success_registed';
            } else {
                $output = $this->updateMember($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('member_srl',$output->get('member_srl'));
            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 사용자 삭제 (관리자용)
         **/
        function procDelete() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $member_srl = Context::get('member_srl');

            $output = $this->deleteMember($member_srl);
            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->setMessage("success_deleted");
        }

        /**
         * @brief 회원 관리용 기본 정보의 추가
         **/
        function procInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('enable_join','redirect_url','agreement');
            if($args->enable_join!='Y') $args->enable_join = 'N';

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('member',$args);
            return $output;
        }

        /**
         * @brief 사용자 그룹 추가
         **/
        function procInsertGroup() {
            $args = Context::gets('title','description','is_default');
            $output = $this->insertGroup($args);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 사용자 그룹 정보 수정
         **/
        function procUpdateGroup() {
            $group_srl = Context::get('group_srl');
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->deleteGroup($group_srl);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $args = Context::gets('group_srl','title','description','is_default');
                        $output = $this->updateGroup($args);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_updated';
                    break;
            }

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 가입 항목 추가
         **/
        function procInsertJoinForm() {
            $oDB = &DB::getInstance();

            $args->member_join_form_srl = Context::get('member_join_form_srl');

            $args->column_type = Context::get('column_type');
            $args->column_name = Context::get('column_name');
            $args->column_title = Context::get('column_title');
            $args->default_value = explode('|@|', Context::get('default_value'));
            $args->is_active = Context::get('is_active');
            if(!in_array(strtoupper($args->is_active), array('Y','N'))) $args->is_active = 'N';
            $args->required = Context::get('required');
            if(!in_array(strtoupper($args->required), array('Y','N'))) $args->required = 'N';
            $args->description = Context::get('description');
            $args->list_order = $oDB->getNextSequence();

            // 기본값의 정리
            if(in_array($args->column_type, array('checkbox','select')) && count($args->default_value) ) {
                $args->default_value = serialize($args->default_value);
            } else {
                $args->default_value = '';
            }

            // member_join_form_srl이 있으면 수정, 없으면 추가
            if(!$args->member_join_form_srl) $output = $oDB->executeQuery('member.insertJoinForm', $args);
            else $output = $oDB->executeQuery('member.updateJoinForm', $args);

            if(!$output->toBool()) return $output;

            $this->add('act','dispJoinForm');
            $this->setMessage('success_registed');
        }

        /**
         * @brief 가입 항목의 상/하 이동 및 내용 수정
         **/
        function procUpdateJoinForm() {
            $member_join_form_srl = Context::get('member_join_form_srl');
            $mode = Context::get('mode');

            switch($mode) {
                case 'up' :
                        $output = $this->moveJoinFormUp($member_join_form_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'down' :
                        $output = $this->moveJoinFormDown($member_join_form_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'delete' :
                        $output = $this->deleteJoinForm($member_join_form_srl);
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 금지 아이디 추가
         **/
        function procInsertDeniedID() {
            $user_id = Context::get('user_id');
            $description = Context::get('description');

            $oMemberController = &getController('member');
            $output = $oMemberController->insertDeniedID($user_id, $description);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 금지 아이디 업데이트
         **/
        function procUpdateDeniedID() {
            $user_id = Context::get('user_id');
            $mode = Context::get('mode');

            $oMemberController = &getController('member');

            switch($mode) {
                case 'delete' :
                        $output = $oMemberController->deleteDeniedID($user_id);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_deleted';
                    break;
            }

            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 관리자를 추가한다
         **/
        function insertAdmin($args) {
            $args->is_admin = 'Y';
            return $this->insertMember($args);
        }

        /**
         * @brief member 테이블에 사용자 추가
         **/
        function insertMember($args) {
            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            if($args->denied!='Y') $args->denied = 'N';
            if($args->is_admin!='Y') $args->is_admin = 'N';
            list($args->email_id, $args->email_host) = explode('@', $args->email_address);

            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 금지 아이디인지 체크
            if($oMemberModel->isDeniedID($args->user_id)) return new Object(-1,'denied_user_id');

            // 아이디, 닉네임, email address 의 중복 체크
            $member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
            if($member_srl) return new Object(-1,'msg_exists_user_id');

            $member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
            if($member_srl) return new Object(-1,'msg_exists_nick_name');

            $member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
            if($member_srl) return new Object(-1,'msg_exists_email_address');

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // DB에 입력
            $args->member_srl = $oDB->getNextSequence();
            if($args->password) $args->password = md5($args->password);
            else unset($args->password);

            $output = $oDB->executeQuery('member.insertMember', $args);
            if(!$output->toBool()) return $output;

            // 기본 그룹을 입력
            $default_group = $oMemberModel->getDefaultGroup();

            // 기본 그룹에 추가
            $output = $this->addMemberToGroup($args->member_srl,$default_group->group_srl);
            if(!$output->toBool()) return $output;

            $output->add('member_srl', $args->member_srl);
            return $output;
        }

        /**
         * @brief member 정보 수정
         **/
        function updateMember($args) {
            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 수정하려는 대상의 원래 정보 가져오기
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->is_default = 'N';
            if($args->denied!='Y') $args->denied = 'N';
            if($args->is_admin!='Y') $args->use_category = 'N';
            list($args->email_id, $args->email_host) = explode('@', $args->email_address);

            // 아이디, 닉네임, email address 의 중복 체크
            $member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_user_id');

            $member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_nick_name');

            $member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_email_address');

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // DB에 update
            if($args->password) $args->password = md5($args->password);
            else $args->password = $member_info->password;

            $output = $oDB->executeQuery('member.updateMember', $args);
            if(!$output->toBool()) return $output;

            // 그룹에 추가
            $output = $oDB->executeQuery('member.deleteMemberGroupMember', $args);
            if(!$output->toBool()) return $output;

            $group_srl_list = explode('|@|', $args->group_srl_list);
            for($i=0;$i<count($group_srl_list);$i++) {
                $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);

                if(!$output->toBool()) return $output;
            }

            $output->add('member_srl', $args->member_srl);
            return $output;
        }

        /**
         * @brief 사용자 삭제
         **/
        function deleteMember($member_srl) {

            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 해당 사용자의 정보를 가져옴
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            if(!$member_info) return new Object(-1, 'msg_not_exists_member');

            // 관리자의 경우 삭제 불가능
            if($member_info->is_admin == 'Y') return new Object(-1, 'msg_cannot_delete_admin');

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // member_group_member에서 해당 항목들 삭제
            $args->member_srl = $member_srl;
            $output = $oDB->executeQuery('member.deleteMemberGroupMember', $args);
            if(!$output->toBool()) return $output;

            // member 테이블에서 삭제
            return $oDB->executeQuery('member.deleteMember', $args);
        }

        /**
         * @brief member_srl에 group_srl을 추가
         **/
        function addMemberToGroup($member_srl,$group_srl) {
            $args->member_srl = $member_srl;
            $args->group_srl = $group_srl;

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 추가
            return  $oDB->executeQuery('member.addMemberToGroup',$args);
        }

        /**
         * @brief 회원의 그룹값을 변경
         **/
        function changeGroup($source_group_srl, $target_group_srl) {
            // DB객체 생성
            $oDB = &DB::getInstance();

            $args->source_group_srl = $source_group_srl;
            $args->target_group_srl = $target_group_srl;

            return $oDB->executeQuery('member.changeGroup', $args);
        }

        /**
         * @brief 그룹 등록
         **/
        function insertGroup($args) {
            $oDB = &DB::getInstance();

            // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
            if($args->is_default!='Y') {
                $args->is_default = 'N';
            } else {
                $output = $oDB->executeQuery('member.updateGroupDefaultClear');
                if(!$output->toBool()) return $output;
            }

            return $oDB->executeQuery('member.insertGroup', $args);
        }

        /**
         * @brief 그룹 정보 수정
         **/
        function updateGroup($args) {
            $oDB = &DB::getInstance();

            // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
            if($args->is_default!='Y') $args->is_default = 'N';
            else {
                $output = $oDB->executeQuery('member.updateGroupDefaultClear');
                if(!$output->toBool()) return $output;
            }

            return $oDB->executeQuery('member.updateGroup', $args);
        }

        /**
         * 그룹 삭제
         **/
        function deleteGroup($group_srl) {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 삭제 대상 그룹을 가져와서 체크 (is_default == 'Y'일 경우 삭제 불가)
            $group_info = $oMemberModel->getGroup($group_srl);

            if(!$group_info) return new Object(-1, 'lang->msg_not_founded');
            if($group_info->is_default == 'Y') return new Object(-1, 'msg_not_delete_default');

            // is_default == 'Y'인 그룹을 가져옴
            $default_group = $oMemberModel->getDefaultGroup();
            $default_group_srl = $default_group->group_srl;

            // default_group_srl로 변경
            $this->changeGroup($group_srl, $default_group_srl);

            // 그룹 삭제
            $oDB = &DB::getInstance();
            $args->group_srl = $group_srl;
            return $oDB->executeQuery('member.deleteGroup', $args);
        }

        /**
         * @brief 금지아이디 등록
         **/
        function insertDeniedID($user_id, $desription = '') {
            $oDB = &DB::getInstance();

            $args->user_id = $user_id;
            $args->description = $description;
            $args->list_order = -1*$oDB->getNextSequence();

            return $oDB->executeQuery('member.insertDeniedID', $args);
        }

        /**
         * @brief 금지아이디 삭제
         **/
        function deleteDeniedID($user_id) {
            $oDB = &DB::getInstance();

            $args->user_id = $user_id;
            return $oDB->executeQuery('member.deleteDeniedID', $args);
        }

        /**
         * @brief 가입폼 항목을 삭제
         **/
        function deleteJoinForm($member_join_form_srl) {
            $oDB = &DB::getInstance();

            $args->member_join_form_srl = $member_join_form_srl;
            $output = $oDB->executeQuery('member.deleteJoinForm', $args);
            return $output;
        }

        /**
         * @brief 가입항목을 상단으로 이동
         **/
        function moveJoinFormUp($member_join_form_srl) {
            $oDB = &DB::getInstance();

            $oMemberModel = &getModel('member');

            // 선택된 가입항목의 정보를 구한다
            $args->member_join_form_srl = $member_join_form_srl;
            $output = $oDB->executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;

            // 전체 가입항목 목록을 구한다
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            $prev_member_join_form = NULL;
            foreach($join_form_list as $key => $val) {
                if($val->member_join_form_srl == $member_join_form_srl) break;
                $prev_member_join_form = $val;
            }

            // 이전 가입항목가 없으면 그냥 return
            if(!$prev_member_join_form) return new Object();

            // 선택한 가입항목의 정보
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $prev_member_join_form->list_order;

            // 대상 가입항목의 정보
            $prev_args->member_join_form_srl = $prev_member_join_form->member_join_form_srl;
            $prev_args->list_order = $list_order;

            // DB 처리
            $output = $oDB->executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            $oDB->executeQuery('member.updateMemberJoinFormListorder', $prev_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief 가입항목을 하단으로 이동
         **/
        function moveJoinFormDown($member_join_form_srl) {
            $oDB = &DB::getInstance();

            $oMemberModel = &getModel('member');

            // 선택된 가입항목의 정보를 구한다
            $args->member_join_form_srl = $member_join_form_srl;
            $output = $oDB->executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;

            // 전체 가입항목 목록을 구한다
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            for($i=0;$i<count($join_form_srl_list);$i++) {
                if($join_form_srl_list[$i]==$member_join_form_srl) break;
            }

            $next_member_join_form_srl = $join_form_srl_list[$i+1];

            // 이전 가입항목가 없으면 그냥 return
            if(!$next_member_join_form_srl) return new Object();
            $next_member_join_form = $join_form_list[$next_member_join_form_srl];

            // 선택한 가입항목의 정보
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $next_member_join_form->list_order;

            // 대상 가입항목의 정보
            $next_args->member_join_form_srl = $next_member_join_form->member_join_form_srl;
            $next_args->list_order = $list_order;

            // DB 처리
            $output = $oDB->executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            $output = $oDB->executeQuery('member.updateMemberJoinFormListorder', $next_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

    }
?>
