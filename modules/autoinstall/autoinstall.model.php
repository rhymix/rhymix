<?php

class AutoinstallModel extends Autoinstall
{
	/**
	 * Get module configuration
	 *
	 * @return object
	 */
	public static function getConfig()
	{
		return ModuleModel::getModuleConfig('autoinstall') ?: new \stdClass;
	}
}
