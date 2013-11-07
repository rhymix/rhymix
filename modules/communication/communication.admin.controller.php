<?php

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
		$args = Context::gets('skin', 'colorset', 'editor_skin', 'sel_editor_colorset', 'mskin', 'mcolorset', 'layout_srl', 'mlayout_srl');
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

		// create the module module Controller object
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('communication', $args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommunicationAdminConfig');
		
		return $this->setRedirectUrl($returnUrl, $output);
	}

}
/* End of file communication.admin.controller.php */
/* Location: ./modules/comment/communication.admin.controller.php */
