<?php

class AutoinstallAdminView extends Autoinstall
{
	/**
	 * initialize
	 *
	 * @return void
	 */
	public function init()
	{
		// Get module configuration
		$config = AutoinstallAdminModel::getAutoInstallAdminModuleConfig();
		$this->config = $config;

		// Update the package list.
		$package_count = Rhymix\Modules\Autoinstall\Models\Package::getPackageCount();
		if (!$package_count || !isset($config->last_update_check) || ($config->last_update_check < time() - 86400))
		{
			$success = Rhymix\Modules\Autoinstall\Models\Package::updatePackageList();
			if ($success)
			{
				$config->last_update_check = time();
				ModuleController::getInstance()->insertModuleConfig('autoinstall', $config);
			}
		}

		// Set package types for the view, with appropriate translations.
		$package_types = [];
		foreach (self::$package_types as $type)
		{
			$package_types[$type] = Context::getLang('autoinstall.typename.' . $type);
		}
		Context::set('package_types', $package_types);

		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/**
	 * Display package list
	 *
	 * @return void
	 */
	public function dispAutoinstallAdminIndex()
	{
		$type = trim(Context::get('type') ?? 'featured');
		$page = intval(Context::get('page')) ?: 1;
		$search_keyword = escape(trim(Context::get('search_keyword') ?? ''), false);
		Context::set('type', $type);
		Context::set('page', $page);
		Context::set('search_keyword', $search_keyword);

		$output = Rhymix\Modules\Autoinstall\Models\Package::searchPackages($type, $search_keyword, 20, $page);
		Context::set('package_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('index');
	}

	/**
	 * Display package detail
	 *
	 * @return void
	 */
	public function dispAutoinstallAdminPackageDetail()
	{
		$package_srl = intval(Context::get('package_srl'));
		if (!$package_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$package = Rhymix\Modules\Autoinstall\Models\Package::getPackageDetail($package_srl);
		if (!$package)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}

		Context::set('package', $package);
		Context::set('type', $package->type);
		Context::set('update_info', $package->getUpdateInfo());

		$this->setTemplateFile('package_detail');
	}
}
