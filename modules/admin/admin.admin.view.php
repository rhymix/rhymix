<?php

/**
 * Preserved for backward compatibility
 *
 * @deprecated
 */
class AdminAdminView extends Admin
{
	/**
	 * Make the admin menu.
	 *
	 * @deprecated
	 */
	public function makeGnbUrl($module = 'admin')
	{
		Rhymix\Modules\Admin\Controllers\Base::getInstance()->loadAdminMenu($module);
	}

	/**
	 * Display FTP Configuration(settings) page
	 *
	 * @deprecated
	 */
	public function dispAdminConfigFtp()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}
}
