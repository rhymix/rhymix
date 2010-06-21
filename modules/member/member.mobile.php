<?php

class memberMobile extends member
{
	function init() {
		// 회원 관리 정보를 받음
		$oModuleModel = &getModel('module');
		$this->member_config = $oModuleModel->getModuleConfig('member');
		if(!$this->member_config->skin) $this->member_config->skin = "default";
		if(!$this->member_config->colorset) $this->member_config->colorset = "white";

		Context::set('member_config', $this->member_config);
		$skin = $this->member_config->mskin;

		// template path 지정
		$tpl_path = sprintf('%sm.skins/%s', $this->module_path, $skin);
		if(!$skin || !is_dir($tpl_path)) $tpl_path = sprintf('%sm.skins/%s', $this->module_path, 'default');
		$this->setTemplatePath($tpl_path);
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
