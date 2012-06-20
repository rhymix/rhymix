<?php
	/**
	 * adminAdminModel class
	 * admin model class of admin module
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/admin
	 * @version 0.1
	 */
    class adminAdminModel extends admin
    {
		/**
		 * Ftp root path
		 * @var string
		 */
        var $pwd;
		/**
		 * Buffer for Admin GNB menu
		 * @var string
		 */
		var $gnbLangBuffer;

		/**
		 * Add file list to Object after sftp connect
		 * @return void|Object
		 */
        function getSFTPList()
        {
            $ftp_info =  Context::getRequestVars();
            if(!$ftp_info->ftp_host)
            {
                $ftp_info->ftp_host = "127.0.0.1";
            }
            $connection = ssh2_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
            if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
            {
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $sftp = ssh2_sftp($connection);
            $curpwd = "ssh2.sftp://$sftp".$this->pwd;
            $dh = @opendir($curpwd);
			if(!$dh) return new Object(-1, 'msg_ftp_invalid_path');
            $list = array();
            while(($file = readdir($dh)) !== false) {
                if(is_dir($curpwd.$file))
                {
                    $file .= "/";
                }
                else
                {
                    continue;
                }
                $list[] = $file;
            }
            closedir($dh);
            $this->add('list', $list);
        }

		/**
		 * Add file list to Object after ftp connect
		 * @return void|Object
		 */
        function getAdminFTPList()
        {
			Context::loadLang('./modules/autoinstall/lang');
            set_time_limit(5);
            require_once(_XE_PATH_.'libs/ftp.class.php');
            $ftp_info =  Context::getRequestVars();
            if(!$ftp_info->ftp_user || !$ftp_info->ftp_password)
            {
                return new Object(-1, 'msg_ftp_invalid_auth_info');
            }

            $this->pwd = $ftp_info->ftp_root_path;

            if(!$ftp_info->ftp_host)
            {
                $ftp_info->ftp_host = "127.0.0.1";
            }

			if (!$ftp_info->ftp_port || !is_numeric ($ftp_info->ftp_port)) {
				$ftp_info->ftp_port = "21";
			}

            if($ftp_info->sftp == 'Y')
            {
				if(!function_exists(ssh2_sftp))
				{
                    return new Object(-1,'disable_sftp_support');
				}
                return $this->getSFTPList();
            }

            $oFtp = new ftp();
            if($oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port)){
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
					$_list = $oFtp->ftp_rawlist($this->pwd);
					$oFtp->ftp_quit();
				}
                else
                {
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }
			}
            $list = array();

			if($_list){
                foreach($_list as $k => $v){
					$src = null;
					$src->data = $v;
					$res = Context::convertEncoding($src);
					$v = $res->data;
                    if(strpos($v,'d') === 0 || strpos($v, '<DIR>')) $list[] = substr(strrchr($v,' '),1) . '/';
                }
            }
			else
			{
				return new Object(-1,'msg_ftp_no_directory');
			}
            $this->add('list', $list);
        }

		/**
		 * Parameter arrange for send to XE collect server
		 * @param string $type 'WORKING', 'INSTALL'
		 * @return string
		 */
		function getEnv($type='WORKING') {

			 $skip = array(
					 	'ext' => array('pcre','json','hash','dom','session','spl','standard','date','ctype','tokenizer','apache2handler','filter','posix','reflection','pdo')
						,'module' => array('addon','admin','autoinstall', 'comment', 'communication', 'counter', 'document', 'editor', 'file', 'importer', 'install', 'integration_search', 'layout', 'member', 'menu', 'message', 'module', 'opage', 'page', 'point', 'poll', 'rss', 'session', 'spamfilter', 'tag',  'trackback', 'trash', 'widget')
						,'addon' => array('autolink', 'blogapi', 'captcha', 'counter', 'member_communication', 'member_extra_info', 'mobile', 'openid_delegation_id', 'point_level_icon', 'resize_image' )
					);

			$info = array();
			$info['type'] = ($type !='INSTALL' ? 'WORKING' : 'INSTALL');
			$info['location'] = _XE_LOCATION_;
			$info['package'] = _XE_PACKAGE_;
			$info['host'] = $db_type->default_url ? $db_type->default_url : getFullUrl();
			$info['app'] = $_SERVER['SERVER_SOFTWARE'];
			$info['xe_version'] = __ZBXE_VERSION__;
			$info['php'] = phpversion();

			$db_info = Context::getDBInfo();
			$info['db_type'] = Context::getDBType();
			$info['use_rewrite'] = $db_info->use_rewrite;
			$info['use_db_session'] = $db_info->use_db_session == 'Y' ?'Y':'N';
			$info['use_ssl'] = $db_info->use_ssl;

			$info['phpext'] = '';
			foreach (get_loaded_extensions() as $ext) {
				$ext = strtolower($ext);
				if(in_array($ext, $skip['ext'])) continue;
				$info['phpext'] .= '|'. $ext;
			}
			$info['phpext'] = substr($info['phpext'],1);

			$info['module'] = '';
			$oModuleModel = &getModel('module');
			$module_list = $oModuleModel->getModuleList();
			foreach($module_list as $module){
				if(in_array($module->module, $skip['module'])) continue;
				$info['module']  .= '|'.$module->module;
			}
			$info['module'] = substr($info['module'],1);

			$info['addon'] = '';
			$oAddonAdminModel = &getAdminModel('addon');
			$addon_list = $oAddonAdminModel->getAddonList();
			foreach($addon_list as $addon){
				if(in_array($addon->addon, $skip['addon'])) continue;
				$info['addon'] .= '|'.$addon->addon;
			}
			$info['addon'] = substr($info['addon'],1);

			$param = '';
			foreach($info as $k => $v){
				if($v) $param .= sprintf('&%s=%s',$k,urlencode($v));
			}
			$param = substr($param, 1);

			return $param;
		}

		/**
		 * Return theme info list by theme directory list
		 * @return array
		 */
		function getThemeList(){
			$path = _XE_PATH_.'themes';
			$list = FileHandler::readDir($path);

			$theme_info = array();
			if(count($list) > 0){
				foreach($list as $val){
					$theme_info[$val] = $this->getThemeInfo($val);
				}
			}

			return $theme_info;
		}

		/**
		 * Return theme info
		 * @param string $theme_name
		 * @param array $layout_list
		 * @return object
		 */
		function getThemeInfo($theme_name, $layout_list = null){
			if ($GLOBALS['__ThemeInfo__'][$theme_name]) return $GLOBALS['__ThemeInfo__'][$theme_name];

			$info_file = _XE_PATH_.'themes/'.$theme_name.'/conf/info.xml';
            if(!file_exists($info_file)) return;

            $oXmlParser = new XmlParser();
            $_xml_obj = $oXmlParser->loadXmlFile($info_file);

            if(!$_xml_obj->theme) return;
            $xml_obj = $_xml_obj->theme;
            if(!$_xml_obj->theme) return;

            // 스킨이름
			$theme_info->name = $theme_name;
            $theme_info->title = $xml_obj->title->body;
			$thumbnail = './themes/'.$theme_name.'/thumbnail.png';
			$theme_info->thumbnail = (file_exists($thumbnail))?$thumbnail:null;
			$theme_info->version = $xml_obj->version->body;
			sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
			$theme_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
			$theme_info->description = $xml_obj->description->body;
			$theme_info->path = './themes/'.$theme_name.'/';

			if(!is_array($xml_obj->publisher)) $publisher_list[] = $xml_obj->publisher;
			else $publisher_list = $xml_obj->publisher;

			foreach($publisher_list as $publisher) {
				unset($publisher_obj);
				$publisher_obj->name = $publisher->name->body;
				$publisher_obj->email_address = $publisher->attrs->email_address;
				$publisher_obj->homepage = $publisher->attrs->link;
				$theme_info->publisher[] = $publisher_obj;
			}

			$layout = $xml_obj->layout;
			$layout_path = $layout->directory->attrs->path;
			$layout_parse = explode('/',$layout_path);
			switch($layout_parse[1]){
				case 'themes' : {
									$layout_info->name = $theme_name.'.'.$layout_parse[count($layout_parse)-1];
									break;
								}
				case 'layouts' : {
									$layout_info->name = $layout_parse[count($layout_parse)-1];
									 break;
								}
			}
			$layout_info->path = $layout_path;

			$site_info = Context::get('site_module_info');
			// check layout instance
			$is_new_layout = true;
			$oLayoutModel = &getModel('layout');
			$layout_info_list = array();
			$layout_list = $oLayoutModel->getLayoutList($site_info->site_srl);
			if ($layout_list){
				foreach($layout_list as $val){
					if ($val->layout == $layout_info->name){
						$is_new_layout = false;
						$layout_info->layout_srl = $val->layout_srl;
						break;
					}
				}
			}

			if ($is_new_layout){
				$site_module_info = Context::get('site_module_info');
				$args->site_srl = (int)$site_module_info->site_srl;
				$args->layout_srl = getNextSequence();
				$args->layout = $layout_info->name;
				$args->title = $layout_info->name;
				$args->layout_type = "P";
				// Insert into the DB
				$oLayoutAdminController = &getAdminController('layout');
				$output = $oLayoutAdminController->insertLayout($args);
				$layout_info->layout_srl = $args->layout_srl;
			}

			$theme_info->layout_info = $layout_info;

			$skin_infos = $xml_obj->skininfos;
			if(is_array($skin_infos->skininfo))$skin_list = $skin_infos->skininfo;
			else $skin_list = array($skin_infos->skininfo);

			$oModuleModel = &getModel('module');
			$skins = array();
			foreach($skin_list as $val){
				unset($skin_info);
				unset($skin_parse);
				$skin_parse = explode('/',$val->directory->attrs->path);
				switch($skin_parse[1]){
					case 'themes' : {
										$is_theme = true;
										$module_name = $skin_parse[count($skin_parse)-1];
										$skin_info->name = $theme_name.'.'.$module_name;
										break;
									}
					case 'modules' : {
										$is_theme = false;
										 $module_name = $skin_parse[2];
										 $skin_info->name = $skin_parse[count($skin_parse)-1];
										 break;
									}
				}
				$skin_info->path = $val->directory->attrs->path;
				$skins[$module_name] = $skin_info;

				if ($is_theme){
					if (!$GLOBALS['__ThemeModuleSkin__'][$module_name]){
						$GLOBALS['__ThemeModuleSkin__'][$module_name] = array();
						$GLOBALS['__ThemeModuleSkin__'][$module_name]['skins'] = array();
						$moduleInfo = $oModuleModel->getModuleInfoXml($module_name);
						$GLOBALS['__ThemeModuleSkin__'][$module_name]['title'] = $moduleInfo->title;
					}
					$GLOBALS['__ThemeModuleSkin__'][$module_name]['skins'][$skin_info->name] = $oModuleModel->loadSkinInfo($skin_info->path, '', '');
				}
			}
			$theme_info->skin_infos = $skins;

			$GLOBALS['__ThemeInfo__'][$theme_name] = $theme_info;
			return $theme_info;
		}

		/**
		 * Return theme module skin list
		 * @return array
		 */
		function getModulesSkinList(){
			if ($GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__']) return $GLOBALS['__ThemeModuleSkin__'];
            $searched_list = FileHandler::readDir('./modules');
            sort($searched_list);

            $searched_count = count($searched_list);
            if(!$searched_count) return;

			$exceptionModule = array('editor', 'poll', 'homepage', 'textyle');

			$oModuleModel = &getModel('module');
            foreach($searched_list as $val) {
				$skin_list = $oModuleModel->getSkins('./modules/'.$val);

				if (is_array($skin_list) && count($skin_list) > 0 && !in_array($val, $exceptionModule)){
					if(!$GLOBALS['__ThemeModuleSkin__'][$val]){
						$GLOBALS['__ThemeModuleSkin__'][$val] = array();
						$moduleInfo = $oModuleModel->getModuleInfoXml($val);
						$GLOBALS['__ThemeModuleSkin__'][$val]['title'] = $moduleInfo->title;
						$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array();
					}
					$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array_merge($GLOBALS['__ThemeModuleSkin__'][$val]['skins'], $skin_list);
				}
			}
			$GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__'] = true;

			return $GLOBALS['__ThemeModuleSkin__'];
		}

		/**
		 * Return admin menu language
		 * @return array
		 */
		function getAdminMenuLang()
		{
			$currentLang = Context::getLangType();
			$cacheFile = sprintf('./files/cache/menu/admin_lang/adminMenu.%s.lang.php', $currentLang);

            // Update if no cache file exists or it is older than xml file
            if(!is_readable($cacheFile))
			{
				$oModuleModel = &getModel('module');
				$installed_module_list = $oModuleModel->getModulesXmlInfo();

				$this->gnbLangBuffer = '<?php ';
				foreach($installed_module_list AS $key=>$value)
				{
					$moduleActionInfo = $oModuleModel->getModuleActionXml($value->module);
					if(is_object($moduleActionInfo->menu))
					{
						foreach($moduleActionInfo->menu AS $key2=>$value2)
						{
							$lang->menu_gnb_sub[$key2] = $value2->title;
							$this->gnbLangBuffer .=sprintf('$lang->menu_gnb_sub[\'%s\'] = \'%s\';', $key2, $value2->title);
						}
					}
				}
				$this->gnbLangBuffer .= ' ?>';
				FileHandler::writeFile($cacheFile, $this->gnbLangBuffer);
			}
			else include $cacheFile;

			return $lang->menu_gnb_sub;
		}

		/**
		 * Get admin favorite list
		 * @param int $siteSrl if default site, siteSrl is zero
		 * @param bool $isGetModuleInfo
		 * @return object
		 */
		function getFavoriteList($siteSrl = 0, $isGetModuleInfo = false)
		{
			$args->site_srl = $siteSrl;
			$output = executeQueryArray('admin.getFavoriteList', $args);
			if (!$output->toBool()) return $output;
			if (!$output->data) return new Object();

			if($isGetModuleInfo && is_array($output->data))
			{
				$oModuleModel = &getModel('module');
				foreach($output->data AS $key=>$value)
				{
					$moduleInfo = $oModuleModel->getModuleInfoXml($value->module);
					$output->data[$key]->admin_index_act = $moduleInfo->admin_index_act;
					$output->data[$key]->title = $moduleInfo->title;
				}
			}

			$returnObject = new Object();
			$returnObject->add('favoriteList', $output->data);
			return $returnObject;
		}

		/**
		 * Check available insert favorite
		 * @param int $siteSrl if default site, siteSrl is zero
		 * @param string $module
		 * @return object
		 */
		function isExistsFavorite($siteSrl, $module)
		{
			$args->site_srl = $siteSrl;
			$args->module = $module;
			$output = executeQuery('admin.getFavorite', $args);
			if (!$output->toBool()) return $output;

			$returnObject = new Object();
			if ($output->data)
			{
				$returnObject->add('result', true);
				$returnObject->add('favoriteSrl', $output->data->admin_favorite_srl);
			}
			else
			{
				$returnObject->add('result', false);
			}

			return $returnObject;
		}

        /**
         * Return site list
		 * @return void
         */
		function getSiteAllList()
		{
			if(Context::get('domain')) $args->domain = Context::get('domain');
			$columnList = array('domain', 'site_srl');

			$siteList = array();
			$output = executeQueryArray('admin.getSiteAllList', $args, $columnList);
			if($output->toBool()) $siteList = $output->data;

			$this->add('site_list', $siteList);
		}

        /**
         * Return site count
		 * @param string $date
		 * @return int
         */
		function getSiteCountByDate($date = '')
		{
			if($date) $args->regDate = date('Ymd', strtotime($date));

			$output = executeQuery('admin.getSiteCountByDate', $args);
			if(!$output->toBool()) return 0;

			return $output->data->count;
		}

		function getFaviconUrl()
		{
			return $this->iconUrlCheck('favicon.ico','faviconSample.png');
		}

		function getMobileIconUrl()
		{
			return $this->iconUrlCheck('mobicon.png','mobiconSample.png');
		}

		function iconUrlCheck($iconname,$default_icon_name)
		{
			$file_exsit = FileHandler::readFile(_XE_PATH_.'files/attach/xeicon/'.$iconname);
			if(!$file_exsit){
				$icon_url = './modules/admin/tpl/img/'.$default_icon_name	;
			} else {
				$icon_url = $db_info->default_url.'files/attach/xeicon/'.$iconname;
			}
			return $icon_url;
		}
	}
