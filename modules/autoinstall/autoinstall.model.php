<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Model class of the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class autoinstallModel extends autoinstall
{

	/**
	 * Get category information
	 *
	 * @param int $category_srl The sequence of category to get information
	 * @return object
	 */
	function getCategory($category_srl)
	{
		$args = new stdClass();
		$args->category_srl = $category_srl;
		$output = executeQueryArray("autoinstall.getCategory", $args);
		if(!$output->data)
		{
			return null;
		}
		return array_shift($output->data);
	}

	/**
	 * Get packages information
	 *
	 * @return array
	 */
	function getPackages()
	{
		$output = executeQueryArray("autoinstall.getPackages");
		if(!$output->data)
		{
			return array();
		}
		return $output->data;
	}

	/**
	 * Get installed packages information
	 *
	 * @param int $package_srl The sequence of package to get information
	 * @return object
	 */
	function getInstalledPackage($package_srl)
	{
		$args = new stdClass();
		$args->package_srl = $package_srl;
		$output = executeQueryArray("autoinstall.getInstalledPackage", $args);
		if(!$output->data)
		{
			return null;
		}
		return array_shift($output->data);
	}

	/**
	 * Get one package information
	 *
	 * @param int $package_srl The sequence of package to get information
	 * @return object
	 */
	function getPackage($package_srl)
	{
		$args = new stdClass();
		$args->package_srl = $package_srl;
		$output = executeQueryArray("autoinstall.getPackage", $args);
		if(!$output->data)
		{
			return null;
		}
		return array_shift($output->data);
	}

	/**
	 * Get category list
	 *
	 * @return array
	 */
	function getCategoryList()
	{
		$output = executeQueryArray("autoinstall.getCategories");
		if(!$output->toBool() || !$output->data)
		{
			return array();
		}

		$categoryList = array();
		foreach($output->data as $category)
		{
			$category->children = array();
			$categoryList[$category->category_srl] = $category;
		}

		$depth0 = array();
		foreach($categoryList as $key => $category)
		{
			if($category->parent_srl)
			{
				$categoryList[$category->parent_srl]->children[] = & $categoryList[$key];
			}
			else
			{
				$depth0[] = $key;
			}
		}
		$resultList = array();
		foreach($depth0 as $category_srl)
		{
			$this->setDepth($categoryList[$category_srl], 0, $categoryList, $resultList);
		}
		return $resultList;
	}

	/**
	 * Get pcakge count in category
	 *
	 * @param int $category_srl The sequence of category to get count
	 * @return int
	 */
	function getPackageCount($category_srl)
	{
		$args = new stdClass();
		$args->category_srl = $category_srl;
		$output = executeQuery("autoinstall.getPackageCount", $args);
		if(!$output->data)
		{
			return 0;
		}
		return $output->data->count;
	}

	/**
	 * Get installed package count
	 *
	 * @return int
	 */
	function getInstalledPackageCount()
	{
		$output = executeQuery("autoinstall.getInstalledPackageCount");
		if(!$output->data)
		{
			return 0;
		}
		return $output->data->count;
	}

	/**
	 * Set depth, children list and package count of category
	 *
	 * @param object $item Category information
	 * @param int $depth Depth of category
	 * @param array $list Category list
	 * @param array $resultList Final result list
	 * @return string $siblingList Comma seperated list
	 */
	function setDepth(&$item, $depth, &$list, &$resultList)
	{
		$resultList[$item->category_srl] = &$item;
		$item->depth = $depth;
		$siblingList = $item->category_srl;
		foreach($item->children as $child)
		{
			$siblingList .= "," . $this->setDepth($list[$child->category_srl], $depth + 1, $list, $resultList);
		}
		if(count($item->children) < 1)
		{
			$item->nPackages = $this->getPackageCount($item->category_srl);
		}
		$item->childrenList = $siblingList;
		return $siblingList;
	}

	/**
	 * Get lastest package information
	 *
	 * @return object Returns lastest package information. If no result returns null.
	 */
	function getLatestPackage()
	{
		$output = executeQueryArray("autoinstall.getLatestPackage");
		if(!$output->data)
		{
			return null;
		}
		return array_shift($output->data);
	}

	/**
	 * Get installed package informations
	 *
	 * @param array $package_list Package sequence list to get information
	 * @return array Returns array contains pacakge information. If no result returns empty array.
	 */
	function getInstalledPackages($package_list)
	{
		$args = new stdClass();
		$args->package_list = $package_list;
		$output = executeQueryArray("autoinstall.getInstalledPackages", $args);
		$result = array();
		if(!$output->data)
		{
			return $result;
		}
		foreach($output->data as $value)
		{
			$result[$value->package_srl] = $value;
		}
		return $result;
	}

	/**
	 * Get installed package list
	 *
	 * @param int $page
	 * @return Object
	 */
	function getInstalledPackageList($page)
	{
		$args = new stdClass();
		$args->page = $page;
		$args->list_count = 10;
		$args->page_count = 5;
		if(Context::getDBType() == 'mssql')
		{
			$args->sort_index = 'package_srl';
		}
		$output = executeQueryArray("autoinstall.getInstalledPackageList", $args);
		$res = array();
		if($output->data)
		{
			foreach($output->data as $val)
			{
				$res[$val->package_srl] = $val;
			}
		}
		$output->data = $res;
		return $output;
	}

	/**
	 * Get type using path
	 *
	 * @param string $path Path to get type
	 * @return string
	 */
	function getTypeFromPath($path)
	{
		if(!$path)
		{
			return NULL;
		}

		if($path == ".")
		{
			return "core";
		}

		$path_array = explode("/", $path);
		$target_name = array_pop($path_array);
		if(!$target_name)
		{
			$target_name = array_pop($path_array);
		}
		$type = substr(array_pop($path_array), 0, -1);
		return $type;
	}

	/**
	 * Get config file path by type
	 *
	 * @param string $type Type to get config file path
	 * @return string
	 */
	function getConfigFilePath($type)
	{
		$config_file = NULL;
		switch($type)
		{
			case "m.layout":
			case "module":
			case "addon":
			case "layout":
			case "widget":
			case 'theme': // for backward compatibility
				$config_file = "/conf/info.xml";
				break;
			case "component":
				$config_file = "/info.xml";
				break;
			case "m.skin":
			case "skin":
			case "widgetstyle":
			case "style":
				$config_file = "/skin.xml";
				break;
			case "drcomponent":
				$config_file = "/info.xml";
				break;
		}
		return $config_file;
	}

	/**
	 * Returns target is removable
	 *
	 * @param string $path Path
	 * @return bool
	 */
	function checkRemovable($path)
	{
		$path_array = explode("/", $path);
		$target_name = array_pop($path_array);
		$oModule = ModuleModel::getModuleInstallClass($target_name);
		if(!$oModule)
		{
			return FALSE;
		}
		if(method_exists($oModule, "moduleUninstall"))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get sequence of package by path
	 *
	 * @param string $path Path to get sequence
	 * @return int
	 */
	function getPackageSrlByPath($path)
	{
		if(!$path)
		{
			return;
		}

		if(substr($path, -1) == '/')
		{
			$path = substr($path, 0, strlen($path) - 1);
		}

		if(!$GLOBALS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path])
		{
			$args = new stdClass();
			$args->path = $path;
			$output = executeQuery('autoinstall.getPackageSrlByPath', $args);

			$GLOBALS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path] = $output->data->package_srl;
		}

		return $GLOBALS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path];
	}

	/**
	 * Get remove url by package srl
	 *
	 * @param int $packageSrl Sequence of pakcage to get url
	 * @return string
	 */
	function getRemoveUrlByPackageSrl($packageSrl)
	{
		$ftp_info = Context::getFTPInfo();
		if(!$ftp_info->ftp_root_path)
		{
			return;
		}

		if(!$packageSrl)
		{
			return;
		}

		return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminUninstall', 'package_srl', $packageSrl);
	}

	/**
	 * Get remove url by path
	 *
	 * @param string $path Path to get url
	 * @return string
	 */
	function getRemoveUrlByPath($path)
	{
		if(!$path)
		{
			return;
		}

		$ftp_info = Context::getFTPInfo();
		if(!$ftp_info->ftp_root_path)
		{
			return;
		}

		$packageSrl = $this->getPackageSrlByPath($path);
		if(!$packageSrl)
		{
			return;
		}

		return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminUninstall', 'package_srl', $packageSrl);
	}

	/**
	 * Get update url by package srl
	 *
	 * @param int $packageSrl Sequence to get url
	 * @return string
	 */
	function getUpdateUrlByPackageSrl($packageSrl)
	{
		if(!$packageSrl)
		{
			return;
		}

		return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminInstall', 'package_srl', $packageSrl);
	}

	/**
	 * Get update url by path
	 *
	 * @param string $path Path to get url
	 * @return string
	 */
	function getUpdateUrlByPath($path)
	{
		if(!$path)
		{
			return;
		}

		$packageSrl = $this->getPackageSrlByPath($path);
		if(!$packageSrl)
		{
			return;
		}

		return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminInstall', 'package_srl', $packageSrl);
	}

	function getHaveInstance($columnList = array())
	{
		$output = executeQueryArray('autoinstall.getHaveInstance', NULL, $columnList);
		if(!$output->data)
		{
			return array();
		}

		return $output->data;
	}

}
/* End of file autoinstall.model.php */
/* Location: ./modules/autoinstall/autoinstall.model.php */
