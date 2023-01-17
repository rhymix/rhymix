<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use MenuAdminController;
use MenuAdminModel;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Admin\Models\AdminMenu as AdminMenuModel;
use Rhymix\Modules\Admin\Models\Favorite as FavoriteModel;

class AdminMenu extends Base
{
	/**
	 * Display Admin Menu configuration page.
	 */
	public function dispAdminSetup()
	{
		$oMenuAdminModel = MenuAdminModel::getInstance();
		$output = $oMenuAdminModel->getMenuByTitle(AdminMenuModel::getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);

		$this->setTemplateFile('admin_setup');
	}

	/**
	 * Reset the admin menu to the default configuration.
	 */
	public function procAdminMenuReset()
	{
		$oMenuAdminController = MenuAdminController::getInstance();
		$oMenuAdminModel = MenuAdminModel::getInstance();
		for ($i = 0; $i < 100; $i++)
		{
			$output = $oMenuAdminModel->getMenuByTitle(AdminMenuModel::getAdminMenuName());
			$admin_menu_srl = $output->menu_srl ?? 0;
			if ($admin_menu_srl)
			{
				$output = $oMenuAdminController->deleteMenu($admin_menu_srl);
				if (!$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				break;
			}
		}

		Cache::delete('admin_menu_langs:' . Context::getLangType());
		Storage::deleteDirectory(\RX_BASEDIR . 'files/cache/menu/admin_lang/');

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Insert or delete a module as favorite.
	 */
	public function procAdminToggleFavorite()
	{
		// Check if favorite exists.
		$module_name = Context::get('module_name');
		$output = FavoriteModel::isFavorite($module_name);
		if(!$output->toBool())
		{
			return $output;
		}

		// Insert or delete.
		if($output->get('result') && $output->get('favoriteSrl'))
		{
			$favorite_srl = $output->get('favoriteSrl');
			$output = FavoriteModel::deleteFavorite($favorite_srl);
			$result = 'off';
		}
		else
		{
			$output = FavoriteModel::insertFavorite($module_name);
			$result = 'on';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('result', $result);

		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}
}
