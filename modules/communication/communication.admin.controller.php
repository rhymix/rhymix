<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief communication module of the admin controller class
 */
class communicationAdminController extends communication
{

	/**
	 * Initialization
	 */
	function init()
	{

	}

	/**
	 * save configurations of the communication module
	 * @return void|Object (success : void, fail : Object)
	 */
	function procCommunicationAdminInsertConfig()
	{
		// get the default information
		$args = Context::gets('skin', 'colorset', 'editor_skin', 'sel_editor_colorset', 'mskin', 'mcolorset', 'layout_srl', 'mlayout_srl', 'grant_write_default','grant_write_group');
		$args->editor_colorset = $args->sel_editor_colorset;
		unset($args->sel_editor_colorset);

		if(!$args->skin)
		{
			$args->skin = 'default';
		}

		if(!$args->colorset)
		{
			$args->colorset = 'white';
		}

		if(!$args->editor_skin)
		{
			$args->editor_skin = 'default';
		}

		if(!$args->mskin)
		{
			$args->mskin = 'default';
		}

		if(!$args->layout_srl)
		{
			$args->layout_srl = NULL;
		}

		$oCommunicationModel = getModel('communication');
		$args->grant_write = $oCommunicationModel->getGrantArray($args->grant_write_default, $args->grant_write_group);
		unset($args->grant_write_default);
		unset($args->grant_write_group);

		// create the module module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('communication', $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommunicationAdminConfig');
		
		return $this->setRedirectUrl($returnUrl, $output);
	}

}
/* End of file communication.admin.controller.php */
/* Location: ./modules/comment/communication.admin.controller.php */
