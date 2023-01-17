<?php

namespace Rhymix\Modules\Admin\Models;

use Context;
use FileHandler;
use MemberModel;
use MenuAdminModel;
use ModuleModel;

class AdminMenu
{
	public const ADMIN_MENU_NAME = '__ADMINMENU_V17__';

	public const DEFAULT_MENU_STRUCTURE = [
		'dashboard' => [],
		'menu' => [
			'menu.siteMap',
			'menu.siteDesign',
		],
		'user' => [
			'member.userList',
			'member.userSetting',
			'member.userGroup',
			'point.point',
		],
		'content' => [
			'board.board',
			'page.page',
			'document.document',
			'comment.comment',
			'file.file',
			'poll.poll',
			'editor.editor',
			'spamfilter.spamFilter',
			'trash.trash',
		],
		'configuration' => [
			'admin.adminConfigurationGeneral',
			'admin.adminMenuSetup',
			'module.filebox',
		],
		'advanced' => [
			'autoinstall.easyInstall',
			'layout.installedLayout',
			'module.installedModule',
			'addon.installedAddon',
			'widget.installedWidget',
			'module.multilingual',
			'importer.importer',
			'rss.rss',
		],
	];

	public static function getAdminMenuName()
	{
		return self::ADMIN_MENU_NAME;
	}

	public static function getAdminMenuLang()
	{
		static $lang = null;

		if ($lang === null)
		{
			$lang = \Rhymix\Framework\Cache::get('admin_menu_langs:' . Context::getLangType());
		}

		if ($lang === null)
		{
			$lang = [];
			$installed_module_list = ModuleModel::getModulesXmlInfo();
			foreach ($installed_module_list as $value)
			{
				$moduleActionInfo = ModuleModel::getModuleActionXml($value->module);
				if (isset($moduleActionInfo->menu) && is_object($moduleActionInfo->menu))
				{
					foreach ($moduleActionInfo->menu as $key2 => $value2)
					{
						$lang[$key2] = $value2->title;
					}
				}
			}

			\Rhymix\Framework\Cache::set('admin_menu_langs:' . Context::getLangType(), $lang, 0, true);
		}

		return $lang;
	}

	public static function checkAdminMenu()
	{
		if (!Context::isInstalled())
		{
			return;
		}

		$oMenuAdminModel = MenuAdminModel::getInstance();
		$output = $oMenuAdminModel->getMenuByTitle(self::ADMIN_MENU_NAME);

		if (!$output->menu_srl)
		{
			self::createXeAdminMenu();
			$output = $oMenuAdminModel->getMenuByTitle(self::ADMIN_MENU_NAME);
		}
		else
		{
			if (!is_readable(FileHandler::getRealPath($output->php_file)))
			{
				$oMenuAdminController = getAdminController('menu');
				$oMenuAdminController->makeXmlFile($output->menu_srl);
			}
			Context::set('admin_menu_srl', $output->menu_srl);
		}

		self::_deleteOldAdminMenu();

		$returnObj = new \stdClass;
		$returnObj->menu_srl = $output->menu_srl;
		$returnObj->php_file = FileHandler::getRealPath($output->php_file);

		return $returnObj;
	}

	/**
	 * Regenerate xe admin default menu
	 * @return void
	 */
	public static function createXeAdminMenu()
	{
		//insert menu
		$args = new \stdClass;
		$args->title = self::ADMIN_MENU_NAME;
		$menu_srl = $args->menu_srl = getNextSequence();
		$args->listorder = $args->menu_srl * -1;
		$output = executeQuery('menu.insertMenu', $args);
		Context::set('admin_menu_srl', $menu_srl);
		unset($args);

		// gnb item create
		foreach (array_keys(self::DEFAULT_MENU_STRUCTURE) as $value)
		{
			//insert menu item
			$args = new \stdClass;
			$args->menu_srl = $menu_srl;
			$args->menu_item_srl = getNextSequence();
			$args->name = '{$lang->menu_gnb[\'' . $value . '\']}';
			if($value == 'dashboard')
			{
				$args->url = getUrl(['module' => 'admin']);
			}
			else
			{
				$args->url = '#';
			}
			$args->listorder = -1 * $args->menu_item_srl;
			$output = executeQuery('menu.insertMenuItem', $args);
		}

		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuItems($menu_srl, 0, ['menu_item_srl', 'name']);
		if (is_array($output->data))
		{
			foreach ($output->data as $value)
			{
				preg_match('/\{\$lang->menu_gnb\[(.*?)\]\}/i', $value->name, $m);
				$gnbDBList[$m[1]] = $value->menu_item_srl;
			}
		}

		$output = MemberModel::getAdminGroup(['group_srl']);
		$admin_group_srl = $output->group_srl;

		// gnb common argument setting
		$args = new \stdClass;
		$args->menu_srl = $menu_srl;
		$args->open_window = 'N';
		$args->expand = 'N';
		$args->normal_btn = '';
		$args->hover_btn = '';
		$args->active_btn = '';
		$args->group_srls = $admin_group_srl;

		$moduleActionInfo = array();
		foreach (self::DEFAULT_MENU_STRUCTURE as $key => $items)
		{
			if (!$items)
			{
				continue;
			}

			foreach ($items as $item)
			{
				list($module_name, $menu_name) = explode('.', $item);
				if (!isset($moduleActionInfo[$module_name]))
				{
					$moduleActionInfo[$module_name] = ModuleModel::getModuleActionXml($module_name);
				}

				$args->menu_item_srl = getNextSequence();
				$args->parent_srl = $gnbDBList["'" . $key . "'"];
				$args->name = '{$lang->menu_gnb_sub[\'' . $menu_name . '\']}';
				$args->url = getUrl('', 'module', 'admin', 'act', $moduleActionInfo[$module_name]->menu->{$menu_name}->index);
				$args->listorder = -1 * $args->menu_item_srl;
				$output = executeQuery('menu.insertMenuItem', $args);
			}
		}

		$oMenuAdminConroller = getAdminController('menu');
		$oMenuAdminConroller->makeXmlFile($menu_srl);

		// does not recreate lang cache sometimes
		FileHandler::RemoveFilesInDir('./files/cache/lang');
		FileHandler::RemoveFilesInDir('./files/cache/menu/admin_lang');
	}

