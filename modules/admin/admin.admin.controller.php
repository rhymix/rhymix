<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminController class
 * admin controller class of admin module
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class adminAdminController extends admin
{

	/**
	 * initialization
	 * @return void
	 */
	function init()
	{
		// forbit access if the user is not an administrator
		$oMemberModel = getModel('member');
		$logged_info = $oMemberModel->getLoggedInfo();
		if($logged_info->is_admin != 'Y')
		{
			return $this->stop("admin.msg_is_not_administrator");
		}
	}

	/**
	 * Admin menu reset
	 * @return void
	 */
	function procAdminMenuReset()
	{
		$menuSrl = Context::get('menu_srl');
		if(!$menuSrl)
		{
			return $this->stop('msg_invalid_request');
		}

		$oMenuAdminController = getAdminController('menu');
		$output = $oMenuAdminController->deleteMenu($menuSrl);
		if(!$output->toBool())
		{
			return $output;
		}

		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'files/cache/menu/admin_lang/');

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Regenerate all cache files
	 * @return void
	 */
	function procAdminRecompileCacheFile()
	{
		// rename cache dir
		Rhymix\Framework\Storage::move(\RX_BASEDIR . 'files/cache', \RX_BASEDIR . 'files/cache_' . time());
		Rhymix\Framework\Storage::createDirectory(\RX_BASEDIR . 'files/cache');

		// remove module extend cache
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/config/module_extend.php');

		// remove debug files
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_debug_message.php');
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_debug_db_query.php');
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_db_slow_query.php');

		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();

		// call recompileCache for each module
		foreach($module_list as $module)
		{
			$oModule = NULL;
			$oModule = getClass($module->module);
			if(method_exists($oModule, 'recompileCache'))
			{
				$oModule->recompileCache();
			}
		}

		// remove object cache
		if (!in_array(Rhymix\Framework\Cache::getDriverName(), array('file', 'sqlite', 'dummy')))
		{
			Rhymix\Framework\Cache::clearAll();
		}

		// remove old cache dir
		$tmp_cache_list = FileHandler::readDir(\RX_BASEDIR . 'files', '/^(cache_[0-9]+)/');
		if($tmp_cache_list)
		{
			foreach($tmp_cache_list as $tmp_dir)
			{
				if(strval($tmp_dir) !== '')
				{
					$tmp_dir = \RX_BASEDIR . 'files/' . strval($tmp_dir);
					if (!Rhymix\Framework\Storage::isDirectory($tmp_dir))
					{
						continue;
					}
					
					// If possible, use system command to speed up recursive deletion
					if (function_exists('exec') && !preg_match('/(?<!_)exec/', ini_get('disable_functions')))
					{
						if (strncasecmp(\PHP_OS, 'win', 3) == 0)
						{
							@exec('rmdir /S /Q ' . escapeshellarg($tmp_dir));
						}
						else
						{
							@exec('rm -rf ' . escapeshellarg($tmp_dir));
						}
					}
					
					// If the directory still exists, delete using PHP.
					Rhymix\Framework\Storage::deleteDirectory($tmp_dir);
				}
			}
		}

		// remove duplicate indexes (only for CUBRID)
		$db_type = Context::getDBType();
		if($db_type == 'cubrid')
		{
			$db = DB::getInstance();
			$db->deleteDuplicateIndexes();
		}

		// check autoinstall packages
		$oAutoinstallAdminController = getAdminController('autoinstall');
		$oAutoinstallAdminController->checkInstalled();

		$this->setMessage('success_updated');
	}

	/**
	 * Logout
	 * @return void
	 */
	function procAdminLogout()
	{
		$oMemberController = getController('member');
		$oMemberController->procMemberLogout();

		header('Location: ' . getNotEncodedUrl(''));
	}

	public function procAdminInsertDefaultDesignInfo()
	{
		$vars = Context::getRequestVars();
		if(!$vars->site_srl)
		{
			$vars->site_srl = 0;
		}

		// create a DesignInfo file
		$output = $this->updateDefaultDesignInfo($vars);
		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	public function updateDefaultDesignInfo($vars)
	{
		$siteDesignPath = _XE_PATH_ . 'files/site_design/';

		$vars->module_skin = json_decode($vars->module_skin);

		if(!is_dir($siteDesignPath))
		{
			FileHandler::makeDir($siteDesignPath);
		}

		$siteDesignFile = _XE_PATH_ . 'files/site_design/design_' . $vars->site_srl . '.php';

		$layoutTarget = 'layout_srl';
		$skinTarget = 'skin';

		if($vars->target_type == 'M')
		{
			$layoutTarget = 'mlayout_srl';
			$skinTarget = 'mskin';
		}

		if(is_readable($siteDesignFile))
		{
			include($siteDesignFile);
		}
		else
		{
			$designInfo = new stdClass();
		}

		$layoutSrl = (!$vars->layout_srl) ? 0 : $vars->layout_srl;

		$designInfo->{$layoutTarget} = $layoutSrl;

		foreach($vars->module_skin as $moduleName => $skinName)
		{
			if($moduleName == 'ARTICLE')
			{
				$moduleName = 'page';
			}

			if(!isset($designInfo->module->{$moduleName})) $designInfo->module->{$moduleName} = new stdClass();
			$designInfo->module->{$moduleName}->{$skinTarget} = $skinName;
		}

		$this->makeDefaultDesignFile($designInfo, $vars->site_srl);

		return new Object();
	}

	function makeDefaultDesignFile($designInfo, $site_srl = 0)
	{
		$buff = array();
		$buff[] = '<?php if(!defined("__XE__")) exit();';
		$buff[] = '$designInfo = new stdClass;';

		if($designInfo->layout_srl)
		{
			$buff[] = sprintf('$designInfo->layout_srl = %s; ', $designInfo->layout_srl);
		}

		if($designInfo->mlayout_srl)
		{
			$buff[] = sprintf('$designInfo->mlayout_srl = %s;', $designInfo->mlayout_srl);
		}

		$buff[] = '$designInfo->module = new stdClass;';

		foreach($designInfo->module as $moduleName => $skinInfo)
		{
			$buff[] = sprintf('$designInfo->module->%s = new stdClass;', $moduleName);
			foreach($skinInfo as $target => $skinName)
			{
				$buff[] = sprintf('$designInfo->module->%s->%s = \'%s\';', $moduleName, $target, $skinName);
			}
		}

		$siteDesignFile = _XE_PATH_ . 'files/site_design/design_' . $site_srl . '.php';
		FileHandler::writeFile($siteDesignFile, implode(PHP_EOL, $buff));
	}

	/**
	 * Toggle favorite
	 * @return void
	 */
	function procAdminToggleFavorite()
	{
		$siteSrl = Context::get('site_srl');
		$moduleName = Context::get('module_name');

		// check favorite exists
		$oModel = getAdminModel('admin');
		$output = $oModel->isExistsFavorite($siteSrl, $moduleName);
		if(!$output->toBool())
		{
			return $output;
		}

		// if exists, delete favorite
		if($output->get('result'))
		{
			$favoriteSrl = $output->get('favoriteSrl');
			$output = $this->_deleteFavorite($favoriteSrl);
			$result = 'off';
		}
		// if not exists, insert favorite
		else
		{
			$output = $this->_insertFavorite($siteSrl, $moduleName);
			$result = 'on';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('result', $result);

		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	/**
	 * Cleanning favorite
	 * @return Object
	 */
	function cleanFavorite()
	{
		$oModel = getAdminModel('admin');
		$output = $oModel->getFavoriteList();
		if(!$output->toBool())
		{
			return $output;
		}

		$favoriteList = $output->get('favoriteList');
		if(!$favoriteList)
		{
			return new Object();
		}

		$deleteTargets = array();
		foreach($favoriteList as $favorite)
		{
			if($favorite->type == 'module')
			{
				$modulePath = _XE_PATH_ . 'modules/' . $favorite->module;
				if(!is_dir($modulePath))
				{
					$deleteTargets[] = $favorite->admin_favorite_srl;
				}
			}
		}

		if(!count($deleteTargets))
		{
			return new Object();
		}

		$args = new stdClass();
		$args->admin_favorite_srls = $deleteTargets;
		$output = executeQuery('admin.deleteFavorites', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		return new Object();
	}

	/**
	 * Enviroment gathering agreement
	 * @return void
	 */
	function procAdminEnviromentGatheringAgreement()
	{
		$isAgree = Context::get('is_agree');
		if($isAgree == 'true')
		{
			$_SESSION['enviroment_gather'] = 'Y';
		}
		else
		{
			$_SESSION['enviroment_gather'] = 'N';
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * Admin config update
	 * @return void
	 */
	function procAdminUpdateConfig()
	{
		$adminTitle = Context::get('adminTitle');
		$file = $_FILES['adminLogo'];

		$oModuleModel = getModel('module');
		$oAdminConfig = $oModuleModel->getModuleConfig('admin');

		if(!is_object($oAdminConfig))
		{
			$oAdminConfig = new stdClass();
		}

		if($file['tmp_name'])
		{
			$target_path = 'files/attach/images/admin/';
			FileHandler::makeDir($target_path);

			// Get file information
			list($width, $height, $type, $attrs) = @getimagesize($file['tmp_name']);
			if($type == 3)
			{
				$ext = 'png';
			}
			elseif($type == 2)
			{
				$ext = 'jpg';
			}
			else
			{
				$ext = 'gif';
			}

			$target_filename = sprintf('%s%s.%s.%s', $target_path, 'adminLogo', date('YmdHis'), $ext);
			@move_uploaded_file($file['tmp_name'], $target_filename);

			$oAdminConfig->adminLogo = $target_filename;
		}
		if($adminTitle)
		{
			$oAdminConfig->adminTitle = strip_tags($adminTitle);
		}
		else
		{
			unset($oAdminConfig->adminTitle);
		}

		if($oAdminConfig)
		{
			$oModuleController = getController('module');
			$oModuleController->insertModuleConfig('admin', $oAdminConfig);
		}

		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Admin logo delete
	 * @return void
	 */
	function procAdminDeleteLogo()
	{
		$oModuleModel = getModel('module');
		$oAdminConfig = $oModuleModel->getModuleConfig('admin');

		Rhymix\Framework\Storage::delete(_XE_PATH_ . $oAdminConfig->adminLogo);
		unset($oAdminConfig->adminLogo);

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('admin', $oAdminConfig);

		$this->setMessage('success_deleted', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Insert favorite
	 * @return object query result
	 */
	function _insertFavorite($siteSrl, $module, $type = 'module')
	{
		$args = new stdClass();
		$args->adminFavoriteSrl = getNextSequence();
		$args->site_srl = $siteSrl;
		$args->module = $module;
		$args->type = $type;
		$output = executeQuery('admin.insertFavorite', $args);
		return $output;
	}

	/**
	 * Delete favorite
	 * @return object query result
	 */
	function _deleteFavorite($favoriteSrl)
	{
		$args = new stdClass();
		$args->admin_favorite_srl = $favoriteSrl;
		$output = executeQuery('admin.deleteFavorite', $args);
		return $output;
	}

	/**
	 * Delete all favorite
	 * @return object query result
	 */
	function _deleteAllFavorite()
	{
		$args = new stdClass;
		$output = executeQuery('admin.deleteAllFavorite', $args);
		return $output;
	}

	/**
	 * Remove admin icon
	 * @return object|void
	 */
	function procAdminRemoveIcons()
	{
		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$iconname = Context::get('iconname');
		$file_exist = FileHandler::readFile(_XE_PATH_ . 'files/attach/xeicon/' . $virtual_site . $iconname);
		if($file_exist)
		{
			@Rhymix\Framework\Storage::delete(_XE_PATH_ . 'files/attach/xeicon/' . $virtual_site . $iconname);
		}
		else
		{
			return new Object(-1, 'fail_to_delete');
		}
		$this->setMessage('success_deleted');
	}
	
	/**
	 * Update general configuration.
	 */
	function procAdminUpdateConfigGeneral()
	{
		$oModuleController = getController('module');
		$vars = Context::getRequestVars();
		
		// Site title and HTML footer
		$args = new stdClass;
		$args->siteTitle = $vars->site_title;
		$args->siteSubtitle = $vars->site_subtitle;
		$args->htmlFooter = $vars->html_footer;
		$oModuleController->updateModuleConfig('module', $args);
		
		// Index module
		$site_args = new stdClass();
		$site_args->site_srl = 0;
		$site_args->index_module_srl = $vars->index_module_srl;
		$site_args->default_language = $vars->default_lang;
		$oModuleController->updateSite($site_args);
		
		// Default and enabled languages
		$enabled_lang = $vars->enabled_lang;
		if (!in_array($vars->default_lang, $enabled_lang))
		{
			$enabled_lang[] = $vars->default_lang;
		}
		Rhymix\Framework\Config::set('locale.default_lang', $vars->default_lang);
		Rhymix\Framework\Config::set('locale.enabled_lang', array_values($enabled_lang));
		Rhymix\Framework\Config::set('locale.auto_select_lang', $vars->auto_select_lang === 'Y');
		
		// Default time zone
		Rhymix\Framework\Config::set('locale.default_timezone', $vars->default_timezone);
		
		// Mobile view
		Rhymix\Framework\Config::set('mobile.enabled', $vars->use_mobile_view === 'Y');
		Rhymix\Framework\Config::set('mobile.tablets', $vars->tablets_as_mobile === 'Y');
		if (Rhymix\Framework\Config::get('use_mobile_view') !== null)
		{
			Rhymix\Framework\Config::set('use_mobile_view', $vars->use_mobile_view === 'Y');
		}
		
		// Favicon and mobicon
		$this->_saveFavicon('favicon.ico', $vars->is_delete_favicon);
		$this->_saveFavicon('mobicon.png', $vars->is_delete_mobicon);
		$this->_saveDefaultImage($vars->is_delete_default_image);
		
		// Save
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigGeneral'));
	}
	
	/**
	 * Update security configuration.
	 */
	function procAdminUpdateSecurity()
	{
		$vars = Context::getRequestVars();
		
		// iframe filter
		$iframe_whitelist = $vars->mediafilter_iframe;
		$iframe_whitelist = array_filter(array_map('trim', preg_split('/[\r\n]/', $iframe_whitelist)), function($item) {
			return $item !== '';
		});
		$iframe_whitelist = array_unique(array_map(function($item) {
			return Rhymix\Framework\Filters\MediaFilter::formatPrefix($item);
		}, $iframe_whitelist));
		natcasesort($iframe_whitelist);
		Rhymix\Framework\Config::set('mediafilter.iframe', array_values($iframe_whitelist));
		
		// object filter
		$object_whitelist = $vars->mediafilter_object;
		$object_whitelist = array_filter(array_map('trim', preg_split('/[\r\n]/', $object_whitelist)), function($item) {
			return $item !== '';
		});
		$object_whitelist = array_unique(array_map(function($item) {
			return Rhymix\Framework\Filters\MediaFilter::formatPrefix($item);
		}, $object_whitelist));
		natcasesort($object_whitelist);
		Rhymix\Framework\Config::set('mediafilter.object', array_values($object_whitelist));
		
		// Remove old embed filter
		$config = Rhymix\Framework\Config::getAll();
		unset($config['embedfilter']);
		Rhymix\Framework\Config::setAll($config);
		
		// Admin IP access control
		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->admin_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));
		if (!Rhymix\Framework\Filters\IpFilter::validateRanges($allowed_ip)) {
			return new Object(-1, 'msg_invalid_ip');
		}
		
		$denied_ip = array_map('trim', preg_split('/[\r\n]/', $vars->admin_denied_ip));
		$denied_ip = array_unique(array_filter($denied_ip, function($item) {
			return $item !== '';
		}));
		if (!Rhymix\Framework\Filters\IpFilter::validateRanges($denied_ip)) {
			return new Object(-1, 'msg_invalid_ip');
		}
		
		$oMemberAdminModel = getAdminModel('member');
		if (!$oMemberAdminModel->getMemberAdminIPCheck($allowed_ip, $denied_ip))
		{
			return new Object(-1, 'msg_current_ip_will_be_denied');
		}
		
		Rhymix\Framework\Config::set('admin.allow', array_values($allowed_ip));
		Rhymix\Framework\Config::set('admin.deny', array_values($denied_ip));
		
		// Save
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSecurity'));
	}
	
	/**
	 * Update advanced configuration.
	 */
	function procAdminUpdateAdvanced()
	{
		$vars = Context::getRequestVars();
		
		// Default URL
		$default_url = rtrim(trim($vars->default_url), '/\\') . '/';
		if (!filter_var($default_url, FILTER_VALIDATE_URL) || !preg_match('@^https?://@', $default_url))
		{
			return new Object(-1, 'msg_invalid_default_url');
		}
		if (parse_url($default_url, PHP_URL_PATH) !== RX_BASEURL)
		{
			return new Object(-1, 'msg_invalid_default_url');
		}
		
		// SSL and ports
		if ($vars->http_port == 80) $vars->http_port = null;
		if ($vars->https_port == 443) $vars->https_port = null;
		$use_ssl = $vars->use_ssl ?: 'none';
		
		// Check if all URL configuration is consistent
		if ($use_ssl === 'always' && !preg_match('@^https://@', $default_url))
		{
			return new Object(-1, 'msg_default_url_ssl_inconsistent');
		}
		if ($vars->http_port && preg_match('@^http://@', $default_url) && parse_url($default_url, PHP_URL_PORT) != $vars->http_port)
		{
			return new Object(-1, 'msg_default_url_http_port_inconsistent');
		}
		if ($vars->https_port && preg_match('@^https://@', $default_url) && parse_url($default_url, PHP_URL_PORT) != $vars->https_port)
		{
			return new Object(-1, 'msg_default_url_https_port_inconsistent');
		}
		
		// Set all URL configuration
		Rhymix\Framework\Config::set('url.default', $default_url);
		Rhymix\Framework\Config::set('url.http_port', $vars->http_port ?: null);
		Rhymix\Framework\Config::set('url.https_port', $vars->https_port ?: null);
		Rhymix\Framework\Config::set('url.ssl', $use_ssl);
		getController('module')->updateSite((object)array(
			'site_srl' => 0,
			'domain' => preg_replace('@^https?://@', '', $default_url),
		));
		
		// Object cache
		if ($vars->object_cache_type)
		{
			if ($vars->object_cache_type === 'memcached' || $vars->object_cache_type === 'redis')
			{
				$cache_servers = array($vars->object_cache_type . '://' . $vars->object_cache_host . ':' . intval($vars->object_cache_port));
				if ($vars->object_cache_type === 'redis')
				{
					$cache_servers[0] .= '/' . intval($vars->object_cache_dbnum);
				}
			}
			else
			{
				$cache_servers = array();
			}
			if (!Rhymix\Framework\Cache::getDriverInstance($vars->object_cache_type, $cache_servers))
			{
				return new Object(-1, 'msg_cache_handler_not_supported');
			}
			Rhymix\Framework\Config::set('cache', array(
				'type' => $vars->object_cache_type,
				'ttl' => intval($vars->cache_default_ttl ?: 86400),
				'servers' => $cache_servers,
			));
		}
		else
		{
			Rhymix\Framework\Config::set('cache', array());
		}
		
		// Thumbnail settings
		$args = new stdClass;
		$args->thumbnail_type = $vars->thumbnail_type === 'ratio' ? 'ratio' : 'crop';
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('document', $args);
		
		// Other settings
		Rhymix\Framework\Config::set('use_rewrite', $vars->use_rewrite === 'Y');
		Rhymix\Framework\Config::set('use_sso', $vars->use_sso === 'Y');
		Rhymix\Framework\Config::set('session.delay', $vars->delay_session === 'Y');
		Rhymix\Framework\Config::set('session.use_db', $vars->use_db_session === 'Y');
		Rhymix\Framework\Config::set('view.minify_scripts', $vars->minify_scripts ?: 'common');
		Rhymix\Framework\Config::set('view.concat_scripts', $vars->concat_scripts ?: 'none');
		Rhymix\Framework\Config::set('view.server_push', $vars->use_server_push === 'Y');
		Rhymix\Framework\Config::set('view.gzip', $vars->use_gzip === 'Y');
		
		// Save
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigAdvanced'));
	}
	
	/**
	 * Update debug configuration.
	 */
	function procAdminUpdateDebug()
	{
		$vars = Context::getRequestVars();
		
		// Debug settings
		Rhymix\Framework\Config::set('debug.enabled', $vars->debug_enabled === 'Y');
		Rhymix\Framework\Config::set('debug.log_slow_queries', max(0, floatval($vars->debug_log_slow_queries)));
		Rhymix\Framework\Config::set('debug.log_slow_triggers', max(0, floatval($vars->debug_log_slow_triggers)));
		Rhymix\Framework\Config::set('debug.log_slow_widgets', max(0, floatval($vars->debug_log_slow_widgets)));
		Rhymix\Framework\Config::set('debug.display_type', strval($vars->debug_display_type) ?: 'comment');
		Rhymix\Framework\Config::set('debug.display_to', strval($vars->debug_display_to) ?: 'admin');
		
		// Debug content
		$debug_content = array_values($vars->debug_display_content);
		Rhymix\Framework\Config::set('debug.display_content', $debug_content);
		
		// Log filename
		$log_filename = strval($vars->debug_log_filename);
		$log_filename_today = str_replace(array('YYYY', 'YY', 'MM', 'DD'), array(
			getInternalDateTime(RX_TIME, 'Y'),
			getInternalDateTime(RX_TIME, 'y'),
			getInternalDateTime(RX_TIME, 'm'),
			getInternalDateTime(RX_TIME, 'd'),
		), $log_filename);
		if (file_exists(RX_BASEDIR . $log_filename_today) && !is_writable(RX_BASEDIR . $log_filename_today))
		{
			return new Object(-1, 'msg_debug_log_filename_not_writable');
		}
		if (!file_exists(dirname(RX_BASEDIR . $log_filename)) && !FileHandler::makeDir(dirname(RX_BASEDIR . $log_filename)))
		{
			return new Object(-1, 'msg_debug_log_filename_not_writable');
		}
		if (!is_writable(dirname(RX_BASEDIR . $log_filename)))
		{
			return new Object(-1, 'msg_debug_log_filename_not_writable');
		}
		Rhymix\Framework\Config::set('debug.log_filename', $log_filename);
		
		// IP access control
		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->debug_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));
		if (!Rhymix\Framework\Filters\IpFilter::validateRanges($allowed_ip)) {
			return new Object(-1, 'msg_invalid_ip');
		}
		Rhymix\Framework\Config::set('debug.allow', array_values($allowed_ip));
		
		// Save
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigDebug'));
	}
	
	/**
	 * Update SEO configuration.
	 */
	function procAdminUpdateSEO()
	{
		$vars = Context::getRequestVars();
		
		$args = new stdClass;
		$args->meta_keywords = $vars->site_meta_keywords ? implode(', ', array_map('trim', explode(',', $vars->site_meta_keywords))) : '';
		$args->meta_description = trim(utf8_normalize_spaces($vars->site_meta_description));
		$oModuleController = getController('module');
		$oModuleController->updateModuleConfig('module', $args);
		
		Rhymix\Framework\Config::set('seo.main_title', trim(utf8_normalize_spaces($vars->seo_main_title)));
		Rhymix\Framework\Config::set('seo.subpage_title', trim(utf8_normalize_spaces($vars->seo_subpage_title)));
		Rhymix\Framework\Config::set('seo.document_title', trim(utf8_normalize_spaces($vars->seo_document_title)));
		
		Rhymix\Framework\Config::set('seo.og_enabled', $vars->og_enabled === 'Y');
		Rhymix\Framework\Config::set('seo.og_extract_description', $vars->og_extract_description === 'Y');
		Rhymix\Framework\Config::set('seo.og_extract_images', $vars->og_extract_images === 'Y');
		Rhymix\Framework\Config::set('seo.og_use_timestamps', $vars->og_use_timestamps === 'Y');
		
		// Save
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSEO'));
	}
	
	/**
	 * Update sitelock configuration.
	 */
	function procAdminUpdateSitelock()
	{
		$vars = Context::gets('sitelock_locked', 'sitelock_allowed_ip', 'sitelock_title', 'sitelock_message');
		
		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->sitelock_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));
		
		if ($vars->sitelock_locked === 'Y')
		{
			if (!Rhymix\Framework\Filters\IpFilter::inRanges('127.0.0.1', $allowed_ip))
			{
				array_unshift($allowed_ip, '127.0.0.1');
			}
			if (!Rhymix\Framework\Filters\IpFilter::inRanges(RX_CLIENT_IP, $allowed_ip))
			{
				array_unshift($allowed_ip, RX_CLIENT_IP);
			}
		}
		
		if (!Rhymix\Framework\Filters\IpFilter::validateRanges($allowed_ip))
		{
			return new Object(-1, 'msg_invalid_ip');
		}
		
		Rhymix\Framework\Config::set('lock.locked', $vars->sitelock_locked === 'Y');
		Rhymix\Framework\Config::set('lock.title', trim($vars->sitelock_title));
		Rhymix\Framework\Config::set('lock.message', trim($vars->sitelock_message));
		Rhymix\Framework\Config::set('lock.allow', array_values($allowed_ip));
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSitelock'));
	}
	
	/**
	 * Update FTP configuration.
	 */
	function procAdminUpdateFTPInfo()
	{
		$vars = Context::getRequestVars();
		$vars->ftp_path = str_replace('\\', '/', rtrim(trim($vars->ftp_path), '/\\')) . '/';
		if (strlen($vars->ftp_pass) === 0)
		{
			$vars->ftp_pass = Rhymix\Framework\Config::get('ftp.pass');
		}
		
		// Test FTP connection.
		if ($vars->ftp_sftp !== 'Y')
		{
			if (!($conn = @ftp_connect($vars->ftp_host, $vars->ftp_port, 3)))
			{
				return new Object(-1, 'msg_ftp_not_connected');
			}
			if (!@ftp_login($conn, $vars->ftp_user, $vars->ftp_pass))
			{
				return new Object(-1, 'msg_ftp_invalid_auth_info');
			}
			if (!@ftp_pasv($conn, $vars->ftp_pasv === 'Y'))
			{
				return new Object(-1, 'msg_ftp_cannot_set_passive_mode');
			}
			if (!@ftp_chdir($conn, $vars->ftp_path))
			{
				return new Object(-1, 'msg_ftp_invalid_path');
			}
			ftp_close($conn);
		}
		else
		{
			if (!function_exists('ssh2_connect'))
			{
				return new Object(-1, 'disable_sftp_support');
			}
			if (!($conn = ssh2_connect($vars->ftp_host, $vars->ftp_port)))
			{
				return new Object(-1, 'msg_ftp_not_connected');
			}
			if (!@ssh2_auth_password($conn, $vars->ftp_user, $vars->ftp_pass))
			{
				return new Object(-1, 'msg_ftp_invalid_auth_info');
			}
			if (!@($sftp = ssh2_sftp($conn)))
			{
				return new Object(-1, 'msg_ftp_sftp_error');
			}
			if (!@ssh2_sftp_stat($sftp, $vars->ftp_path . 'common/defaults/config.php'))
			{
				return new Object(-1, 'msg_ftp_invalid_path');
			}
			unset($sftp, $conn);
		}
		
		// Save settings.
		Rhymix\Framework\Config::set('ftp.host', $vars->ftp_host);
		Rhymix\Framework\Config::set('ftp.port', $vars->ftp_port);
		Rhymix\Framework\Config::set('ftp.user', $vars->ftp_user);
		Rhymix\Framework\Config::set('ftp.pass', $vars->ftp_pass);
		Rhymix\Framework\Config::set('ftp.path', $vars->ftp_path);
		Rhymix\Framework\Config::set('ftp.pasv', $vars->ftp_pasv === 'Y');
		Rhymix\Framework\Config::set('ftp.sftp', $vars->ftp_sftp === 'Y');
		Rhymix\Framework\Config::save();
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigFtp'));
	}
	
	/**
	 * Remove FTP configuration.
	 */
	function procAdminRemoveFTPInfo()
	{
		Rhymix\Framework\Config::set('ftp.host', null);
		Rhymix\Framework\Config::set('ftp.port', null);
		Rhymix\Framework\Config::set('ftp.user', null);
		Rhymix\Framework\Config::set('ftp.pass', null);
		Rhymix\Framework\Config::set('ftp.path', null);
		Rhymix\Framework\Config::set('ftp.pasv', true);
		Rhymix\Framework\Config::set('ftp.sftp', false);
		Rhymix\Framework\Config::save();
		$this->setMessage('success_deleted');
	}
	
	/**
	 * Upload favicon and mobicon.
	 */
	public function procAdminFaviconUpload()
	{
		if ($favicon = Context::get('favicon'))
		{
			$name = 'favicon';
			$tmpFileName = $this->_saveFaviconTemp($favicon, 'favicon.ico');
		}
		elseif ($mobicon = Context::get('mobicon'))
		{
			$name = 'mobicon';
			$tmpFileName = $this->_saveFaviconTemp($mobicon, 'mobicon.png');
		}
		elseif ($default_image = Context::get('default_image'))
		{
			$name = 'default_image';
			$tmpFileName = $this->_saveFaviconTemp($default_image, 'default_image.png');
		}
		else
		{
			$name = $tmpFileName = '';
			Context::set('msg', lang('msg_invalid_format'));
		}
		
		Context::set('name', $name);
		Context::set('tmpFileName', $tmpFileName . '?' . time());
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile("favicon_upload.html");
	}
	
	protected function _saveFaviconTemp($icon, $iconname)
	{
		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if ($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$original_filename = $icon['tmp_name'];
		$type = $icon['type'];
		$relative_filename = 'files/attach/xeicon/'.$virtual_site.'tmp/'.$iconname;
		$target_filename = \RX_BASEDIR . $relative_filename;

		if (!preg_match('/^(favicon|mobicon|default_image)\.(ico|png|jpe?g)$/', $iconname))
		{
			Context::set('msg', lang('msg_invalid_format'));
			return;
		}
		
		Rhymix\Framework\Storage::copy($original_filename, $target_filename, 0666 & ~umask());
		return $relative_filename;
	}
	
	protected function _saveFavicon($iconname, $deleteIcon = false)
	{
		$image_filepath = 'files/attach/xeicon/';
		$site_info = Context::get('site_module_info');
		if ($site_info->site_srl)
		{
			$image_filepath .= $site_info->site_srl . '/';
		}
		
		if ($deleteIcon)
		{
			Rhymix\Framework\Storage::delete(\RX_BASEDIR . $image_filepath . $iconname);
			return;
		}
		
		$tmpicon_filepath = $image_filepath . 'tmp/' . $iconname;
		$icon_filepath = $image_filepath . $iconname;
		if (file_exists(\RX_BASEDIR . $tmpicon_filepath))
		{
			Rhymix\Framework\Storage::move(\RX_BASEDIR . $tmpicon_filepath, \RX_BASEDIR . $icon_filepath);
		}
	}
	
	protected function _saveDefaultImage($deleteIcon = false)
	{
		$image_filepath = 'files/attach/xeicon/';
		$site_info = Context::get('site_module_info');
		if ($site_info->site_srl)
		{
			$image_filepath .= $site_info->site_srl . '/';
		}
		
		if ($deleteIcon)
		{
			$info = Rhymix\Framework\Storage::readPHPData($image_filepath . 'default_image.php');
			if ($info['filename'])
			{
				Rhymix\Framework\Storage::delete(\RX_BASEDIR . $info['filename']);
			}
			Rhymix\Framework\Storage::delete($image_filepath . 'default_image.php');
			return;
		}
		
		$tmpicon_filepath = \RX_BASEDIR . $image_filepath . 'tmp/default_image.png';
		if (file_exists($tmpicon_filepath))
		{
			list($width, $height, $type) = @getimagesize($tmpicon_filepath);
			switch ($type)
			{
				case 'image/gif': $target_filename = $image_filepath . 'default_image.gif'; break;
				case 'image/jpeg': $target_filename = $image_filepath . 'default_image.jpg'; break;
				case 'image/png': default: $target_filename = $image_filepath . 'default_image.png';
			}
			Rhymix\Framework\Storage::move($tmpicon_filepath, \RX_BASEDIR . $target_filename);
			Rhymix\Framework\Storage::writePHPData(\RX_BASEDIR . 'files/attach/xeicon/' . $virtual_site . 'default_image.php', array(
				'filename' => $target_filename, 'width' => $width, 'height' => $height,
			));
		}
	}
}
/* End of file admin.admin.controller.php */
/* Location: ./modules/admin/admin.admin.controller.php */
