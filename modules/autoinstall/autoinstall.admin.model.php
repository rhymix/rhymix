<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Model class of the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class AutoinstallAdminModel extends Autoinstall
{
	/**
	 * For backward compatibility only
	 */
	public static function getAutoInstallAdminModuleConfig()
	{
		return ModuleModel::getModuleConfig('autoinstall') ?: new stdClass();
	}

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
		$path_list = explode('/', dirname($path));
		$real_path = './';

		$check_path = realpath($real_path);
		while ($path_list)
		{
			$check_path = realpath($real_path . implode('/', $path_list));
			if(FileHandler::isDir($check_path))
			{
				break;
			}
			array_pop($path_list);
		}

		if (!FileHandler::isWritableDir($check_path))
		{
			$output = new BaseObject(-1, 'msg_unwritable_directory');
			$output->add('path', FileHandler::getRealPath($check_path));
			return $output;
		}
		return new BaseObject();
	}
}
