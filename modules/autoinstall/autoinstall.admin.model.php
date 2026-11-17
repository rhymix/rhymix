<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Model class of the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class AutoinstallAdminModel extends Autoinstall
{
	/**
	 * Get module configuration
	 *
	 * @return object
	 */
	public static function getAutoInstallAdminModuleConfig()
	{
		return ModuleModel::getModuleConfig('autoinstall') ?: new \stdClass;
	}

	/**
	 * Does the package list need to be updated?
	 *
	 * @param ?object $config
	 * @return bool
	 */
	public static function needsUpdatePackageList($config = null): bool
	{
		$config = $config ?? self::getAutoInstallAdminModuleConfig();
		if (!isset($config->last_update_check) || ($config->last_update_check < time() - self::AUTO_REFRESH_INTERVAL))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * For backward compatibility only
	 */
	public function getAutoinstallAdminMenuPackageList()
	{

	}

	public function getAutoinstallAdminLayoutPackageList()
	{

	}

	public function getAutoinstallAdminSkinPackageList()
	{

	}

	public function getAutoinstallAdminIsAuthed()
	{
		$this->add('is_authed', 0);
	}

	public function getNeedUpdateList()
	{
		return [];
	}

	public function getInstallInfo($package_srl)
	{
		return new stdClass();
	}

	public function getAutoInstallAdminInstallInfo()
	{
		$this->add('package', new stdClass());
	}

	public function checkUseDirectModuleInstall()
	{
		return new BaseObject();
	}

	public static function isWritableDir($path)
	{
		return new BaseObject();
	}
}
