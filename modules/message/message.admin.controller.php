<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  messageAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of message module
 */
class messageAdminController extends message
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Configuration
	 */
	function procMessageAdminInsertConfig()
	{
		// Get information
		$args = Context::gets('skin', 'mskin', 'colorset', 'mcolorset');
		// Create a module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('message',$args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMessageAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file message.admin.controller.php */
/* Location: ./modules/message/message.admin.controller.php */
