<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  installAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of the install module
 */
class installAdminController extends install
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Install the module
	 */
	function procInstallAdminInstall()
	{
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oInstallController = getController('install');
		$oInstallController->installModule($module_name, './modules/'.$module_name);

		$this->setMessage('success_installed');
	}

	/**
	 * @brief Upate the module
	 */
	function procInstallAdminUpdate()
	{
		@set_time_limit(0);
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oModule = getModule($module_name, 'class');
		if($oModule) $output = $oModule->moduleUpdate();
		else $output = new Object(-1, 'invalid_request');

		return $output;
	}

	function procInstallAdminUpdateIndexModule()
	{
		if(!Context::get('index_module_srl') || !Context::get('menu_item_srl'))
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$site_args = new stdClass();
		$site_args->site_srl = 0;
		$site_args->index_module_srl = Context::get('index_module_srl');
		$oModuleController = getController('module');
		$oModuleController->updateSite($site_args);

		// get menu item info
		$menuItemSrl = Context::get('menu_item_srl');
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuItemInfo($menuItemSrl);

		// update homeSitemap.php cache file
		$oMenuAdminController = getAdminController('menu');
		$homeMenuCacheFile = $oMenuAdminController->getHomeMenuCacheFile();
		if(file_exists($homeMenuCacheFile))
		{
			include($homeMenuCacheFile);
		}

		if(!$homeMenuSrl || $homeMenuSrl != $output->menu_srl)
		{
			$oMenuAdminController->makeHomemenuCacheFile($output->menu_srl);
		}

		$this->setMessage('success_updated');
	}

	function procInstallAdminRemoveFTPInfo()
	{
		$ftp_config_file = Context::getFTPConfigFile();
		if(file_exists($ftp_config_file)) unlink($ftp_config_file);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);
		$this->setMessage('success_deleted');
	}

	function procInstallAdminSaveFTPInfo()
	{
		$ftp_info = Context::getFTPInfo();
		$ftp_info->ftp_user = Context::get('ftp_user');
		$ftp_info->ftp_port = Context::get('ftp_port');
		$ftp_info->ftp_host = Context::get('ftp_host');
		$ftp_info->ftp_pasv = Context::get('ftp_pasv');
		if(!$ftp_info->ftp_pasv) $ftp_info->ftp_pasv = "N";
		$ftp_info->sftp = Context::get('sftp');

		$ftp_root_path = Context::get('ftp_root_path');
		if(substr($ftp_root_path, strlen($ftp_root_path)-1) == "/")
		{
			$ftp_info->ftp_root_path = $ftp_root_path;
		}
		else
		{
			$ftp_info->ftp_root_path = $ftp_root_path.'/';
		}

		$ftp_info->ftp_password = Context::get('ftp_password');

		$buff = '<?php if(!defined("__XE__")) exit();'."\n\$ftp_info = new stdClass;\n";
		foreach($ftp_info as $key => $val)
		{
			if(!$val) continue;
			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|file_get_contents|file_put_contents|exec|proc_open|popen|passthru|show_source|phpinfo|system|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $val)))
			{
				continue;
			}
			$buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
		}
		$buff .= "?>";
		$config_file = Context::getFTPConfigFile();
		FileHandler::WriteFile($config_file, $buff);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);

		$this->setMessage('success_updated');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigFtp');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file install.admin.controller.php */
/* Location: ./modules/install/install.admin.controller.php */
