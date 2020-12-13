<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin view class of the integration_search module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_searchAdminController extends integration_search
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Save Settings
	 *
	 * @return mixed
	 */
	function procIntegration_searchAdminInsertConfig()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('integration_search') ?: new stdClass;
		$config = (object)get_object_vars($config);

		$config->skin = Context::get('skin');
		$config->mskin = Context::get('mskin');
		$config->target = Context::get('target');
		$config->target_module_srl = Context::get('target_module_srl');
		if(!$config->target_module_srl) $config->target_module_srl = '';

		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('integration_search', $config);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispIntegration_searchAdminContent');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Save the skin information
	 *
	 * @return mixed
	 */
	function procIntegration_searchAdminInsertSkin()
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('integration_search');
		$config = (object)get_object_vars($config);

		// Get skin information (to check extra_vars)
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $config->skin);
		
		// Check received variables (delete the basic variables such as mo, act, module_srl, page)
		$obj = Context::getRequestVars();
		unset($obj->act);
		unset($obj->module_srl);
		unset($obj->page);
		
		// Separately handle if the extra_vars is an image type in the original skin_info
		if($skin_info->extra_vars)
		{
			foreach($skin_info->extra_vars as $vars)
			{
				if($vars->type!='image') continue;

				$image_obj = $obj->{$vars->name};
				// Get a variable on a request to delete
				$del_var = $obj->{"del_".$vars->name};
				unset($obj->{"del_".$vars->name});
				if($del_var == 'Y')
				{
					FileHandler::removeFile($module_info->{$vars->name});
					continue;
				}
				// Use the previous data if not uploaded
				if(!$image_obj['tmp_name'])
				{
					$obj->{$vars->name} = $module_info->{$vars->name};
					continue;
				}
				// Ignore if the file is not successfully uploaded, and check uploaded file
				if(!is_uploaded_file($image_obj['tmp_name']))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Ignore if the file is not an image
				if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name']))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Upload the file to a path
				$path = sprintf("./files/attach/images/%s/", $module_srl);
				// Create a directory
				if(!FileHandler::makeDir($path)) return false;

				$filename = $path.$image_obj['name'];
				// Move the file
				if(!move_uploaded_file($image_obj['tmp_name'], $filename))
				{
					unset($obj->{$vars->name});
					continue;
				}
				// Change a variable
				unset($obj->{$vars->name});
				$obj->{$vars->name} = $filename;
			}
		}
		
		// Serialize and save 
		$config->skin_vars = serialize($obj);

		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('integration_search', $config);

		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispIntegration_searchAdminSkinInfo');
		return $this->setRedirectUrl($returnUrl, $output);
	}
}
/* End of file integration_search.admin.controller.php */
/* Location: ./modules/integration_search/integration_search.admin.controller.php */
