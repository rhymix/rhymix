<?php

class memberMobile extends member
{
    function init() {
        // Get the member configuration
        $oModuleModel = &getModel('module');
        $this->member_config = $oModuleModel->getModuleConfig('member');
        
        Context::set('member_config', $this->member_config);

        $this->setTemplatePath($this->module_path.'tpl');
    }

    function dispMemberLoginForm() {
        if(Context::get('is_logged')) {
            Context::set('redirect_url', getUrl('act',''));
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('redirect.html');
            return;
        }

        // Set a template file

        Context::set('referer_url', $_SERVER['HTTP_REFERER']);
        $this->setTemplateFile('login_form');
    }

    function dispMemberSignUpForm(){
        $oMemberModel = &getModel('member');

        if($oMemberModel->isLogged()) return $this->stop('msg_already_logged');

        $trigger_output = ModuleHandler::triggerCall('member.dispMemberSignUpForm', 'before', $this->member_config);
        if(!$trigger_output->toBool()) return $trigger_output;

        if ($this->member_config->enable_join != 'Y') return $this->stop('msg_signup_disabled');
        Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

        $member_config = $oMemberModel->getMemberConfig();
        Context::set('member_config', $member_config);

        $this->setTemplateFile('signup_form');
    }

    function dispMemberInfo() {
        $oMemberModel = &getModel('member');
        $logged_info = Context::get('logged_info');

        // Don't display member info to non-logged user
        if(!$logged_info->member_srl) return $this->stop('msg_not_permitted');
        $member_srl = Context::get('member_srl');
        if(!$member_srl && Context::get('is_logged')) {
            $member_srl = $logged_info->member_srl;
        } elseif(!$member_srl) {
            return $this->dispMemberSignUpForm();
        }
            
        $site_module_info = Context::get('site_module_info');
        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, $site_module_info->site_srl);
        unset($member_info->password);
        unset($member_info->email_id);
        unset($member_info->email_host);
        unset($member_info->email_address);
                
        if(!$member_info->member_srl) return $this->dispMemberSignUpForm();
                
        Context::set('member_info', $member_info);
        Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));
        if ($member_info->member_srl == $logged_info->member_srl)
            Context::set('openids', $oMemberModel->getMemberOpenIDByMemberSrl($member_srl));
        $this->setTemplateFile('member_info_mobile');
    }

    /**
     * @brief Edit member profile
     **/
    function dispMemberModifyInfo() {
        $oMemberModel = &getModel('member');
        $oModuleModel = &getModel('module');
        $memberModuleConfig = $oModuleModel->getModuleConfig('member');

        // A message appears if the user is not logged-in
        if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

        $logged_info = Context::get('logged_info');
        $member_srl = $logged_info->member_srl;

        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
        $member_info->signature = $oMemberModel->getSignature($member_srl);
        Context::set('member_info',$member_info);

        // Receive a member join form
        Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

        Context::set('openids', $oMemberModel->getMemberOpenIDByMemberSrl($member_srl));

        // Call getEditor of the editor module and set it for signiture
        if($member_info->member_srl) {
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'member_srl';
            $option->content_key_name = 'signature';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = false;
            $option->resizable = false;
            $option->disable_html = true;
            $option->height = 200;
            $option->skin = $this->member_config->editor_skin;
            $option->colorset = $this->member_config->editor_colorset;
            $editor = $oEditorModel->getEditor($member_info->member_srl, $option);
            Context::set('editor', $editor);
        }

        // Set a template file
        $this->setTemplateFile('modify_info');
    }

    /**
     * @brief Change the user password
     **/
    function dispMemberModifyPassword() {
        $oMemberModel = &getModel('member');

        // A message appears if the user is not logged-in
        if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

        $logged_info = Context::get('logged_info');
        $member_srl = $logged_info->member_srl;

        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
        Context::set('member_info',$member_info);

        // Set a template file
        $this->setTemplateFile('modify_password');
    }

    /**
     * @brief Member withdrawl
     **/
    function dispMemberLeave() {
        $oMemberModel = &getModel('member');

        // A message appears if the user is not logged-in
        if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

        $logged_info = Context::get('logged_info');
        $member_srl = $logged_info->member_srl;

        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
        Context::set('member_info',$member_info);

        // Set a template file
        $this->setTemplateFile('leave_form');
    }
}
?>
