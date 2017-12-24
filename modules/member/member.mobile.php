<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class memberMobile extends memberView
{
	/**
	 * Support method are 
	 * dispMemberInfo, dispMemberSignUpForm, dispMemberFindAccount, dispMemberGetTempPassword, dispMemberModifyInfo, dispMemberModifyInfoBefore
	 */
	var $memberInfo;

	function init()
	{
		// Get the member configuration
		$oMemberModel = getModel('member');
		$this->member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $this->member_config);
		$oSecurity = new Security();
		$oSecurity->encodeHTML('member_config.signupForm..');

		// Set the template path
		$mskin = $this->member_config->mskin;
		if(!$mskin)
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
		}
		elseif($mskin === '/USE_RESPONSIVE/')
		{
			$template_path = sprintf("%sskins/%s/", $this->module_path, $this->member_config->skin);
			if(!is_dir($template_path) || !$this->member_config->skin)
			{
				$template_path = sprintf("%sskins/%s/", $this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}

		// if member_srl exists, set memberInfo
		$member_srl = Context::get('member_srl');
		if($member_srl)
		{
			$oMemberModel = getModel('member');
			$this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if(!$this->memberInfo)
			{
				Context::set('member_srl','');
			}
			else
			{
				Context::set('member_info',$this->memberInfo);
			}
		}

		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->member_config->mlayout_srl);
		if($layout_info)
		{
			$this->module_info->mlayout_srl = $this->member_config->mlayout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	function dispMemberModifyInfo()
	{
		parent::dispMemberModifyInfo();

		if($this->member_info)
		{
			Context::set('oMemberInfo', get_object_vars($this->member_info));
		}
	}

	function dispMemberScrappedDocument()
	{
		parent::dispMemberScrappedDocument();
	}
}
/* End of file member.mobile.php */
/* Location: ./modules/member/member.mobile.php */
