<?php
    /**
     * @class  memberAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  member module's admin view class
     **/

    class memberAdminView extends member {

        var $group_list = NULL; ///< group list 
        var $memberInfo = NULL; ///< selected member info 

        /**
         * @brief initialization 
         **/
        function init() {
            $oMemberModel = &getModel('member');

            // if member_srl exists, set memberInfo
            $member_srl = Context::get('member_srl');
            if($member_srl) {
                $this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                if(!$this->memberInfo) Context::set('member_srl','');
                else Context::set('member_info',$this->memberInfo);
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
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->memberInfo));
            $this->setTemplateFile('member_info');
        }

        /**
         * @brief display member insert form
         **/
        function dispMemberAdminInsert() {
            // retrieve extend form
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->memberInfo));

            $memberInfo = Context::get('member_info');
            $memberInfo->signature = $oMemberModel->getSignature($this->memberInfo->member_srl);
            Context::set('member_info', $memberInfo);

            // get an editor for the signature
            if($this->memberInfo->member_srl) {
                $oEditorModel = &getModel('editor');
                $option->primary_key_name = 'member_srl';
                $option->content_key_name = 'signature';
                $option->allow_fileupload = false;
                $option->enable_autosave = false;
                $option->enable_default_component = true;
                $option->enable_component = false;
                $option->resizable = false;
                $option->height = 200;
                $editor = $oEditorModel->getEditor($this->memberInfo->member_srl, $option);
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
         * @brief Display a list of member join form
         **/
        function dispMemberAdminJoinFormList() {
            // Create a member model object
            $oMemberModel = &getModel('member');
            // Get join form list which is additionally set
            $form_list = $oMemberModel->getJoinFormList();
            Context::set('form_list', $form_list);

            $this->setTemplateFile('join_form_list');
        }

        /**
         * @brief Display an admin page for memebr join forms
         **/
        function dispMemberAdminInsertJoinForm() {
            // Get the value of join_form
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
         * @brief Display denied ID list
         **/
        function dispMemberAdminDeniedIDList() {
            // Create a member model object
            $oMemberModel = &getModel('member');
            // Get a denied ID list
            $output = $oMemberModel->getDeniedIDList();

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('denied_id_list');
        }

        /**
         * @brief Update all the member groups
         **/
        function dispMemberAdminManageGroup() {
            // Get a list of the selected member
            $args->member_srl = trim(Context::get('member_srls'));
            $output = executeQueryArray('member.getMembers', $args);
            Context::set('member_list', $output->data);
            // Get a list of the selected member
            $oMemberModel = &getModel('member');
            Context::set('member_groups', $oMemberModel->getGroups());

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('manage_member_group');
        }

        /**
         * @brief Delete all members
         **/
        function dispMemberAdminDeleteMembers() {
            // Get a list of the selected member
            $args->member_srl = trim(Context::get('member_srls'));
            $output = executeQueryArray('member.getMembers', $args);
            Context::set('member_list', $output->data);

            $this->setLayoutFile('popup_layout');
            $this->setTemplateFile('delete_members');
        }

		/**
		 *
		 **/

		function dispMemberAdminSiteMemberList(){
            $oMemberModel = &getModel('member');
			$site_module_info = Context::get('current_module_info');
			$site_srl = $site_module_info->site_srl;

            // 회원 그룹을 구함
            $group_list = $oMemberModel->getGroups($site_srl);
            if(!$group_list) $group_list = array();
            Context::set('group_list', $group_list);

			// 회원 목록을 구함
            $args->selected_group_srl = Context::get('selected_group_srl');
            if(!isset($group_list[$args->selected_group_srl])) {
                $args->selected_group_srl = implode(',',array_keys($group_list));
            }
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = ereg_replace("[^0-9]","",$search_keyword);
                        break;
                    case 'regdate_more' :
                            $args->s_regdate_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'regdate_less' :
                            $args->s_regdate_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'last_login' :
                            $args->s_last_login = $search_keyword;
                        break;
                    case 'last_login_more' :
                            $args->s_last_login_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'last_login_less' :
                            $args->s_last_login_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
                        break;
                    case 'extra_vars' :
                            $args->s_extra_vars = ereg_replace("[^0-9]","",$search_keyword);
                        break;
                }
            }

		    $query_id = 'member.getMemberListWithinGroup';
		    $args->sort_index = "member.member_srl";
		    $args->sort_order = "desc";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $output = executeQuery($query_id, $args);

            $members = array();
            if(count($output->data)) {
                foreach($output->data as $key=>$val) {
                    $members[] = $val->member_srl;
                }
            }

            $members_groups = $oMemberModel->getMembersGroups($members, $site_srl);
            Context::set('members_groups',$members_groups);
            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			$this->setTemplateFile('stUserList');
		}
    }
?>
