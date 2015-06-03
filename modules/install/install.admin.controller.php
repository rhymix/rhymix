<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  installAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of the install module
 */
class installAdminController extends install
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Install the module
	 */
	function procInstallAdminInstall()
	{
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oInstallController = getController('install');
		$oInstallController->installModule($module_name, './modules/'.$module_name);

		$this->setMessage('success_installed');
	}

	/**
	 * @brief Upate the module
	 */
	function procInstallAdminUpdate()
	{
		@set_time_limit(0);
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oModule = getModule($module_name, 'class');
		if($oModule) $output = $oModule->moduleUpdate();
		else $output = new Object(-1, 'invalid_request');

		return $output;
	}

	/**
	 * @brief Change settings
	 */
	function procInstallAdminSaveTimeZone()
	{
		$db_info = Context::getDBInfo();

		$admin_ip_list = Context::get('admin_ip_list');

		if($admin_ip_list)
		{
			$admin_ip_list = preg_replace("/[\r|\n|\r\n]+/",",",$admin_ip_list);
			$admin_ip_list = preg_replace("/\s+/","",$admin_ip_list);
			if(preg_match('/(<\?|<\?php|\?>)/xsm', $admin_ip_list))
			{
				$admin_ip_list = '';
			}
			$admin_ip_list .= ',127.0.0.1,' . $_SERVER['REMOTE_ADDR'];
			$admin_ip_list = explode(',',trim($admin_ip_list, ','));
			$admin_ip_list = array_unique($admin_ip_list);
			if(!IpFilter::validate($admin_ip_list)) {
				return new Object(-1, 'msg_invalid_ip');
			}
		}
		
		$default_url = Context::get('default_url');
		if($default_url && strncasecmp('http://', $default_url, 7) !== 0 && strncasecmp('https://', $default_url, 8) !== 0) $default_url = 'http://'.$default_url;
		if($default_url && substr($default_url, -1) !== '/') $default_url = $default_url.'/';

		/* convert NON Alphabet URL to punycode URL - Alphabet URL will not be changed */
		require_once(_XE_PATH_ . 'libs/idna_convert/idna_convert.class.php');
		$IDN = new idna_convert(array('idn_version' => 2008));
		$default_url = $IDN->encode($default_url);

		$use_ssl = Context::get('use_ssl');
		if(!$use_ssl) $use_ssl = 'none';

		$http_port = Context::get('http_port');
		$https_port = Context::get('https_port');

		$use_rewrite = Context::get('use_rewrite');
		if($use_rewrite!='Y') $use_rewrite = 'N';

		$use_sso = Context::get('use_sso');
		if($use_sso !='Y') $use_sso = 'N';

		$use_db_session = Context::get('use_db_session');
		if($use_db_session!='Y') $use_db_session = 'N';

		$qmail_compatibility = Context::get('qmail_compatibility');
		if($qmail_compatibility!='Y') $qmail_compatibility = 'N';

		$use_html5 = Context::get('use_html5');
		if(!$use_html5) $use_html5 = 'N';

		$db_info->default_url = $default_url;
		$db_info->qmail_compatibility = $qmail_compatibility;
		$db_info->use_db_session = $use_db_session;
		$db_info->use_rewrite = $use_rewrite;
		$db_info->use_sso = $use_sso;
		$db_info->use_ssl = $use_ssl;
		$db_info->use_html5 = $use_html5;
		$db_info->admin_ip_list = $admin_ip_list;

		if($http_port) $db_info->http_port = (int) $http_port;
		else if($db_info->http_port) unset($db_info->http_port);

		if($https_port) $db_info->https_port = (int) $https_port;
		else if($db_info->https_port) unset($db_info->https_port);

		unset($db_info->lang_type);

		$oInstallController = getController('install');
		if(!$oInstallController->makeConfigFile())
		{
			return new Object(-1, 'msg_invalid_request');
		}
		else
		{
			Context::setDBInfo($db_info);
			if($default_url)
			{
				$site_args = new stdClass;
				$site_args->site_srl = 0;
				$site_args->domain = $default_url;
				$oModuleController = getController('module');
				$oModuleController->updateSite($site_args);
			}
			$this->setRedirectUrl(Context::get('error_return_url'));
		}
	}

	function procInstallAdminUpdateIndexModule()
	{
		if(!Context::get('index_module_srl') || !Context::get('menu_item_srl'))
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$site_args = new stdClass();
		$site_args->site_srl = 0;
		$site_args->index_module_srl = Context::get('index_module_srl');
		$oModuleController = getController('module');
		$oModuleController->updateSite($site_args);

		// get menu item info
		$menuItemSrl = Context::get('menu_item_srl');
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuItemInfo($menuItemSrl);

		// update homeSitemap.php cache file
		$oMenuAdminController = getAdminController('menu');
		$homeMenuCacheFile = $oMenuAdminController->getHomeMenuCacheFile();
		if(file_exists($homeMenuCacheFile))
		{
			include($homeMenuCacheFile);
		}

		if(!$homeMenuSrl || $homeMenuSrl != $output->menu_srl)
		{
			$oMenuAdminController->makeHomemenuCacheFile($output->menu_srl);
		}

		$this->setMessage('success_updated');
	}

	function procInstallAdminRemoveFTPInfo()
	{
		$ftp_config_file = Context::getFTPConfigFile();
		if(file_exists($ftp_config_file)) unlink($ftp_config_file);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);
		$this->setMessage('success_deleted');
	}

	function procInstallAdminSaveFTPInfo()
	{
		$ftp_info = Context::getFTPInfo();
		$ftp_info->ftp_user = Context::get('ftp_user');
		$ftp_info->ftp_port = Context::get('ftp_port');
		$ftp_info->ftp_host = Context::get('ftp_host');
		$ftp_info->ftp_pasv = Context::get('ftp_pasv');
		if(!$ftp_info->ftp_pasv) $ftp_info->ftp_pasv = "N";
		$ftp_info->sftp = Context::get('sftp');

		$ftp_root_path = Context::get('ftp_root_path');
		if(substr($ftp_root_path, strlen($ftp_root_path)-1) == "/")
		{
			$ftp_info->ftp_root_path = $ftp_root_path;
		}
		else
		{
			$ftp_info->ftp_root_path = $ftp_root_path.'/';
		}

		if(ini_get('safe_mode'))
		{
			$ftp_info->ftp_password = Context::get('ftp_password');
		}

		$buff = '<?php if(!defined("__XE__")) exit();'."\n\$ftp_info = new stdClass;\n";
		foreach($ftp_info as $key => $val)
		{
			if(!$val) continue;
			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|file_get_contents|file_put_contents|exec|proc_open|popen|passthru|show_source|phpinfo|system|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $val)))
			{
				continue;
			}
			$buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
		}
		$buff .= "?>";
		$config_file = Context::getFTPConfigFile();
		FileHandler::WriteFile($config_file, $buff);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);

		$this->setMessage('success_updated');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigFtp');
		$this->setRedirectUrl($returnUrl);
	}

	function procInstallAdminConfig()
	{
		$use_mobile_view = Context::get('use_mobile_view');
		if($use_mobile_view!='Y') $use_mobile_view = 'N';

		$time_zone = Context::get('time_zone');

		$db_info = Context::getDBInfo();
		$db_info->use_mobile_view = $use_mobile_view;
		$db_info->time_zone = $time_zone;

		unset($db_info->lang_type);
		Context::setDBInfo($db_info);
		$oInstallController = getController('install');
		if(!$oInstallController->makeConfigFile())
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$site_args = new stdClass();
		$site_args->site_srl = 0;
		$site_args->index_module_srl = Context::get('index_module_srl');//
		$site_args->default_language = Context::get('change_lang_type');//
		$oModuleController = getController('module');
		$oModuleController->updateSite($site_args);

		//언어 선택
		$selected_lang = Context::get('selected_lang');
		$this->saveLangSelected($selected_lang);

		//save icon images
		$deleteFavicon = Context::get('is_delete_favicon');
		$deleteMobicon = Context::get('is_delete_mobicon');

		$this->updateIcon('favicon.ico',$deleteFavicon);
		$this->updateIcon('mobicon.png',$deleteMobicon);

		//모듈 설정 저장(썸네일, 풋터스크립트)
		$config = new stdClass();
		$config->thumbnail_type = Context::get('thumbnail_type');
		$config->htmlFooter = Context::get('htmlFooter');
		$config->siteTitle = Context::get('site_title');
		$this->setModulesConfig($config);

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	public function procInstallAdminConfigIconUpload() {
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("after_upload_config_image.html");

		$favicon = Context::get('favicon');
		$mobicon = Context::get('mobicon');
		if(!$favicon && !$mobicon) {
			Context::set('msg', Context::getLang("msg_invalid_format"));
			return;
		}
		if($favicon) {
			$name = 'favicon';
			$tmpFileName = $this->saveIconTmp($favicon,'favicon.ico');
		} else {
			$name = 'mobicon';
			$tmpFileName = $this->saveIconTmp($mobicon,'mobicon.png');
		}

		Context::set('name', $name);
		Context::set('tmpFileName', $tmpFileName.'?'.$_SERVER['REQUEST_TIME']);
	}

	/**
	 * @brief Supported languages (was procInstallAdminSaveLangSelected)
	 */
	function saveLangSelected($selected_lang)
	{
		$langs = $selected_lang;

		$lang_supported = Context::loadLangSupported();
		$buff = null;
		for($i=0;$i<count($langs);$i++)
		{
			$buff .= sprintf("%s,%s\n", $langs[$i], $lang_supported[$langs[$i]]);

		}
		FileHandler::writeFile(_XE_PATH_.'files/config/lang_selected.info', trim($buff));
		//$this->setMessage('success_updated');
	}

	/* 썸내일 보여주기 방식 변경.*/
	function setModulesConfig($config)
	{
		$args = new stdClass();

		if(!$config->thumbnail_type || $config->thumbnail_type != 'ratio' ) $args->thumbnail_type = 'crop';
		else $args->thumbnail_type = 'ratio';

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('document',$args);

		unset($args);

		$args = new stdClass;
		$args->htmlFooter = $config->htmlFooter;
		$args->siteTitle = $config->siteTitle;
		$oModuleController->updateModuleConfig('module',$args);

		return $output;
	}

	private function saveIconTmp($icon, $iconname)
	{

		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$target_file = $icon['tmp_name'];
		$type = $icon['type'];
		$relative_filename = 'files/attach/xeicon/'.$virtual_site.'tmp/'.$iconname;
		$target_filename = _XE_PATH_.$relative_filename;

		list($width, $height, $type_no, $attrs) = @getimagesize($target_file);
		if($iconname == 'favicon.ico')
		{
			if(!preg_match('/^.*(x-icon|\.icon)$/i',$type)) {
				Context::set('msg', '*.ico '.Context::getLang('msg_possible_only_file'));
				return;
			}
		}
		else if($iconname == 'mobicon.png')
		{
			if(!preg_match('/^.*(png).*$/',$type)) {
				Context::set('msg', '*.png '.Context::getLang('msg_possible_only_file'));
				return;
			}
			if(!(($height == '57' && $width == '57') || ($height == '114' && $width == '114'))) {
				Context::set('msg', Context::getLang('msg_invalid_format').' (size : 57x57, 114x114)');
				return;
			}
		}
		else
		{
			Context::set('msg', Context::getLang('msg_invalid_format'));
			return;
		}

		$fitHeight = $fitWidth = $height;
		//FileHandler::createImageFile($target_file, $target_filename, $fitHeight, $fitWidth, $ext);
		FileHandler::copyFile($target_file, $target_filename);
		return $relative_filename;
	}

	private function updateIcon($iconname, $deleteIcon = false) {

		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$image_filepath = _XE_PATH_.'files/attach/xeicon/' . $virtual_site;

		if($deleteIcon) {
			FileHandler::removeFile($image_filepath.$iconname);
			return;
		}

		$tmpicon_filepath = $image_filepath.'tmp/'.$iconname;
		$icon_filepath = $image_filepath.$iconname;
		if(file_exists($tmpicon_filepath))
		{
			FileHandler::moveFile($tmpicon_filepath, $icon_filepath);
		}

		FileHandler::removeFile($tmpicon_filepath);
	}


}
/* End of file install.admin.controller.php */
/* Location: ./modules/install/install.admin.controller.php */
