<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminModel class
 * admin model class of admin module
 * @author NAVER (developers@xpressengine.com)
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
	 * Find XE installed path on sftp
	 */
	function getSFTPPath()
	{
		$ftp_info = Context::getRequestVars();

		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		if(!$ftp_info->ftp_port || !is_numeric($ftp_info->ftp_port))
		{
			$ftp_info->ftp_port = '22';
		}

		$connection = ssh2_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
		if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
		{
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}
		$sftp = ssh2_sftp($connection);

		// create temp file
		$pin = $_SERVER['REQUEST_TIME'];
		FileHandler::writeFile('./files/cache/ftp_check', $pin);

		// create path candidate
		$xe_path = _XE_PATH_;
		$path_info = array_reverse(explode('/', _XE_PATH_));
		array_pop($path_info); // remove last '/'
		$path_candidate = array();

		$temp = '';
		foreach($path_info as $path)
		{
			$temp = '/' . $path . $temp;
			$path_candidate[] = $temp;
		}

		// try
		foreach($path_candidate as $path)
		{
			// upload check file
			if(!@ssh2_scp_send($connection, FileHandler::getRealPath('./files/cache/ftp_check'), $path . 'ftp_check.html'))
			{
				continue;
			}

			// get check file
			$result = FileHandler::getRemoteResource(getNotencodedFullUrl() . 'ftp_check.html');

			// delete temp check file
			@ssh2_sftp_unlink($sftp, $path . 'ftp_check.html');

			// found
			if($result == $pin)
			{
				$found_path = $path;
				break;
			}
		}

		FileHandler::removeFile('./files/cache/ftp_check', $pin);

		if($found_path)
		{
			$this->add('found_path', $found_path);
		}
	}

	function getFTPPath()
	{
		$ftp_info = Context::getRequestVars();

		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		if(!$ftp_info->ftp_port || !is_numeric($ftp_info->ftp_port))
		{
			$ftp_info->ftp_port = '22';
		}

		$connection = ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
		if(!$connection)
		{
			return new Object(-1, sprintf(Context::getLang('msg_ftp_not_connected'), $ftp_host));
		}

		$login_result = @ftp_login($connection, $ftp_info->ftp_user, $ftp_info->ftp_password);
		if(!$login_result)
		{
			ftp_close($connection);
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}

		// create temp file
		$pin = $_SERVER['REQUEST_TIME'];
		FileHandler::writeFile('./files/cache/ftp_check', $pin);

		// create path candidate
		$xe_path = _XE_PATH_;
		$path_info = array_reverse(explode('/', _XE_PATH_));
		array_pop($path_info); // remove last '/'
		$path_candidate = array();

		$temp = '';
		foreach($path_info as $path)
		{
			$temp = '/' . $path . $temp;
			$path_candidate[] = $temp;
		}

		// try
		foreach($path_candidate as $path)
		{
			// upload check file
			if(!ftp_put($connection, $path . 'ftp_check.html', FileHandler::getRealPath('./files/cache/ftp_check'), FTP_BINARY))
			{
				continue;
			}

			// get check file
			$result = FileHandler::getRemoteResource(getNotencodedFullUrl() . 'ftp_check.html');

			// delete temp check file
			ftp_delete($connection, $path . 'ftp_check.html');

			// found
			if($result == $pin)
			{
				$found_path = $path;
				break;
			}
		}

		FileHandler::removeFile('./files/cache/ftp_check', $pin);

		if($found_path)
		{
			$this->add('found_path', $found_path);
		}
	}

	/**
	 * Find XE installed path on ftp
	 */
	function getAdminFTPPath()
	{
		Context::loadLang(_XE_PATH_ . 'modules/autoinstall/lang');
		@set_time_limit(5);
		require_once(_XE_PATH_ . 'libs/ftp.class.php');

		$ftp_info = Context::getRequestVars();

		if(!$ftp_info->ftp_user || !$ftp_info->ftp_password)
		{
			return new Object(1, 'msg_ftp_invalid_auth_info');
		}

		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = '127.0.0.1';
		}

		if(!$ftp_info->ftp_port || !is_numeric($ftp_info->ftp_port))
		{
			$ftp_info->ftp_port = '21';
		}

		if($ftp_info->sftp == 'Y')
		{
			if(!function_exists('ssh2_sftp'))
			{
				return new Object(-1, 'disable_sftp_support');
			}
			return $this->getSFTPPath();
		}

		if($ftp_info->ftp_pasv == 'N')
		{
			if(function_exists('ftp_connect'))
			{
				return $this->getFTPPath();
			}
			$ftp_info->ftp_pasv = "Y";
		}

		$oFTP = new ftp();
		if(!$oFTP->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port))
		{
			return new Object(1, sprintf(Context::getLang('msg_ftp_not_connected'), $ftp_info->ftp_host));
		}

		if(!$oFTP->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
		{
			return new Object(1, 'msg_ftp_invalid_auth_info');
		}

		// create temp file
		$pin = $_SERVER['REQUEST_TIME'];
		FileHandler::writeFile('./files/cache/ftp_check', $pin);

		// create path candidate
		$xe_path = _XE_PATH_;
		$path_info = array_reverse(explode('/', _XE_PATH_));
		array_pop($path_info); // remove last '/'
		$path_candidate = array();

		$temp = '';
		foreach($path_info as $path)
		{
			$temp = '/' . $path . $temp;
			$path_candidate[] = $temp;
		}

		// try
		foreach($path_candidate as $path)
		{
			// upload check file
			if(!$oFTP->ftp_put($path . 'ftp_check.html', FileHandler::getRealPath('./files/cache/ftp_check')))
			{
				continue;
			}

			// get check file
			$result = FileHandler::getRemoteResource(getNotencodedFullUrl() . 'ftp_check.html');

			// delete temp check file
			$oFTP->ftp_delete($path . 'ftp_check.html');

			// found
			if($result == $pin)
			{
				$found_path = $path;
				break;
			}
		}

		FileHandler::removeFile('./files/cache/ftp_check', $pin);

		if($found_path)
		{
			$this->add('found_path', $found_path);
		}
	}

	/**
	 * Add file list to Object after sftp connect
	 * @return void|Object
	 */
	function getSFTPList()
	{
		$ftp_info = Context::getRequestVars();
		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}
		$connection = ssh2_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
		if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
		{
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}

		$sftp = ssh2_sftp($connection);
		$curpwd = "ssh2.sftp://$sftp" . $this->pwd;
		$dh = @opendir($curpwd);
		if(!$dh)
		{
			return new Object(-1, 'msg_ftp_invalid_path');
		}
		$list = array();
		while(($file = readdir($dh)) !== FALSE)
		{
			if(is_dir($curpwd . $file))
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
		Context::loadLang(_XE_PATH_ . 'modules/autoinstall/lang');
		@set_time_limit(5);

		require_once(_XE_PATH_ . 'libs/ftp.class.php');

		$ftp_info = Context::getRequestVars();
		if(!$ftp_info->ftp_user || !$ftp_info->ftp_password)
		{
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}

		$this->pwd = $ftp_info->ftp_root_path;

		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		if(!$ftp_info->ftp_port || !is_numeric($ftp_info->ftp_port))
		{
			$ftp_info->ftp_port = "21";
		}

		if($ftp_info->sftp == 'Y')
		{
			if(!function_exists('ssh2_sftp'))
			{
				return new Object(-1, 'disable_sftp_support');
			}
			return $this->getSFTPList();
		}

		$oFtp = new ftp();
		if($oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port))
		{
			if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				$_list = $oFtp->ftp_rawlist($this->pwd);
				$oFtp->ftp_quit();
			}
			else
			{
				return new Object(-1, 'msg_ftp_invalid_auth_info');
			}
		}
		$list = array();

		if($_list)
		{
			foreach($_list as $k => $v)
			{
				$src = new stdClass();
				$src->data = $v;
				$res = Context::convertEncoding($src);
				$v = $res->data;
				if(strpos($v, 'd') === 0 || strpos($v, '<DIR>'))
				{
					$list[] = substr(strrchr($v, ' '), 1) . '/';
				}
			}
		}
		else
		{
			return new Object(-1, 'msg_ftp_no_directory');
		}
		$this->add('list', $list);
	}

	/**
	 * Parameter arrange for send to XE collect server
	 * @param string $type 'WORKING', 'INSTALL'
	 * @return string
	 */
	function getEnv($type = 'WORKING')
	{
		$skip = array(
			'ext' => array('pcre', 'json', 'hash', 'dom', 'session', 'spl', 'standard', 'date', 'ctype', 'tokenizer', 'apache2handler', 'filter', 'posix', 'reflection', 'pdo')
			, 'module' => array('addon', 'admin', 'autoinstall', 'comment', 'communication', 'counter', 'document', 'editor', 'file', 'importer', 'install', 'integration_search', 'layout', 'member', 'menu', 'message', 'module', 'opage', 'page', 'point', 'poll', 'rss', 'session', 'spamfilter', 'tag', 'trackback', 'trash', 'widget')
			, 'addon' => array('autolink', 'blogapi', 'captcha', 'counter', 'member_communication', 'member_extra_info', 'mobile', 'openid_delegation_id', 'point_level_icon', 'resize_image')
			, 'layout' => array('default')
			, 'widget' => array('content', 'language_select', 'login_info','mcontent')
			, 'widgetstyle' => array(),
		);
		$info = array();
		$db_info = Context::getDBInfo();

		$info['type'] = ($type != 'INSTALL' ? 'WORKING' : 'INSTALL');
		$info['location'] = _XE_LOCATION_;
		$info['package'] = _XE_PACKAGE_;
		$info['host'] = $db_type->default_url ? $db_type->default_url : getFullUrl();
		$info['app'] = $_SERVER['SERVER_SOFTWARE'];
		$info['xe_version'] = __XE_VERSION__;
		$info['php'] = phpversion();

		$info['db_type'] = Context::getDBType();
		$info['use_rewrite'] = $db_info->use_rewrite;
		$info['use_db_session'] = $db_info->use_db_session == 'Y' ? 'Y' : 'N';
		$info['use_ssl'] = $db_info->use_ssl;
		
		$info['phpext'] = '';
		foreach(get_loaded_extensions() as $ext)
		{
			$ext = strtolower($ext);
			if(in_array($ext, $skip['ext']))
			{
				continue;
			}
			$info['phpext'] .= '|' . $ext;
		}
		$info['phpext'] = substr($info['phpext'], 1);

		$info['module'] = '';
		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();
		if($module_list) foreach($module_list as $module)
		{
			if(in_array($module->module, $skip['module']))
			{
				continue;
			}
			$info['module'] .= '|' . $module->module;
		}
		$info['module'] = substr($info['module'], 1);

		$info['addon'] = '';
		$oAddonAdminModel = getAdminModel('addon');
		$addon_list = $oAddonAdminModel->getAddonList();
		if($addon_list) foreach($addon_list as $addon)
		{
			if(in_array($addon->addon, $skip['addon']))
			{
				continue;
			}
			$info['addon'] .= '|' . $addon->addon;
		}
		$info['addon'] = substr($info['addon'], 1);

		$info['layout'] = "";
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getDownloadedLayoutList();
		if($layout_list) foreach($layout_list as $layout)
		{
			if(in_array($layout->layout, $skip['layout']))
			{
				continue;
			}
			$info['layout'] .= '|' . $layout->layout;
		}
		$info['layout'] = substr($info['layout'], 1);

		$info['widget'] = "";
		$oWidgetModel = getModel('widget');
		$widget_list = $oWidgetModel->getDownloadedWidgetList();
		if($widget_list) foreach($widget_list as $widget)
		{
			if(in_array($widget->widget, $skip['widget']))
			{
				continue;
			}
			$info['widget'] .= '|' . $widget->widget;
		}
		$info['widget'] = substr($info['widget'], 1);

		$info['widgetstyle'] = "";
		$oWidgetModel = getModel('widget');
		$widgetstyle_list = $oWidgetModel->getDownloadedWidgetStyleList();
		if($widgetstyle_list) foreach($widgetstyle_list as $widgetstyle)
		{
			if(in_array($widgetstyle->widgetStyle, $skip['widgetstyle']))
			{
				continue;
			}
			$info['widgetstyle'] .= '|' . $widgetstyle->widgetStyle;
		}
		$info['widgetstyle'] = substr($info['widgetstyle'], 1);

		$param = '';
		foreach($info as $k => $v)
		{
			if($v)
			{
				$param .= sprintf('&%s=%s', $k, urlencode($v));
			}
		}
		$param = substr($param, 1);

		return $param;
	}

	/**
	 * Return theme info list by theme directory list
	 * @return array
	 */
	function getThemeList()
	{
		$path = _XE_PATH_ . 'themes';
		$list = FileHandler::readDir($path);

		$theme_info = array();
		if(count($list) > 0)
		{
			foreach($list as $val)
			{
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
	function getThemeInfo($theme_name, $layout_list = NULL)
	{
		if($GLOBALS['__ThemeInfo__'][$theme_name])
		{
			return $GLOBALS['__ThemeInfo__'][$theme_name];
		}

		$info_file = _XE_PATH_ . 'themes/' . $theme_name . '/conf/info.xml';
		if(!file_exists($info_file))
		{
			return;
		}

		$oXmlParser = new XmlParser();
		$_xml_obj = $oXmlParser->loadXmlFile($info_file);
		if(!$_xml_obj->theme)
		{
			return;
		}

		$xml_obj = $_xml_obj->theme;

		// 스킨이름
		$theme_info = new stdClass();
		$theme_info->name = $theme_name;
		$theme_info->title = $xml_obj->title->body;
		$thumbnail = './themes/' . $theme_name . '/thumbnail.png';
		$theme_info->thumbnail = (FileHandler::exists($thumbnail)) ? $thumbnail : NULL;
		$theme_info->version = $xml_obj->version->body;
		$date_obj = new stdClass();
		sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
		$theme_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
		$theme_info->description = $xml_obj->description->body;
		$theme_info->path = './themes/' . $theme_name . '/';

		if(!is_array($xml_obj->publisher))
		{
			$publisher_list = array();
			$publisher_list[] = $xml_obj->publisher;
		}
		else
		{
			$publisher_list = $xml_obj->publisher;
		}

		$theme_info->publisher = array();
		foreach($publisher_list as $publisher)
		{
			$publisher_obj = new stdClass();
			$publisher_obj->name = $publisher->name->body;
			$publisher_obj->email_address = $publisher->attrs->email_address;
			$publisher_obj->homepage = $publisher->attrs->link;
			$theme_info->publisher[] = $publisher_obj;
		}

		$layout = $xml_obj->layout;
		$layout_path = $layout->directory->attrs->path;
		$layout_parse = explode('/', $layout_path);
		$layout_info = new stdClass();
		switch($layout_parse[1])
		{
			case 'themes' :
					$layout_info->name = $theme_name . '|@|' . $layout_parse[count($layout_parse) - 1];
					break;

			case 'layouts' :
					$layout_info->name = $layout_parse[count($layout_parse) - 1];
					break;

		}
		$layout_info->title = $layout_parse[count($layout_parse) - 1];
		$layout_info->path = $layout_path;

		$site_info = Context::get('site_module_info');
		// check layout instance
		$is_new_layout = TRUE;
		$oLayoutModel = getModel('layout');
		$layout_info_list = array();
		$layout_list = $oLayoutModel->getLayoutList($site_info->site_srl);
		if($layout_list)
		{
			foreach($layout_list as $val)
			{
				if($val->layout == $layout_info->name)
				{
					$is_new_layout = FALSE;
					$layout_info->layout_srl = $val->layout_srl;
					break;
				}
			}
		}

		if($is_new_layout)
		{
			$site_module_info = Context::get('site_module_info');
			$args = new stdClass();
			$args->site_srl = (int) $site_module_info->site_srl;
			$args->layout_srl = getNextSequence();
			$args->layout = $layout_info->name;
			$args->title = $layout_info->title;
			$args->layout_type = "P";
			// Insert into the DB
			$oLayoutAdminController = getAdminController('layout');
			$output = $oLayoutAdminController->insertLayout($args);
			$layout_info->layout_srl = $args->layout_srl;
		}

		$theme_info->layout_info = $layout_info;

		$skin_infos = $xml_obj->skininfos;
		if(is_array($skin_infos->skininfo))
		{
			$skin_list = $skin_infos->skininfo;
		}
		else
		{
			$skin_list = array($skin_infos->skininfo);
		}

		$oModuleModel = getModel('module');
		$skins = array();
		foreach($skin_list as $val)
		{
			$skin_info = new stdClass();
			unset($skin_parse);
			$skin_parse = explode('/', $val->directory->attrs->path);
			switch($skin_parse[1])
			{
				case 'themes' :
						$is_theme = TRUE;
						$module_name = $skin_parse[count($skin_parse) - 1];
						$skin_info->name = $theme_name . '|@|' . $module_name;
						break;

				case 'modules' :
						$is_theme = FALSE;
						$module_name = $skin_parse[2];
						$skin_info->name = $skin_parse[count($skin_parse) - 1];
						break;

			}
			$skin_info->path = $val->directory->attrs->path;
			$skin_info->is_theme = $is_theme;
			$skins[$module_name] = $skin_info;

			if($is_theme)
			{
				if(!$GLOBALS['__ThemeModuleSkin__'][$module_name])
				{
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
	function getModulesSkinList()
	{
		if($GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__'])
		{
			return $GLOBALS['__ThemeModuleSkin__'];
		}
		$searched_list = FileHandler::readDir('./modules');
		sort($searched_list);

		$searched_count = count($searched_list);
		if(!$searched_count)
		{
			return;
		}

		$exceptionModule = array('editor', 'poll', 'homepage', 'textyle');

		$oModuleModel = getModel('module');
		foreach($searched_list as $val)
		{
			$skin_list = $oModuleModel->getSkins(_XE_PATH_ . 'modules/' . $val);

			if(is_array($skin_list) && count($skin_list) > 0 && !in_array($val, $exceptionModule))
			{
				if(!$GLOBALS['__ThemeModuleSkin__'][$val])
				{
					$GLOBALS['__ThemeModuleSkin__'][$val] = array();
					$moduleInfo = $oModuleModel->getModuleInfoXml($val);
					$GLOBALS['__ThemeModuleSkin__'][$val]['title'] = $moduleInfo->title;
					$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array();
				}
				$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array_merge($GLOBALS['__ThemeModuleSkin__'][$val]['skins'], $skin_list);
			}
		}
		$GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__'] = TRUE;

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
			$lang = new stdClass();
			$oModuleModel = getModel('module');
			$installed_module_list = $oModuleModel->getModulesXmlInfo();

			$this->gnbLangBuffer = '<?php $lang = new stdClass();';
			foreach($installed_module_list AS $key => $value)
			{
				$moduleActionInfo = $oModuleModel->getModuleActionXml($value->module);
				if(is_object($moduleActionInfo->menu))
				{
					foreach($moduleActionInfo->menu AS $key2 => $value2)
					{
						$lang->menu_gnb_sub[$key2] = $value2->title;
						$this->gnbLangBuffer .=sprintf('$lang->menu_gnb_sub[\'%s\'] = \'%s\';', $key2, $value2->title);
					}
				}
			}
			$this->gnbLangBuffer .= ' ?>';
			FileHandler::writeFile($cacheFile, $this->gnbLangBuffer);
		}
		else
		{
			include $cacheFile;
		}

		return $lang->menu_gnb_sub;
	}

	/**
	 * Get admin favorite list
	 * @param int $siteSrl if default site, siteSrl is zero
	 * @param bool $isGetModuleInfo
	 * @return object
	 */
	function getFavoriteList($siteSrl = 0, $isGetModuleInfo = FALSE)
	{
		$args = new stdClass();
		$args->site_srl = $siteSrl;
		$output = executeQueryArray('admin.getFavoriteList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		if(!$output->data)
		{
			return new Object();
		}

		if($isGetModuleInfo && is_array($output->data))
		{
			$oModuleModel = getModel('module');
			foreach($output->data AS $key => $value)
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
		$args = new stdClass();
		$args->site_srl = $siteSrl;
		$args->module = $module;
		$output = executeQuery('admin.getFavorite', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$returnObject = new Object();
		if($output->data)
		{
			$returnObject->add('result', TRUE);
			$returnObject->add('favoriteSrl', $output->data->admin_favorite_srl);
		}
		else
		{
			$returnObject->add('result', FALSE);
		}

		return $returnObject;
	}

	/**
	 * Return site list
	 * @return void
	 */
	function getSiteAllList()
	{
		if(Context::get('domain'))
		{
			$domain = Context::get('domain');
		}
		$siteList = $this->getAllSitesThatHaveModules($domain);
		$this->add('site_list', $siteList);
	}

	/**
	 * Returns a list of all sites that contain modules
	 * For each site domain and site_srl are retrieved
	 *
	 * @return array
	 */
	function getAllSitesThatHaveModules($domain = NULL)
	{
		$args = new stdClass();
		if($domain)
		{
			$args->domain = $domain;
		}
		$columnList = array('domain', 'site_srl');

		$siteList = array();
		$output = executeQueryArray('admin.getSiteAllList', $args, $columnList);
		if($output->toBool())
		{
			$siteList = $output->data;
		}

		$oModuleModel = getModel('module');
		foreach($siteList as $key => $value)
		{
			$args->site_srl = $value->site_srl;
			$list = $oModuleModel->getModuleSrlList($args);

			if(!is_array($list))
			{
				$list = array($list);
			}

			foreach($list as $k => $v)
			{
				if(!is_dir(_XE_PATH_ . 'modules/' . $v->module))
				{
					unset($list[$k]);
				}
			}

			if(!count($list))
			{
				unset($siteList[$key]);
			}
		}
		return $siteList;
	}

	/**
	 * Return site count
	 * @param string $date
	 * @return int
	 */
	function getSiteCountByDate($date = '')
	{
		$args = new stdClass();

		if($date)
		{
			$args->regDate = date('Ymd', strtotime($date));
		}

		$output = executeQuery('admin.getSiteCountByDate', $args);
		if(!$output->toBool())
		{
			return 0;
		}

		return $output->data->count;
	}

	function getFaviconUrl($default = true)
	{
		return $this->iconUrlCheck('favicon.ico', 'faviconSample.png', $default);
	}

	function getMobileIconUrl($default = true)
	{
		return $this->iconUrlCheck('mobicon.png', 'mobiconSample.png', $default);
	}

	function iconUrlCheck($iconname, $default_icon_name, $default)
	{
		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$file_exsit = FileHandler::readFile(_XE_PATH_ . 'files/attach/xeicon/' . $virtual_site . $iconname);
		if(!$file_exsit && $default === true)
        {
            $icon_url = './modules/admin/tpl/img/' . $default_icon_name;
        }
        elseif($file_exsit)
		{
			$default_url = Context::getDefaultUrl();
			$icon_url = $default_url . 'files/attach/xeicon/' . $virtual_site . $iconname;
		}
		return $icon_url;
	}

}
/* End of file admin.admin.model.php */
/* Location: ./modules/admin/admin.admin.model.php */
