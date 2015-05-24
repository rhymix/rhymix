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
		// Get the configuration information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('communication');

		// get the default information
		$args = Context::gets('able_module', 'skin', 'colorset', 'editor_skin', 'sel_editor_colorset', 'mskin', 'mcolorset', 'layout_srl', 'mlayout_srl', 'grant_write_default','grant_write_group');

		//if module IO config is off
		if($args->able_module === 'Y')
		{
			// Re-install triggers, if it was disabled.
			if($config->able_module == 'N')
			{
				$this->moduleUpdate();
			}

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
		}
		else
		{
			//module IO config is OFF, Other settings will not be modified.
			$config->able_module = 'N';
			$args = $config;

			// Delete Triggers
			$oModuleController = getController('module');
			$oModuleController->deleteModuleTriggers('communication');
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
