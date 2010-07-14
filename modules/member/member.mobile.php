<?php

class memberMobile extends member
{
	function init() {
		// 회원 관리 정보를 받음
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

		// 템플릿 파일 지정

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

        // 비회원일 경우 정보 열람 중지
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
	 * @brief 회원 정보 수정
	 **/
	function dispMemberModifyInfo() {
		$oMemberModel = &getModel('member');
		$oModuleModel = &getModel('module');
		$memberModuleConfig = $oModuleModel->getModuleConfig('member');

		// 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		$member_info->signature = $oMemberModel->getSignature($member_srl);
		Context::set('member_info',$member_info);

		// 추가 가입폼 목록을 받음
		Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

		Context::set('openids', $oMemberModel->getMemberOpenIDByMemberSrl($member_srl));

		// 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
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

		// 템플릿 파일 지정
		$this->setTemplateFile('modify_info');
	}

	/**
	 * @brief 회원 비밀번호 수정
	 **/
	function dispMemberModifyPassword() {
		$oMemberModel = &getModel('member');

		// 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		Context::set('member_info',$member_info);

		// 템플릿 파일 지정
		$this->setTemplateFile('modify_password');
	}

	/**
	 * @brief 탈퇴 화면
	 **/
	function dispMemberLeave() {
		$oMemberModel = &getModel('member');

		// 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
		if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;

		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		Context::set('member_info',$member_info);

		// 템플릿 파일 지정
		$this->setTemplateFile('leave_form');
	}
}
?>
