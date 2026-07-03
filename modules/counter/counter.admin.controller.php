<?php

class CounterAdminController extends counter
{
	/**
	 * Save admin config.
	 */
	public function procCounterAdminInsertConfig()
	{
		// Update the module config.
		$config = CounterModel::getConfig();
		$config->is_enabled = Context::get('is_enabled') === 'Y' ? 'Y' : 'N';
		$output = Rhymix\Modules\Module\Models\ModuleConfig::insertModuleConfig('counter', $config);
		if (!$output->toBool())
		{
			return $output;
		}

		// Register or unregister the main event handler.
		if ($config->is_enabled === 'Y')
		{
			if (!Rhymix\Modules\Module\Models\Event::isRegistered('display', 'before', 'counter', 'controller', 'triggerExecute'))
			{
				Rhymix\Modules\Module\Models\Event::registerHandler('display', 'before', 'counter', 'controller', 'triggerExecute');
			}
		}
		else
		{
			if (Rhymix\Modules\Module\Models\Event::isRegistered('display', 'before', 'counter', 'controller', 'triggerExecute'))
			{
				Rhymix\Modules\Module\Models\Event::unregisterHandler('display', 'before', 'counter', 'controller', 'triggerExecute');
			}
		}

		// Deactivate legacy counter addon.
		$oAddonAdminController = AddonAdminController::getInstance();
		$oAddonAdminController->doDeactivate('counter', 0, 'pc');
		$oAddonAdminController->doDeactivate('counter', 0, 'mobile');

		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispCounterAdminConfig']));
	}
}
