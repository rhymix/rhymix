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
           // if(Context::getRequestMethod() == "GET") return new Object(-1, "msg_invalid_request");
            // Extract the necessary information in advance
            $args = Context::gets('member_srl','email_address','find_account_answer', 'allow_mailing','allow_message','denied','is_admin','description','group_srl_list','limit_date');
            $oMemberModel = &getModel ('member');
            $config = $oMemberModel->getMemberConfig ();
			$getVars = array();
			if ($config->signupForm){
				foreach($config->signupForm as $formInfo){
					if($formInfo->isDefaultForm && ($formInfo->isUse || $formInfo->required || $formInfo->mustRequired)){
						$getVars[] = $formInfo->name;
					}
				}
			}
			foreach($getVars as $val){
				$args->{$val} = Context::get($val);
			}
			$args->member_srl = Context::get('member_srl');
			if (Context::get('reset_password'))
				$args->password = Context::get('reset_password');
			else unset($args->password);

            // Remove some unnecessary variables from all the vars
            $all_args = Context::getRequestVars();
            unset($all_args->module);
            unset($all_args->act);
            unset($all_args->mid);
            unset($all_args->error_return_url);
            unset($all_args->success_return_url);
            unset($all_args->ruleset);
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

			// remove whitespace
			$checkInfos = array('user_id', 'nick_name', 'email_address');
			$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
			foreach($checkInfos as $val){
				if(isset($args->{$val})){
					$args->{$val} = str_replace($replaceStr, '', $args->{$val});
				}
			}

            $oMemberController = &getController('member');
            // Execute insert or update depending on the value of member_srl
            if(!$args->member_srl) {
				$args->password = Context::get('password');
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

			$profile_image = $_FILES['profile_image'];
			if (is_uploaded_file($profile_image['tmp_name'])){
				$oMemberController->insertProfileImage($args->member_srl, $profile_image['tmp_name']);
			}

			$image_mark = $_FILES['image_mark'];
			if (is_uploaded_file($image_mark['tmp_name'])){
				$oMemberController->insertImageMark($args->member_srl, $image_mark['tmp_name']);
			}

			$image_name = $_FILES['image_name'];
			if (is_uploaded_file($image_name['tmp_name'])){
				$oMemberController->insertImageName($args->member_srl, $image_name['tmp_name']);
			}
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

		function procMemberAdminInsertConfig(){
            $input_args = Context::gets(
				'enable_join',
				'enable_confirm',
				'webmaster_name',
				'webmaster_email',
				'limit_day',
				'change_password_date',
				'agreement',
				'after_login_url',
				'after_logout_url',
				'redirect_url',
				'skin',
				'colorset',
                'profile_image', 'profile_image_max_width', 'profile_image_max_height',
                'image_name', 'image_name_max_width', 'image_name_max_height',
                'image_mark', 'image_mark_max_width', 'image_mark_max_height'
            );

			$list_order = Context::get('list_order');
			$usable_list = Context::get('usable_list');
			$all_args = Context::getRequestVars();

			$oModuleController = &getController('module');
            $oMemberModel = &getModel('member');

			// default setting start
            if($input_args->enable_join != 'Y'){
				$args->enable_join = 'N';
			}else{
				$args = $input_args;
				$args->enable_join = 'Y';
				if($args->enable_confirm !='Y') $args->enable_confirm = 'N';
				$args->limit_day = (int)$args->limit_day;
				if(!$args->change_password_date) $args->change_password_date = 0; 
				if(!trim(strip_tags($args->agreement))) $args->agreement = null;
				if(!trim(strip_tags($args->after_login_url))) $args->after_login_url = null;
				if(!trim(strip_tags($args->after_logout_url))) $args->after_logout_url = null;
				if(!trim(strip_tags($args->redirect_url))) $args->redirect_url = null;

				if(!$args->skin) $args->skin = "default";
				if(!$args->colorset) $args->colorset = "white";

				$args->profile_image = $args->profile_image?'Y':'N';
				$args->image_name = $args->image_name?'Y':'N';
				$args->image_mark = $args->image_mark?'Y':'N';
				if($args->signature!='Y') $args->signature = 'N';
				$args->identifier = $all_args->identifier;

				// signupForm
				global $lang;
				$signupForm = array();
				$items = array('user_id', 'password', 'user_name', 'nick_name', 'email_address', 'find_account_question', 'homepage', 'blog', 'birthday', 'signature', 'profile_image', 'image_name', 'image_mark', 'profile_image_max_width', 'profile_image_max_height', 'image_name_max_width', 'image_name_max_height', 'image_mark_max_width', 'image_mark_max_height');
				$mustRequireds = array('email_address', 'nick_name', 'password', 'find_account_question');
				$extendItems = $oMemberModel->getJoinFormList();
				foreach($list_order as $key){
					unset($signupItem);
					$signupItem->isIdentifier = ($key == $all_args->identifier);
					$signupItem->isDefaultForm = in_array($key, $items);
					
					$signupItem->name = $key;
					if(in_array($key, $items)) $signupItem->title = $key;
					else $signupItem->title = $lang->{$key};
					$signupItem->mustRequired = in_array($key, $mustRequireds);
					$signupItem->imageType = (strpos($key, 'image') !== false);
					$signupItem->required = ($all_args->{$key} == 'required') || $signupItem->mustRequired || $signupItem->isIdentifier;
					$signupItem->isUse = in_array($key, $usable_list) || $signupItem->required;

					if ($signupItem->imageType){
						$signupItem->max_width = $all_args->{$key.'_max_width'};
						$signupItem->max_height = $all_args->{$key.'_max_height'};
					}

					// set extends form
					if (!$signupItem->isDefaultForm){
						$extendItem = $extendItems[$all_args->{$key.'_member_join_form_srl'}];
						$signupItem->type = $extendItem->column_type;
						$signupItem->member_join_form_srl = $extendItem->member_join_form_srl;
						$signupItem->title = $extendItem->column_title;
						$signupItem->description = $extendItem->description;

						// check usable value change, required/option
						if ($signupItem->isUse != ($extendItem->is_active == 'Y') || $signupItem->required != ($extendItem->required == 'Y')){
							unset($update_args);
							$update_args->member_join_form_srl = $extendItem->member_join_form_srl;
							$update_args->is_active = $signupItem->isUse?'Y':'N';
							$update_args->required = $signupItem->required?'Y':'N';

							$update_output = executeQuery('member.updateJoinForm', $update_args);
						}
						unset($extendItem);
					}
					$signupForm[] = $signupItem;
				}
				$args->signupForm = $signupForm;

				// create Ruleset
				$this->_createSignupRuleset($signupForm, $args->agreement);
				$this->_createLoginRuleset($args->identifier);
				$this->_createFindAccountByQuestion($args->identifier);
			}
			$output = $oModuleController->updateModuleConfig('member', $args);
			// default setting end

 			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminConfig');
				$this->setRedirectUrl($returnUrl);
				return;
 			}
		}

		function _createSignupRuleset($signupForm, $agreement = null){
			$xml_file = './files/ruleset/insertMember.xml';
			$buff = '<?xml version="1.0" encoding="utf-8"?>'
					.'<ruleset version="1.5.0">'
				    .'<customrules>'
					.'</customrules>'
					.'<fields>%s</fields>'						
					.'</ruleset>';

			$fields = array();
			
			if ($agreement){
				$fields[] = '<field name="accept_agreement"><if test="$act == \'procMemberInsert\'" attr="required" value="true" /></field>';
			}
			foreach($signupForm as $formInfo){
				if ($formInfo->required || $formInfo->mustRequired){
					if($formInfo->type == 'tel' || $formInfo->type == 'kr_zip'){
						$fields[] = sprintf('<field name="%s[]" required="true" />', $formInfo->name);
					}else if($formInfo->name == 'password'){
						$fields[] = '<field name="password"><if test="$act == \'procMemberInsert\'" attr="required" value="true" /><if test="$act == \'procMemberInsert\'" attr="length" value="3:20" /></field>';
						$fields[] = '<field name="password2"><if test="$act == \'procMemberInsert\'" attr="required" value="true" /><if test="$act == \'procMemberInsert\'" attr="equalto" value="password" /></field>';
					}else if($formInfo->name == 'find_account_question'){
						$fields[] = '<field name="find_account_question"><if test="$act != \'procMemberAdminInsert\'" attr="required" value="true" /></field>';
						$fields[] = '<field name="find_account_answer"><if test="$act != \'procMemberAdminInsert\'" attr="required" value="true" /><if test="$act != \'procMemberAdminInsert\'" attr="length" value=":250" /></field>';
					}else if($formInfo->name == 'email_address'){
						$fields[] = sprintf('<field name="%s" required="true" rule="email"/>', $formInfo->name);
					}else if($formInfo->name == 'user_id'){
						$fields[] = sprintf('<field name="%s" required="true" rule="userid" length="3:20" />', $formInfo->name);
					}else if(strpos($formInfo->name, 'image') !== false){
						$fields[] = sprintf('<field name="%s"><if test="$act != \'procMemberAdminInsert\' &amp;&amp; $__%s_exist != \'true\'" attr="required" value="true" /></field>', $formInfo->name, $formInfo->name);
					}else{
						$fields[] = sprintf('<field name="%s" required="true" />', $formInfo->name);
					}
				}
			}

			$xml_buff = sprintf($buff, implode('', $fields));
            FileHandler::writeFile($xml_file, $xml_buff);
			unset($xml_buff);

			$validator   = new Validator($xml_file);
			$validator->setCacheDir('files/cache');
			$validator->getJsPath();
		}

		function _createLoginRuleset($identifier){
			$xml_file = './files/ruleset/login.xml';
			$buff = '<?xml version="1.0" encoding="utf-8"?>'
					.'<ruleset version="1.5.0">'
				    .'<customrules>'
					.'</customrules>'
					.'<fields>%s</fields>'						
					.'</ruleset>';

			$fields = array();
			$trans = array('email_address'=>'email', 'user_id'=> 'userid');
			$fields[] = sprintf('<field name="user_id" required="true" rule="%s"/>', $trans[$identifier]);
			$fields[] = '<field name="password" required="true" />';

			$xml_buff = sprintf($buff, implode('', $fields));
            Filehandler::writeFile($xml_file, $xml_buff);

			$validator   = new Validator($xml_file);
			$validator->setCacheDir('files/cache');
			$validator->getJsPath();
		}

		function _createFindAccountByQuestion($identifier){
			$xml_file = './files/ruleset/find_member_account_by_question.xml';
			$buff = '<?xml version="1.0" encoding="utf-8"?>'
					.'<ruleset version="1.5.0">'
				    .'<customrules>'
					.'</customrules>'
					.'<fields>%s</fields>'						
					.'</ruleset>';

			$fields = array();
			if ($identifier == 'user_id')
				$fields[] = '<field name="user_id" required="true" rule="userid" />';

			$fields[] = '<field name="email_address" required="true" rule="email" />';
			$fields[] = '<field name="find_account_question" required="true" />';
			$fields[] = '<field name="find_account_answer" required="true" length=":250"/>';

			$xml_buff = sprintf($buff, implode('', $fields));
            Filehandler::writeFile($xml_file, $xml_buff);

			$validator   = new Validator($xml_file);
			$validator->setCacheDir('files/cache');
			$validator->getJsPath();
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
            $args->default_value = explode("\n", str_replace("\r", '', Context::get('default_value')));
            $args->required = Context::get('required');
			$args->is_active = (isset($args->required));
            if(!in_array(strtoupper($args->required), array('Y','N')))$args->required = 'N';
            $args->description = Context::get('description');
            // Default values
            if(in_array($args->column_type, array('checkbox','select','radio')) && count($args->default_value) ) {
                $args->default_value = serialize($args->default_value);
            } else {
                $args->default_value = '';
            }
            // Fix if member_join_form_srl exists. Add if not exists.
            $isInsert;
			if(!$args->member_join_form_srl){
				$isInsert = true;
				$args->list_order = $args->member_join_form_srl = getNextSequence();
                $output = executeQuery('member.insertJoinForm', $args);
            }else{
                $output = executeQuery('member.updateJoinForm', $args);
            }

            if(!$output->toBool()) return $output;

			// memberConfig update
			$signupItem->name = $args->column_name;
			$signupItem->title = $args->column_title;
			$signupItem->type = $args->column_type;
			$signupItem->member_join_form_srl = $args->member_join_form_srl;
			$signupItem->required = ($args->required == 'Y');
			$signupItem->isUse = ($args->is_active == 'Y');
			$signupItem->description = $args->description;

			$oMemberModel = &getModel('member');
			$config = $oMemberModel->getMemberConfig();

			if($isInsert){
				$config->signupForm[] = $signupItem;	
			}else{
				foreach($config->signupForm as $key=>$val){
					if ($val->member_join_form_srl == $signupItem->member_join_form_srl){
						$config->signupForm[$key] = $signupItem;
					}
				}
			}
			$oModuleController = &getController('module');
			$output = $oModuleController->updateModuleConfig('member', $config);

            $this->setMessage('success_registed');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminJoinFormList');
				$this->setRedirectUrl($returnUrl);
				return;
			}
        }

		function procMemberAdminDeleteJoinForm(){
            $member_join_form_srl = Context::get('member_join_form_srl');
			$this->deleteJoinForm($member_join_form_srl);

			$oMemberModel = &getModel('member');
			$config = $oMemberModel->getMemberConfig();

			foreach($config->signupForm as $key=>$val){
				if ($val->member_join_form_srl == $member_join_form_srl){
					unset($config->signupForm[$key]);
					break;
				}
			}
			$oModuleController = &getController('module');
			$output = $oModuleController->updateModuleConfig('member', $config);
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
		 * selected member manager layer in dispAdminList 
		 **/
		function procMemberAdminSelectedMemberManage(){
			$var = Context::getRequestVars();
			$groups = $var->groups;
			$members = $var->member_srls;

            $oDB = &DB::getInstance();
            $oDB->begin();

			$oMemberController = &getController('member');
			foreach($members as $key=>$member_srl){
				unset($args);
				$args->member_srl = $member_srl; 
				switch($var->type){
					case 'modify':{
									  if (count($groups) > 0){
											$args->site_srl = 0;
											// One of its members to delete all the group
											$output = executeQuery('member.deleteMemberGroupMember', $args);
											if(!$output->toBool()) {
												$oDB->rollback();
												return $output;
											}
											// Enter one of the loop a
											foreach($groups as $group_srl) {
												$output = $oMemberController->addMemberToGroup($args->member_srl,$group_srl);
												if(!$output->toBool()) {
													$oDB->rollback();
													return $output;
												}
											}
									  }
									  if ($var->denied){
										  $args->denied = $var->denied;
										  $output = executeQuery('member.updateMemberDeniedInfo', $args);
										  if(!$output->toBool()) {
											  $oDB->rollback();
											  return $output;
										  }
									  }
									  break;
								  }
					case 'delete':{
									  $oMemberController->memberInfo = null;
									  $output = $oMemberController->deleteMember($member_srl);
									  if(!$output->toBool()) {
										  $oDB->rollback();
										  return $output;
									  }
								  }
				}
			}

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

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminList');
				$this->setRedirectUrl($returnUrl);
				return;
			}
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
				htmlHeader();
				alertScript($lang->success_updated);
				reload(true);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
        }

        /**
         * @brief Add a denied ID
         **/
        function procMemberAdminInsertDeniedID() {
            $user_ids = Context::get('user_id');

			$user_ids = explode(',',$user_ids);
			$success_ids = array();

			foreach($user_ids as $val){
				$output = $this->insertDeniedID($val, '');
				if($output->toBool()) $success_ids[] = $val;
			}

			$this->add('user_ids', implode(',',$success_ids));

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
         * @brief find_account_answerInsert a group
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
			if(!$args->group_srl) return new Object(-1, 'lang->msg_not_founded');
            if($args->is_default!='Y') {
				$args->is_default = 'N';
			} else {
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

        /**
         * Set group config
         **/
		function procMemberAdminGroupConfig() {
			$vars = Context::getRequestVars();	

			$oMemberModel = &getModel('member');
			$oModuleController = &getController('module');

			// group image mark option
			$config = $oMemberModel->getMemberConfig();
			$config->group_image_mark = $vars->group_image_mark;
			$output = $oModuleController->updateModuleConfig('member', $config);

			// group data save
			$group_srls = $vars->group_srls;
			foreach($group_srls as $order=>$group_srl){
				unset($update_args);
				$update_args->title = $vars->group_titles[$order];
				$update_args->is_default = ($vars->defaultGroup == $group_srl)?'Y':'N';
				$update_args->description = $vars->descriptions[$order];
				$update_args->image_mark = $vars->image_marks[$order];
				$update_args->list_order = $order + 1;

				if (is_numeric($group_srl)){
					$update_args->group_srl = $group_srl;
					$output = $this->updateGroup($update_args);
				}else
					$output = $this->insertGroup($update_args);
			}

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemberAdminGroupList');
				$this->setRedirectUrl($returnUrl);
				return;
			}
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
    }
?>
