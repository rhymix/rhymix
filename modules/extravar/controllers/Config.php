<?php

namespace Rhymix\Modules\Extravar\Controllers;

use Context;
use ModuleController;
use ModuleModel;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Framework\Storage;

/**
 * Controller for module configuration by administrator.
 */
class Config extends Base
{
	/**
	 * Display the config page.
	 */
	public function dispExtravarAdminConfig()
	{
		// Get current module config.
		$config = ModuleModel::getModuleConfig($this->module) ?: new \stdClass;
		Context::set('config', $config);

		// Get the list of installed skins.
		$skins = ModuleModel::getSkins($this->module_path);
		Context::set('skin_list', $skins);

		// Set admin template path.
		$this->setTemplatePath($this->module_path . 'views');
		$this->setTemplateFile('config.blade.php');
	}

	/**
	 * Save module config.
	 */
	public function procExtravarAdminInsertConfig()
	{
		// Get current module config.
		$config = ModuleModel::getModuleConfig($this->module) ?: new \stdClass;

		// Update the config object.
		$vars = Context::getRequestVars();
		$config->skin = trim($vars->skin ?? '');
		if (!Storage::isDirectory(sprintf('%s/skins/%s/', rtrim($this->module_path, '/'), $config->skin)))
		{
			throw new TargetNotFound;
		}

		// Save the updated config.
		$output = ModuleController::getInstance()->insertModuleConfig($this->module, $config);
		if (!$output->toBool())
		{
			return $output;
		}

		// Redirect back to the config page.
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl([
			'module' => 'admin',
			'act' => 'dispExtravarAdminConfig',
		]));
	}
}
