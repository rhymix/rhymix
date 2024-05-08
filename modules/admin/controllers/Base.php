<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use DB;
use Rhymix\Framework\Config;
use Rhymix\Framework\Security;
use Rhymix\Framework\Exceptions\NotPermitted;
use Rhymix\Modules\Admin\Models\AdminMenu as AdminMenuModel;
use Rhymix\Modules\Admin\Models\Favorite as FavoriteModel;

class Base extends \ModuleObject
{
	/**
	 * Initilization
	 *
	 * @return void
	 */
	public function init()
	{
		// Only allow administrators.
		if (!$this->user->isAdmin())
		{
			throw new NotPermitted('admin.msg_is_not_administrator');
		}

		// Set the default URL.
		Context::set('xe_default_url', Context::getDefaultUrl());

		// Set the layout and template path.
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setLayoutPath($this->getTemplatePath());
		$this->setLayoutFile('layout.html');

		// Check system configuration.
		$this->checkSystemConfiguration();

		// Load the admin menu.
		$this->loadAdminMenu();
	}

	/**
	 * check system configuration.
	 *
	 * @return void
	 */
	public function checkSystemConfiguration()
	{
		$changed = false;

		// Check encryption keys.
		if (config('crypto.encryption_key') === null)
		{
			config('crypto.encryption_key', Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('crypto.authentication_key') === null)
		{
			config('crypto.authentication_key', Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('crypto.session_key') === null)
		{
			config('crypto.session_key', Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('file.folder_structure') === null)
		{
			config('file.folder_structure', 1);
			$changed = true;
		}

		// Save new configuration.
		if ($changed)
		{
			Config::save();
		}
	}

	/**
	 * Load the admin menu.
	 *
	 * @return void
	 */
	public function loadAdminMenu($module = 'admin')
	{
		global $lang;

		// Check is_shortcut column
		$oDB = DB::getInstance();
		if (!$oDB->isColumnExists('menu_item', 'is_shortcut'))
		{
			return;
		}

		$lang->menu_gnb_sub = AdminMenuModel::getAdminMenuLang();
		$result = AdminMenuModel::checkAdminMenu();
		include $result->php_file;

		// get current menu's subMenuTitle
		$moduleActionInfo = \ModuleModel::getModuleActionXml($module);
		$moduleMenus = isset($moduleActionInfo->menu) ? (array)$moduleActionInfo->menu : [];
		$currentAct = Context::get('act');
		$subMenuTitle = '';

		foreach($moduleMenus as $value)
		{
			if(is_array($value->acts) && in_array($currentAct, $value->acts))
			{
				$subMenuTitle = $value->title;
				break;
			}
		}
		if (!$subMenuTitle && $currentAct && count($moduleMenus))
		{
			$subMenuTitle = array_first($moduleMenus)->title;
		}
		if (!$subMenuTitle)
		{
			if ($currentAct)
			{
				$moduleInfo = \ModuleModel::getModuleInfoXml($module);
				$subMenuTitle = $moduleInfo->title ?? 'Dashboard';
			}
			else
			{
				$subMenuTitle = 'Dashboard';
			}
		}

		// get current menu's srl(=parentSrl)
		$parentSrl = 0;
		foreach ((array)$menu->list as $parentKey => $parentMenu)
		{
			if (!is_array($parentMenu['list']) || !count($parentMenu['list']))
			{
				continue;
			}
			if ($parentMenu['href'] == '#' && count($parentMenu['list']))
			{
				$firstChild = current($parentMenu['list']);
				$menu->list[$parentKey]['href'] = $firstChild['href'];
			}
			if ($currentAct)
			{
				foreach ($parentMenu['list'] as $childMenu)
				{
					if (preg_match('/\b' . preg_quote($currentAct, '/') . '$/', $childMenu['href']))
					{
						$parentSrl = $childMenu['parent_srl'];
					}
				}
			}
		}

		// Get list of favorite
		$output = FavoriteModel::getFavorites(true);
		Context::set('favorite_list', $output->get('favoriteList'));

		Context::set('subMenuTitle', $subMenuTitle);
		Context::set('gnbUrlList', $menu->list);
		Context::set('parentSrl', $parentSrl);
		Context::set('gnb_title_info', $gnbTitleInfo ?? null);
		Context::addBrowserTitle($subMenuTitle);
	}

	/**
	 * Alias for backward compatibility.
	 *
	 * @deprecated
	 */
	public static function getAdminMenuName()
	{
		return AdminMenuModel::getAdminMenuName();
	}

	/**
	 * Alias for backward compatibility.
	 *
	 * @deprecated
	 */
	public static function getAdminMenuLang()
	{
		return AdminMenuModel::getAdminMenuLang();
	}
}
