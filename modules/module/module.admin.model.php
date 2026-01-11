<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminModel
 * @author NAVER (developers@xpressengine.com)
 * @version 0.1
 * @brief AdminModel class of the "module" module
 */
class ModuleAdminModel extends Module
{
	public static function getModuleMidList($args)
	{
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQueryArray('module.getModuleMidList', $args);
		if(!$output->toBool()) return $output;
		ModuleModel::syncModuleToSite($output->data);
		return $output;
	}

	public static function getSelectedManageHTML($grantList, $tabChoice = array(), $modulePath = NULL)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->getSelectedManageHTML($grantList, $tabChoice, $modulePath);
	}

	public static function getModuleGrantHTML($module_srl, $source_grant_list)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->getModuleGrantHTML($module_srl, $source_grant_list);
	}

	public function getModuleAdminGrant()
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleInfo::getInstance();
		$output = $oController->getModuleAdminGrant();
		$this->copyResponseFrom($oController);
		return $output;
	}

	/**
	 * Get defined scopes of module admin.
	 *
	 * @return array
	 */
	public static function getModuleAdminScopes(): array
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getManagerScopes();
	}

	/**
	 * @brief Common:: skin setting page for the module
	 */
	public static function getModuleSkinHTML($module_srl)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->getModuleSkinHTML((int)$module_srl, 'P');
	}

	/**
	 * Common:: skin setting page for the module (mobile)
	 *
	 * @param $module_srl sequence of module
	 * @return string The html code
	 */
	public static function getModuleMobileSkinHTML($module_srl)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->getModuleSkinHTML((int)$module_srl, 'M');
	}

	/**
	 * Skin setting page for the module
	 *
	 * @param $module_srl sequence of module
	 * @param $mode P or M
	 * @return string The HTML code
	 */
	public static function _getModuleSkinHTML($module_srl, $mode)
	{
		$oController = Rhymix\Modules\Module\Controllers\ModuleConfig::getInstance();
		return $oController->getModuleSkinHTML((int)$module_srl, $mode);
	}

	/**
	 * @brief Get values for a particular language code
	 * Return its corresponding value if lang_code is specified. Otherwise return $name.
	 */
	public static function getLangCode($site_srl, $name, $isFullLanguage = FALSE)
	{
		return Rhymix\Modules\Module\Models\Lang::getUserLang($name, (bool)$isFullLanguage);
	}

	/**
	 * @brief Return current lang list
	 */
	public static function getLangListByLangcode($args)
	{
		return Rhymix\Modules\Module\Models\Lang::search($args);
	}
}
/* End of file module.admin.model.php */
/* Location: ./modules/module/module.admin.model.php */
