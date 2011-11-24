<?php
    /**
     * @class  autoinstallModel
     * @author NHN (developers@xpressengine.com)
     * @brief Model class of the autoinstall module
     **/

    class autoinstallModel extends autoinstall {

        function getCategory($category_srl)
        {
            $args->category_srl = $category_srl;
            $output = executeQueryArray("autoinstall.getCategory", $args);
            if(!$output->data) return null;
            return array_shift($output->data);
        }

        function getPackages()
        {
            $output = executeQueryArray("autoinstall.getPackages");
            if(!$output->data) return array();
            return $output->data;
        }

        function getInstalledPackage($package_srl)
        {
            $args->package_srl = $package_srl;
            $output = executeQueryArray("autoinstall.getInstalledPackage", $args);
            if(!$output->data) return null;
            return array_shift($output->data);
        }

        function getPackage($package_srl)
        {
            $args->package_srl = $package_srl;
            $output = executeQueryArray("autoinstall.getPackage", $args);
            if(!$output->data) return null;
            return array_shift($output->data);
        }

        function getCategoryList()
        {
            $output = executeQueryArray("autoinstall.getCategories");
            if(!$output->toBool() || !$output->data) return array();

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
                    $categoryList[$category->parent_srl]->children[] =& $categoryList[$key];
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

        function getPackageCount($category_srl)
        {
            $args->category_srl = $category_srl;
            $output = executeQuery("autoinstall.getPackageCount", $args);
            if(!$output->data) return 0;
            return $output->data->count;
        }

        function getInstalledPackageCount()
        {
            $output = executeQuery("autoinstall.getInstalledPackageCount", $args);
            if(!$output->data) return 0;
            return $output->data->count;
        }

        function setDepth(&$item, $depth, &$list, &$resultList)
        {
            $resultList[$item->category_srl] =& $item;
            $item->depth = $depth;
            $siblingList = $item->category_srl;
            foreach($item->children as $child)
            {
                $siblingList .= ",".$this->setDepth($list[$child->category_srl], $depth+1, $list, $resultList);
            }
            if(count($item->children) < 1)
            {
                $item->nPackages = $this->getPackageCount($item->category_srl);
            }
            $item->childrenList = $siblingList;
            return $siblingList;
        }

        function getLatestPackage() {
            $output = executeQueryArray("autoinstall.getLatestPackage");
            if(!$output->data) return null;
            return array_shift($output->data);
        }

        function getInstalledPackages($package_list) {
            $args->package_list = $package_list;
            $output = executeQueryArray("autoinstall.getInstalledPackages", $args);
            $result = array();
            if(!$output->data) return $result;
            foreach($output->data as $value)
            {
                $result[$value->package_srl] = $value;
            }
            return $result;
        }

        function getInstalledPackageList($page)
        {
            $args->page = $page;
			$args->list_count = 10;
			$args->page_count = 5;
            $output = executeQueryArray("autoinstall.getInstalledPackageList", $args);
            $res = array();
			if ($output->data)
			{
				foreach($output->data as $val)
				{
					$res[$val->package_srl] = $val;
				}
			}
            $output->data = $res;
            return $output;
        }

		function getTypeFromPath($path)
		{
			if(!$path) return null;
			if($path == ".") return "core";
			$path_array = explode("/", $path);
            $target_name = array_pop($path_array);
            $type = substr(array_pop($path_array), 0, -1);
			return $type;
		}

		function getConfigFilePath($type)
		{
			$config_file = null;
			switch($type)
			{
				case "m.layout":
				case "module":
					case "addon":
					case "layout":
					case "widget":
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

		function checkRemovable($path)
		{
			$path_array = explode("/", $path);
            $target_name = array_pop($path_array);
			$oModule =& getModule($target_name, "class");
			if(!$oModule) return false;
			if(method_exists($oModule, "moduleUninstall")) return true;
			else return false;
		}

		function getPackageSrlByPath($path)
		{
			if (!$path) return;

			if(substr($path,-1) == '/') $path = substr($path, 0, strlen($path)-1);

			if (!$GLOBLAS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path])
			{
				$args->path = $path;
				$output = executeQuery('autoinstall.getPackageSrlByPath', $args);

				$GLOBLAS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path] = $output->data->package_srl;
			}

			return $GLOBLAS['XE_AUTOINSTALL_PACKAGE_SRL_BY_PATH'][$path];
		}

		function getRemoveUrlByPackageSrl($packageSrl)
		{
            $ftp_info =  Context::getFTPInfo();
            if (!$ftp_info->ftp_root_path) return;
			
			if (!$packageSrl) return;

			return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminUninstall', 'package_srl', $packageSrl);
		}

		function getRemoveUrlByPath($path)
		{
			if (!$path) return;

            $ftp_info =  Context::getFTPInfo();
            if (!$ftp_info->ftp_root_path) return;

			$packageSrl = $this->getPackageSrlByPath($path);
			if (!$packageSrl) return;

			return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminUninstall', 'package_srl', $packageSrl);
		}

		function getUpdateUrlByPackageSrl($packageSrl)
		{
			if (!$packageSrl) return;

			return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminInstall', 'package_srl', $packageSrl);
		}

		function getUpdateUrlByPath($path)
		{
			if (!$path) return;

			$packageSrl = $this->getPackageSrlByPath($path);
			if (!$packageSrl) return;

			return getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAutoinstallAdminInstall', 'package_srl', $packageSrl);
		}
   }
?>
