<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * admin class
 * Base class of admin module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class admin extends ModuleObject
{

	private $adminMenuName = '__ADMINMENU_V17__';
	public function getAdminMenuName()
	{
		return $this->adminMenuName;
	}

	/**
	 * Install admin module
	 * @return Object
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists("admin_favorite", "type"))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Update module
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists("admin_favorite", "type"))
		{
			$oAdminAdminModel = getAdminModel('admin');
			$output = $oAdminAdminModel->getFavoriteList();
			$favoriteList = $output->get('favoriteList');

			$oDB->dropColumn('admin_favorite', 'admin_favorite_srl');
			$oDB->addColumn('admin_favorite', "admin_favorite_srl", "number", 11, 0);
			$oDB->addColumn('admin_favorite', "type", "varchar", 30, 'module');
			if(is_array($favoriteList))
			{
				$oAdminAdminController = getAdminController('admin');
				$oAdminAdminController->_deleteAllFavorite();
				foreach($favoriteList AS $key => $value)
				{
					$oAdminAdminController->_insertFavorite($value->site_srl, $value->module);
				}
			}
		}
		return new Object();
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{

	}

	public function checkAdminMenu()
	{
		// for admin menu
		if(Context::isInstalled())
		{
			$oMenuAdminModel = getAdminModel('menu');
			$output = $oMenuAdminModel->getMenuByTitle($this->adminMenuName);

			if(!$output->menu_srl)
			{
				$this->createXeAdminMenu();
				$output = $oMenuAdminModel->getMenuByTitle($this->adminMenuName);
			}
			else
			{
				if(!is_readable(FileHandler::getRealPath($output->php_file)))
				{
					$oMenuAdminController = getAdminController('menu');
					$oMenuAdminController->makeXmlFile($output->menu_srl);
				}
				Context::set('admin_menu_srl', $output->menu_srl);
			}

			$this->_oldAdminmenuDelete();

			$returnObj = new stdClass();
			$returnObj->menu_srl = $output->menu_srl;
			$returnObj->php_file = FileHandler::getRealPath($output->php_file);

			return $returnObj;
		}
	}

	/**
	 * Regenerate xe admin default menu
	 * @return void
	 */
	public function createXeAdminMenu()
	{
		//insert menu
		$args = new stdClass();
		$args->title = $this->adminMenuName;
		$menuSrl = $args->menu_srl = getNextSequence();
		$args->listorder = $args->menu_srl * -1;
		$output = executeQuery('menu.insertMenu', $args);
		Context::set('admin_menu_srl', $menuSrl);
		unset($args);

		// gnb item create
		$gnbList = array('dashboard', 'menu', 'user', 'content', 'configuration', 'advanced');
		foreach($gnbList AS $key => $value)
		{
			//insert menu item
			$args = new stdClass();
			$args->menu_srl = $menuSrl;
			$args->menu_item_srl = getNextSequence();
			$args->name = '{$lang->menu_gnb[\'' . $value . '\']}';
			if($value == 'dashboard')
			{
				$args->url = 'index.php?module=admin';
			}
			else
			{
				$args->url = '#';
			}
			$args->listorder = -1 * $args->menu_item_srl;
			$output = executeQuery('menu.insertMenuItem', $args);
		}

		$oMenuAdminModel = getAdminModel('menu');
		$columnList = array('menu_item_srl', 'name');
		$output = $oMenuAdminModel->getMenuItems($menuSrl, 0, $columnList);
		if(is_array($output->data))
		{
			foreach($output->data AS $key => $value)
			{
				preg_match('/\{\$lang->menu_gnb\[(.*?)\]\}/i', $value->name, $m);
				$gnbDBList[$m[1]] = $value->menu_item_srl;
			}
		}
		unset($args);

		$gnbModuleList = array(
			0 => array(
				'module' => 'menu',
				'subMenu' => array('siteMap', 'siteDesign'),
			),
			1 => array(
				'module' => 'member',
				'subMenu' => array('userList', 'userSetting', 'userGroup'),
			),
			2 => array(
				'module' => 'document',
				'subMenu' => array('document'),
			),
			3 => array(
				'module' => 'comment',
				'subMenu' => array('comment'),
			),
			4 => array(
				'module' => 'file',
				'subMenu' => array('file'),
			),
			5 => array(
				'module' => 'poll',
				'subMenu' => array('poll'),
			),
			6 => array(
				'module' => 'rss',
				'subMenu' => array('rss'),
			),
			7 => array(
				'module' => 'module',
				'subMenu' => array('multilingual'),
			),
			8 => array(
				'module' => 'importer',
				'subMenu' => array('importer'),
			),
			9 => array(
				'module' => 'trash',
				'subMenu' => array('trash'),
			),
			10 => array(
				'module' => 'autoinstall',
				'subMenu' => array('easyInstall'),
			),
			11 => array(
				'module' => 'layout',
				'subMenu' => array('installedLayout'),
			),
			12 => array(
				'module' => 'module',
				'subMenu' => array('installedModule'),
			),
			13 => array(
				'module' => 'widget',
				'subMenu' => array('installedWidget'),
			),
			14 => array(
				'module' => 'addon',
				'subMenu' => array('installedAddon'),
			),
			15 => array(
				'module' => 'editor',
				'subMenu' => array('editor'),
			),
			16 => array(
				'module' => 'spamfilter',
				'subMenu' => array('spamFilter'),
			),
			17 => array(
				'module' => 'admin',
				'subMenu' => array('adminConfigurationGeneral', 'adminConfigurationFtp', 'adminMenuSetup'),
			),
			18 => array(
				'module' => 'file',
				'subMenu' => array('fileUpload'),
			),
			19 => array(
				'module' => 'module',
				'subMenu' => array('filebox'),
			),
			20 => array(
				'module' => 'point',
				'subMenu' => array('point')
			),
		);

		$oMemberModel = getModel('member');
		$output = $oMemberModel->getAdminGroup(array('group_srl'));
		$adminGroupSrl = $output->group_srl;

		// gnb sub item create
		// common argument setting
		$args = new stdClass();
		$args->menu_srl = $menuSrl;
		$args->open_window = 'N';
		$args->expand = 'N';
		$args->normal_btn = '';
		$args->hover_btn = '';
		$args->active_btn = '';
		$args->group_srls = $adminGroupSrl;
		$oModuleModel = getModel('module');

		foreach($gnbModuleList AS $key => $value)
		{
			if(is_array($value['subMenu']))
			{
				$moduleActionInfo = $oModuleModel->getModuleActionXml($value['module']);
				foreach($value['subMenu'] AS $key2 => $value2)
				{
					$gnbKey = "'" . $this->_getGnbKey($value2) . "'";

					//insert menu item
					$args->menu_item_srl = getNextSequence();
					$args->parent_srl = $gnbDBList[$gnbKey];
					$args->name = '{$lang->menu_gnb_sub[\'' . $value2 . '\']}';
					$args->url = 'index.php?module=admin&act=' . $moduleActionInfo->menu->{$value2}->index;
					$args->listorder = -1 * $args->menu_item_srl;
					$output = executeQuery('menu.insertMenuItem', $args);
				}
			}
		}

		$oMenuAdminConroller = getAdminController('menu');
		$oMenuAdminConroller->makeXmlFile($menuSrl);

		// does not recreate lang cache sometimes
		FileHandler::RemoveFilesInDir('./files/cache/lang');
		FileHandler::RemoveFilesInDir('./files/cache/menu/admin_lang');
	}

	/**
	 * Return parent menu key by child menu
	 * @return string
	 */
	function _getGnbKey($menuName)
	{
		switch($menuName)
		{
			case 'siteMap':
			case 'siteDesign':
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
			case 'spamFilter':
				return 'content';
				break;
			case 'easyInstall':
			case 'installedLayout':
			case 'installedModule':
			case 'installedWidget':
			case 'installedAddon':
			case 'editor':
				return 'advanced';
				break;
			case 'adminConfigurationGeneral':
			case 'adminConfigurationFtp':
			case 'adminMenuSetup':
			case 'fileUpload':
			case 'filebox':
				return 'configuration';
				break;
			default:
				return 'advanced';
		}
	}

	/**
	 * Return parent old menu key by child menu
	 * @return string
	 */
	function _getOldGnbKey($menuName)
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

	private function _oldAdminmenuDelete()
	{
		$oMenuAdminModel = getAdminModel('menu');

		$output = $oMenuAdminModel->getMenuByTitle($this->adminMenuName);
		$newAdminmenuSrl = $output->menu_srl;
		$output = $oMenuAdminModel->getMenuItems($newAdminmenuSrl, 0);
		$newAdminParentMenuList = array();
		if(is_array($output->data))
		{
			foreach($output->data AS $key => $value)
			{
				$tmp = explode('\'', $value->name);
				$newAdminParentMenuList[$tmp[1]] = $value;
			}
		}
		unset($output);

		// old admin menu
		$output = $oMenuAdminModel->getMenuByTitle('__XE_ADMIN__');
		$menuSrl = $output->menu_srl;

		$oMenuAdminController = getAdminController('menu');
		if($menuSrl)
		{
			$output = $oMenuAdminModel->getMenuItems($menuSrl);
			if(is_array($output->data))
			{
				$parentMenu = array();
				foreach($output->data AS $key => $menuItem)
				{
					if($menuItem->parent_srl == 0)
					{
						$tmp = explode('\'', $menuItem->name);
						$parentMenuKey = $tmp[1];
						$parentMenu[$menuItem->menu_item_srl] = $parentMenuKey;
					}
				}

				$isUserAddedMenuMoved = FALSE;
				foreach($output->data AS $key => $menuItem)
				{
					if($menuItem->parent_srl != 0)
					{
						$tmp = explode('\'', $menuItem->name);
						$menuKey = $tmp[1];

						$result = $this->_getOldGnbKey($menuKey);
						if($result == 'user_added_menu')
						{
							// theme menu use not anymore
							/* if($parentMenu[$menuItem->parent_srl] == 'theme')
							  {
							  $newParentItem = $newAdminParentMenuList['menu'];
							  }
							  else */
							if($parentMenu[$menuItem->parent_srl] == 'extensions')
							{
								$newParentItem = $newAdminParentMenuList['advanced'];
							}
							else
							{
								$newParentItem = $newAdminParentMenuList[$parentMenu[$menuItem->parent_srl]];
							}
							$menuItem->menu_srl = $newParentItem->menu_srl;
							$menuItem->parent_srl = $newParentItem->menu_item_srl;

							$output = executeQuery('menu.updateMenuItem', $menuItem);
							$isUserAddedMenuMoved = TRUE;
						}
					}
				}

				if($isUserAddedMenuMoved)
				{
					$oMenuAdminController->makeXmlFile($newAdminmenuSrl);
				}
			}
		}

		// all old admin menu delete
		$output = $oMenuAdminModel->getMenuListByTitle('__XE_ADMIN__');
		if(is_array($output))
		{
			foreach($output AS $key=>$value)
			{
				$oMenuAdminController->deleteMenu($value->menu_srl);
			}
		}
	}

}
/* End of file admin.class.php */
/* Location: ./modules/admin/admin.class.php */
