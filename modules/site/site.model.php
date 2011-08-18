<?php
    /**
     * @class  siteModel
     * @author NHN (developers@xpressengine.com)
     * @brief model class of the comment module
     **/

    class siteModel extends site {

        /**
         * @brief Initialization
         **/
        function init() {
        }

		function getSiteAdminMenu($member_info = null){
			if (!$member_info) $member_info = Context::get('logged_info');
			if (!$member_info) return null;

			return $this->makeGnbUrl($member_info->member_srl);

		}

		function getSiteAllList()
		{
			if(Context::get('domain')) $args->domain = Context::get('domain');
			$columnList = array('domain', 'site_srl');

			$siteList = array();
			$output = executeQueryArray('site.getSiteAllList', $args, $columnList);
			if($output->toBool()) $siteList = $output->data;

			$this->add('site_list', $siteList);
		}

		function makeGnbUrl($member_srl)
		{
			global $lang;
			$oAdminAdminModel = &getAdminModel('admin');
			$lang->menu_gnb_sub = $oAdminAdminModel->getAdminMenuLang();

			$oMenuAdminModel = &getAdminModel('menu');
			$outputs = $oMenuAdminModel->getMenus($member_srl);
			if (!$outputs) $this->_createXeAdminMenu($member_srl);
			$output = $outputs[0];

			$menu_info = $oMenuAdminModel->getMenu($output->menu_srl);

			if(is_readable($menu_info->php_file))
				include $menu_info->php_file;
			else {
				//header('location:'.getNotEncodedUrl('', 'module', 'admin'));
				return;
			}

            $oModuleModel = &getModel('module');
			$moduleActionInfo = $oModuleModel->getModuleActionXml("admin");
			if(is_object($moduleActionInfo->menu))
			{
				$subMenuTitle = '';
				foreach($moduleActionInfo->menu AS $key=>$value)
				{
					if($value->acts && in_array(Context::get('act'), $value->acts))
					{
						$subMenuTitle = $value->title;
						break;
					}
				}
			}

			$parentSrl = 0;
			if(is_array($menu->list))
			{
				foreach($menu->list AS $key=>$value)
				{
					$parentMenu = $value;
					if(is_array($parentMenu['list']) && count($parentMenu['list']) > 0)
					{
						foreach($parentMenu['list'] AS $key2=>$value2)
						{
							$childMenu = $value2;
							if($subMenuTitle == $childMenu['text'])
							{
								$parentSrl = $childMenu['parent_srl'];
								break;
							}
						}
					}
				}
			}

			$return_output->menuList = $menu->list;
			$return_output->parentSrl = $parentSrl;

			return $return_output;
		}

        /**
         * @brief regenerate xe admin default menu
         * @return none
         **/
		function _createXeAdminMenu($site_admin_srl)
		{
			//insert menu
            $args->title = "__XE_SITE_ADMIN__";
            $args->menu_srl = getNextSequence();
            $args->listorder = $args->menu_srl * -1;
			$args->site_srl = $site_admin_srl;
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
					$moduleActionInfo = $oModuleModel->getModuleActionXml($value->module);
					if(is_object($moduleActionInfo->menu))
					{
						foreach($moduleActionInfo->menu AS $key2=>$value2)
						{
							if ($value2->type == 'site' || $value2->type == 'all'){
								$gnbKey = "'".$this->_getGnbKey($key2)."'";

								//insert menu item
								$args->menu_item_srl = getNextSequence();
								$args->parent_srl = $gnbDBList[$gnbKey];
								$args->name = '{$lang->menu_gnb_sub[\''.$key2.'\']}';
								$args->url = getNotEncodedUrl('', 'module', 'admin', 'act', $value2->index);
								$args->listorder = -1*$args->menu_item_srl;
								$output = executeQuery('menu.insertMenuItem', $args);
							}
						}
					}
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
				case 'siteUserList':
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
