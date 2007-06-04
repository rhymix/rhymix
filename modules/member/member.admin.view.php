<?php
    /**
     * @class  memberAdminView
     * @author zero (zero@nzeo.com)
     * @brief  member module의 admin view class
     **/

    class memberAdminView extends member {

        var $group_list = NULL; ///< 그룹 목록 정보
        var $member_info = NULL; ///< 선택된 사용자의 정보

        /**
         * @brief 초기화
         **/
        function init() {
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
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 회원 목록 출력
         **/
        function dispMemberAdminList() {

            // member model 객체 생성후 목록을 구해옴
            $oMemberModel = &getAdminModel('member');
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
        function dispMemberAdminConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if(!$config->image_name_max_width) $config->image_name_max_width = 90;
            if(!$config->image_name_max_height) $config->image_name_max_height = 20;
            if(!$config->image_mark_max_width) $config->image_mark_max_width = 20;
            if(!$config->image_mark_max_height) $config->image_mark_max_height = 20;
            Context::set('config',$config);

            // 회원 관리 모듈의 스킨 목록을 구함
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            // 에디터를 받음
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getEditor(0, false, true);
            Context::set('editor', $editor);

            // 템플릿 파일 지정
            $this->setTemplateFile('member_config');
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispMemberAdminInfo() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 정보 입력 화면 출력
         **/
        function dispMemberAdminInsert() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $member_info = Context::get('member_info');
            $member_info->signature = $oMemberModel->getSignature($this->member_info->member_srl);
            Context::set('member_info', $member_info);

            // 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
            if($this->member_info->member_srl) {
                $oEditorModel = &getModel('editor');
                $editor = $oEditorModel->getEditor($this->member_info->member_srl, false, false);
                Context::set('editor', $editor);
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('insert_member');
        }

        /**
         * @brief 회원 삭제 화면 출력
         **/
        function dispMemberAdminDeleteForm() {
            if(!Context::get('member_srl')) return $this->dispContent();
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 그룹 목록 출력
         **/
        function dispMemberAdminGroupList() {
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
        function dispMemberAdminJoinFormList() {
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
        function dispMemberAdminInsertJoinForm() {
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
        function dispMemberAdminDeniedIDList() {
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
