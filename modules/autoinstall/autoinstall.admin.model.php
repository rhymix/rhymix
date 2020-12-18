<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Model class of the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class autoinstallAdminModel extends autoinstall
{

	var $layout_category_srl = 18322954;
	var $mobile_layout_category_srl = 18994172;
	var $module_skin_category_srl = 18322943;
	var $module_mobile_skin_category_srl = 18994170;

	/**
	 * Pre process parameters
	 */
	function preProcParam(&$order_target, &$order_type, &$page)
	{
		$order_target_array = array('newest' => 1, 'download' => 1, 'popular' => 1);
		if(!isset($order_target_array[$order_target]))
		{
			$order_target = 'newest';
		}

		$order_type_array = array('asc' => 1, 'desc' => 1);
		if(!isset($order_type_array[$order_type]))
		{
			$order_type = 'desc';
		}

		$page = (int) $page;
		if($page < 1)
		{
			$page = 1;
		}
	}

	/**
	 * Return list of package that can have instance
	 */
	function getAutoinstallAdminMenuPackageList()
	{
		$search_keyword = Context::get('search_keyword');
		$order_target = Context::get('order_target');
		$order_type = Context::get('order_type');
		$page = Context::get('page');

		$this->preProcParam($order_target, $order_type, $page);
		$this->getPackageList('menu', $order_target, $order_type, $page, $search_keyword);
	}

	/**
	 * Return list of layout package
	 */
	function getAutoinstallAdminLayoutPackageList()
	{
		$search_keyword = Context::get('search_keyword');
		$order_target = Context::get('order_target');
		$order_type = Context::get('order_type');
		$page = Context::get('page');

		$type_array = array('M' => 1, 'P' => 1);
		$type = Context::get('type');
		if(!isset($type_array[$type]))
		{
			$type = 'P';
		}

		if($type == 'P')
		{
			$category_srl = $this->layout_category_srl;
		}
		else
		{
			$category_srl = $this->mobile_layout_category_srl;
		}

		$this->preProcParam($order_target, $order_type, $page);
		$this->getPackageList('layout', $order_target, $order_type, $page, $search_keyword, $category_srl);
	}

	/**
	 * Return list of module skin package
	 */
	function getAutoinstallAdminSkinPackageList()
	{
		Context::setRequestMethod('JSON');
		$search_keyword = Context::get('search_keyword');
		$order_target = Context::get('order_target');
		$order_type = Context::get('order_type');
		$page = Context::get('page');
		$parent_program = Context::get('parent_program');

		$type_array = array('M' => 1, 'P' => 1);
		$type = Context::get('type');
		if(!isset($type_array[$type]))
		{
			$type = 'P';
		}

		if($type == 'P')
		{
			$category_srl = $this->module_skin_category_srl;
		}
		else
		{
			$category_srl = $this->module_mobile_skin_category_srl;
		}

		$this->preProcParam($order_target, $order_type, $page);
		$this->getPackageList('skin', $order_target, $order_type, $page, $search_keyword, $category_srl, $parent_program);
	}

	/**
	 * Get Package List
	 */
	function getPackageList($type, $order_target = 'newest', $order_type = 'desc', $page = '1', $search_keyword = NULL, $category_srl = NULL, $parent_program = NULL)
	{
		if($type == 'menu')
		{
			$params["act"] = "getResourceapiMenuPackageList";
		}
		elseif($type == 'skin')
		{
			$params["act"] = "getResourceapiSkinPackageList";
			$params['parent_program'] = $parent_program;
		}
		else
		{
			$params["act"] = "getResourceapiPackagelist";
		}

		$oAdminView = getAdminView('autoinstall');
		$params["order_target"] = $order_target;
		$params["order_type"] = $order_type;
		$params["page"] = $page;

		if($category_srl)
		{
			$params["category_srl"] = $category_srl;
		}

		if($search_keyword)
		{
			$params["search_keyword"] = $search_keyword;
		}

		$xmlDoc = XmlGenerater::getXmlDoc($params);
		if($xmlDoc && $xmlDoc->response->packagelist->item)
		{
			$item_list = $oAdminView->rearranges($xmlDoc->response->packagelist->item);
			$this->add('item_list', $item_list);
			$array = array('total_count', 'total_page', 'cur_page', 'page_count', 'first_page', 'last_page');
			$page_nav = $oAdminView->rearrange($xmlDoc->response->page_navigation, $array);
			$page_navigation = new PageHandler($page_nav->total_count, $page_nav->total_page, $page_nav->cur_page, 5);
			$this->add('page_navigation', $page_navigation);
		}
	}

	/**
	 * Get is authed ftp
	 */
	function getAutoinstallAdminIsAuthed()
	{
		$oAdminModel = getAdminModel('autoinstall');
		$package = $oAdminModel->getInstallInfo(Context::get('package_srl'));
		
		$is_authed = 0;
		$output = $oAdminModel->checkUseDirectModuleInstall($package);
		if($output->toBool()==TRUE)
		{
			$is_authed = 1;
		}
		else
		{
			$ftp_info = Context::getFTPInfo();
			if(!$ftp_info->ftp_root_path)
			{
				$is_authed = -1;
			}
			else
			{
				$is_authed = (int) isset($_SESSION['ftp_password']);
			}
		}
		
		$this->add('is_authed', $is_authed);
	}

	/**
	 * Returns list of need update
	 */
	public function getNeedUpdateList()
	{
		$oModel = getModel('autoinstall');
		$output = executeQueryArray('autoinstall.getNeedUpdate');
		if(!is_array($output->data))
		{
			return NULL;
		}

		$result = array();
		$xml = new XeXmlParser();
		foreach($output->data as $package)
		{
			$packageSrl = $package->package_srl;

			$packageInfo = new stdClass();
			$packageInfo->currentVersion = $package->current_version;
			$packageInfo->version = $package->version;
			$packageInfo->type = $oModel->getTypeFromPath($package->path);
			$packageInfo->url = $oModel->getUpdateUrlByPackageSrl($package->package_srl);

			if($packageInfo->type == 'core')
			{
				continue;
			}
			else
			{
				$configFile = $oModel->getConfigFilePath($packageInfo->type);
				$xmlDoc = $xml->loadXmlFile(FileHandler::getRealPath($package->path) . $configFile);

				if($xmlDoc)
				{
					$type = $packageInfo->type;
					if($type == "drcomponent")
					{
						$type = "component";
					}
					if($type == "style" || $type == "m.skin")
					{
						$type = "skin";
					}
					if($type == "m.layout")
					{
						$type = "layout";
					}
					$title = $xmlDoc->{$type}->title->body;
				}
				else
				{
					$pathInfo = explode('/', $package->path);
					$title = $pathInfo[count($pathInfo) - 1];
				}
			}
			$packageInfo->title = $title;

			$result[] = $packageInfo;
		}

		return $result;
	}

	/**
	 * Get install info
	 *
	 * @param int $packageSrl Package sequence to get info
	 * @return stdClass install info
	 */
	public function getInstallInfo($packageSrl)
	{
		$params["act"] = "getResourceapiInstallInfo";
		$params["package_srl"] = $packageSrl;
		$xmlDoc = XmlGenerater::getXmlDoc($params);
		$oModel = getModel('autoinstall');

		$targetpackages = array();
		if($xmlDoc)
		{
			$xmlPackage = $xmlDoc->response->package;
			$package = new stdClass();
			$package->package_srl = $xmlPackage->package_srl->body;
			$package->title = $xmlPackage->title->body;
			$package->package_description = $xmlPackage->package_description->body;
			$package->version = $xmlPackage->version->body;
			$package->path = $xmlPackage->path->body;
			if($xmlPackage->depends)
			{
				if(!is_array($xmlPackage->depends->item))
				{
					$xmlPackage->depends->item = array($xmlPackage->depends->item);
				}

				$package->depends = array();
				foreach($xmlPackage->depends->item as $item)
				{
					$dep_item = new stdClass();
					$dep_item->package_srl = $item->package_srl->body;
					$dep_item->title = $item->title->body;
					$dep_item->version = $item->version->body;
					$dep_item->path = $item->path->body;
					$package->depends[] = $dep_item;
					$targetpackages[$dep_item->package_srl] = 1;
				}

				$packages = $oModel->getInstalledPackages(array_keys($targetpackages));
				$package->deplist = "";
				foreach($package->depends as $key => $dep)
				{
					if($dep->path === '.')
					{
						unset($package->depends[$key]);
						continue;
					}
					
					if(!$packages[$dep->package_srl])
					{
						$package->depends[$key]->installed = FALSE;
						$package->package_srl .= "," . $dep->package_srl;
					}
					else
					{
						$package->depends[$key]->installed = TRUE;
						$package->depends[$key]->cur_version = $packages[$dep->package_srl]->current_version;
						if($packages[$dep->package_srl]->current_version === 'RX_VERSION')
						{
							$package->need_update = FALSE;
						}
						elseif(version_compare($dep->version, $packages[$dep->package_srl]->current_version, ">"))
						{
							$package->depends[$key]->need_update = TRUE;
							$package->package_srl .= "," . $dep->package_srl;
						}
						else
						{
							$package->need_update = FALSE;
						}
					}
				}
			}

			$installedPackage = $oModel->getInstalledPackage($packageSrl);
			if($installedPackage)
			{
				$package->installed = TRUE;
				$package->cur_version = $installedPackage->current_version;
				$package->need_update = $installedPackage->current_version !== 'RX_VERSION' && version_compare($package->version, $installedPackage->current_version, ">");
			}

			if($package->path === '.')
			{
				$package->contain_core = TRUE;
				$package->contain_core_version = $package->version;
			}
		}

		return $package;
	}

	/**
	 * get install info (act)
	 */
	public function getAutoInstallAdminInstallInfo()
	{
		$packageSrl = Context::get('package_srl');
		if(!$packageSrl)
		{
			return $this->setError('msg_invalid_request');
		}

		$package = $this->getInstallInfo($packageSrl);
		$this->add('package', $package);
	}

	public function checkUseDirectModuleInstall($package)
	{
		$directModuleInstall = TRUE;
		$arrUnwritableDir = array();
		$output = $this->isWritableDir($package->path);
		if($output->toBool()==FALSE)
		{
			$directModuleInstall = FALSE;
			$arrUnwritableDir[] = $output->get('path');
		}
		
		if(!is_array($package->depends))
		{
			$package->depends = array();
		}

		foreach($package->depends as $dep)
		{
			$output = $this->isWritableDir($dep->path);
			if($output->toBool()==FALSE)
			{
				$directModuleInstall = FALSE;
				$arrUnwritableDir[] = $output->get('path');
			}
		}

		if($directModuleInstall==FALSE)
		{
			$output = new BaseObject(-1, 'msg_direct_inall_invalid');
			$output->add('path', $arrUnwritableDir);
			return $output;
		}

		return new BaseObject();
	}

	public function isWritableDir($path)
	{
		$path_list = explode('/', dirname($path));
		$real_path = './';

		while($path_list)
		{
			$check_path = realpath($real_path . implode('/', $path_list));
			if(FileHandler::isDir($check_path))
			{
				break;
			}
			array_pop($path_list);
		}

		if(FileHandler::isWritableDir($check_path)==FALSE)
		{
			$output = new BaseObject(-1, 'msg_unwritable_directory');
			$output->add('path', FileHandler::getRealPath($check_path));
			return $output;
		}
		return new BaseObject();
	}

	public function getAutoInstallAdminModuleConfig()
	{
		$oModuleModel = getModel('module');
		$config_info = $oModuleModel->getModuleConfig('autoinstall');
		$_location_site = 'https://xe1.xpressengine.com/';
		$_download_server = 'https://download.xpressengine.com/';

		$config = new stdClass();
		$config->location_site = $config_info->location_site ? $config_info->location_site : $_location_site;
		$config->download_server = $config_info->download_server ? $config_info->download_server : $_download_server;
		
		return $config;
	}

}
/* End of file autoinstall.admin.model.php */
/* Location: ./modules/autoinstall/autoinstall.admin.model.php */
