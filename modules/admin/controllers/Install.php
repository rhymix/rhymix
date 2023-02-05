<?php

namespace Rhymix\Modules\Admin\Controllers;

use Rhymix\Framework\DB;
use Rhymix\Modules\Admin\Models\Favorite as FavoriteModel;

class Install extends Base
{
	/**
	 * Install module
	 *
	 * @return void
	 */
	public function moduleInstall()
	{

	}

	/**
	 * Check if update is necessary
	 *
	 * @return bool
	 */
	public function checkUpdate()
	{
		$oDB = DB::getInstance();
		if (!$oDB->isColumnExists('admin_favorite', 'type'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Update module
	 *
	 * @return void
	 */
	public function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if (!$oDB->isColumnExists('admin_favorite', 'type'))
		{
			$output = FavoriteModel::getFavorites();
			$favorites = $output->get('favorites');

			$oDB->dropColumn('admin_favorite', 'admin_favorite_srl');
			$oDB->addColumn('admin_favorite', 'admin_favorite_srl', 'number', null, 0);
			$oDB->addColumn('admin_favorite', 'type', 'varchar', 30, 'module');
			if (is_array($favorites))
			{
				$oAdminAdminController = getAdminController('admin');
				$oAdminAdminController->_deleteAllFavorite();
				foreach($favorites as $value)
				{
					$oAdminAdminController->_insertFavorite(0, $value->module);
				}
			}
		}
	}
}