	/**
	 * Return parent old menu key by child menu
	 *
	 * @return string
	 */
	protected static function _getOldGnbKey($menuName)
	{
		switch($menuName)
		{
			case 'siteMap':
				return 'menu';
				break;
			case 'userList':
			case 'userSetting':
			case 'userGroup':
			case 'point':
				return 'user';
				break;
			case 'document':
			case 'comment':
			case 'file':
			case 'poll':
			case 'rss':
			case 'multilingual':
			case 'importer':
			case 'trash':
				return 'content';
				break;
			case 'easyInstall':
			case 'installedLayout':
			case 'installedModule':
			case 'installedWidget':
			case 'installedAddon':
			case 'editor':
			case 'spamFilter':
				return 'extensions';
				break;
			case 'adminConfigurationGeneral':
			case 'adminConfigurationFtp':
			case 'adminMenuSetup':
			case 'fileUpload':
			case 'filebox':
				return 'configuration';
				break;
			default:
				return 'user_added_menu';
		}
	}

	/**
	 * Delete old admin menu
	 */
	protected static function _deleteOldAdminMenu()
	{
		$oMenuAdminModel = getAdminModel('menu');

		$output = $oMenuAdminModel->getMenuByTitle(self::ADMIN_MENU_NAME);
		$newAdminmenuSrl = $output->menu_srl;
		$output = $oMenuAdminModel->getMenuItems($newAdminmenuSrl, 0);
		$newAdminParentMenuList = array();
		if (is_array($output->data))
		{
			foreach ($output->data as $value)
			{
				$tmp = explode('\'', $value->name);
				$newAdminParentMenuList[$tmp[1]] = $value;
			}
		}
		unset($output);

		// old admin menu
		$output = $oMenuAdminModel->getMenuByTitle('__XE_ADMIN__');
		$menu_srl = $output->menu_srl ?? 0;

		$oMenuAdminController = getAdminController('menu');
		if ($menu_srl)
		{
			$output = $oMenuAdminModel->getMenuItems($menu_srl);
			if (is_array($output->data))
			{
				$parentMenu = array();
				foreach ($output->data as $menu_item)
				{
					if ($menu_item->parent_srl == 0)
					{
						$tmp = explode('\'', $menu_item->name);
						$parentMenuKey = $tmp[1];
						$parentMenu[$menu_item->menu_item_srl] = $parentMenuKey;
					}
				}

				$isUserAddedMenuMoved = FALSE;
				foreach ($output->data as $menu_item)
				{
					if ($menu_item->parent_srl != 0)
					{
						$tmp = explode('\'', $menu_item->name);
						$menuKey = $tmp[1];

						$result = self::_getOldGnbKey($menuKey);
						if ($result === 'user_added_menu')
						{
							if ($parentMenu[$menu_item->parent_srl] == 'extensions')
							{
								$newParentItem = $newAdminParentMenuList['advanced'];
							}
							else
							{
								$newParentItem = $newAdminParentMenuList[$parentMenu[$menu_item->parent_srl]];
							}
							$menu_item->menu_srl = $newParentItem->menu_srl;
							$menu_item->parent_srl = $newParentItem->menu_item_srl;

							$output = executeQuery('menu.updateMenuItem', $menu_item);
							$isUserAddedMenuMoved = true;
						}
					}
				}

				if ($isUserAddedMenuMoved)
				{
					$oMenuAdminController->makeXmlFile($newAdminmenuSrl);
				}
			}
		}

		// all old admin menu delete
		$output = $oMenuAdminModel->getMenuListByTitle('__XE_ADMIN__');
		if (is_array($output))
		{
			foreach ($output as $value)
			{
				$oMenuAdminController->deleteMenu($value->menu_srl);
			}
		}
	}
}
