<?php
    /**
     * @class  memberView
     * @author zero (zero@nzeo.com)
     * @brief  member module의 View class
     **/

    class memberView extends member {

        var $group_list = NULL; ///< 그룹 목록 정보
        var $member_info = NULL; ///< 선택된 사용자의 정보

        /**
         * @brief 초기화
         **/
        function init() {
            // 관리자 모듈에서 요청중이면 initAdmin(), 아니면 initNormal()
            if(Context::get('module')=='admin') $this->initAdmin();
            else $this->initNormal();
        }

        /**
         * @brief 관리자 페이지의 초기화
         **/
        function initAdmin() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // member_srl이 있으면 미리 체크하여 member_info 세팅
            $member_srl = Context::get('member_srl');
            if($member_srl) {
                $this->member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                if(!$this->member_info) Context::set('member_srl','');
                else Context::set('member_info',$this->member_info);
            }

            // group 목록 가져오기
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 일반 페이지 초기화
         **/
        function initNormal() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 회원 가입 폼 출력
         **/
        function dispSignUpForm() {
            $oMemberModel = &getModel('member');

            // 로그인한 회원일 경우 해당 회원의 정보를 받음
            if($oMemberModel->isLogged()) {
                $logged_info = Context::get('logged_info');
                $member_srl = $logged_info->member_srl;
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                Context::set('member_info',$member_info);
            }
            
            // 추가 가입폼 목록을 받음
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'skins/default');
            $this->setTemplateFile('insert_member');
        }

        /**
         * @brief 로그인 폼 출력
         **/
        function dispLoginForm() {
            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'skins/default');
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 로그아웃 출력
         **/
        function dispLogout() {
            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'skins/default');
            $this->setTemplateFile('logout');
        }

        /**
         * @brief 회원 목록 출력
         **/
        function dispMemberList() {

            // member model 객체 생성후 목록을 구해옴
            $oMemberModel = &getModel('member');
            $output = $oMemberModel->getMemberList();

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('member_list');
        }

        /**
         * @brief 회원 관리에 필요한 기본 설정들
         **/
        function dispModuleConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('member_config');
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispMemberInfo() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 정보 입력 화면 출력
         **/
        function dispMemberInsert() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

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
        function dispGroupList() {
            $group_srl = Context::get('group_srl');

            if($group_srl && $this->group_list[$group_srl]) {
                Context::set('selected_group', $this->group_list[$group_srl]);
                $this->setTemplateFile('group_update_form');
            } else {
                $this->setTemplateFile('group_list');
            }
        }

        /**
         * @brief 회원 가입 폼 목록 출력
         **/
        function dispJoinFormList() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 추가로 설정한 가입 항목 가져오기
            $form_list = $oMemberModel->getJoinFormList();
            Context::set('form_list', $form_list);

            $this->setTemplateFile('join_form_list');
        }

        /**
         * @brief 회원 가입 폼 관리 화면 출력
         **/
        function dispInsertJoinForm() {
            // 수정일 경우 대상 join_form의 값을 구함
            $member_join_form_srl = Context::get('member_join_form_srl');
            if($member_join_form_srl) {
                $oMemberModel = &getModel('member');
                $join_form = $oMemberModel->getJoinForm($member_join_form_srl);
                if(!$join_form) Context::set('member_join_form_srl','',true);
                else Context::set('join_form', $join_form);
            }
            $this->setTemplateFile('insert_join_form');
        }

        /**
         * @brief 금지 목록 아이디 출력
         **/
        function dispDeniedIDList() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 사용금지 목록 가져오기
            $output = $oMemberModel->getDeniedIDList();

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('denied_id_list');
        }
    }
?>
