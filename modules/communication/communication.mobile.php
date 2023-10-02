<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationMobile
 * @author NAVER (developers@xpressengine.com)
 * Mobile class of communication module
 */
class CommunicationMobile extends communicationView
{
	function init()
	{
		$this->config = CommunicationModel::getConfig();
		Context::set('communication_config', $this->config);
		$this->setLayoutAndTemplatePaths('M', $this->config);
	}

	/**
	 * Display list of message box
	 * @return void
	 */
	function dispCommunicationMessageBoxList()
	{
		// Check member mid
		$oMemberView = MemberView::getInstance();
		if (!$oMemberView->checkMidAndRedirect())
		{
			$this->setRedirectUrl($oMemberView->getRedirectUrl());
			return;
		}

		$this->setTemplateFile('message_box');
	}
}
/* End of file communication.mobile.php */
/* Location: ./modules/comment/communication.mobile.php */
