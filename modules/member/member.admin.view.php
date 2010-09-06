<?php
    /**
     * @class  memberAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  member module's admin view class
     **/

    class memberAdminView extends member {

        var $group_list = NULL; ///< group list 
        var $member_info = NULL; ///< selected member info 

        /**
         * @brief initialization 
         **/
        function init() {
            $oMemberModel = &getModel('member');

            // if member_srl exists, set member_info
            $member_srl = Context::get('member_srl');
            if($member_srl) {
                $this->member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                if(!$this->member_info) Context::set('member_srl','');
                else Context::set('member_info',$this->member_info);
            }

            // retrieve group list 
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief display member list 
         **/
        function dispMemberAdminList() {

            $oMemberAdminModel = &getAdminModel('member');
            $oMemberModel = &getModel('member');
            $output = $oMemberAdminModel->getMemberList();

            // retrieve list of groups for each member
            if($output->data) {
                foreach($output->data as $key => $member) {
                    $output->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl,0);
                }
            }

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('member_list');
        }

        /**
         * @brief default configuration for member management
         **/
        function dispMemberAdminConfig() {
            // retrieve configuration via module model instance
            $oModuleModel = &getModel('module');
            $oMemberModel = &getModel('member');
            $config = $oMemberModel->getMemberConfig();
            Context::set('config',$config);

            // list of skins for member module
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            // retrieve skins of editor
            $oEditorModel = &getModel('editor');
            Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

            // get an editor
            $option->primary_key_name = 'temp_srl';
            $option->content_key_name = 'agreement';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 300;
            $editor = $oEditorModel->getEditor(0, $option);
            Context::set('editor', $editor);

            $this->setTemplateFile('member_config');
        }

        /**
         * @brief display member information
         **/
        function dispMemberAdminInfo() {
            $oMemberModel = &getModel('member');
            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $member_config);
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));
            $this->setTemplateFile('member_info');
        }

        /**
         * @brief display member insert form
         **/
        function dispMemberAdminInsert() {
            // retrieve extend form
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $member_info = Context::get('member_info');
            $member_info->signature = $oMemberModel->getSignature($this->member_info->member_srl);
            Context::set('member_info', $member_info);

            // get an editor for the signature
            if($this->member_info->member_srl) {
                $oEditorModel = &getModel('editor');
                $option->primary_key_name = 'member_srl';
                $option->content_key_name = 'signature';
                $option->allow_fileupload = false;
                $option->enable_autosave = false;
                $option->enable_default_component = true;
                $option->enable_component = false;
                $option->resizable = false;
                $option->height = 200;
                $editor = $oEditorModel->getEditor($this->member_info->member_srl, $option);
                Context::set('editor', $editor);
            }

            $this->setTemplateFile('insert_member');
        }

        /**
         * @brief display member delete form
         **/
        function dispMemberAdminDeleteForm() {
            if(!Context::get('member_srl')) return $this->dispMemberAdminList();
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief display group list
         **/
        function dispMemberAdminGroupList() {
            $oModuleModel = &getModel('module');

            $config = $oModuleModel->getModuleConfig('member');
            if($config->group_image_mark_order) $config->group_image_mark_order = explode(',', $config->group_image_mark_order);
            Context::set('config', $config);

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

        /**
         * @brief 회원 그룹 일괄 변경
         **/
        function dispMemberAdminManageGroup() {
            // 선택된 회원 목록을 구함
            $args->member_srl = trim(Context::get('member_srls'));
            $output = executeQueryArray('member.getMembers', $args);
            Context::set('member_list', $output->data);

            // 회원 그룹 목록을 구함
            $oMemberModel = &getModel('member');
            Context::set('member_groups', $oMemberModel->getGroups());

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('manage_member_group');
        }

        /**
         * @brief 회원 일괄 삭제
         **/
        function dispMemberAdminDeleteMembers() {
            // 선택된 회원 목록을 구함
            $args->member_srl = trim(Context::get('member_srls'));
            $output = executeQueryArray('member.getMembers', $args);
            Context::set('member_list', $output->data);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_members');
        }
    }
?>
