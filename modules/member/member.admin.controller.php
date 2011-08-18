<?php
    /**
     * @class  memberAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief member module of the admin controller class
     **/

    class memberAdminController extends member {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Add a user (Administrator)
         **/
        function procMemberAdminInsert() {
            if(Context::getRequestMethod() == "GET") return new Object(-1, "msg_invalid_request");
            // Extract the necessary information in advance
            $args = Context::gets('member_srl','user_id','user_name','nick_name','homepage','blog','birthday','email_address','password','allow_mailing','allow_message','denied','is_admin','description','group_srl_list','limit_date');
            // Remove some unnecessary variables from all the vars
            $all_args = Context::getRequestVars();
            unset($all_args->module);
            unset($all_args->act);
            if(!isset($args->limit_date)) $args->limit_date = "";
            // Add extra vars after excluding necessary information from all the requested arguments
            $extra_vars = delObjectVars($all_args, $args);
            $args->extra_vars = serialize($extra_vars);
            // Check if an original member exists having the member_srl
            if($args->member_srl) {
                // Create a member model object
                $oMemberModel = &getModel('member');
                // Get memebr profile
				$columnList = array('member_srl');
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl, 0, $columnList);
                // If no original member exists, make a new one
                if($member_info->member_srl != $args->member_srl) unset($args->member_srl);
            }

            $oMemberController = &getController('member');
            // Execute insert or update depending on the value of member_srl
            if(!$args->member_srl) {
                $output = $oMemberController->insertMember($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oMemberController->updateMember($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;
            // Save Signature
            $signature = Context::get('signature');
            $oMemberController->putSignature($args->member_srl, $signature);
            // Return result
            $this->add('member_srl', $args->member_srl);
            $this->setMessage($msg_code);

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Delete a user (Administrator)
         **/
        function procMemberAdminDelete() {
            // Separate all the values into DB entries and others
            $member_srl = Context::get('member_srl');

            $oMemberController = &getController('member');
            $output = $oMemberController->deleteMember($member_srl);
            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->setMessage("success_deleted");
        }

        /**
         * @brief Add information for member administration
         **/
        function procMemberAdminInsertConfig() {
            // Get the basic information
            $args = Context::gets(
                'webmaster_name', 'webmaster_email',
                'skin', 'colorset',
                'editor_skin', 'editor_colorset',
                'enable_openid', 'enable_join', 'enable_confirm', 'limit_day',
                'after_login_url', 'after_logout_url', 'redirect_url', 'agreement',
                'profile_image', 'profile_image_max_width', 'profile_image_max_height',
                'image_name', 'image_name_max_width', 'image_name_max_height',
                'image_mark', 'image_mark_max_width', 'image_mark_max_height',
                'group_image_mark', 'group_image_mark_max_width', 'group_image_mark_max_height',
                'signature','signature_max_height','change_password_date'
            );

            if(!$args->skin) $args->skin = "default";
            if(!$args->colorset) $args->colorset = "white";
            if(!$args->editor_skin) $args->editor_skin= "xpresseditor";
            if(!$args->editor_colorset) $args->editor_colorset = "white";
            if($args->enable_join!='Y') $args->enable_join = 'N';
            if($args->enable_openid!='Y') $args->enable_openid= 'N';
            if($args->profile_image !='Y') $args->profile_image = 'N';
            if($args->image_name!='Y') $args->image_name = 'N';
            if($args->image_mark!='Y') $args->image_mark = 'N';
            if($args->group_image_mark!='Y') $args->group_image_mark = 'N';
            if($args->signature!='Y') $args->signature = 'N';
            if(!trim(strip_tags($args->agreement))) $args->agreement = null;
            $args->limit_day = (int)$args->limit_day;
            if(!$args->change_password_date) $args->change_password_date = 0; 

            $oMemberController = &getController('member');
            $output = $oMemberController->setMemberConfig($args);

			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminConfig');
				header('location:'.$returnUrl);
				return;
			}
            return $output;
        }

        /**
         * @brief Add a user group
         **/
        function procMemberAdminInsertGroup() {
            $args = Context::gets('title','description','is_default','image_mark');
            $output = $this->insertGroup($args);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminGroupList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Update user group information
         **/
        function procMemberAdminUpdateGroup() {
            $group_srl = Context::get('group_srl');

			$args = Context::gets('group_srl','title','description','is_default','image_mark');
			$args->site_srl = 0;
			$output = $this->updateGroup($args);
			if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_updated');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminGroupList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Update user group information
         **/
        function procMemberAdminDeleteGroup() {
            $group_srl = Context::get('group_srl');

			$output = $this->deleteGroup($group_srl);
			if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminGroupList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Add a join form
         **/
        function procMemberAdminInsertJoinForm() {
            $args->member_join_form_srl = Context::get('member_join_form_srl');

            $args->column_type = Context::get('column_type');
            $args->column_name = strtolower(Context::get('column_name'));
            $args->column_title = Context::get('column_title');
            $args->default_value = explode('|@|', Context::get('default_value'));
            $args->is_active = Context::get('is_active');
            if(!in_array(strtoupper($args->is_active), array('Y','N'))) $args->is_active = 'N';
            $args->required = Context::get('required');
            if(!in_array(strtoupper($args->required), array('Y','N'))) $args->required = 'N';
            $args->description = Context::get('description');
            // Default values
            if(in_array($args->column_type, array('checkbox','select','radio')) && count($args->default_value) ) {
                $args->default_value = serialize($args->default_value);
            } else {
                $args->default_value = '';
            }
            // Fix if member_join_form_srl exists. Add if not exists.
            if(!$args->member_join_form_srl){
                $args->list_order = getNextSequence();
                $output = executeQuery('member.insertJoinForm', $args);
            }else{
                $output = executeQuery('member.updateJoinForm', $args);
            }

            if(!$output->toBool()) return $output;

            $this->add('act','dispJoinForm');
            $this->setMessage('success_registed');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminJoinFormList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Move up/down the member join form and modify it
         **/
        function procMemberAdminUpdateJoinForm() {
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
         * @brief Delete the selected members
         */
        function procMemberAdminDeleteMembers() {
            $target_member_srls = Context::get('target_member_srls');
            if(!$target_member_srls) return new Object(-1, 'msg_invalid_request');
            $member_srls = explode(',', $target_member_srls);
            $oMemberController = &getController('member');

            foreach($member_srls as $member) {
                $output = $oMemberController->deleteMember($member);
                if(!$output->toBool()) {
                    $this->setMessage('failed_deleted');
                    return $output;
                }
            }

            $this->setMessage('success_deleted');
        }

        /**
         * @brief Update a group of selected memebrs
         **/
        function procMemberAdminUpdateMembersGroup() {
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return new Object(-1,'msg_invalid_request');
            $member_srls = explode(',',$member_srl);

            $group_srl = Context::get('group_srls');
            if(!is_array($group_srl)) $group_srls = explode('|@|', $group_srl);
			else $group_srls = $group_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();
            // Delete a group of selected members
            $args->member_srl = $member_srl;
            $output = executeQuery('member.deleteMembersGroup', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // Add to a selected group
            $group_count = count($group_srls);
            $member_count = count($member_srls);
            for($j=0;$j<$group_count;$j++) {
                $group_srl = (int)trim($group_srls[$j]);
                if(!$group_srl) continue;
                for($i=0;$i<$member_count;$i++) {
                    $member_srl = (int)trim($member_srls[$i]);
                    if(!$member_srl) continue;

                    $args = null;
                    $args->member_srl = $member_srl;
                    $args->group_srl = $group_srl;

                    $output = executeQuery('member.addMemberToGroup', $args);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }
            }
            $oDB->commit();
            $this->setMessage('success_updated');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				global $lang;
				alertScript($lang->success_updated);
				reload(true);
				closePopupScript();
				exit;
			}
        }

        /**
         * @brief Add a denied ID
         **/
        function procMemberAdminInsertDeniedID() {
            $user_id = Context::get('user_id');
            $description = Context::get('description');

            $output = $this->insertDeniedID($user_id, $description);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminDeniedIDList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Update denied ID
         **/
        function procMemberAdminUpdateDeniedID() {
            $user_id = Context::get('user_id');
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->deleteDeniedID($user_id);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_deleted';
                    break;
            }

            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief Add an administrator
         **/
        function insertAdmin($args) {
            // Assign an administrator
            $args->is_admin = 'Y';
            // Get admin group and set
            $oMemberModel = &getModel('member');
            $admin_group = $oMemberModel->getAdminGroup();
            $args->group_srl_list = $admin_group->group_srl;

            $oMemberController = &getController('member');
            return $oMemberController->insertMember($args);
        }

        /**
         * @brief Change the group values of member
         **/
        function changeGroup($source_group_srl, $target_group_srl) {
            $args->source_group_srl = $source_group_srl;
            $args->target_group_srl = $target_group_srl;

            return executeQuery('member.changeGroup', $args);
        }

        /**
         * @brief Insert a group
         **/
        function insertGroup($args) {
            if(!$args->site_srl) $args->site_srl = 0;
            // Check the value of is_default. 
            if($args->is_default!='Y') {
                $args->is_default = 'N';
            } else {
                $output = executeQuery('member.updateGroupDefaultClear', $args);
                if(!$output->toBool()) return $output;
            }
			
			if (!$args->group_srl) $args->group_srl = getNextSequence();

            return executeQuery('member.insertGroup', $args);
        }

        /**
         * @brief Modify Group Information
         **/
        function updateGroup($args) {
            // Check the value of is_default. 
            if($args->is_default!='Y') $args->is_default = 'N';
            else {
                $output = executeQuery('member.updateGroupDefaultClear', $args);
                if(!$output->toBool()) return $output;
            }

            return executeQuery('member.updateGroup', $args);
        }

        /**
         * Delete a Group
         **/
        function deleteGroup($group_srl, $site_srl = 0) {
            // Create a member model object
            $oMemberModel = &getModel('member');
            // Check the group_srl (If is_default == 'Y', it cannot be deleted)
			$columnList = array('group_srl', 'is_default');
            $group_info = $oMemberModel->getGroup($group_srl, $columnList);

            if(!$group_info) return new Object(-1, 'lang->msg_not_founded');
            if($group_info->is_default == 'Y') return new Object(-1, 'msg_not_delete_default');
            // Get groups where is_default == 'Y'
			$columnList = array('site_srl', 'group_srl');
            $default_group = $oMemberModel->getDefaultGroup($site_srl, $columnList);
            $default_group_srl = $default_group->group_srl;
            // Change to default_group_srl
            $this->changeGroup($group_srl, $default_group_srl);

            $args->group_srl = $group_srl;
            return executeQuery('member.deleteGroup', $args);
        }


        function procMemberAdminUpdateGroupOrder() {
			$vars = Context::getRequestVars();
			
			foreach($vars->group_srls as $key => $val){
				$args->group_srl = $val;
				$args->list_order = $key + 1;
				executeQuery('member.updateMemberGroupListOrder', $args);
			}

			header(sprintf('Location:%s', getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminGroupList')));
        }

        /**
         * @brief Register denied ID
         **/
        function insertDeniedID($user_id, $description = '') {
            $args->user_id = $user_id;
            $args->description = $description;
            $args->list_order = -1*getNextSequence();

            return executeQuery('member.insertDeniedID', $args);
        }

        /**
         * @brief Delete a denied ID
         **/
        function deleteDeniedID($user_id) {
            $args->user_id = $user_id;
            return executeQuery('member.deleteDeniedID', $args);
        }

        /**
         * @brief Delete a join form
         **/
        function deleteJoinForm($member_join_form_srl) {
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.deleteJoinForm', $args);
            return $output;
        }

        /**
         * @brief Move up a join form
         **/
        function moveJoinFormUp($member_join_form_srl) {
            $oMemberModel = &getModel('member');
            // Get information of the join form
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;
            // Get a list of all join forms
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            $prev_member_join_form = NULL;
            foreach($join_form_list as $key => $val) {
                if($val->member_join_form_srl == $member_join_form_srl) break;
                $prev_member_join_form = $val;
            }
            // Return if no previous join form exists
            if(!$prev_member_join_form) return new Object();
            // Information of the join form
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $prev_member_join_form->list_order;
            // Information of the target join form
            $prev_args->member_join_form_srl = $prev_member_join_form->member_join_form_srl;
            $prev_args->list_order = $list_order;
            // Execute Query
            $output = executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            executeQuery('member.updateMemberJoinFormListorder', $prev_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief Move down a join form
         **/
        function moveJoinFormDown($member_join_form_srl) {
            $oMemberModel = &getModel('member');
            // Get information of the join form
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;
            // Get information of all join forms
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            for($i=0;$i<count($join_form_srl_list);$i++) {
                if($join_form_srl_list[$i]==$member_join_form_srl) break;
            }

            $next_member_join_form_srl = $join_form_srl_list[$i+1];
            // Return if no previous join form exists
            if(!$next_member_join_form_srl) return new Object();
            $next_member_join_form = $join_form_list[$next_member_join_form_srl];
            // Information of the join form
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $next_member_join_form->list_order;
            // Information of the target join form
            $next_args->member_join_form_srl = $next_member_join_form->member_join_form_srl;
            $next_args->list_order = $list_order;
            // Execute Query
            $output = executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('member.updateMemberJoinFormListorder', $next_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

		function _checkGrantSiteAdmin(){
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
			
			$site_module_info = Context::get('site_module_info');
			$oModuleModel = &getModel('module');
			$grant = $oModuleModel->getGrant($site_module_info, $logged_info);
            if(!$grant->is_site_admin) return $this->stop("msg_is_not_administrator");

			$output->grant = $grant;
			$output->site_srl = $site_module_info->site_srl;
			return $output; 
		}
		/**
		 *
		 **/
		function procMemberAdminSiteMemberManage(){
			// check siteadmin grant
			$check_info = $this->_checkGrantSiteAdmin();

			$oMemberController = &getController('member');

			$var = Context::getRequestVars();
			$groups = $var->groups;
			$members = $var->member_srls;

			$oDB = &DB::getInstance();
			$oDB->begin();

			// site_srl set
			$args->site_srl = $check_info->site_srl;
			if ($var->type == 'modify'){
				if (count($groups) > 1){
					$args->member_srl = $var->member_srls;
					// delete origin group
					$output = executeQuery('member.deleteMembersGroup', $args);
					if (!$output->toBool()){
						$oDB->rollback();
						return $output;
					}

					foreach($groups as $group_srl){
						foreach($members as $member_srl){
							$output = $oMemberController->addMemberToGroup($member_srl, $group_srl, $check_info->site_srl);
							if (!$output->toBool()){
								$oDB->rollback();
								return $output;
							}
						}
					}
				}
				else {
					$args->group_srl = $groups[0];
					$args->member_srl = $var->member_srls;
					$oMemberController->replaceMemberGroup($args);
				}
			}else if ($var->type == 'delete'){
				$args->member_srl = $members;
				$output = executeQuery('member.deleteMembersGroup', $args);
				if(!$output->toBool()){
					$oDB->rollback();
					return $output;
				}
			}

            $oDB->commit(true);

			$message = $var->message;
			// Send a message
			if($message) {
				$oCommunicationController = &getController('communication');

				$logged_info = Context::get('logged_info');
				$title = cut_str($message,10,'...');
				$sender_member_srl = $logged_info->member_srl;

				foreach($members as $member_srl){
					$oCommunicationController->sendMessage($sender_member_srl, $member_srl, $title, $message, false);
				}
			}

			$this->setRedirectUrl(Context::get('return_url'));
		}
    }
?>
