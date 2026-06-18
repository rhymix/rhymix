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
		$config = AutoinstallModel::getConfig();
		$this->config = $config;

		// Update the package list.
		$package_count = AutoinstallModel::getPackageCount();
		if (!$package_count || !isset($config->last_update_check) || ($config->last_update_check < time() - 86400))
		{
			$success = AutoinstallModel::updatePackageList();
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

		$output = AutoinstallModel::searchPackages($type, $search_keyword, 20, $page);
		Context::set('package_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('index');
	}
}
