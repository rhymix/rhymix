<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief communication module of the admin controller class
 */
class CommunicationAdminController extends communication
{
	/**
	 * save configurations of the communication module
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationAdminInsertConfig()
	{
		// get the default information
		$args = Context::gets('enable_message', 'enable_friend', 'enable_attachment', 'attachment_size_limit', 'skin', 'colorset', 'editor_skin', 'sel_editor_colorset', 'mskin', 'mcolorset', 'layout_srl', 'mlayout_srl', 'grant_send_default','grant_send_group');
		$args->editor_colorset = $args->sel_editor_colorset;
		unset($args->sel_editor_colorset);

		$oCommunicationModel = getModel('communication');
		$args->grant_send = $oCommunicationModel->getGrantArray($args->grant_send_default, $args->grant_send_group);
		unset($args->grant_send_default);
		unset($args->grant_send_group);
		$args->attachment_size_limit = intval($args->attachment_size_limit) ?? 0;

		// create the module module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('communication', $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommunicationAdminConfig');

		return $this->setRedirectUrl($returnUrl, $output);
	}

}
/* End of file communication.admin.controller.php */
/* Location: ./modules/comment/communication.admin.controller.php */
