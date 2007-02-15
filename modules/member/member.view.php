<?php
    /**
     * @file   : modules/member/member.admin.php
     * @author : zero <zero@nzeo.com>
     * @desc   : member의 관리자 파일
     *           Module class에서 상속을 받아서 사용
     *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
     *           미리 기록을 하여야 함
     **/

    class memberView extends Module {

        var $group_list = NULL; ///< 그룹 목록 정보
        var $member_info = NULL; ///< 선택된 사용자의 정보

        /**
         * @brief 초기화
         **/
        function dispInit() {
            // 멤버모델 객체 생성
            $oMemberModel = getModule('member', 'model');

            // member_srl이 있으면 미리 체크하여 member_info 세팅
            $member_srl = Context::get('member_srl');
            if($member_srl) {
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                if(!$member_info) Context::set('member_srl','');
                else Context::set('member_info',$this->member_info);
            }

            // group 목록 가져오기
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

            return true;
        }

        /**
         * @brief 회원 목록 출력
         **/
        function dispContent() {
            // 등록된 member 모듈을 불러와 세팅
            $oDB = &DB::getInstance();
            $args->sort_index = "member_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $output = $oDB->executeQuery('member.getMemberList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('list');
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispInfo() {
            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 정보 입력 화면 출력
         **/
        function dispInsert() {
            // 템플릿 파일 지정
            $this->setTemplateFile('insert_member');
        }

        /**
         * @brief 회원 삭제 화면 출력
         **/
        function dispDeleteForm() {
            if(!Context::get('member_srl')) return $this->dispContent();
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 그룹 목록 출력
         **/
        function dispGroup() {
            $group_srl = Context::get('group_srl');

            if($group_srl && $this->group_list[$group_srl]) {
                Context::set('selected_group', $this->group_list[$group_srl]);
                $this->setTemplateFile('group_update_form');
            } else {
                $this->setTemplateFile('group_list');
            }
        }

        /**
         * @brief 회원 가입 폼 관리 화면 출력
         **/
        function dispJoinForm() {
            $this->setTemplateFile('join_form');
        }

        /**
         * @brief 금지 목록 아이디 출력
         **/
        function dispDeniedID() {
            // 멤버모델 객체 생성
            $oMemberModel = getModule('member', 'model');

            // 사용금지 목록 가져오기
            $output = $oMemberModel->getDeniedIDList();

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('denied_list');
        }

        function procInsert() {
        // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
        $args = Context::gets('member_srl','user_id','user_name','nick_name','email_address','password','allow_mailing','denied','is_admin','signature','profile_image','image_nick','image_mark','description','group_srl_list');

        // member_srl이 있으면 원본을 구해온다
        $oMember = getModule('member');

        // member_srl이 넘어오면 원 모듈이 있는지 확인
        if($args->member_srl) {
        $member_info = $oMember->getMemberInfoByMemberSrl($args->member_srl);
        // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
        if($member_info->member_srl != $args->member_srl) unset($args->member_srl);
        }

        // member_srl의 값에 따라 insert/update
        if(!$args->member_srl) {
        $output = $oMember->insertMember($args);
        $msg_code = 'success_registed';
        } else {
        $output = $oMember->updateMember($args);
        $msg_code = 'success_updated';
        }

        if(!$output->toBool()) return $output;

        $this->add('sid','member');
        $this->add('member_srl',$output->get('member_srl'));
        $this->add('act','dispInfo');
        $this->add('page',Context::get('page'));
        $this->setMessage($msg_code);
        }

        function procDelete() {
        // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
        $member_srl = Context::get('member_srl');

        // member_srl이 있으면 원본을 구해온다
        $oMember = getModule('member');
        $output = $oMember->deleteMember($member_srl);
        if(!$output->toBool()) return $output;

        $this->add('sid','member');
        $this->add('page',Context::get('page'));
        $this->setMessage("success_deleted");
        }

        function procInsertGroup() {
        $args = Context::gets('title','description','is_default');
        $oMember = getModule('member');
        $output = $oMember->insertGroup($args);
        if(!$output->toBool()) return $output;

        $this->add('sid','member');
        $this->add('act','dispGroup');
        $this->add('group_srl','');
        $this->add('page',Context::get('page'));
        $this->setMessage('success_registed');
        }

        function procUpdateGroup() {
        $group_srl = Context::get('group_srl');
        $mode = Context::get('mode');

        $oMember = getModule('member');

        switch($mode) {
        case 'delete' :
        $output = $oMember->deleteGroup($group_srl);
        if(!$output->toBool()) return $output;
        $msg_code = 'success_deleted';
        break;
        case 'update' :
        $args = Context::gets('group_srl','title','description','is_default');
        $output = $oMember->updateGroup($args);
        if(!$output->toBool()) return $output;
        $msg_code = 'success_updated';
        break;
        }

        $this->add('sid','member');
        $this->add('act','dispGroup');
        $this->add('group_srl','');
        $this->add('page',Context::get('page'));
        $this->setMessage($msg_code);
        }

        function procInsertJoinForm() {
        $args->column_type = Context::get('column_type');
        $args->column_name = Context::get('column_name');
        $args->column_title = Context::get('column_title');

        $oDB = &DB::getInstance();
        $output = $oDB->executeQuery('member.insertJoinForm', $args);
        if(!$output->toBool()) return $output;

        $this->add('sid','member');
        $this->add('act','dispJoinForm');
        $this->setMessage('success_registed');
        }

        function procInsertDeniedID() {
        $user_id = Context::get('user_id');
        $description = Context::get('description');
        $oMember = getModule('member');
        $output = $oMember->insertDeniedID($user_id, $description);
        if(!$output->toBool()) return $output;

        $this->add('sid','member');
        $this->add('act','dispDeniedID');
        $this->add('group_srl','');
        $this->add('page',Context::get('page'));
        $this->setMessage('success_registed');
        }

        function procUpdateDeniedID() {
        $user_id = Context::get('user_id');
        $mode = Context::get('mode');

        $oMember = getModule('member');

        switch($mode) {
        case 'delete' :
        $output = $oMember->deleteDeniedID($user_id);
        if(!$output->toBool()) return $output;
        $msg_code = 'success_deleted';
        break;
        }

        $this->add('sid','member');
        $this->add('act','dispDeniedID');
        $this->add('page',Context::get('page'));
        $this->setMessage($msg_code);
        }
    }
?>
