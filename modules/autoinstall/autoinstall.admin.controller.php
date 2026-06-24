<?php

class AutoinstallAdminController extends Autoinstall
{
	/**
	 * Download package
	 *
	 * @return void
	 */
	public function procAutoinstallAdminDownloadPackage()
	{
		// Validate package status
		$package_srl = intval(Context::get('package_srl'));
		$mode = Context::get('mode') === 'update' ? 'update' : 'install';
		$package = $this->_validatePackageSrl($package_srl, $mode);
		if ($package instanceof BaseObject && !$package->toBool())
		{
			return $package;
		}

		// Download package
		$output = Rhymix\Modules\Autoinstall\Models\Installer::downloadPackage($package);
		if (!$output->toBool())
		{
			$output->setMessage(nl2br($output->getMessage()));
			return $output;
		}
	}

	/**
	 * Install package
	 */
	public function procAutoinstallAdminInstallPackage()
	{
		// Validate package status
		$package_srl = intval(Context::get('package_srl'));
		$mode = Context::get('mode') === 'update' ? 'update' : 'install';
		$package = $this->_validatePackageSrl($package_srl, $mode);
		if ($package instanceof BaseObject && !$package->toBool())
		{
			return $package;
		}

		// Install package
		$output = Rhymix\Modules\Autoinstall\Models\Installer::installPackage($package, $mode);
		if (!$output->toBool())
		{
			$output->setMessage(nl2br($output->getMessage()));
			return $output;
		}
	}

	/**
	 * Post-install cleanup
	 */
	public function procAutoinstallAdminPostInstallPackage()
	{
		// Validate package status
		$package_srl = intval(Context::get('package_srl'));
		$package = $this->_validatePackageSrl($package_srl, 'none');
		if ($package instanceof BaseObject && !$package->toBool())
		{
			return $package;
		}

		// If it's not a module, skip the post-install cleanup
		if ($package->type !== 'module')
		{
			return new BaseObject();
		}

		// Find the module name.
		$module_name = preg_match('!/modules/(\w+)!', $package->install_path, $matches) ? $matches[1] : '';
		if (!$module_name)
		{
			return new BaseObject(-1, 'msg_autoinstall_invalid_module_install_path');
		}

		// Install and update the module.
		try
		{
			$module_class = ModuleModel::getModuleInstallClass($module_name);
			if ($module_class && method_exists($module_class, 'moduleInstall'))
			{
				$module_class->moduleInstall();
			}
			if ($module_class && method_exists($module_class, 'checkUpdate'))
			{
				if ($module_class->checkUpdate() && method_exists($module_class, 'moduleUpdate'))
				{
					$module_class->moduleUpdate();
				}
			}
		}
		catch (\Throwable $e)
		{
			return new BaseObject(-1, $e->getMessage());
		}
	}

	/**
	 * Uninstall package
	 *
	 * @return void
	 */
	public function procAutoinstallAdminUninstallPackage()
	{
		// Validate package status
		$package_srl = intval(Context::get('package_srl'));
		$package = $this->_validatePackageSrl($package_srl, 'uninstall');
		if ($package instanceof BaseObject && !$package->toBool())
		{
			return $package;
		}

		// Uninstall package
		$output = Rhymix\Modules\Autoinstall\Models\Installer::uninstallPackage($package);
		if (!$output->toBool())
		{
			$output->setMessage(nl2br($output->getMessage()));
			return $output;
		}

		$this->setMessage('msg_autoinstall_success_uninstalled');
	}

	/**
	 * Validate package_srl and installation mode
	 *
	 * @param int $package_srl
	 * @param string $mode
	 * @return Rhymix\Modules\Autoinstall\Models\Package|BaseObject
	 */
	protected function _validatePackageSrl(int $package_srl, string $mode): object
	{
		if ($package_srl <= 0)
		{
			return new BaseObject(-1, 'msg_autoinstall_invalid_package_srl');
		}

		$package = Rhymix\Modules\Autoinstall\Models\Package::getPackageDetail($package_srl);
		if (!$package)
		{
			return new BaseObject(-1, 'msg_autoinstall_package_not_found');
		}
		if (!$package->isInstallable())
		{
			return new BaseObject(-1, 'msg_autoinstall_package_not_installable');
		}
		if ($mode === 'none')
		{
			return $package;
		}
		if ($mode === 'install' && $package->isInstalled())
		{
			return new BaseObject(-1, 'msg_autoinstall_package_already_installed');
		}
		if ($mode === 'update' && !$package->isInstalled())
		{
			return new BaseObject(-1, 'msg_autoinstall_package_not_installed');
		}
		if ($mode === 'uninstall' && !$package->isInstalled())
		{
			return new BaseObject(-1, 'msg_autoinstall_package_not_installed');
		}

		return $package;
	}
}
