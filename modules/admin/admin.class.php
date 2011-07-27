<?php
    /**
     * @class  admin
     * @author NHN (developers@xpressengine.com)
     * @brief  base class of admin module 
     **/

    class admin extends ModuleObject {
		var $xeMenuTitle;

        /**
         * @brief install admin module 
         * @return new Object
         **/
        function moduleInstall() {
            return new Object();
        }

        /**
         * @brief if update is necessary it returns true
         **/
        function checkUpdate() {
			$this->xeMenuTitle = '__XE_ADMIN__';
			$oMenuAdminModel = &getAdminModel('menu');
			$output = $oMenuAdminModel->getMenuByTitle($this->xeMenuTitle);

			if(!$output->menu_srl)
			{
				$this->_createXeAdminMenu();
			}

            return false;
        }

        /**
         * @brief update module 
         * @return new Object
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief regenerate cache file
         * @return none
         **/
        function recompileCache() {

            // remove compiled templates
            FileHandler::removeFilesInDir("./files/cache/template_compiled");

            // remove optimized files 
            FileHandler::removeFilesInDir("./files/cache/optimized");

            // remove js_filter_compiled files 
            FileHandler::removeFilesInDir("./files/cache/js_filter_compiled");

            // remove cached queries 
            FileHandler::removeFilesInDir("./files/cache/queries");

            // remove ./files/cache/news* files 
            $directory = dir(_XE_PATH_."files/cache/");
            while($entry = $directory->read()) {
                if(substr($entry,0,11)=='newest_news') FileHandler::removeFile("./files/cache/".$entry);
            }
            $directory->close();
        }

        /**
         * @brief regenerate xe admin default menu
         * @return none
         **/
		function _createXeAdminMenu()
		{
			//insert menu
            $args->title = $this->xeMenuTitle;
            $args->menu_srl = getNextSequence();
			//$args->menu_srl = 3302;
            $args->listorder = $args->menu_srl * -1;
            $output = executeQuery('menu.insertMenu', $args);
			$menuSrl = $args->menu_srl;
			unset($args);

			// gnb item create
			$gnbList = array('dashboard', 'site', 'user', 'content', 'theme', 'extensions', 'configuration');
			foreach($gnbList AS $key=>$value)
			{
				//insert menu item
				$args->menu_srl = $menuSrl;
				$args->menu_item_srl = getNextSequence();
				$args->name = '{$lang->menu_gnb[\''.$value.'\']}';
				if($value == 'dashboard') $args->url = getUrl('', 'module', 'admin');
				else $args->url = '#';
				$args->listorder = -1*$args->menu_item_srl;
                $output = executeQuery('menu.insertMenuItem', $args);
			}

			$oMenuAdminModel = &getAdminModel('menu');
			$columnList = array('menu_item_srl', 'name');
			$output = $oMenuAdminModel->getMenuItems($menuSrl, 0, $columnList);
			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$value)
				{
					preg_match('/\{\$lang->menu_gnb\[(.*?)\]\}/i', $value->name, $m);
					$gnbDBList[$m[1]] = $value->menu_item_srl;
				}
			}

			unset($args);
            $oModuleModel = &getModel('module');
            $installed_module_list = $oModuleModel->getModulesXmlInfo();

			// gnb sub item create
			if(is_array($installed_module_list))
			{
				$oMemberModel = &getModel('member');
				$output = $oMemberModel->getAdminGroup(array('group_srl'));
				$adminGroupSrl = $output->group_srl;

				// common argument setting
				$args->menu_srl = $menuSrl;
				$args->open_window = 'N';
				$args->expand = 'N';
				$args->normal_btn = '';
				$args->hover_btn = '';
				$args->active_btn = '';
				$args->group_srls = $adminGroupSrl;

				foreach($installed_module_list AS $key=>$value)
				{
					//if($value->module == 'document')
					//{
						$moduleActionInfo = $oModuleModel->getModuleActionXml($value->module);
						if(is_object($moduleActionInfo->menu))
						{
							foreach($moduleActionInfo->menu AS $key2=>$value2)
							{
								$gnbKey = "'".$this->_getGnbKey($key2)."'";

								//insert menu item
								$args->menu_item_srl = getNextSequence();
								$args->parent_srl = $gnbDBList[$gnbKey];
								//$args->name = '{$lang->menu_gnb_sub['.$gnbKey.'][\''.$key2.'\']}';
								$args->name = '{$lang->menu_gnb_sub[\''.$key2.'\']}';
								$args->url = getNotEncodedUrl('', 'module', 'admin', 'act', $value2->index);
								$args->listorder = -1*$args->menu_item_srl;
								$output = executeQuery('menu.insertMenuItem', $args);
							}
						}
					//}
				}
			}

			$oMenuAdminConroller = &getAdminController('menu');
			$oMenuAdminConroller->makeXmlFile($menuSrl);
		}

		function _getGnbKey($menuName)
		{
			switch($menuName) {
				case 'site':
					return 'site';
					break;
				case 'userList':
				case 'userSetting':
				case 'point':
					return 'user';
					break;
				case 'document':
				case 'comment':
				case 'trackback':
				case 'file':
				case 'poll':
				case 'importer':
					return 'content';
					break;
				case 'theme':
					return 'theme';
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
				case 'adminConfiguration':
				case 'adminMenuSetup':
				case 'fileUpload':
					return 'configuration';
					break;
				default:
					return 'extensions';
			}
		}
    }
?>
