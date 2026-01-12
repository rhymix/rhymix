<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of the module module
 */
class ModuleAdminController extends Rhymix\Modules\Module\Controllers\Base
{
	/**
	 * Copy a module.
	 */
	public function procModuleAdminCopyModule($obj = null)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleInfo::getInstance();
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
	 * Save the file of user-defined language code.
	 */
	public function makeCacheDefinedLangCode()
	{
		return Rhymix\Modules\Module\Models\Lang::generateCache();
	}

	/**
	 * Set design info.
	 */
	public function setDesignInfo($moduleSrl = 0, $mid = '', $skinType = 'P', $layoutSrl = 0, $isSkinFix = 'Y', $skinName = '', $skinVars = NULL)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->setDesignInfo(
			(int)$moduleSrl,
			(string)$mid,
			(string)$skinType,
			(int)$layoutSrl,
			(string)$isSkinFix,
			(string)$skinName,
			$skinVars
		);
	}
}
/* End of file module.admin.controller.php */
/* Location: ./modules/module/module.admin.controller.php */
