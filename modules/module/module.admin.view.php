<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief admin view class of the module module
 */
class ModuleAdminView extends Module
{
	/**
	 * @brief Applying the default settings to all modules
	 */
	public function dispModuleAdminModuleSetup()
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		$output = $oController->dispModuleAdminModuleSetup();
		$this->copyResponseFrom($oController);
		return $output;
	}

	/**
	 * @brief Apply module addition settings to all modules
	 */
	public function dispModuleAdminModuleAdditionSetup()
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		$output = $oController->dispModuleAdminModuleAdditionSetup();
		$this->copyResponseFrom($oController);
		return $output;
	}

	/**
	 * @brief Applying module permission settings to all modules
	 */
	public function dispModuleAdminModuleGrantSetup()
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		$output = $oController->dispModuleAdminModuleGrantSetup();
		$this->copyResponseFrom($oController);
		return $output;
	}
}
/* End of file module.admin.view.php */
/* Location: ./modules/module/module.admin.view.php */
