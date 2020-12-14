<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * menuAdminController class
 * admin controller class of the menu module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/menu
 * @version 0.1
 */
class menuAdminController extends menu
{
	/**
	 * menu number
	 * @var int
	 */
	var $menuSrl = null;
	/**
	 * item key list
	 * @var array
	 */
	var $itemKeyList = array();
	/**
	 * map
	 * @var array
	 */
	var $map = array();
	/**
	 * checked
	 * @var array
	 */
	var $checked = array();
	/**
	 * inserted menu item serial number
	 * @var array
	 */
	var $insertedMenuItemSrlList = array();
	/**
	 * home module's mid
	 * @var string
	 */
	private $homeModuleMid = NULL;
	/**
	 * home menu cache file
	 * @var string
	 */
	private $homeMenuCacheFile = 'files/cache/menu/homeSitemap.php';

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
	}

	function __construct() {
		$this->homeMenuCacheFile = _XE_PATH_ . $this->homeMenuCacheFile;
		parent::__construct();
	}

	/**
	 * Add a menu
	 * @return void|object
	 */
	function procMenuAdminInsert()
	{
		// List variables
		$site_module_info = Context::get('site_module_info');

		$output = $this->addMenu(Context::get('title'), (int)$site_module_info->site_srl);
		if(!$output->toBool()) return $output;

		$this->add('menu_srl', $output->get('menuSrl'));
		$this->setMessage('success_registed');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMenuAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Add a menu
	 *
	 * @param string $title
	 * @param int $siteSrl
	 * @return Object If success, it contains menuSrl
	 */
	public function addMenu($title, $siteSrl = 0)
	{
		$args = new stdClass();
		$args->site_srl = $siteSrl;
		$args->title = $title;

		$args->menu_srl = getNextSequence();
		$args->listorder = $args->menu_srl * -1;

		$output = executeQuery('menu.insertMenu', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$output->add('menuSrl', $args->menu_srl);
		return $output;
	}

	function linkAllModuleInstancesToSitemap()
	{
		$unlinked_modules = false;
		$args = new stdClass;
		$args->site_srl = 0;
		$output = executeQueryArray('module.getNotLinkedModuleBySiteSrl',$args);
		if($output->toBool() && $output->data && count($output->data) > 0)
		{
			$unlinked_modules = $output->data;
		}

		if($unlinked_modules)
		{
			$unlinked_menu_srl = $this->getUnlinkedMenu();
			$output = $this->updateLinkModule($unlinked_modules, $unlinked_menu_srl);
		}

	}

	function getUnlinkedMenu()
	{
		// 'unlinked' menu 존재여부 확인
		$oModuleModel = getModel('module');
		$moduleConfig = $oModuleModel->getModuleConfig('menu') ?: new stdClass;

		if($moduleConfig->unlinked_menu_srl)
		{
			$menuArgs = new stdClass;
			$menuArgs->menu_srl = $moduleConfig->unlinked_menu_srl;
			$menuOutput = executeQuery('menu.getMenu', $menuArgs);
			if(!$menuOutput->data)
			{
				unset($moduleConfig->unlinked_menu_srl);
			}
		}

		if(!$moduleConfig->unlinked_menu_srl)
		{
			$output = $this->addMenu('unlinked', 0);
			if($output->toBool())
			{
				$moduleConfig->unlinked_menu_srl = $output->get('menuSrl');
				$oModuleController = getController('module');
				$oModuleController->updateModuleConfig('menu', $moduleConfig);
			}
			else
			{
				return false;
			}
		}

		return $moduleConfig->unlinked_menu_srl;
	}

	/**
	 * insert menu when not linked module.
	 *
	 * @param array $moduleInfos
	 * @param int $menuSrl
	 *
	 * @return Object
	 */
	function updateLinkModule($moduleInfos, $menuSrl)
	{
		if(!$moduleInfos || !is_array($moduleInfos) || count($moduleInfos) == 0 || $menuSrl == 0)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		foreach($moduleInfos as $moduleInfo)
		{
			// search menu.
			$args = new stdClass;
			$args->url = $moduleInfo->mid;
			$args->site_srl = $moduleInfo->site_srl;
			$args->is_shortcut = 'N';

			$output = executeQuery('menu.getMenuItemByUrl', $args);

			if($output->toBool() && $output->data)
			{
				$moduleInfo->menu_srl = $output->data->menu_srl;
			}
			else
			{
				// create menu item.
				$item_args = new stdClass();
				$item_args->menu_srl = $menuSrl;
				$item_args->url = $moduleInfo->mid;
				$item_args->name = $moduleInfo->mid;
				$item_args->menu_item_srl = getNextSequence();
				$item_args->listorder = -1*$item_args->menu_item_srl;

				$output = executeQuery('menu.insertMenuItem', $item_args);
				if(!$output->toBool())
				{
					return $output;
				}
				$moduleInfo->menu_srl = $menuSrl;
			}

			$output = executeQuery('module.updateModule', $moduleInfo);

			return $output;
		}

		Rhymix\Framework\Cache::clearGroup('site_and_module');
		$oMenuAdminController = getAdminController('menu');
		$oMenuAdminController->makeXmlFile($menuSrl);

		return new BaseObject();
	}



	/**
	 * Change the menu title
	 * @return void|object
	 */
	function procMenuAdminUpdate()
	{
		// List variables
		$args = new stdClass();
		$args->title = Context::get('title');
		$args->menu_srl = Context::get('menu_srl');

		$output = executeQuery('menu.updateMenu', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_registed');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMenuAdminManagement', 'menu_srl', $args->menu_srl);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete menu process method
	 * @return void|Object
	 */
	function procMenuAdminDelete()
	{
		$menu_srl = Context::get('menu_srl');

		$oMenuAdminModel = getAdminModel('menu');
		$menuInfo = $oMenuAdminModel->getMenu($menu_srl);

		$oAdmin = getClass('admin');
		if($menuInfo->title == $oAdmin->getAdminMenuName())
			throw new Rhymix\Framework\Exception('msg_adminmenu_cannot_delete');

		// get menu properies with child menu
		$phpFile = sprintf("./files/cache/menu/%d.php", $menu_srl);
		$originMenu = NULL;

		if(is_readable(FileHandler::getRealPath($phpFile)))
		{
			include(FileHandler::getRealPath($phpFile));
		}

		// check home menu in originMenu
		$oModuleModel = getModel('module');
		$siteInfo = $oModuleModel->getSiteInfo($menuInfo->site_srl);

		$isStartmenuInclude = false;

		if(is_array($menu->list))
		{
			foreach($menu->list AS $key=>$value)
			{
				$originMenu = $value;
				$this->_checkHomeMenuInOriginMenu($originMenu, $siteInfo->mid, $isStartmenuInclude);

				if($isStartmenuInclude)
					break;
			}
		}

		if($isStartmenuInclude)
		{
			throw new Rhymix\Framework\Exception('msg_cannot_delete_homemenu');
		}

		$output = $this->deleteMenu($menu_srl);
		if(!$output->toBool())
		{
			throw new Rhymix\Framework\Exception($output->message);
		}

		$this->setMessage('success_deleted', 'info');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMenuAdminSiteMap');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete menu
	 * Delete menu_item and xml cache files
	 * @return Object
	 */
	function deleteMenu($menu_srl)
	{
		$oDB = DB::getInstance();
		$oDB->begin();

		$args = new stdClass();
		$args->menu_srl = $menu_srl;

		$oMenuAdminModel = getAdminModel('menu');
		$menuInfo = $oMenuAdminModel->getMenu($args->menu_srl);

		// Delete modules
		$output = executeQueryArray('menu.getMenuItems', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		foreach($output->data as $itemInfo)
		{
			if($itemInfo->is_shortcut != 'Y' && strncasecmp('http', $itemInfo->url, 4) !== 0)
			{
				$moduleInfo = $oModuleModel->getModuleInfoByMid($itemInfo->url, $menuInfo->site_srl);
				if($moduleInfo->module_srl)
				{
					$output = $oModuleController->onlyDeleteModule($moduleInfo->module_srl);
					if(!$output->toBool())
					{
						$oDB->rollback();
						return $output;
					}
				}
			}
		}

		// Delete menu items
		$output = executeQuery("menu.deleteMenuItems", $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Delete the menu
		$output = executeQuery("menu.deleteMenu", $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete cache files
		$cache_list = FileHandler::readDir("./files/cache/menu","",false,true);
		if(count($cache_list))
		{
			foreach($cache_list as $cache_file)
			{
				$pos = strpos($cache_file, $menu_srl.'.');
				if($pos>0)FileHandler::removeFile($cache_file);
			}
		}
		// Delete images of menu buttons
		$image_path = sprintf('./files/attach/menu_button/%d', $menu_srl);
		FileHandler::removeDir($image_path);

		$oDB->commit();

		return new BaseObject(0,'success_deleted');
	}

	/**
	 * Add an item to the menu, simple version
	 * @return void
	 */
	public function procMenuAdminInsertItem($request = NULL)
	{
		$isProc = false;
		if(!$request)
		{
			$isProc = true;
			$request = Context::getRequestVars();
		}

		if(!$request->parent_srl || !$request->menu_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$this->_setMenuSrl($request->parent_srl, $request->menu_srl);
		if(!$request->menu_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		if($request->is_shortcut == 'Y')
		{
			$result = $this->_insertShortcut($request);
		}
		else
		{
			$result = $this->_insertMenu($request, $isProc);
		}

		if($result->error < 0)
		{
			return new BaseObject($result->error, $result->message);
		}

		// recreate menu cache file
		$this->makeXmlFile($request->menu_srl);

		if(!$isProc)
		{
			return $this->get('menu_item_srl');
		}
	}

	private function _setMenuSrl(&$parent_srl, &$menu_srl)
	{
		// set menu srl
		$oMenuAdminModel = getAdminModel('menu');
		$itemInfo = $oMenuAdminModel->getMenuItemInfo($parent_srl);
		// parent_srl is parent menu item's srl
		if($itemInfo->menu_srl)
		{
			$menu_srl = $itemInfo->menu_srl;
		}
		// in this case, parent_srl is menu srl
		else
		{
			$output = $oMenuAdminModel->getMenu($parent_srl);
			if($output->menu_srl == $parent_srl)
			{
				$menu_srl = $output->menu_srl;
				$parent_srl = 0;
			}
		}
	}

	private function _insertShortcut(&$request)
	{
		$oDB = DB::getInstance();
		$oDB->begin();

		// type is url
		if(strncasecmp('http', $request->shortcut_target, 4) === 0 || preg_match('/^(\.\/|\.\.\/|\/).*$/', $request->shortcut_target))
		{
			// set menu variable
			$args = new stdClass();
			$args->menu_srl = $request->menu_srl;
			$args->parent_srl = $request->parent_srl;
			$args->open_window = $request->menu_open_window;
			$args->expand = $request->menu_expand;
			$args->is_shortcut = $request->is_shortcut;
			$args->url = $request->shortcut_target;

			if(!$args->open_window) $args->open_window = 'N';
			if(!$args->expand) $args->expand = 'N';
			if(!$args->is_shortcut) $args->is_shortcut = 'Y';

			if($request->menu_name_key) $args->name = $request->menu_name_key;
			else $args->name = $request->menu_name;
		}
		// type is module short cut
		else if(is_numeric($request->shortcut_target))
		{
			// Get original information
			$oMenuAdminModel = getAdminModel('menu');
			$itemInfo = $oMenuAdminModel->getMenuItemInfo($request->shortcut_target);
			if(!$itemInfo->menu_item_srl)
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}
			unset($itemInfo->normal_btn, $itemInfo->hover_btn, $itemInfo->active_btn);

			$args = $itemInfo;
			if(count($args->group_srls) == 0)
			{
				unset($args->group_srls);
			}
			$args->menu_srl = $request->menu_srl;
			$args->name = $request->menu_name;
			$args->parent_srl = $request->parent_srl;
			$args->is_shortcut = $request->is_shortcut;
		}
		// empty target shortcut
		else
		{
			$args = new stdClass();
			$args->menu_srl = $request->menu_srl;
			$args->name = $request->menu_name;
			$args->parent_srl = $request->parent_srl;
			$args->is_shortcut = $request->is_shortcut;
			$args->url = '#';
		}

		if($request->menu_desc) $args->desc = $request->menu_desc;
		else $args->desc = '';

		$args->menu_item_srl = getNextSequence();
		$args->listorder = -1*$args->menu_item_srl;
		$output = executeQuery('menu.insertMenuItem', $args);
		if(!$output->toBool()) return $output;

		$oDB->commit();

		$this->add('menu_item_srl', $args->menu_item_srl);
		$this->setMessage('success_registed', 'info');
	}

	private function _insertMenu(&$request, $isProc)
	{
		$oDB = DB::getInstance();
		$oDB->begin();

		// set menu variable
		$args = new stdClass();
		$args->menu_srl = $request->menu_srl;
		$args->parent_srl = $request->parent_srl;
		$args->open_window = $request->menu_open_window;
		$args->expand = $request->menu_expand;
		$args->expand = $request->menu_expand;
		$args->is_shortcut = $request->is_shortcut;

		if(!$args->open_window) $args->open_window = 'N';
		if(!$args->expand) $args->expand = 'N';
		if(!$args->is_shortcut) $args->is_shortcut = 'N';

		if($request->menu_name_key) $args->name = $request->menu_name_key;
		else $args->name = $request->menu_name;

		if($request->menu_desc) $args->desc = $request->menu_desc;
		else $args->desc = '';

		if($request->module_id && strncasecmp('http', $request->module_id, 4) === 0)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// when menu copy, module already copied
		if($isProc)
		{
			$result = $this->_insertModule($request, $args);
			if(!$result->toBool())
			{
				throw new Rhymix\Framework\Exception($result->message);
			}
		}

		// if setting button variables, set argument button variables for db insert. but not upload in this method
		if($request->normal_btn) $args->normal_btn = $request->normal_btn;
		if($request->hover_btn) $args->hover_btn = $request->hover_btn;
		if($request->active_btn) $args->active_btn = $request->active_btn;

		if(!$request->module_id)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$args->url = $request->module_id;
		$args->menu_item_srl = getNextSequence();
		$args->listorder = -1*$args->menu_item_srl;
		$output = executeQuery('menu.insertMenuItem', $args);
		if(!$output->toBool()) return $output;

		$oDB->commit();

		$this->add('menu_item_srl', $args->menu_item_srl);
		$this->setMessage('success_registed', 'info');
	}

	/**
	 * insert module by men create value
	 * @request value of client request
	 * @args value for menu create
	 * @return bool result of create module
	 */
	private function _insertModule(&$request, &$args)
	{
		$cmArgs = new stdClass();
		switch ($request->module_type)
		{
			case 'WIDGET' :
			case 'ARTICLE' :
			case 'OUTSIDE' :
				$cmArgs->module = 'page';
				$cmArgs->page_type = $request->module_type;
				break;
			default:
				$cmArgs->module = $request->module_type;
				unset($cmArgs->page_type);
		}

		//module create
		$site_module_info = Context::get('site_module_info');
		$cmArgs->site_srl = (int)$site_module_info->site_srl;
		$cmArgs->browser_title = $args->name;
		$cmArgs->menu_srl = $request->menu_srl;
		$cmArgs->layout_srl = -1;
		$cmArgs->mlayout_srl = -1;
		$cmArgs->is_skin_fix = 'N';
		$cmArgs->is_mskin_fix = 'N';

		if(Mobile::isMobileEnabled() === true)
		{
			$cmArgs->use_mobile = 'Y';
		}

		// if mid is empty, auto create mid
		if(!$request->module_id)
		{
			$randomMid = $this->_makeRandomMid();
			$request->module_id = $cmArgs->module.'_'.$randomMid;
		}
		$cmArgs->mid = $request->module_id;

		// check already created module instance
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleInfoByMid($request->module_id);
		if($output->module_srl)
		{
			throw new Rhymix\Framework\Exception('msg_module_name_exists');
		}

		$oModuleController = getController('module');
		$output = $oModuleController->insertModule($cmArgs);

		return $output;
	}

	/**
	 * Update an item to the menu, simple version
	 * @return void
	 */
	public function procMenuAdminUpdateItem()
	{
		$request = Context::getRequestVars();

		if(!$request->menu_item_srl || !$request->menu_name)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// variables set
		if($request->menu_open_window != "Y") $request->menu_open_window = "N";
		if($request->menu_expand != "Y") $request->menu_expand = "N";

		// Get original information
		$oMenuAdminModel = getAdminModel('menu');
		$itemInfo = $oMenuAdminModel->getMenuItemInfo($request->menu_item_srl);
		$args = $itemInfo;

		// if menu type is module, check exists module and update
		if($itemInfo->is_shortcut == 'Y')
		{
			// type is url
			if(strncasecmp('http', $request->shortcut_target, 4) === 0 || preg_match('/^(\.\/|\.\.\/|\/).*$/', $request->shortcut_target))
			{
				$args->url = $request->shortcut_target;
			}
			// type is module short cut
			else if(is_numeric($request->shortcut_target))
			{
				// Get new original information
				$newItemInfo = $oMenuAdminModel->getMenuItemInfo($request->shortcut_target);
				if(!$newItemInfo->menu_item_srl)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}

				$args->url = $newItemInfo->url;
				$args->is_shortcut = 'Y';
			}
			else
			{
				$args->url = '#';
			}
		}
		else
		{
			// check already created module instance
			$oModuleModel = getModel('module');
			if($request->module_id != $itemInfo->url)
			{
				$output = $oModuleModel->getModuleInfoByMid($request->module_id);
				if($output->module_srl)
				{
					throw new Rhymix\Framework\Exception('msg_module_name_exists');
				}
			}

			// if not exist module, return error
			$moduleInfo = $oModuleModel->getModuleInfoByMid($itemInfo->url);
			if(!$moduleInfo)
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}

			$moduleInfo->mid = $request->module_id;
			if($request->browser_title)
			{
				$moduleInfo->browser_title = $request->browser_title;
			}
			$oModuleController = getController('module');
			$oModuleController->updateModule($moduleInfo);
			$args->url = $request->module_id;
		}

		if($request->menu_name_key)
		{
			$args->name = $request->menu_name_key;
		}
		else
		{
			$args->name = $request->menu_name;
		}

		if($request->menu_desc) $args->desc = $request->menu_desc;
		else $args->desc = '';

		unset($args->group_srls);
		$args->open_window = $request->menu_open_window;
		$args->expand = $request->menu_expand;
		$output = $this->_updateMenuItem($args);

		$this->makeXmlFile($args->menu_srl);

		$this->add('menu_item_srl', $args->menu_item_srl);
		$this->setMessage('success_updated', 'info');
	}

	/**
	 * upload button
	 * @retun void
	 */
	public function procMenuAdminButtonUpload()
	{
		$args = Context::getRequestVars();

		$oMenuAdminModel = getAdminModel('menu');
		$item_info = $oMenuAdminModel->getMenuItemInfo($args->menu_item_srl);
		$args->menu_srl = $item_info->menu_srl;

		$btnOutput = $this->_uploadButton($args);

		if($btnOutput['normal_btn'])
		{
			$this->add('normal_btn', $btnOutput['normal_btn']);
			$item_info->normal_btn = $btnOutput['normal_btn'];
		}
		if($btnOutput['hover_btn'])
		{
			$this->add('hover_btn', $btnOutput['hover_btn']);
			$item_info->hover_btn = $btnOutput['hover_btn'];
		}
		if($btnOutput['active_btn'])
		{
			$this->add('active_btn', $btnOutput['active_btn']);
			$item_info->active_btn = $btnOutput['active_btn'];
		}

		// group_srls check
		if(count($item_info->group_srls) == 0)
		{
			unset($item_info->group_srls);
		}

		// Button delete check
		if(!$btnOutput['normal_btn'] && $args->isNormalDelete == 'Y')
		{
			$item_info->normal_btn = '';
		}
		if(!$btnOutput['hover_btn'] && $args->isHoverDelete == 'Y')
		{
			$item_info->hover_btn = '';
		}
		if(!$btnOutput['active_btn'] && $args->isActiveDelete == 'Y')
		{
			$item_info->active_btn = '';
		}

		$output = $this->_updateMenuItem($item_info);

		// recreate menu cache file
		$this->makeXmlFile($args->menu_srl);
	}

	public function updateMenuItem($itemInfo)
	{
		$output = $this->_updateMenuItem($itemInfo);

		// recreate menu cache file
		$this->makeXmlFile($itemInfo->menu_srl);
		return $output;
	}

	public function _updateMenuItem($itemInfo)
	{
		$output = executeQuery('menu.updateMenuItem', $itemInfo);

		return $output;
	}

	/**
	 * Delete menu item(menu of the menu)
	 * @return void|Object
	 */
	function procMenuAdminDeleteItem()
	{
		// argument variables
		$args = new stdClass();
		$args->menu_srl = Context::get('menu_srl');
		$args->menu_item_srl = Context::get('menu_item_srl');
		$args->is_force = Context::get('is_force');

		$returnObj = $this->deleteItem($args);
		if(is_object($returnObj))
		{
			$this->setError($returnObj->error);
			$this->setMessage($returnObj->message);
		}
		else
		{
			$this->setMessage('success_deleted');
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMenuAdminManagement', 'menu_srl', $args->menu_srl);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete menu item ( Only include BO )
	 * @args menu_srl, menu_item_srl, is_force
	 * @return void|Object
	 */
	public function deleteItem($args)
	{
		$oModuleModel = getModel('module');
		$oMenuAdminModel = getAdminModel('menu');

		// Get original information
		$itemInfo = $oMenuAdminModel->getMenuItemInfo($args->menu_item_srl);
		$args->menu_srl = $itemInfo->menu_srl;

		// Display an error that the category cannot be deleted if it has a child node	603
		if($args->is_force != 'Y')
		{
			$output = executeQuery('menu.getChildMenuCount', $args);
			if(!$output->toBool()) return $output;
			if($output->data->count > 0)
			{
				return new BaseObject(-1001, 'msg_cannot_delete_for_child');
			}
		}

		// Get information of the menu
		$menuInfo = $oMenuAdminModel->getMenu($args->menu_srl);
		$menu_title = $menuInfo->title;

		// check admin menu delete
		$oAdmin = getClass('admin');
		if($menu_title == $oAdmin->getAdminMenuName() && $itemInfo->parent_srl == 0)
		{
			return new BaseObject(-1002, 'msg_cannot_delete_for_admin_topmenu');
		}

		if($itemInfo->parent_srl) $parent_srl = $itemInfo->parent_srl;

		// get menu properies with child menu
		$phpFile = sprintf("./files/cache/menu/%d.php", $args->menu_srl);
		$originMenu = NULL;

		if(is_readable(FileHandler::getRealPath($phpFile)))
		{
			include(FileHandler::getRealPath($phpFile));

			if(is_array($menu->list))
			{
				$this->_searchMenu($menu->list, $args->menu_item_srl, $originMenu);
			}
		}

		// check home menu in originMenu
		$siteInfo = $oModuleModel->getSiteInfo($menuInfo->site_srl);
		$isStartmenuInclude = false;
		$this->_checkHomeMenuInOriginMenu($originMenu, $siteInfo->mid, $isStartmenuInclude);
		if($isStartmenuInclude)
		{
			return new BaseObject(-1003, 'msg_cannot_delete_homemenu');
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		$this->_recursiveDeleteMenuItem($oDB, $menuInfo, $originMenu);

		$oDB->commit();

		// recreate menu cache file
		$this->makeXmlFile($args->menu_srl);

		$this->add('xml_file', $xml_file);
		$this->add('menu_title', $menu_title);
		$this->add('menu_item_srl', $parent_srl);

		return new BaseObject(0, 'success_deleted');
	}

	private function _checkHomeMenuInOriginMenu($originMenu, $startMid, &$isStartmenuInclude)
	{
		if($originMenu['is_shortcut'] != 'Y' && $originMenu['url'] == $startMid)
		{
			$isStartmenuInclude = true;
		}

		if(!$isStartmenuInclude && is_array($originMenu['list']))
		{
			foreach($originMenu['list'] AS $key=>$value)
			{
				$this->_checkHomeMenuInOriginMenu($value, $startMid, $isStartmenuInclude);
			}
		}
	}

	private function _deleteMenuItem(&$oDB, &$menuInfo, $node)
	{
		// Remove from the DB
		$args = new stdClass();
		$args->menu_srl = $menuSrl;
		$args->menu_item_srl = $node['node_srl'];
		$output = executeQuery("menu.deleteMenuItem", $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Update the xml file and get its location
		$xml_file = $this->makeXmlFile($args->menu_srl);
		// Delete all of image buttons
		if($node['normal_btn']) FileHandler::removeFile($node['normal_btn']);
		if($node['hover_btn']) FileHandler::removeFile($node['hover_btn']);
		if($node['active_btn']) FileHandler::removeFile($node['active_btn']);

		// Delete module
		if($node['is_shortcut'] != 'Y' && strncasecmp('http', $node['url'], 4) !== 0)
		{
			$oModuleController = getController('module');
			$oModuleModel = getModel('module');

			// reference menu's url modify
			$args->url = $node['url'];
			$args->site_srl = $menuInfo->site_srl;
			$args->is_shortcut = 'Y';
			$output = executeQuery('menu.getMenuItemByUrl', $args);
			if($output->data->menu_item_srl)
			{
				$output->data->url = '';
				$referenceItem = $output->data;
				$output = $this->_updateMenuItem($referenceItem);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			$moduleInfo = $oModuleModel->getModuleInfoByMid($node['url'], $menuInfo->site_srl);
			if($moduleInfo->module_srl)
			{
				$output = $oModuleController->onlyDeleteModule($moduleInfo->module_srl);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}
		return new BaseObject(0, 'success');
	}

	private function _recursiveDeleteMenuItem(&$oDB, &$menuInfo, $node)
	{
		$output = $this->_deleteMenuItem($oDB, $menuInfo, $node);
		if(!$output->toBool())
		{
			throw new Rhymix\Framework\Exception($output->message);
		}

		if(is_array($node['list']))
		{
			foreach($node['list'] AS $key=>$value)
			{
				$this->_recursiveDeleteMenuItem($oDB, $menuInfo, $value);
			}
		}
	}

	/**
	 * Move menu items
	 * @return void
	 */
	function procMenuAdminMoveItem()
	{
		$mode = Context::get('mode');	//move
		$parent_srl = Context::get('parent_srl');	// Parent menu item serial number
		$source_srl = Context::get('source_srl');	// Same hierarchy's menu item serial number
		$target_srl = Context::get('target_srl');	// Self menu item serial number

		if(!$mode || !$parent_srl || !$target_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oMenuAdminModel = getAdminModel('menu');

		// get original menu item info for cache file recreate
		$originalItemInfo = $oMenuAdminModel->getMenuItemInfo($target_srl);
		$targetMenuInfo = $oMenuAdminModel->getMenu($target_srl);
		$targetMenuItemInfo = $oMenuAdminModel->getMenuItemInfo($target_srl);
		if(!$originalItemInfo->menu_item_srl || (!$targetMenuInfo->menu_srl && !$targetMenuItemInfo->menu_item_srl))
		{
			throw new Rhymix\Framework\Exception('msg_empty_menu_item');
		}

		// get menu properies with child menu
		$phpFile = sprintf(_XE_PATH_ . "files/cache/menu/%d.php", $originalItemInfo->menu_srl);
		$originMenu = NULL;

		if(is_readable(FileHandler::getRealPath($phpFile)))
		{
			include(FileHandler::getRealPath($phpFile));

			if(is_array($menu->list))
			{
				$this->_searchMenu($menu->list, $originalItemInfo->menu_item_srl, $originMenu);
			}
		}

		// get target menu info for move
		$targetMenuItemInfo = $oMenuAdminModel->getMenuItemInfo($parent_srl);
		// if move in same sitemap
		if($targetMenuItemInfo->menu_item_srl)
		{
			$menu_srl = $targetMenuItemInfo->menu_srl;
		}
		// if move to other sitemap
		else
		{
			$targetMenuInfo = $oMenuAdminModel->getMenu($parent_srl);
			$menu_srl = $targetMenuInfo->menu_srl;
			$parent_srl = 0;
		}

		if(!$this->homeModuleMid)
		{
			$oModuleModel = getModel('module');
			$oMenuAdminController = getAdminController('menu');
			$columnList = array('modules.mid',);
			$output = $oModuleModel->getSiteInfo(0, $columnList);
			if($output->mid)
			{
				$this->homeModuleMid = $output->mid;
			}
		}

		$this->moveMenuItem($menu_srl, $parent_srl, $source_srl, $target_srl, $mode, $originMenu['is_shortcut'], $originMenu['url']);
		if(count($originMenu['list']) > 0)
		{
			$this->_recursiveUpdateMenuItem($originMenu['list'], $menu_srl);
		}

		//recreate original menu
		$xml_file = $this->makeXmlFile($originalItemInfo->menu_srl);

		//recreate target menu
		$xml_file = $this->makeXmlFile($menu_srl);
	}

	private function _recursiveUpdateMenuItem($node, $menu_srl)
	{
		if(is_array($node))
		{
			foreach($node AS $key=>$node)
			{
				$args = new stdClass();
				$args->menu_srl = $menu_srl;
				$args->menu_item_srl = $node['node_srl'];
				$output = $this->_updateMenuItem($args);

				//module's menu_srl move also
				if($node['is_shortcut'] == 'N' && !empty($node['url']))
				{
					$oModuleModel = getModel('module');
					$moduleInfo = $oModuleModel->getModuleInfoByMid($node['url']);
					if($menu_srl != $moduleInfo->menu_srl)
					{
						$moduleInfo->menu_srl = $menu_srl;
						$oModuleController = getController('module');
						$output = $oModuleController->updateModule($moduleInfo);
					}
				}

				if(count($node['list']) > 0)
				{
					$this->_recursiveUpdateMenuItem($node['list'], $menu_srl);
				}
			}
		}
	}

	/**
	 * cop menu item
	 * @return void
	 */
	public function procMenuAdminCopyItem()
	{
		$parentSrl = Context::get('parent_srl');
		$menuItemSrl = Context::get('menu_item_srl');

		$oMenuModel = getAdminModel('menu');
		$itemInfo = $oMenuModel->getMenuItemInfo($menuItemSrl);
		$menuSrl = $itemInfo->menu_srl;

		// get menu properies with child menu
		$phpFile = sprintf(_XE_PATH_ . "files/cache/menu/%s.php", $menuSrl);
		$originMenu = NULL;

		if(is_readable(FileHandler::getRealPath($phpFile)))
		{
			include(FileHandler::getRealPath($phpFile));

			if(is_array($menu->list))
			{
				$this->_searchMenu($menu->list, $menuItemSrl, $originMenu);
			}
		}

		// copy the menu item with recursively
		if(is_array($originMenu))
		{
			$this->_copyMenu($menuSrl, $parentSrl, $originMenu);
		}
		$this->add('insertedMenuItemSrlList', $this->insertedMenuItemSrlList);
	}

	/**
	 * search menu_item in full menu with recursively
	 * @param $menuList menu list
	 * @param $menuItemSrl current menu item serial number
	 * @param $originMenu find result menu
	 * @return void
	 */
	private function _searchMenu(&$menuList, $menuItemSrl, &$originMenu)
	{
		if(array_key_exists($menuItemSrl, $menuList))
		{
			$originMenu = $menuList[$menuItemSrl];
			return;
		}

		foreach($menuList AS $key=>$value)
		{
			if(count($value['list']) > 0)
			{
				$this->_searchMenu($value['list'], $menuItemSrl, $originMenu);
			}
		}
	}

	private function _copyMenu($menuSrl, $parentSrl, &$originMenu)
	{
		$oMenuAdminModel = getAdminModel('menu');
		$menuItemInfo = $oMenuAdminModel->getMenuItemInfo($originMenu['node_srl']);

		// default argument setting
		$args = new stdClass();
		$args->menu_srl = $menuSrl;
		if($parentSrl == 0) $args->parent_srl = $menuSrl;
		else $args->parent_srl = $parentSrl;
		$args->menu_name_key = $originMenu['text'];
		$args->menu_name = $originMenu['text'];
		$args->menu_open_window = $originMenu['open_window'];
		$args->menu_expand = $originMenu['expand'];
		$args->normal_btn = $menuItemInfo->normal_btn;
		$args->hover_btn = $menuItemInfo->hover_btn;
		$args->active_btn = $menuItemInfo->active_btn;
		$args->is_shortcut = $menuItemInfo->is_shortcut;

		$isModuleCopySuccess = false;
		// if menu have a reference of module instance
		if($menuItemInfo->is_shortcut == 'N' && strncasecmp('http', $originMenu['url'], 4) !== 0 )
		{
			$oModuleModel = getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByMid($originMenu['url']);

			$args->module_type = $moduleInfo->module;
			$randomMid = $this->_makeRandomMid();
			$args->module_id = $moduleInfo->module.'_'.$randomMid;
			$args->layout_srl = $moduleInfo->layout_srl;

			$oModuleAdminController = getAdminController('module');
			$copyArg = new stdClass();
			$copyArg->module_srl = $moduleInfo->module_srl;
			$copyArg->mid_1 = $args->module_id;
			$copyArg->browser_title_1 = $moduleInfo->browser_title;
			$copyArg->isMenuCreate = FALSE;
			$copiedModuleSrl = $oModuleAdminController->procModuleAdminCopyModule($copyArg);

			$args->module_srl = $copiedModuleSrl;

			if($copiedModuleSrl)
			{
				$isModuleCopySuccess = true;
			}
		}
		// if menu type is shortcut
		else if($menuItemInfo->is_shortcut == 'Y')
		{
			$args->shortcut_target = $originMenu['url'];
			$isModuleCopySuccess = true;
		}

		if($isModuleCopySuccess)
		{
			// if have a group permission
			if($menuItemInfo->group_srls)
			{
				$args->group_srls = $menuItemInfo->group_srls;
			}

			// menu copy
			$output = $this->procMenuAdminInsertItem($args);
			/*if($output && !$output->toBool())
			{
			return $output;
			}*/

			// if have a button, copy a button image also
			$insertedMenuItemSrl = $this->get('menu_item_srl');
			if($menuItemInfo->normal_btn || $menuItemInfo->hover_btn || $menuItemInfo->active_btn)
			{
				// copy & upate
				$update_item_info = $oMenuAdminModel->getMenuItemInfo($insertedMenuItemSrl);
				$copied_info = $this->_copyButton($insertedMenuItemSrl,$update_item_info->menu_srl, $menuItemInfo);
				if(count($update_item_info->group_srls) == 0)
				{
					unset($update_item_info->group_srls);
				}
				$update_item_info->normal_btn = $copied_info['normal_btn'];
				$update_item_info->hover_btn = $copied_info['hover_btn'];
				$update_item_info->active_btn = $copied_info['active_btn'];
				$output = $this->_updateMenuItem($update_item_info);
			}
			$this->insertedMenuItemSrlList[] = $insertedMenuItemSrl;
		}

		// if have a child menu, copy child menu also
		$childMenu = array_shift($originMenu['list']);
		if(count($childMenu) > 0)
		{
			$this->_copyMenu($menuSrl, $insertedMenuItemSrl, $childMenu);
		}
	}

	private function _makeRandomMid()
	{
		$time = $_SERVER['REQUEST_TIME'];
		$randomString = "";
		for($i=0;$i<4;$i++)
		{
			$case = rand(0, 1);
			if($case) $doc = rand(65, 90);
			else $doc = rand(97, 122);

			$randomString .= chr($doc);
		}

		return $randomString.substr($time, -2);
	}

	/**
	 * Arrange menu items
	 * @return void|object
	 */
	function procMenuAdminArrangeItem()
	{
		$this->menuSrl = Context::get('menu_srl');
		$args = new stdClass();
		$args->title = Context::get('title');
		$parentKeyList = Context::get('parent_key');
		$this->itemKeyList = Context::get('item_key');

		// menu name update
		$args->menu_srl = $this->menuSrl;
		$output = executeQuery('menu.updateMenu', $args);
		if(!$output->toBool()) return $output;

		$this->map = array();
		if(is_array($parentKeyList))
		{
			foreach($parentKeyList as $no=>$srl)
			{
				if($srl === 0) continue;
				if(!is_array($this->map[$srl]))$this->map[$srl] = array();
				$this->map[$srl][] = $no;
			}
		}

		$result = array();
		if(is_array($this->itemKeyList))
		{
			foreach($this->itemKeyList as $srl)
			{
				if(!$this->checked[$srl])
				{
					$target = new stdClass();
					$this->checked[$srl] = 1;
					$target->node = $srl;
					$target->child= array();

					while(count($this->map[$srl]))
					{
						$this->_setParent($srl, array_shift($this->map[$srl]), $target);
					}
					$result[] = $target;
				}
			}
		}

		if(is_array($result))
		{
			$i = 0;
			foreach($result AS $key=>$node)
			{
				$this->moveMenuItem($this->menuSrl, 0, $i, $node->node, 'move');	//move parent node
				$this->_recursiveMoveMenuItem($node);	//move child node
				$i = $node->node;
			}
		}

		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMenuAdminManagement', 'menu_srl', $args->menu_srl);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Set parent number to child
	 * @param int $parent_srl
	 * @param int $child_index
	 * @param object $target
	 * @return void
	 */
	function _setParent($parent_srl, $child_index, &$target)
	{
		$child_srl = $this->itemKeyList[$child_index];
		$this->checked[$child_srl] = 1;

		$child_node = new stdClass();
		$child_node->node = $child_srl;
		$child_node->parent_node = $parent_srl;
		$child_node->child = array();
		$target->child[] = $child_node;

		while(count($this->map[$child_srl]))
		{
			$this->_setParent($child_srl, array_shift($this->map[$child_srl]), $child_node);
		}
		//return $target;
	}

	/**
	 * move item with sub directory(recursive)
	 * @param object $result
	 * @return void
	 */
	function _recursiveMoveMenuItem($result)
	{
		$i = 0;
		while(count($result->child))
		{
			unset($node);
			$node = array_shift($result->child);

			$this->moveMenuItem($this->menuSrl, $node->parent_node, $i, $node->node, 'move');
			$this->_recursiveMoveMenuItem($node);
			$i = $node->node;
		}
	}

	/**
	 * move menu item
	 * @param int $menu_srl
	 * @param int $parent_srl
	 * @param int $source_srl
	 * @param int $target_srl
	 * @param string $mode 'move' or 'insert'
	 * @return void
	 */
	function moveMenuItem($menu_srl, $parent_srl, $source_srl, $target_srl, $mode, $isShortcut='Y', $url=NULL)
	{
		// Get the original menus
		$oMenuAdminModel = getAdminModel('menu');

		$target_item = $oMenuAdminModel->getMenuItemInfo($target_srl);
		if($target_item->menu_item_srl != $target_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
		// Move the menu location(change the order menu appears)
		if($mode == 'move')
		{
			$args = new stdClass();
			$args->parent_srl = $parent_srl;
			$args->menu_srl = $menu_srl;

			if($source_srl)
			{
				$source_item = $oMenuAdminModel->getMenuItemInfo($source_srl);
				if($source_item->menu_item_srl != $source_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
				$args->listorder = $source_item->listorder-1;
			}
			else
			{
				$output = executeQuery('menu.getMaxListorder', $args);
				if(!$output->toBool()) return $output;
				$args->listorder = (int)$output->data->listorder;
				if(!$args->listorder) $args->listorder= 0;
			}
			$args->parent_srl = $parent_srl;
			$output = executeQuery('menu.updateMenuItemListorder', $args);
			if(!$output->toBool()) return $output;

			$args->parent_srl = $parent_srl;
			$args->menu_item_srl = $target_srl;
			$output = executeQuery('menu.updateMenuItemNode', $args);
			if(!$output->toBool()) return $output;

			//module's menu_srl move also
			if($isShortcut == 'N' && !empty($url))
			{
				$oModuleModel = getModel('module');
				$moduleInfo = $oModuleModel->getModuleInfoByMid($url);
				if($menu_srl != $moduleInfo->menu_srl)
				{
					$moduleInfo->menu_srl = $menu_srl;
					$oModuleController = getController('module');
					$output = $oModuleController->updateModule($moduleInfo);
				}

				// change home menu cache file
				if($url == $this->homeModuleMid)
				{
					if(file_exists($this->homeMenuCacheFile))
					{
						include($this->homeMenuCacheFile);
					}
					if(!$homeMenuSrl || $homeMenuSrl != $menu_srl)
					{
						$this->makeHomemenuCacheFile($menu_srl);
					}
				}
			}
			// Add a child
		}
		elseif($mode == 'insert')
		{
			$args = new stdClass();
			$args->menu_item_srl = $target_srl;
			$args->parent_srl = $parent_srl;
			$args->listorder = -1*getNextSequence();
			$output = executeQuery('menu.updateMenuItemNode', $args);
			if(!$output->toBool()) return $output;
		}

		$xml_file = $this->makeXmlFile($menu_srl);
		return $xml_file;
	}

	/**
	 * Update xml file
	 * XML file is not often generated after setting menus on the admin page\n
	 * For this occasional cases, manually update was implemented. \n
	 * It looks unnecessary at this moment however no need to eliminate the feature. Just leave it.
	 * @return void
	 */
	function procMenuAdminMakeXmlFile()
	{
		// Check input value
		$menu_srl = Context::get('menu_srl');
		// Get information of the menu
		$oMenuAdminModel = getAdminModel('menu');
		$menu_info = $oMenuAdminModel->getMenu($menu_srl);
		$menu_title = $menu_info->title;
		// Re-generate the xml file
		$xml_file = $this->makeXmlFile($menu_srl);
		// Set return value
		$this->add('menu_title',$menu_title);
		$this->add('xml_file',$xml_file);
	}

	/**
	 * Register a menu image button
	 * @return void
	 */
	function procMenuAdminUploadButton()
	{
		$menu_srl = Context::get('menu_srl');
		$menu_item_srl = Context::get('menu_item_srl');
		$target = Context::get('target');
		$target_file = Context::get($target);
		// Error occurs when the target is neither a uploaded file nor a valid file
		if(!$menu_srl || !$menu_item_srl)
		{
			Context::set('error_messge', lang('msg_invalid_request'));

		}
		else if(!$target_file || !is_uploaded_file($target_file['tmp_name']) || !preg_match('/\.(gif|jpeg|jpg|png)$/i',$target_file['name']))
		{
			Context::set('error_messge', lang('msg_invalid_request'));
		}

		// Move the file to a specific director if the uploaded file meets requirement
		else
		{
			$tmp_arr = explode('.',$target_file['name']);
			$ext = $tmp_arr[count($tmp_arr)-1];

			$path = sprintf('./files/attach/menu_button/%d/', $menu_srl);
			$filename = sprintf('%s%d.%s.%s', $path, $menu_item_srl, $target, $ext);

			if(!is_dir($path)) FileHandler::makeDir($path);

			move_uploaded_file($target_file['tmp_name'], $filename);
			Context::set('filename', $filename);
		}

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('menu_file_uploaded');
	}

	/**
	 * Remove the menu image button
	 * @return void
	 */
	function procMenuAdminDeleteButton()
	{
		$menu_srl = Context::get('menu_srl');
		$menu_item_srl = Context::get('menu_item_srl');
		$target = Context::get('target');
		$filename = Context::get('filename');
		FileHandler::removeFile($filename);

		$this->add('target', $target);
	}

	/**
	 * Get all act list for admin menu
	 * @return void
	 */
	function procMenuAdminAllActList()
	{
		$oModuleModel = getModel('module');
		$installed_module_list = $oModuleModel->getModulesXmlInfo();
		if(is_array($installed_module_list))
		{
			$currentLang = Context::getLangType();
			$menuList = array();
			foreach($installed_module_list AS $key=>$value)
			{
				$info = $oModuleModel->getModuleActionXml($value->module);
				if($info->menu) $menuList[$value->module] = $info->menu;
				unset($info->menu);
			}
		}
		$this->add('menuList', $menuList);
	}

	/**
	 * Get all act list for admin menu
	 * @return void|object
	 */
	function procMenuAdminInsertItemForAdminMenu()
	{
		$requestArgs = Context::getRequestVars();
		$tmpMenuName = explode(':', $requestArgs->menu_name);
		$moduleName = $tmpMenuName[0];
		$menuName = $tmpMenuName[1];

		// variable setting
		$logged_info = Context::get('logged_info');
		//$oMenuAdminModel = getAdminModel('menu');
		$oMemberModel = getModel('member');

		//$parentMenuInfo = $oMenuAdminModel->getMenuItemInfo($requestArgs->parent_srl);
		$groupSrlList = $oMemberModel->getMemberGroups($logged_info->member_srl);

		//preg_match('/\{\$lang->menu_gnb\[(.*?)\]\}/i', $parentMenuInfo->name, $m);
		$oModuleModel = getModel('module');
		//$info = $oModuleModel->getModuleInfoXml($moduleName);
		$info = $oModuleModel->getModuleActionXml($moduleName);
		
		$args = new stdClass();
		$args->url = 'index.php?module=admin';
		
		if($info->menu->{$menuName}->index)
		{
			$args->url .= '&act=' . $info->menu->{$menuName}->index;
		}
		else if($info->admin_index_act)
		{
			$args->url .= '&act=' . $info->admin_index_act;
		}
		
		$args->menu_item_srl = (!$requestArgs->menu_item_srl) ? getNextSequence() : $requestArgs->menu_item_srl;
		$args->parent_srl = $requestArgs->parent_srl;
		$args->menu_srl = $requestArgs->menu_srl;
		$args->name = sprintf('{$lang->menu_gnb_sub[\'%s\']}', $menuName);
		$args->open_window = 'N';
		$args->expand = 'N';
		$args->normal_btn = '';
		$args->hover_btn = '';
		$args->active_btn = '';
		$args->group_srls = implode(',', array_keys($groupSrlList));
		$args->listorder = -1*$args->menu_item_srl;

		// Check if already exists
		$oMenuModel = getAdminModel('menu');
		$item_info = $oMenuModel->getMenuItemInfo($args->menu_item_srl);
		// Update if exists
		if($item_info->menu_item_srl == $args->menu_item_srl)
		{
			$output = $this->_updateMenuItem($args);
			if(!$output->toBool()) return $output;
		}
		// Insert if not exist
		else
		{
			$args->listorder = -1*$args->menu_item_srl;
			$output = executeQuery('menu.insertMenuItem', $args);
			if(!$output->toBool()) return $output;
		}
		// Get information of the menu
		$menu_info = $oMenuModel->getMenu($args->menu_srl);
		$menu_title = $menu_info->title;
		// Update the xml file and get its location
		$xml_file = $this->makeXmlFile($args->menu_srl);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Update menu auth (Exposure and Access)
	 * @return void
	 */
	public function procMenuAdminUpdateAuth()
	{
		$menuItemSrl = Context::get('menu_item_srl');
		$exposure = Context::get('exposure');
		$htPerm = Context::get('htPerm');

		$oMenuModel = getAdminModel('menu');
		$itemInfo = $oMenuModel->getMenuItemInfo($menuItemSrl);
		$args = $itemInfo;

		// Menu Exposure update
		// if exposure target is only login user...
		if(!$exposure)
		{
			$args->group_srls = '';
		}
		else
		{
			$exposure = explode(',', $exposure);
			if(in_array($exposure, array('-1','-3')))
			{
				$args->group_srls = $exposure;
			}

			if($exposure) $args->group_srls = implode(',', $exposure);
		}

		$output = $this->_updateMenuItem($args);
		if(!$output->toBool())
		{
			return $output;
		}

		// Module Access update
		unset($args);
		$oMenuAdminModel = getAdminModel('menu');
		$menuInfo = $oMenuAdminModel->getMenu($itemInfo->menu_srl);

		$oModuleModel = getModel('module');
		$moduleInfo = $oModuleModel->getModuleInfoByMid($itemInfo->url, $menuInfo->site_srl);
		$xml_info = $oModuleModel->getModuleActionXML($moduleInfo->module);

		if($itemInfo->is_shortcut === 'Y')
		{
			$moduleGrnatsArgs = new stdClass;
			$moduleGrnatsArgs->module_srl = $moduleInfo->module_srl;
			$output = executeQueryArray('module.getModuleGrants', $moduleGrnatsArgs);
			if(!$output->data) $output->data = array();
			$moduleGrnats = new stdClass();
			foreach($output->data as $grant)
			{
				$moduleGrnats->{$grant->name}[] = $grant->group_srl;
			}
		}

		$grantList = $xml_info->grant;
		if(!$grantList) $grantList = new stdClass;

		$grantList->access = new stdClass();
		$grantList->access->default = 'guest';
		$grantList->manager = new stdClass();
		$grantList->manager->default = 'manager';

		$grant = new stdClass();
		foreach($grantList AS $grantName=>$grantInfo)
		{
			if($htPerm[$grantName])
			{
				$htPerm[$grantName] = explode(',', $htPerm[$grantName]);

				// users in a particular group
				if(is_array($htPerm[$grantName]))
				{
					$grant->{$grantName} = $htPerm[$grantName];
					continue;
				}
				// -1 = Log-in user only, -2 = site members only, 0 = all users
				else
				{
					$grant->{$grantName}[] = $htPerm[$grantName];
					continue;
				}
			}
			else if($itemInfo->is_shortcut === 'Y')
			{
				if(isset($moduleGrnats) && $moduleGrnats->{$grantName}) $grant->{$grantName} = $moduleGrnats->{$grantName};
			}
		}

		if(countobj($grant))
		{
			$oModuleController = getController('module');
			$oModuleController->insertModuleGrants($moduleInfo->module_srl, $grant);
		}

		// recreate menu cache file
		$this->makeXmlFile($itemInfo->menu_srl);
	}

	/**
	 * Generate XML file for menu and return its location
	 * @param int $menu_srl
	 * @return string
	 */
	function makeXmlFile($menu_srl)
	{
		// Return if there is no information when creating the xml file
		if(!$menu_srl) return;
		// Specify the name of the cache file
		$xml_file = sprintf(_XE_PATH_ . "files/cache/menu/%d.xml.php", $menu_srl);
		$php_file = sprintf(_XE_PATH_ . "files/cache/menu/%d.php", $menu_srl);
		// Get menu informaton
		$args = new stdClass();
		$args->menu_srl = $menu_srl;
		$output = executeQuery('menu.getMenu', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data)
		{
			FileHandler::writeFile($xml_file, '<root />');
			FileHandler::writeFile($php_file, '<?php if(!defined("__XE__")) exit(); ?>');
			return $xml_file;
		}		
		$site_srl = 0;
		$domain = null;

		// Get a list of menu items corresponding to menu_srl by listorder
		$args->menu_srl = $menu_srl;
		$args->sort_index = 'listorder';
		$output = executeQuery('menu.getMenuItems', $args);
		if(!$output->toBool()) return $output;
		// If no data found, generate an XML file without node data
		$list = $output->data;
		if(!$list)
		{
			FileHandler::writeFile($xml_file, '<root />');
			FileHandler::writeFile($php_file, '<?php if(!defined("__XE__")) exit(); ?>');
			return $xml_file;
		}
		// Change to an array if only a single data is obtained
		if(!is_array($list)) $list = array($list);
		// Create a tree for loop
		$list_count = count($list);
		for($i=0;$i<$list_count;$i++)
		{
			$node = $list[$i];
			$menu_item_srl = $node->menu_item_srl;
			$parent_srl = $node->parent_srl;

			$tree[$parent_srl][$menu_item_srl] = $node;
		}
		// A common header to set permissions of the cache file and groups
		$header_script =
			'$lang_type = Context::getLangType();' . PHP_EOL .
			'$is_logged = Context::get(\'is_logged\');' . PHP_EOL .
			'$logged_info = Context::get(\'logged_info\');' . PHP_EOL .
			'$site_srl = '.$site_srl.';' . PHP_EOL .
			'$site_admin = false;' . PHP_EOL .
			'if($site_srl) {' . PHP_EOL .
			'  $oModuleModel = getModel(\'module\');' . PHP_EOL .
			'  $site_module_info = $oModuleModel->getSiteInfo($site_srl);' . PHP_EOL .
			'  if($site_module_info) Context::set(\'site_module_info\',$site_module_info);' . PHP_EOL .
			'  else $site_module_info = Context::get(\'site_module_info\');' . PHP_EOL .
			'  $grant = $oModuleModel->getGrant($site_module_info, $logged_info);' . PHP_EOL .
			'  if($grant->manager ==1) $site_admin = true;' . PHP_EOL .
			'}' . PHP_EOL .
			'if($is_logged) {' . PHP_EOL .
			'  if($logged_info->is_admin=="Y") $is_admin = true;' . PHP_EOL .
			'  else $is_admin = false;' . PHP_EOL .
			'  $group_srls = array_keys($logged_info->group_list);' . PHP_EOL .
			'} else {' . PHP_EOL .
			'  $is_admin = false;' . PHP_EOL .
			'  $group_srls = array();' . PHP_EOL .
			'}';
		// Create the xml cache file (a separate session is needed for xml cache)
		$xml_buff = sprintf(
			'<?php ' . PHP_EOL .
			'require_once('.var_export(FileHandler::getRealPath('./common/autoload.php'), true) . ');' . PHP_EOL .
			'Context::init(); ' . PHP_EOL .
			'header("Content-Type: text/xml; charset=UTF-8");' . PHP_EOL .
			'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");' . PHP_EOL .
			'header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");' . PHP_EOL .
			'header("Cache-Control: no-store, no-cache, must-revalidate");' . PHP_EOL .
			'header("Cache-Control: post-check=0, pre-check=0", false);' . PHP_EOL .
			'header("Pragma: no-cache");' . PHP_EOL .
			'%s' . PHP_EOL .
			'$oContext->close();' . PHP_EOL .
			'<root>%s</root>',
			$header_script,
			$this->getXmlTree($tree[0], $tree, $site_srl, $domain)
		);
		// Create php cache file
		$php_output = $this->getPhpCacheCode($tree[0], $tree, $site_srl, $domain);
		$php_buff = sprintf(
			'<?php ' . PHP_EOL .
			'if(!defined("__XE__")) exit();' . PHP_EOL .
			'$menu = new stdClass();' . PHP_EOL .
			'$_menu_names = $_menu_names ?? [];' . PHP_EOL .
			'%s; ' . PHP_EOL .
			'%s; ' . PHP_EOL .
			'$menu->list = array(%s); ' . PHP_EOL .
			'if(!$is_admin) { recurciveExposureCheck($menu->list); }' . PHP_EOL .
			'Context::set("included_menu", $menu); ' . PHP_EOL,
			$header_script,
			$php_output['name'],
			$php_output['buff']
		);
		// Save File
		FileHandler::writeFile($xml_file, $xml_buff);
		FileHandler::writeFile($php_file, $php_buff);
		return $xml_file;
	}

	/**
	 * Create xml data recursively looping for array nodes by referencing to parent_srl
	 * menu xml file uses a tag named "node" and this XML configures menus on admin page.
	 * (Implement tree menu by reading the xml file in tree_menu.js)
	 * @param array $source_node
	 * @param array $tree
	 * @param int $site_srl
	 * @param string $domain
	 * @return string
	 */
	function getXmlTree($source_node, $tree, $site_srl, $domain)
	{
		if(!$source_node) return;

		$oMenuAdminModel = getAdminModel('menu');
		$buff = '';

		foreach($source_node as $menu_item_srl => $node)
		{
			$child_buff = "";
			// Get data of the child nodes
			if($menu_item_srl && isset($tree[$menu_item_srl]))
			{
				$child_buff = $this->getXmlTree($tree[$menu_item_srl], $tree, $site_srl, $domain);
			}
			
			// List variables
			$names = $oMenuAdminModel->getMenuItemNames($node->name, $site_srl);
			$name_arr_str = '';
			foreach($names as $key => $val)
			{
				$name_arr_str .= sprintf('%s => %s, ', var_export($key, true), var_export($val, true));
			}
			$name_str = sprintf('$_names = array(%s); print $_names[$lang_type];', $name_arr_str);

			$url = escape($node->url);
			$desc = escape($node->desc, false);
			if(preg_match('/^([0-9a-zA-Z\_\-]+)$/', $node->url))
			{
				$href = "getSiteUrl('$domain', '','mid','$node->url')";
			}
			else
			{
				$href = var_export($url, true);
			}
			$is_shortcut = strval($node->is_shortcut);
			$open_window = strval($node->open_window);
			$expand = strval($node->expand);

			$normal_btn = strval($node->normal_btn);
			if($normal_btn && strncasecmp('./files/attach/menu_button', $normal_btn, 26) === 0) $normal_btn = escape($normal_btn);
			else $normal_btn = '';
			
			$hover_btn = strval($node->hover_btn);
			if($hover_btn && strncasecmp('./files/attach/menu_button', $hover_btn, 26) === 0) $hover_btn = escape($hover_btn);
			else $hover_btn = '';
			
			$active_btn = strval($node->active_btn);
			if($active_btn && strncasecmp('./files/attach/menu_button', $active_btn, 26) === 0) $active_btn = escape($active_btn);
			else $active_btn = '';

			$group_srls = ($node->group_srls) ? $node->group_srls : '';

			if($normal_btn)
			{
				if($hover_btn) $hover_str = sprintf('onmouseover=&quot;this.src=\'%s\'&quot;', $hover_btn); else $hover_str = '';
				if($active_btn) $active_str = sprintf('onmousedown=&quot;this.src=\'%s\'&quot;', $active_btn); else $active_str = '';
				$link = sprintf('&lt;img src=&quot;%s&quot; onmouseout=&quot;this.src=\'%s\'&quot; alt=&quot;<?php print htmlspecialchars($_names[$lang_type], ENT_COMPAT | ENT_HTML401, \'UTF-8\', false) ?>&quot; %s %s /&gt;', $normal_btn, $normal_btn, $hover_str, $active_str);
			}
			else
			{
				$link = '<?php print $_names[$lang_type]; ?>';
			}

			// If the value of node->group_srls exists
			if($group_srls) {
				$group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s))))||($is_logged&&%s))',$group_srls,$group_srls == -1?1:0);
			}
			else
			{
				$group_check_code = "true";
			}

			$attribute = sprintf(
				'node_srl="%d" parent_srl="%d" menu_name_key=%s text="<?php if(%s) { %s }?>" url="<?php print(%s?%s:"")?>" href="<?php print(%s?%s:"")?>" is_shortcut=%s desc=%s open_window=%s expand=%s normal_btn=%s hover_btn=%s active_btn=%s link="<?php if(%s) {?>%s<?php }?>"',
				$menu_item_srl,
				($node->parent_srl) ? $node->parent_srl : '',
				var_export(($node->name) ? $node->name : '', true),
				$group_check_code,
				$name_str,
				$group_check_code,
				var_export($url, true),
				$group_check_code,
				$href,
				var_export($is_shortcut, true),
				var_export($desc, true),
				var_export($open_window, true),
				var_export($expand, true),
				var_export($normal_btn, true),
				var_export($hover_btn, true),
				var_export($active_btn, true),
				$group_check_code,
				$link
			);

			if($child_buff)
			{
				$buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
			}
			else
			{
				$buff .=  sprintf('<node %s />', $attribute);
			}
		}
		return $buff;
	}

	/**
	 * Return php code converted from nodes in an array
	 * Although xml data can be used for tpl, menu to menu, it needs to use javascript separately
	 * By creating cache file in php and then you can get menu information without DB
	 * This cache includes in ModuleHandler::displayContent() and then Context::set()
	 * @param array $source_node
	 * @param array $tree
	 * @param int $site_srl
	 * @param string $domain
	 * @return array
	 */
	function getPhpCacheCode($source_node, $tree, $site_srl, $domain)
	{
		$output = array('buff' => '', 'url_list' => array(), 'name' => null);
		if(!$source_node) return $output;

		$oMenuAdminModel = getAdminModel('menu');

		foreach($source_node as $menu_item_srl => $node)
		{
			// Get data from child nodes if exist.
			if($menu_item_srl && isset($tree[$menu_item_srl]))
			{
				$child_output = $this->getPhpCacheCode($tree[$menu_item_srl], $tree, $site_srl, $domain);
			}
			else
			{
				$child_output = array('buff' => '', 'url_list' => array(), 'name' => null);
			}

			// List variables
			$names = $oMenuAdminModel->getMenuItemNames($node->name, $site_srl);
			$name_arr_str = '';
			foreach($names as $key => $val)
			{
				if(preg_match('/^\{\$lang->menu_gnb(?:_sub)?\[\'([a-z0-9_]+)\'\]\}$/i', $val))
				{
					$name_arr_str .= sprintf('"%s"=>"%s",', $key, $val);
				}
				else
				{
					$name_arr_str .= sprintf('"%s"=>%s,', $key, var_export(Rhymix\Framework\Filters\HTMLFilter::clean($val, true), true));
				}
			}
			$name_str = sprintf('$_menu_names[%d] = array(%s); %s', $node->menu_item_srl, $name_arr_str, $child_output['name']);

			// If url value is not empty in the current node, put the value into an array url_list
			if($node->url) $child_output['url_list'][] = $node->url;
			$output['url_list'] = array_merge($output['url_list'], $child_output['url_list']);
			// If node->group_srls value exists
			if($node->group_srls)$group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s))))||($is_logged && %s))',$node->group_srls,$node->group_srls == -1?1:0);
			else $group_check_code = "true";

			// List variables
			$href = escape($node->href ?? '', false);
			$url = escape($node->url ?? '', false);
			$desc = escape($node->desc ?? '', false);
			if(preg_match('/^([0-9a-zA-Z\_\-]+)$/i', $node->url))
			{
				$href = "getSiteUrl('$domain', '','mid','$node->url')";
			}
			else
			{
				$href = var_export($url, true);
			}
			$is_shortcut = $node->is_shortcut;
			$open_window = $node->open_window;
			$normal_btn = escape($node->normal_btn);
			$hover_btn = escape($node->hover_btn);
			$active_btn = escape($node->active_btn);

			foreach($child_output['url_list'] as $key =>$val)
			{
				$child_output['url_list'][$key] = addslashes($val);
			}

			$selected = '"'.implode('","',$child_output['url_list']).'"';
			$child_buff = $child_output['buff'];
			$expand = $node->expand;

			$normal_btn = $node->normal_btn;
			if($normal_btn && strncasecmp('./files/attach/menu_button', $normal_btn, 26) === 0) $normal_btn = escape($normal_btn);
			else $normal_btn = '';

			$hover_btn = $node->hover_btn;
			if($hover_btn && strncasecmp('./files/attach/menu_button', $hover_btn, 26) === 0) $hover_btn = escape($hover_btn);
			else $hover_btn = '';

			$active_btn = $node->active_btn;
			if($active_btn && strncasecmp('./files/attach/menu_button', $active_btn, 26) === 0) $active_btn = escape($active_btn);
			else $active_btn = '';

			$group_srls = $node->group_srls;

			if($normal_btn)
			{
				if($hover_btn) $hover_str = sprintf('onmouseover=\"this.src=\'%s\'\"', $hover_btn); else $hover_str = '';
				if($active_btn) $active_str = sprintf('onmousedown=\"this.src=\'%s\'\"', $active_btn); else $active_str = '';
				$link = sprintf('"<img src=\"%s\" onmouseout=\"this.src=\'%s\'\" alt=\"".$_menu_names[%d][$lang_type]."\" %s %s />"', $normal_btn, $normal_btn, $node->menu_item_srl, $hover_str, $active_str);
				if($active_btn) $link_active = sprintf('"<img src=\"%s\" onmouseout=\"this.src=\'%s\'\" alt=\"".$_menu_names[%d][$lang_type]."\" %s />"', $active_btn, $active_btn, $node->menu_item_srl, $hover_str);
				else $link_active = $link;
			}
			else
			{
				$link_active = $link = sprintf('$_menu_names[%d][$lang_type]', $node->menu_item_srl);
			}
			// Create properties (check if it belongs to the menu node by url_list. It looks a trick but fast and powerful)
			$attribute = sprintf('
				"node_srl" => %d,
				"parent_srl" => %d,
				"menu_name_key" => %s,
				"isShow" => (%s ? true : false),
				"text" => (%s ? $_menu_names[%d][$lang_type] : ""),
				"href" => (%s ? %s : ""),
				"url" => (%s ? %s : ""),
				"is_shortcut" => %s,
				"desc" => %s,
				"open_window" => %s,
				"normal_btn" => %s,
				"hover_btn" => %s,
				"active_btn" => %s,
				"selected" => (array(%s) && in_array(Context::get("mid"), array(%s)) ? 1 : 0),
				"expand" => %s,
				"list" => array(%s),
				"link" => (%s ? (array(%s) && in_array(Context::get("mid"), array(%s)) ? %s : %s) : "")' . PHP_EOL,
				$node->menu_item_srl,
				$node->parent_srl,
				var_export(strip_tags($node->name), true),
				$group_check_code,
				$group_check_code,
				$node->menu_item_srl,
				$group_check_code,
				$href,
				$group_check_code,
				var_export($url, true),
				var_export($is_shortcut, true),
				var_export($desc, true),
				var_export($open_window, true),
				var_export($normal_btn, true),
				var_export($hover_btn, true),
				var_export($active_btn, true),
				$selected,
				$selected,
				var_export($expand, true),
				$child_buff,
				$group_check_code,
				$selected,
				$selected,
				$link_active,
				$link
			);

			// Generate buff data
			$output['buff'] .= sprintf('%d => array(%s),', $node->menu_item_srl, $attribute) . PHP_EOL;
			$output['name'] .= $name_str;
		}
		return $output;
	}

	/**
	 * Mapping menu and layout
	 * When setting menu on the layout, map the default layout
	 * @param int $layout_srl
	 * @param array $menu_srl_list
	 */
	function updateMenuLayout($layout_srl, $menu_srl_list)
	{
		if(!count($menu_srl_list)) return;
		// Delete the value of menu_srls
		$args = new stdClass();
		$args->menu_srls = implode(',',$menu_srl_list);
		$output = executeQuery('menu.deleteMenuLayout', $args);
		if(!$output->toBool()) return $output;

		$args->layout_srl = $layout_srl;
		// Mapping menu_srls, layout_srl
		for($i=0;$i<count($menu_srl_list);$i++)
		{
			$args->menu_srl = $menu_srl_list[$i];
			$output = executeQuery('menu.insertMenuLayout', $args);
			if(!$output->toBool()) return $output;
		}
	}

	/**
	 * Register a menu image button
	 * @param object $args
	 * @return array
	 */
	function _uploadButton($args)
	{
		// path setting
		$path = sprintf('./files/attach/menu_button/%d/', $args->menu_srl);
		if($args->menu_normal_btn || $args->menu_hover_btn || $args->menu_active_btn && !is_dir($path))
		{
			FileHandler::makeDir($path);
		}

		if($args->isNormalDelete == 'Y' || $args->isHoverDelete == 'Y' || $args->isActiveDelete == 'Y')
		{
			$oMenuModel = getAdminModel('menu');
			$itemInfo = $oMenuModel->getMenuItemInfo($args->menu_item_srl);

			if($args->isNormalDelete == 'Y' && $itemInfo->normal_btn) FileHandler::removeFile($itemInfo->normal_btn);
			if($args->isHoverDelete == 'Y' && $itemInfo->hover_btn) FileHandler::removeFile($itemInfo->hover_btn);
			if($args->isActiveDelete == 'Y' && $itemInfo->active_btn) FileHandler::removeFile($itemInfo->active_btn);
		}

		$returnArray = array();
		$date = date('YmdHis');

		// normal button
		if($args->menu_normal_btn)
		{
			$tmp_arr = explode('.',$args->menu_normal_btn['name']);
			$ext = $tmp_arr[count($tmp_arr)-1];
			$filename = sprintf('%s%d.%s.%s.%s', $path, $args->menu_item_srl, $date, 'menu_normal_btn', $ext);
			move_uploaded_file($args->menu_normal_btn['tmp_name'], $filename);
			$returnArray['normal_btn'] = $filename;
		}

		// hover button
		if($args->menu_hover_btn)
		{
			$tmp_arr = explode('.',$args->menu_hover_btn['name']);
			$ext = $tmp_arr[count($tmp_arr)-1];
			$filename = sprintf('%s%d.%s.%s.%s', $path, $args->menu_item_srl, $date, 'menu_hover_btn', $ext);
			move_uploaded_file($args->menu_hover_btn['tmp_name'], $filename);
			$returnArray['hover_btn'] = $filename;
		}

		// active button
		if($args->menu_active_btn)
		{
			$tmp_arr = explode('.',$args->menu_active_btn['name']);
			$ext = $tmp_arr[count($tmp_arr)-1];
			$filename = sprintf('%s%d.%s.%s.%s', $path, $args->menu_item_srl, $date, 'menu_active_btn', $ext);
			move_uploaded_file($args->menu_active_btn['tmp_name'], $filename);
			$returnArray['active_btn'] = $filename;
		}
		return $returnArray;
	}

	/**
	 * When copy a menu, button copied also.
	 * @param $args menuItemInfo with button values
	 */
	private function _copyButton($insertedMenuItemSrl, $insertedMenuSrl, &$menuItemInfo)
	{
		$copied_info = array(
			"normal_btn"=>"",
			"hover_btn"=>"",
			"active_btn"=>"",
		);
		//normal_btn
		if($menuItemInfo->normal_btn)
		{
			$originFile = FileHandler::getRealPath($menuItemInfo->normal_btn);
			$targetFile = $this->_changeMenuItemSrlInButtonPath($menuItemInfo->normal_btn, $insertedMenuSrl, $insertedMenuItemSrl, 'normal');

			FileHandler::copyFile($originFile, $targetFile);
			$copied_info['normal_btn'] = $targetFile;
		}

		//hover_btn
		if($menuItemInfo->hover_btn)
		{
			$originFile = FileHandler::getRealPath($menuItemInfo->hover_btn);
			$targetFile = $this->_changeMenuItemSrlInButtonPath($menuItemInfo->hover_btn, $insertedMenuSrl, $insertedMenuItemSrl, 'hover');

			FileHandler::copyFile($originFile, $targetFile);
			$copied_info['hover_btn'] = $targetFile;
		}

		//active_btn
		if($menuItemInfo->active_btn)
		{
			$originFile = FileHandler::getRealPath($menuItemInfo->active_btn);
			$targetFile = $this->_changeMenuItemSrlInButtonPath($menuItemInfo->active_btn, $insertedMenuSrl, $insertedMenuItemSrl, 'active');

			FileHandler::copyFile($originFile, $targetFile);
			$copied_info['active_btn'] = $targetFile;
		}
		return $copied_info;
	}

	private function _changeMenuItemSrlInButtonPath($buttonPath, $menuSrl, $menuItemSrl, $mode)
	{
		$path = sprintf('./files/attach/menu_button/%d/', $menuSrl);
		$tmp_arr = explode('.', $buttonPath);
		$ext = $tmp_arr[count($tmp_arr)-1];
		$date = date("YmdHis");
		return sprintf('%s%d.%s.%s.%s', $path, $menuItemSrl,$date,'menu_'.$mode.'_btn', $ext);
	}

	public function makeHomemenuCacheFile($menuSrl)
	{
		$cacheBuff .= sprintf('<?php if(!defined("__XE__")) exit();');
		$cacheBuff .= sprintf('$homeMenuSrl = %d;', $menuSrl);

		FileHandler::writeFile($this->homeMenuCacheFile, $cacheBuff);
	}

	public function getHomeMenuCacheFile()
	{
		return $this->homeMenuCacheFile;
	}
}
/* End of file menu.admin.controller.php */
/* Location: ./modules/menu/menu.admin.controller.php */
