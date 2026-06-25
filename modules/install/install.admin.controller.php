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
		if(!$module_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		Rhymix\Framework\Session::close();

		$oModuleController = ModuleController::getInstance();
		$oModule = ModuleModel::getModuleInstallClass($module_name);
		if($oModule && method_exists($oModule, 'moduleUpdate'))
		{
			$output = $oModule->moduleUpdate();
			if($output instanceof BaseObject && !$output->toBool())
			{
				Rhymix\Framework\Session::start();
				return $output;
			}
		}

		$output = $oModuleController->registerActionForwardRoutes($module_name);
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}

		$output = $oModuleController->registerEventHandlers($module_name);
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}

		$output = $oModuleController->registerNamespaces($module_name);
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}

		$output = $oModuleController->registerPrefixes($module_name);
		if($output instanceof BaseObject && !$output->toBool())
		{
			Rhymix\Framework\Session::start();
			return $output;
		}

		Rhymix\Framework\Session::start();
		$this->setMessage('success_updated');
	}
}
