<?php
    /**
     * @class  menuAdminModel
     * @author NHN (developers@xpressengine.com)
     * @version 0.1
     * @brief admin model class of the menu module
     **/

    class menuAdminModel extends menu {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Get a list of all menus
         **/
        function getMenuList($obj) {
            if(!$obj->site_srl) {
                $site_module_info = Context::get('site_module_info');
                $obj->site_srl = (int)$site_module_info->site_srl;
            }
            $args->site_srl = $obj->site_srl;
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            // document.getDocumentList query execution
            $output = executeQuery('menu.getMenuList', $args);
            // Return if no result or an error occurs
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

        /**
         * @brief Return all menus
         **/
        function getMenus($site_srl = null) {
            if(!isset($site_srl)) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = (int)$site_module_info->site_srl;
            }
            // Get information from the DB
            $args->site_srl = $site_srl ;
            $args->menu_srl = $menu_srl;
            $output = executeQuery('menu.getMenus', $args);
            if(!$output->data) return;
            $menus = $output->data;
            if(!is_array($menus)) $menus = array($menus);
            return $menus;
        }

        /**
         * @brief Get information of a new menu from the DB
         * Return DB and XML information of the menu
         **/
        function getMenu($menu_srl) {
            // Get information from the DB
            $args->menu_srl = $menu_srl;
            $output = executeQuery('menu.getMenu', $args);
            if(!$output->data) return;
            
            $menu_info = $output->data;
            $menu_info->xml_file = sprintf('./files/cache/menu/%s.xml.php',$menu_srl);
            $menu_info->php_file = sprintf('./files/cache/menu/%s.php',$menu_srl);
            return $menu_info;
        }

        /**
         * @brief Get information of a new menu from the DB, search condition is menu title
         * Return DB and XML information of the menu
         **/
        function getMenuByTitle($title) {
            // Get information from the DB
            $args->title = $title;
            $output = executeQuery('menu.getMenuByTitle', $args);
            if(!$output->data) return;
            
            $menu_info = $output->data;
            $menu_info->xml_file = sprintf('./files/cache/menu/%s.xml.php',$menu_info->menu_srl);
            $menu_info->php_file = sprintf('./files/cache/menu/%s.php',$menu_info->menu_srl);
            return $menu_info;
        }

        /**
         * @brief Return item information of the menu_srl
         * group_srls uses a seperator with comma(,) and converts to an array by explode
         **/
        function getMenuItemInfo($menu_item_srl) {
            // Get the menu information if menu_item_srl exists
            $args->menu_item_srl = $menu_item_srl;
            $output = executeQuery('menu.getMenuItem', $args);
            $node = $output->data;
            if($node->group_srls) $node->group_srls = explode(',',$node->group_srls);
            else $node->group_srls = array();

            $tmp_name = unserialize($node->name);
            if($tmp_name && count($tmp_name) ) {
                $selected_lang = array();
                $rand_name = $tmp_name[Context::getLangType()];
                if(!$rand_name) $rand_name = array_shift($tmp_name);
                $node->name = $rand_name;
            }
            return $node;
        }

        /**
         * @brief Return item information of the menu_srl
         **/
        function getMenuAdminItemInfo()
		{
			$menuItemSrl = Context::get('menu_item_srl');
			$menuItem = $this->getMenuItemInfo($menuItemSrl);

			if(!$menuItem->url)
			{
				$menuItem->moduleType = null;
			}
			else if(!preg_match('/^http/i',$menuItem->url))
			{
				$oModuleModel = &getModel('module');
				$moduleInfo = $oModuleModel->getModuleInfoByMid($menuItem->url);
				if($moduleInfo->mid == $menuItem->url) {
					$menuItem->moduleType = $moduleInfo->module;
					//$menuItem->module_id = $moduleInfo->mid;
					//$menuItem->browser_title = $moduleInfo->browser_title;
					//unset($menuItem->url);
				}
			}
			else
			{
				$menuItem->moduleType = 'url';
				/*$menuItem->url = preg_replace('/^(http|https):\/\//i','',$menuItem->url);*/
			}

			// get groups
			$oMemberModel = &getModel('member');
			$oModuleAdminModel = &getAdminModel('module');
			$output = $oMemberModel->getGroups();
			if(is_array($output))
			{
				$groupList = array();
				foreach($output AS $key=>$value)
				{

					$groupList[$value->group_srl]->group_srl = $value->group_srl;
            		if(substr($value->title,0,12)=='$user_lang->') {
						$tmp = $oModuleAdminModel->getLangCode(0, $value->title);
						$groupList[$value->group_srl]->title = $tmp[Context::getLangType()];
					}
					else $groupList[$value->group_srl]->title = $value->title;

					if(in_array($key, $menuItem->group_srls)) $groupList[$value->group_srl]->isChecked = true;
					else $groupList[$value->group_srl]->isChecked = false;
				}
			}
			$menuItem->groupList = $groupList;

			$this->add('menu_item', $menuItem);
        }

		function getMenuItems($menu_srl, $parent_srl = null, $columnList = array())
		{
			$args->menu_srl = $menu_srl;
			$args->parent_srl = $parent_srl;

			$output = executeQueryArray('menu.getMenuItems', $args, $columnList);
			return $output;
		}

        /**
         * @brief Return menu name in each language to support multi-language
         */
        function getMenuItemNames($source_name, $site_srl = null) {
            if(!$site_srl) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = (int)$site_module_info->site_srl;
            }
            // Get language code
            $oModuleAdminModel = &getAdminModel('module');
            return $oModuleAdminModel->getLangCode($site_srl, $source_name);
        }

        /**
         * @brief Get a template by using the menu_srl and retrun.
         * Return html after compiling tpl on the server in order to add menu information on the admin page
         **/
        function getMenuAdminTplInfo() {
            // Get information on the menu for the parameter settings
            $menu_item_srl = Context::get('menu_item_srl');
            $parent_srl = Context::get('parent_srl');
            // Get a list of member groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
            // Add a sub-menu if there is parent_srl but not menu_item_srl
            if(!$menu_item_srl && $parent_srl) {
                // Get information of the parent menu
                $parent_info = $this->getMenuItemInfo($parent_srl);
                // Default parameter settings for a new menu
                $item_info->menu_item_srl = getNextSequence();
                $item_info->parent_srl = $parent_srl;
                $item_info->parent_menu_name = $parent_info->name;
            // In case of modifying the existing menu or addting a new menu to the root
            } else {
                // Get information of the menu if menu_item_srl exists
                if($menu_item_srl) $item_info = $this->getMenuItemInfo($menu_item_srl);
                // Get only menu_item_srl if no values found, considering it as adding a new menu
                if(!$item_info->menu_item_srl) {
                    $item_info->menu_item_srl = getNextSequence();
                }
            }
            Context::set('item_info', $item_info);
            // Compile the template file into tpl variable and then return it
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'menu_item_info');

            $this->add('tpl', str_replace("\n"," ",$tpl));
        }

        /**
         * @brief when menu add in sitemap, select module list
		 * this menu showing with trigger
         **/
		function getModuleListInSitemap()
		{
			$oModuleModel = &getModel('module');
			$columnList = array('module');
			$moduleList = array('page');

			$output = $oModuleModel->getModuleListByInstance($columnList);
			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$value)
				{
					array_push($moduleList, $value->module);
				}
			}

            // after trigger
            $output = ModuleHandler::triggerCall('menu.getModuleListInSitemap', 'after', $moduleList);
            if(!$output->toBool()) return $output;

			$moduleList = array_unique($moduleList);

			$moduleInfoList = array();
			if(is_array($moduleList))
			{
				foreach($moduleList AS $key=>$value)
				{
					$moduleInfo = $oModuleModel->getModuleInfoXml($value);
					$moduleInfoList[$value] = $moduleInfo;
				}
			}

            return $moduleInfoList;
		}
    }
?>
