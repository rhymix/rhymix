<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  krzipAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief  Krzip module admin controller class.
 */

class krzipAdminController extends krzip
{
	function procKrzipAdminInsertConfig()
	{
		$module_config = Context::getRequestVars();
		getDestroyXeVars($module_config);
		unset($module_config->module);
		unset($module_config->act);
		unset($module_config->mid);
		unset($module_config->vid);

		$oKrzipController = getController('krzip');
		$output = $oKrzipController->updateConfig($module_config);
		if(!$output->toBool())
		{
			return $output;
		}

		$success_return_url = Context::get('success_return_url');
		if($success_return_url)
		{
			$return_url = $success_return_url;
		}
		else
		{
			$return_url = getNotEncodedUrl('', 'module', 'krzip', 'act', 'dispKrzipAdminConfig');
		}

		$this->setMessage('success_registed');
		$this->setRedirectUrl($return_url);
	}
}

/* End of file krzip.admin.controller.php */
/* Location: ./modules/krzip/krzip.admin.controller.php */
