<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class MemberMobile extends MemberView
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

		// Set layout and skin paths
		$this->setLayoutAndTemplatePaths('M', $this->member_config);
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
