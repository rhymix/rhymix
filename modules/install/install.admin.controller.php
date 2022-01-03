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
		if(!$module_name) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oInstallController = getController('install');
		$oInstallController->installModule($module_name, './modules/'.$module_name);
		$oModuleController = getController('module');
		$oModuleController->registerActionForwardRoutes($module_name);
		$this->setMessage('success_installed');
	}

	/**
	 * @brief Upate the module
	 */
	function procInstallAdminUpdate()
	{
		@set_time_limit(0);
		$module_name = Context::get('module_name');
		if(!$module_name) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oModule = ModuleModel::getModuleInstallClass($module_name);
		if(!$oModule || !method_exists($oModule, 'moduleUpdate'))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		Rhymix\Framework\Session::close();
		
		$output = $oModule->moduleUpdate();
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}
		
		$oModuleController = getController('module');
		$output = $oModuleController->registerActionForwardRoutes($module_name);
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}
		
		Rhymix\Framework\Session::start();
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
