<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Admin view class in the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class autoinstallAdminView extends autoinstall
{

	/**
	 * Category list
	 * @var array
	 */
	var $categories;

	/**
	 * Is set a ftp information
	 * @var bool
	 */
	var $ftp_set = FALSE;

	/**
	 * initialize
	 *
	 * @return void
	 */
	function init()
	{
		$oAdminModel = getAdminModel('autoinstall');
		$config = $oAdminModel->getAutoInstallAdminModuleConfig();
		Context::set('config', $config);
		
		$template_path = sprintf("%stpl/", $this->module_path);
		Context::set('original_site', $config->location_site);
		Context::set('uri', $config->download_server);
		$this->setTemplatePath($template_path);

		$ftp_info = Context::getFTPInfo();
		if(!$ftp_info->ftp_root_path)
		{
			Context::set('show_ftp_note', TRUE);
		}
		else
		{
			$this->ftp_set = TRUE;
		}

		$this->dispCategory();
		$oModel = getModel('autoinstall');
		Context::set('tCount', $oModel->getPackageCount(NULL));
		Context::set('iCount', $oModel->getInstalledPackageCount());
	}

	/**
	 * Rearrange one item
	 *
	 * <pre>
	 * $item:
	 * stdClass Object
	 * (
	 * 	[category_srl] => stdClass Object
	 * 		(
	 * 			[body] => xxx
	 * 		)
	 * 	[package_srl] => stdClass Object
	 * 		(
	 * 			[body] => xxx
	 * 		)
	 * 	...
	 * 	[depfrom] => stdClass Object
	 * 		(
	 * 			[body] => xxx
	 * 		)
	 * )
	 *
	 * $targets:
	 * array('category_srl', 'package_srl', 'item_screenshot_url', 'package_voted', 'package_voter', 'package_description', 'package_downloaded', 'item_regdate', 'title', 'item_version', 'package_star', 'depfrom');
	 *
	 * returns:
	 * stdClass Object
	 * (
	 * 	[category_srl] => xxx
	 * 	[package_srl] => xxx
	 * 	...
	 * 	[depfrom] => xxx
	 * )
	 * </pre>
	 *
	 * @param object $item
	 * @param object $targets
	 * @return object
	 */
	function rearrange(&$item, &$targets)
	{
		$ret = new stdClass();
		foreach($targets as $target)
		{
			$ret->{$target} = $item->{$target}->body;
		}
		return $ret;
	}

	/**
	 * Rearrage all item
	 *
	 * <pre>
	 * $items:
	 * Array
	 * (
	 * 	[0] => stdClass Object
	 * 		(
	 * 			[category_srl] => stdClass Object
	 * 				(
	 * 					[body] => xxx
	 * 				)
	 * 			[package_srl] => stdClass Object
	 * 				(
	 * 					[body] => xxx
	 * 				)
	 * 			...
	 * 			[depfrom] => stdClass Object
	 * 				(
	 * 					[body] => xxx
	 * 				)
	 * 		)
	 * 	[1] => stdClass Object
	 * 		(
	 * 			...
	 * 		)
	 * 	...
	 * )
	 *
	 * $packages:
	 * Array
	 * (
	 * 	[<i>package_srl</i>] => stdClass Object
	 * 		(
	 * 			[current_version] => xxx
	 * 			[need_update] => xxx
	 * 			[path] => xxx
	 * 			...
	 * 		)
	 * 	...
	 * )
	 *
	 * return:
	 * Array
	 * (
	 * 	[<i>package_srl</i>] => stdClass Object
	 * 		(
	 * 			[category_srl] => xxx
	 * 			[package_srl] => xxx
	 * 			...
	 * 			[category] => xxx
	 * 			[current_version] => xxx
	 * 			[type] => xxx
	 * 			[need_update] => xxx
	 * 			[avail_remove] => xxx
	 * 			[deps] => Array
	 * 				(
	 * 					[0] => xxx
	 * 					...
	 * 				)
	 * 		)
	 * 	...
	 * )
	 * </pre>
	 *
	 * @param object $items Recived data from server
	 * @param object $packages Local data
	 * @return object
	 */
	function rearranges($items, $packages = null)
	{
		if(!is_array($items))
		{
			$items = array($items);
		}

		$item_list = array();
		$targets = array('category_srl', 'package_srl', 'item_screenshot_url', 'package_voted', 'package_voter', 'package_description', 'package_downloaded', 'item_regdate', 'title', 'item_version', 'package_star', 'depfrom');
		$targetpackages = array();

		foreach($items as $item)
		{
			$targetpackages[$item->package_srl->body] = 0;
		}

		$oModel = getModel('autoinstall');
		
		if($package == null)
		{
			$packages = $oModel->getInstalledPackages(array_keys($targetpackages));
		}

		$depto = array();

		$oAdminModel = getAdminModel('autoinstall');
		$config = $oAdminModel->getAutoInstallAdminModuleConfig();

		foreach($items as $item)
		{
			$v = $this->rearrange($item, $targets);
			$v->item_screenshot_url = str_replace('./', $config->download_server, $v->item_screenshot_url);
			$v->category = $this->categories[$v->category_srl]->title;
			$v->url = $config->location_site . '?mid=download&package_srl=' . $v->package_srl;

			if($packages[$v->package_srl])
			{
				$v->current_version = $packages[$v->package_srl]->current_version;
				// if version is up
				// insert Y
				if($v->current_version === 'RX_VERSION')
				{
					$v->need_update = 'N';
				}
				elseif(version_compare($v->item_version, $v->current_version, '>'))
				{
					$v->need_update = 'Y';
				}
				else
				{
					$v->need_update = 'N';
				}
				//$v->need_update = $packages[$v->package_srl]->need_update;
				$v->type = $oModel->getTypeFromPath($packages[$v->package_srl]->path);

				if($this->ftp_set && $v->depfrom)
				{
					$depfrom = explode(",", $v->depfrom);
					foreach($depfrom as $package_srl)
					{
						$depto[$package_srl][] = $v->package_srl;
					}
				}

				if($v->type == "core")
				{
					continue;
				}
				else if($v->type == "module")
				{
					$v->avail_remove = $oModel->checkRemovable($packages[$v->package_srl]->path);
				}
				else
				{
					$v->avail_remove = TRUE;
				}
			}
			$item_list[$v->package_srl] = $v;
		}

		if(count($depto) > 0)
		{
			$installed = $oModel->getInstalledPackages(implode(",", array_keys($depto)));
			foreach($installed as $key => $val)
			{
				$path = $val->path;
				$type = $oModel->getTypeFromPath($path);

				if(!$type || $type == "core")
				{
					continue;
				}

				$config_file = $oModel->getConfigFilePath($type);
				if(!$config_file)
				{
					continue;
				}

				$xml = new XeXmlParser();
				$xmlDoc = $xml->loadXmlFile(FileHandler::getRealPath($path) . $config_file);
				if(!$xmlDoc)
				{
					continue;
				}

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
				$installed[$key]->title = $title;
			}

			Context::set('installed', $installed);
			$oSecurity = new Security();
			$oSecurity->encodeHTML('installed..');

			foreach($installed as $key => $val)
			{
				foreach($depto[$key] as $package_srl)
				{
					$item_list[$package_srl]->avail_remove = false;
					$item_list[$package_srl]->deps[] = $key;
				}
			}
		}

		return $item_list;
	}

	/**
	 * Display installed packages
	 *
	 * @return Object
	 */
	function dispAutoinstallAdminInstalledPackages()
	{
		$page = Context::get('page');
		if(!$page)
		{
			$page = 1;
		}
		Context::set('page', $page);

		$oModel = getModel('autoinstall');
		$output = $oModel->getInstalledPackageList($page);
		$package_list = $output->data;

		$params["act"] = "getResourceapiPackages";
		$params["package_srls"] = implode(",", array_keys($package_list));
		$body = XmlGenerater::generate($params);
		$request_config = array(
			'ssl_verify_peer' => FALSE,
			'ssl_verify_host' => FALSE
		);

		$oAdminModel = getAdminModel('autoinstall');
		$config = $oAdminModel->getAutoInstallAdminModuleConfig();

		$buff = FileHandler::getRemoteResource($config->download_server, $body, 3, "POST", "application/xml", array(), array(), array(), $request_config);
		$xml_lUpdate = new XeXmlParser();
		$xmlDoc = $xml_lUpdate->parse($buff);
		if($xmlDoc && $xmlDoc->response->packagelist->item)
		{
			$item_list = $this->rearranges($xmlDoc->response->packagelist->item, $package_list);
			$res = array();
			foreach($package_list as $package_srl => $package)
			{
				if($item_list[$package_srl])
				{
					$res[] = $item_list[$package_srl];
				}
			}
			Context::set('item_list', $res);
		}

		if(count($package_list) != count($res))
		{
			$localPackageSrls = array_keys($package_list);
			$remotePackageSrls = array_keys($item_list);
			$targetPackageSrls = array_diff($localPackageSrls, $remotePackageSrls);
			$countDiff = count($targetPackageSrls);
			$output->page_navigation->total_count -= $countDiff;
		}

		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('index');

		$security = new Security();
		$security->encodeHTML('item_list..');
	}

	/**
	 * Display install package
	 *
	 * @return Object
	 */
	function dispAutoinstallAdminInstall()
	{
		$package_srl = Context::get('package_srl');
		if(!$package_srl)
		{
			return $this->dispAutoinstallAdminIndex();
		}

		$oAdminModel = getAdminModel('autoinstall');
		$package = $oAdminModel->getInstallInfo($package_srl);

		Context::set("package", $package);
		Context::set('contain_core', $package->contain_core);
		Context::set('module_config', $oAdminModel->getAutoInstallAdminModuleConfig());

		if(!$_SESSION['ftp_password'])
		{
			Context::set('need_password', TRUE);
		}

		$output = $oAdminModel->checkUseDirectModuleInstall($package);
		if($output->toBool()==TRUE)
		{
			Context::set('show_ftp_note', FALSE);
		}
		Context::set('directModuleInstall', $output);

		$this->setTemplateFile('install');

		$security = new Security();
		$security->encodeHTML('package.', 'package.depends..');
	}

	/**
	 * Display package list
	 *
	 * @return Object
	 */
	function dispAutoinstallAdminIndex()
	{
		$ftp_info = Context::getFTPInfo();
		if(!$ftp_info->ftp_root_path)
		{
			Context::set('show_ftp_note', TRUE);
		}

		$this->setTemplateFile('index');

		$params = array();
		$params["act"] = "getResourceapiLastupdate";
		$body = XmlGenerater::generate($params);
		$request_config = array(
			'ssl_verify_peer' => FALSE,
			'ssl_verify_host' => FALSE
		);

		$oAdminModel = getAdminModel('autoinstall');
		$config = $oAdminModel->getAutoInstallAdminModuleConfig();

		$buff = FileHandler::getRemoteResource($config->download_server, $body, 3, "POST", "application/xml", array(), array(), array(), $request_config);
		$xml_lUpdate = new XeXmlParser();
		$lUpdateDoc = $xml_lUpdate->parse($buff);
		$updateDate = $lUpdateDoc->response->updatedate->body;

		if(!$updateDate)
		{
			Context::set('isNotUpdate', true);
			return;
		}

		$oModel = getModel('autoinstall');
		$item = $oModel->getLatestPackage();
		if(!$item || $item->updatedate < $updateDate || count($this->categories) < 1)
		{
			$oController = getAdminController('autoinstall');
			$oController->_updateinfo();

			if(!$_SESSION['__XE_EASYINSTALL_REDIRECT__'])
			{
				header('location: ' . getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminIndex'));
				$_SESSION['__XE_EASYINSTALL_REDIRECT__'] = TRUE;
				return;
			}
		}
		unset($_SESSION['__XE_EASYINSTALL_REDIRECT__']);

		$page = Context::get('page');
		if(!$page)
		{
			$page = 1;
		}
		Context::set('page', $page);

		$order_type = Context::get('order_type');
		if(!in_array($order_type, array('asc', 'desc')))
		{
			$order_type = 'desc';
		}
		Context::set('order_type', $order_type);

		$order_target = Context::get('order_target');
		if(!in_array($order_target, array('newest', 'download', 'popular')))
		{
			$order_target = 'newest';
		}
		Context::set('order_target', $order_target);

		$search_keyword = Context::get('search_keyword');

		$childrenList = Context::get('childrenList');
		$category_srl = Context::get('category_srl');
		if($childrenList)
		{
			$params["category_srl"] = $childrenList;
		}
		else if($category_srl)
		{
			$params["category_srl"] = $category_srl;
		}

		$params["act"] = "getResourceapiPackagelist";
		$params["order_target"] = $order_target;
		$params["order_type"] = $order_type;
		$params["page"] = $page;
		if($search_keyword)
		{
			$params["search_keyword"] = $search_keyword;
		}
		$xmlDoc = XmlGenerater::getXmlDoc($params);
		if($xmlDoc && $xmlDoc->response->packagelist->item)
		{
			$item_list = $this->rearranges($xmlDoc->response->packagelist->item);
			Context::set('item_list', $item_list);
			$array = array('total_count', 'total_page', 'cur_page', 'page_count', 'first_page', 'last_page');
			$page_nav = $this->rearrange($xmlDoc->response->page_navigation, $array);
			$page_navigation = new PageHandler($page_nav->total_count, $page_nav->total_page, $page_nav->cur_page, 5);
			Context::set('page_navigation', $page_navigation);
		}

		$security = new Security();
		$security->encodeHTML('package.', 'package.depends..', 'item_list..');
		$security->encodeHTML('search_target', 'search_keyword', 'order_target', 'order_type');
	}

	/**
	 * Display category
	 *
	 * @return void
	 */
	function dispCategory()
	{
		$oModel = getModel('autoinstall');
		$this->categories = $oModel->getCategoryList();
		Context::set('categories', $this->categories);
	}

	/**
	 * Display uninstall package
	 *
	 * @return Object
	 */
	function dispAutoinstallAdminUninstall()
	{
		$package_srl = Context::get('package_srl');
		if(!$package_srl)
		{
			return $this->dispAutoinstallAdminIndex();
		}

		$oModel = getModel('autoinstall');
		$oAdminModel = getAdminModel('autoinstall');
		$installedPackage = $oModel->getInstalledPackage($package_srl);
		if(!$installedPackage)
		{
			return $this->dispAutoinstallAdminInstalledPackages();
		}

		if(!$_SESSION['ftp_password'])
		{
			Context::set('need_password', TRUE);
		}

		$installedPackage = $oModel->getPackage($package_srl);
		$path = $installedPackage->path;
		$type = $oModel->getTypeFromPath($path);

		if(!$type || $type == "core")
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$config_file = $oModel->getConfigFilePath($type);
		if(!$config_file)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$output = $oAdminModel->checkUseDirectModuleInstall($installedPackage);
		if($output->toBool()==TRUE)
		{
			Context::set('show_ftp_note', FALSE);
		}
		Context::set('directModuleInstall', $output);

		$params["act"] = "getResourceapiPackages";
		$params["package_srls"] = $package_srl;
		$body = XmlGenerater::generate($params);
		$request_config = array(
			'ssl_verify_peer' => FALSE,
			'ssl_verify_host' => FALSE
		);

		$oAdminModel = getAdminModel('autoinstall');
		$config = $oAdminModel->getAutoInstallAdminModuleConfig();

		$buff = FileHandler::getRemoteResource($config->download_server, $body, 3, "POST", "application/xml", array(), array(), array(), $request_config);
		$xml_lUpdate = new XeXmlParser();
		$xmlDoc = $xml_lUpdate->parse($buff);
		if($xmlDoc && $xmlDoc->response->packagelist->item)
		{
			$item_list = $this->rearranges($xmlDoc->response->packagelist->item);
			$installedPackage->title = $item_list[$package_srl]->title;
			$installedPackage->type = $item_list[$package_srl]->category;
			$installedPackage->avail_remove = $item_list[$package_srl]->avail_remove;
			$installedPackage->deps = $item_list[$package_srl]->deps;
			Context::set('package', $installedPackage);
			$this->setTemplateFile('uninstall');
			Context::addJsFilter($this->module_path . 'tpl/filter', 'uninstall_package.xml');

			$security = new Security();
			$security->encodeHTML('package.');

			$this->setTemplateFile('uninstall');
		}
		else
		{
			throw new Rhymix\Framework\Exception('msg_connection_fail');
		}
	}

	/**
	 * Display config
	 * 
	 */
	function dispAutoinstallAdminConfig()
	{
		$this->setTemplateFile('config');
	}
}
/* End of file autoinstall.admin.view.php */
/* Location: ./modules/autoinstall/autoinstall.admin.view.php */
