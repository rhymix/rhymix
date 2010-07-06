<?php

class memberMobile extends member
{
	function init() {
		// 회원 관리 정보를 받음
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
}
?>
