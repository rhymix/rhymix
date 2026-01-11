<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of the module module
 */
class ModuleAdminController extends Module
{
	/**
	 * Copy a module.
	 */
	public function procModuleAdminCopyModule($obj = null)
	{
		$oController = Rhymix\Modules\Module\COntrollers\ModuleInfo::getInstance();
		$output = $oController->procModuleAdminCopyModule($obj);
		if ($obj)
		{
			return $output;
		}
		else
		{
			$this->copyResponseFrom($oController);
		}
	}

	/**
	 * @brief Save the file of user-defined language code
	 */
	public function makeCacheDefinedLangCode()
	{
		return Rhymix\Modules\Module\Models\Lang::generateCache();
	}
}
/* End of file module.admin.controller.php */
/* Location: ./modules/module/module.admin.controller.php */
