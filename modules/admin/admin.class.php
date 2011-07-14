<?php
    /**
     * @class  admin
     * @author NHN (developers@xpressengine.com)
     * @brief  base class of admin module 
     **/

    class admin extends ModuleObject {

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
			$oMenuAdminModel = &getAdminModel('menu');
			$output = $oMenuAdminModel->getMenuByTitle('__XE_ADMIN__');
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
            $args->title = '__XE_ADMIN__';
            $args->menu_srl = getNextSequence();
            $args->listorder = $args->menu_srl * -1;
            $output = executeQuery('menu.insertMenu', $args);

			$adminUrl = getUrl('', 'module', 'admin');
			$gnbList = array(
				'dashboard'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array()
				),
				'site'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array()
				),
				'user'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array('userList'=>$adminUrl, 'setting'=>$adminUrl, 'point'=>$adminUrl)
				),
				'content'=>array(
					'url'=>getUrl('', 'module', 'admin', 'act', 'dispDocumentAdminList'),
					'lnbList'=>array(
						'document'=>getUrl('', 'module', 'admin', 'act', 'dispDocumentAdminList'),
						'comment'=>$adminUrl,
						'trackback'=>$adminUrl,
						'file'=>$adminUrl,
						'poll'=>$adminUrl,
						'dataMigration'=>$adminUrl
					)
				),
				'theme'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array()
				),
				'extensions'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array('easyInstaller'=>$adminUrl, 'installedLayout'=>$adminUrl, 'installedModule'=>$adminUrl, 'installedWidget'=>$adminUrl, 'installedAddon'=>$adminUrl, 'WYSIWYGEditor'=>$adminUrl, 'spamFilter'=>$adminUrl)
				),
				'configuration'=>array(
					'url'=>$adminUrl,
					'lnbList'=>array('general'=>$adminUrl, 'fileUpload'=>$adminUrl)
				)
			);

			$oMemberModel = &getModel('member');
			$output = $oMemberModel->getAdminGroup(array('group_srl'));
			$adminGroupSrl = $output->group_srl;

			// common argument setting
			$args->open_window = 'N';
			$args->expand = 'N';
			$args->normal_btn = '';
			$args->hover_btn = '';
			$args->active_btn = '';
			$args->group_srls = $adminGroupSrl;

			foreach($gnbList AS $key=>$value)
			{
				//insert menu item
				$args->menu_item_srl = getNextSequence();
				$args->name = '{$lang->menu_gnb[\''.$key.'\']}';
				$args->url = $value['url'];
				$args->listorder = -1*$args->menu_item_srl;
                $output = executeQuery('menu.insertMenuItem', $args);

				if(is_array($value) && count($value)>0)
				{
					$args2->menu_srl = $args->menu_srl;
					$args2->open_window = 'N';
					$args2->expand = 'N';
					$args2->normal_btn = '';
					$args2->hover_btn = '';
					$args2->active_btn = '';
					$args2->group_srls = $adminGroupSrl;
					foreach($value['lnbList'] AS $key2=>$value2)
					{
						//insert menu item
						$args2->menu_item_srl = getNextSequence();
						$args2->parent_srl = $args->menu_item_srl;
						$args2->name = '{$lang->menu_gnb_sub[\''.$key.'\'][\''.$key2.'\']}';
						$args2->url = $value2;
						$args2->listorder = -1*$args2->menu_item_srl;
						$output = executeQuery('menu.insertMenuItem', $args2);
					}
				}
			}
			$oMenuAdminConroller = &getAdminController('menu');
			$oMenuAdminConroller->makeXmlFile($args->menu_srl);
		}
    }
?>
