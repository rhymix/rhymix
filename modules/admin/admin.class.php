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

			$gnbModuleList = array(
				0=>array(
					'module'=>'site',
					'subMenu'=>array('site'),
				),
				1=>array(
					'module'=>'member',
					'subMenu'=>array('userList', 'userSetting'),
				),
				2=>array(
					'module'=>'point',
					'subMenu'=>array('point'),
				),
				3=>array(
					'module'=>'document',
					'subMenu'=>array('document'),
				),
				4=>array(
					'module'=>'comment',
					'subMenu'=>array('comment'),
				),
				5=>array(
					'module'=>'trackback',
					'subMenu'=>array('trackback'),
				),
				6=>array(
					'module'=>'file',
					'subMenu'=>array('file'),
				),
				7=>array(
					'module'=>'poll',
					'subMenu'=>array('poll'),
				),
				8=>array(
					'module'=>'importer',
					'subMenu'=>array('importer'),
				),
				9=>array(
					'module'=>'admin',
					'subMenu'=>array('theme'),
				),
				10=>array(
					'module'=>'autoinstall',
					'subMenu'=>array('easyInstall'),
				),
				11=>array(
					'module'=>'layout',
					'subMenu'=>array('installedLayout'),
				),
				12=>array(
					'module'=>'module',
					'subMenu'=>array('installedModule'),
				),
				13=>array(
					'module'=>'widget',
					'subMenu'=>array('installedWidget'),
				),
				14=>array(
					'module'=>'addon',
					'subMenu'=>array('installedAddon'),
				),
				15=>array(
					'module'=>'editor',
					'subMenu'=>array('editor'),
				),
				16=>array(
					'module'=>'spamfilter',
					'subMenu'=>array('spamFilter'),
				),
				17=>array(
					'module'=>'admin',
					'subMenu'=>array('adminConfiguration', 'adminMenuSetup'),
				),
				18=>array(
					'module'=>'file',
					'subMenu'=>array('fileUpload'),
				),
			);

			$oMemberModel = &getModel('member');
			$output = $oMemberModel->getAdminGroup(array('group_srl'));
			$adminGroupSrl = $output->group_srl;

			// gnb sub item create
			// common argument setting
			$args->menu_srl = $menuSrl;
			$args->open_window = 'N';
			$args->expand = 'N';
			$args->normal_btn = '';
			$args->hover_btn = '';
			$args->active_btn = '';
			$args->group_srls = $adminGroupSrl;
            $oModuleModel = &getModel('module');

			foreach($gnbModuleList AS $key=>$value)
			{
				if(is_array($value['subMenu']))
				{
					$moduleActionInfo = $oModuleModel->getModuleActionXml($value['module']);
					foreach($value['subMenu'] AS $key2=>$value2)
					{
						$gnbKey = "'".$this->_getGnbKey($value2)."'";

						//insert menu item
						$args->menu_item_srl = getNextSequence();
						$args->parent_srl = $gnbDBList[$gnbKey];
						$args->name = '{$lang->menu_gnb_sub[\''.$value2.'\']}';
						$args->url = getNotEncodedUrl('', 'module', 'admin', 'act', $moduleActionInfo->menu->{$value2}->index);
						$args->listorder = -1*$args->menu_item_srl;
						$output = executeQuery('menu.insertMenuItem', $args);
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
