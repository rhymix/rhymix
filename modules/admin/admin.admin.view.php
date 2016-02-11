<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminView class
 * Admin view class of admin module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class adminAdminView extends admin
{

	/**
	 * layout list
	 * @var array
	 */
	var $layout_list;

	/**
	 * easy install check file
	 * @var array
	 */
	var $easyinstallCheckFile = './files/env/easyinstall_last';

	function __construct()
	{
		Context::set('xe_default_url', Context::getDefaultUrl());
	}

	/**
	 * Initilization
	 * @return void
	 */
	function init()
	{
		// forbit access if the user is not an administrator
		$oMemberModel = getModel('member');
		$logged_info = $oMemberModel->getLoggedInfo();
		if($logged_info->is_admin != 'Y')
		{
			return $this->stop("msg_is_not_administrator");
		}

		// change into administration layout
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setLayoutPath($this->getTemplatePath());
		$this->setLayoutFile('layout.html');

		$this->makeGnbUrl();

		// Retrieve the list of installed modules
		$this->checkEasyinstall();
	}

	/**
	 * check easy install
	 * @return void
	 */
	function checkEasyinstall()
	{
		$lastTime = (int) FileHandler::readFile($this->easyinstallCheckFile);
		if($lastTime > $_SERVER['REQUEST_TIME'] - 60 * 60 * 24 * 30)
		{
			return;
		}

		$oAutoinstallModel = getModel('autoinstall');
		$params = array();
		$params["act"] = "getResourceapiLastupdate";
		$body = XmlGenerater::generate($params);
		$buff = FileHandler::getRemoteResource(_XE_DOWNLOAD_SERVER_, $body, 3, "POST", "application/xml");
		$xml_lUpdate = new XmlParser();
		$lUpdateDoc = $xml_lUpdate->parse($buff);
		$updateDate = $lUpdateDoc->response->updatedate->body;

		if(!$updateDate)
		{
			$this->_markingCheckEasyinstall();
			return;
		}

		$item = $oAutoinstallModel->getLatestPackage();
		if(!$item || $item->updatedate < $updateDate)
		{
			$oController = getAdminController('autoinstall');
			$oController->_updateinfo();
		}
		$this->_markingCheckEasyinstall();
	}

	/**
	 * update easy install file content
	 * @return void
	 */
	function _markingCheckEasyinstall()
	{
		$currentTime = $_SERVER['REQUEST_TIME'];
		FileHandler::writeFile($this->easyinstallCheckFile, $currentTime);
	}

	/**
	 * Include admin menu php file and make menu url
	 * Setting admin logo, newest news setting
	 * @return void
	 */
	function makeGnbUrl($module = 'admin')
	{
		global $lang;

		// Check is_shortcut column
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('menu_item', 'is_shortcut'))
		{
			return;
		}

		$oAdminAdminModel = getAdminModel('admin');
		$lang->menu_gnb_sub = $oAdminAdminModel->getAdminMenuLang();

		$result = $oAdminAdminModel->checkAdminMenu();
		include $result->php_file;

		$oModuleModel = getModel('module');

		// get current menu's subMenuTitle
		$moduleActionInfo = $oModuleModel->getModuleActionXml($module);
		$currentAct = Context::get('act');
		$subMenuTitle = '';

		foreach((array)$moduleActionInfo->menu as $key => $value)
		{
			if(is_array($value->acts) && in_array($currentAct, $value->acts))
			{
				$subMenuTitle = $value->title;
				break;
			}
		}
		
		// get current menu's srl(=parentSrl)
		$parentSrl = 0;
		$oMenuAdminConroller = getAdminController('menu');
		foreach((array) $menu->list as $parentKey => $parentMenu)
		{
			if(!is_array($parentMenu['list']) || !count($parentMenu['list']))
			{
				continue;
			}
			if($parentMenu['href'] == '#' && count($parentMenu['list']))
			{
				$firstChild = current($parentMenu['list']);
				$menu->list[$parentKey]['href'] = $firstChild['href'];
			}

			foreach($parentMenu['list'] as $childKey => $childMenu)
			{
				if($subMenuTitle == $childMenu['text'] && $parentSrl == 0)
				{
					$parentSrl = $childMenu['parent_srl'];
				}
			}
		}

		// Admin logo, title setup
		$objConfig = $oModuleModel->getModuleConfig('admin');
		$gnbTitleInfo = new stdClass();
		$gnbTitleInfo->adminTitle = $objConfig->adminTitle ? $objConfig->adminTitle : 'Admin';
		$gnbTitleInfo->adminLogo = $objConfig->adminLogo ? $objConfig->adminLogo : '';

		$browserTitle = ($subMenuTitle ? $subMenuTitle : 'Dashboard') . ' - ' . $gnbTitleInfo->adminTitle;

		// Get list of favorite
		$oAdminAdminModel = getAdminModel('admin');
		$output = $oAdminAdminModel->getFavoriteList(0, true);
		Context::set('favorite_list', $output->get('favoriteList'));

		// Retrieve recent news and set them into context,
		// move from index method, because use in admin footer
		/*
		$newest_news_url = sprintf("http://news.xpressengine.com/%s/news.php?version=%s&package=%s", _XE_LOCATION_, __XE_VERSION__, _XE_PACKAGE_);
		$cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file) + 60 * 60 < $_SERVER['REQUEST_TIME'])
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing administration page
			// Ensure to access the administration page even though news cannot be displayed
			FileHandler::writeFile($cache_file, '');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL' => getFullUrl('')));
		}

		if(file_exists($cache_file))
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item)
			{
				if(!is_array($item))
				{
					$item = array($item);
				}

				foreach($item as $key => $val)
				{
					$obj = new stdClass();
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
		*/

		Context::set('subMenuTitle', $subMenuTitle);
		Context::set('gnbUrlList', $menu->list);
		Context::set('parentSrl', $parentSrl);
		Context::set('gnb_title_info', $gnbTitleInfo);
		Context::setBrowserTitle($browserTitle);
	}

	/**
	 * Display Super Admin Dashboard
	 * @return void
	 */
	function dispAdminIndex()
	{
		// Get statistics
		$args = new stdClass();
		$args->date = date("Ymd000000", $_SERVER['REQUEST_TIME'] - 60 * 60 * 24);
		$today = date("Ymd");

		// Member Status
		$oMemberAdminModel = getAdminModel('member');
		$status = new stdClass();
		$status->member = new stdClass();
		$status->member->todayCount = $oMemberAdminModel->getMemberCountByDate($today);
		$status->member->totalCount = $oMemberAdminModel->getMemberCountByDate();

		// Document Status
		$oDocumentAdminModel = getAdminModel('document');
		$statusList = array('PUBLIC', 'SECRET');
		$status->document = new stdClass();
		$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today, array(), $statusList);
		$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate('', array(), $statusList);

		Context::set('status', $status);

		// Latest Document
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'nick_name', 'member_srl');
		$args->list_count = 5;
		$output = $oDocumentModel->getDocumentList($args, FALSE, FALSE, $columnList);
		Context::set('latestDocumentList', $output->data);
		unset($args, $output, $columnList);

		// Latest Comment
		$oCommentModel = getModel('comment');
		$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
		$args = new stdClass();
		$args->list_count = 5;
		$output = $oCommentModel->getNewestCommentList($args, $columnList);
		if(is_array($output))
		{
			foreach($output AS $key => $value)
			{
				$value->content = strip_tags($value->content);
			}
		}
		Context::set('latestCommentList', $output);
		unset($args, $output, $columnList);

		// Get list of modules
		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();
		if(is_array($module_list))
		{
			$needUpdate = FALSE;
			$addTables = FALSE;
			foreach($module_list AS $key => $value)
			{
				if($value->need_install)
				{
					$addTables = TRUE;
				}
				if($value->need_update)
				{
					$needUpdate = TRUE;
				}
			}
		}

		// Get need update from easy install
		$oAutoinstallAdminModel = getAdminModel('autoinstall');
		$needUpdateList = $oAutoinstallAdminModel->getNeedUpdateList();

		if(is_array($needUpdateList))
		{
			foreach($needUpdateList AS $key => $value)
			{
				$helpUrl = './common/manual/admin/index.html#';
				switch($value->type)
				{
					case 'addon':
						$helpUrl .= 'UMAN_terminology_addon';
						break;
					case 'layout':
					case 'm.layout':
						$helpUrl .= 'UMAN_terminology_layout';
						break;
					case 'module':
						$helpUrl .= 'UMAN_terminology_module';
						break;
					case 'widget':
						$helpUrl .= 'UMAN_terminology_widget';
						break;
					case 'widgetstyle':
						$helpUrl .= 'UMAN_terminology_widgetstyle';
						break;
					default:
						$helpUrl = '';
				}
				$needUpdateList[$key]->helpUrl = $helpUrl;
			}
		}

		$site_module_info = Context::get('site_module_info');
		$oAddonAdminModel = getAdminModel('addon');
		$counterAddonActivated = $oAddonAdminModel->isActivatedAddon('counter', $site_module_info->site_srl );
		if(!$counterAddonActivated)
		{
			$columnList = array('member_srl', 'nick_name', 'user_name', 'user_id', 'email_address');
			$args = new stdClass;
			$args->page = 1;
			$args->list_count = 5;
			$output = executeQuery('member.getMemberList', $args, $columnList);
			Context::set('latestMemberList', $output->data);
			unset($args, $output, $columnList);
		}

		Context::set('module_list', $module_list);
		Context::set('needUpdate', $isUpdated);
		Context::set('addTables', $addTables);
		Context::set('needUpdate', $needUpdate);
		Context::set('newVersionList', $needUpdateList);
		Context::set('counterAddonActivated', $counterAddonActivated);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('module_list..', 'module_list..author..', 'newVersionList..');

		// license agreement check
		$isLicenseAgreement = FALSE;
		$path = FileHandler::getRealPath('./files/env/license_agreement');
		$isLicenseAgreement = FALSE;
		if(file_exists($path))
		{
			$isLicenseAgreement = TRUE;
		}
		Context::set('isLicenseAgreement', $isLicenseAgreement);
		Context::set('layout', 'none');

		$this->setTemplateFile('index');
	}

	/**
	 * Display General Settings page
	 * @return void
	 */
	function dispAdminConfigGeneral()
	{
		// Default and enabled languages
		Context::set('supported_lang', Rhymix\Framework\Lang::getSupportedList());
		Context::set('default_lang', Rhymix\Framework\Config::get('locale.default_lang'));
		Context::set('enabled_lang', Rhymix\Framework\Config::get('locale.enabled_lang'));
		
		// Site title and HTML footer
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		Context::set('site_title', escape($config->siteTitle));
		Context::set('html_footer', escape($config->htmlFooter));
		
		// Index module
		$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
		$start_module = $oModuleModel->getSiteInfo(0, $columnList);
		Context::set('start_module', $start_module);
		
		// Thumbnail settings
		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('thumbnail_type', $config->thumbnail_type ?: 'crop');
		
		// Default time zone
		Context::set('timezones', Rhymix\Framework\DateTime::getTimezoneList());
		Context::set('selected_timezone', Rhymix\Framework\Config::get('locale.default_timezone'));
		
		// Mobile view
		Context::set('use_mobile_view', config('use_mobile_view') ? 'Y' : 'N');
		
		// Favicon and mobicon
		$oAdminModel = getAdminModel('admin');
		$favicon_url = $oAdminModel->getFaviconUrl();
		$mobicon_url = $oAdminModel->getMobileIconUrl();
		Context::set('favicon_url', $favicon_url.'?'.$_SERVER['REQUEST_TIME']);
		Context::set('mobicon_url', $mobicon_url.'?'.$_SERVER['REQUEST_TIME']);
		
		$this->setTemplateFile('config_general');
	}
	
	/**
	 * Display Security Settings page
	 * @return void
	 */
	function dispAdminConfigSecurity()
	{
		// Load embed filter.
		$oEmbedFilter = EmbedFilter::getInstance();
		context::set('embedfilter_iframe', implode(PHP_EOL, $oEmbedFilter->whiteIframeUrlList));
		context::set('embedfilter_object', implode(PHP_EOL, $oEmbedFilter->whiteUrlList));
		
		// Admin IP access control
		$allowed_ip = Rhymix\Framework\Config::get('admin.allow');
		Context::set('admin_allowed_ip', implode(PHP_EOL, $allowed_ip));
		$denied_ip = Rhymix\Framework\Config::get('admin.deny');
		Context::set('admin_denied_ip', implode(PHP_EOL, $denied_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		$this->setTemplateFile('config_security');
	}
	
	/**
	 * Display Advanced Settings page
	 * @return void
	 */
	function dispAdminConfigAdvanced()
	{
		// Default URL
		$default_url = Rhymix\Framework\Config::get('url.default');
		if(strpos($default_url, 'xn--') !== FALSE)
		{
			$default_url = Context::decodeIdna($default_url);
		}
		Context::set('default_url', $default_url);
		
		// SSL and ports
		Context::set('use_ssl', Rhymix\Framework\Config::get('url.ssl') ?: 'none');
		Context::set('http_port', Rhymix\Framework\Config::get('url.http_port'));
		Context::set('https_port', Rhymix\Framework\Config::get('url.https_port'));
		
		// Object cache
		$object_cache_config = Rhymix\Framework\Config::get('cache');
		if (is_array($object_cache_config))
		{
			$object_cache_config = array_first($object_cache_config);
		}
		$object_cache_types = array('apc', 'file', 'memcached', 'redis', 'wincache');
		$object_cache_type = preg_match('/^(' . implode('|', $object_cache_types) . ')/', $object_cache_config, $matches) ? $matches[1] : '';
		Context::set('object_cache_types', $object_cache_types);
		Context::set('object_cache_type', $object_cache_type);
		if ($object_cache_type)
		{
			Context::set('object_cache_host', parse_url($object_cache_config, PHP_URL_HOST) ?: null);
			Context::set('object_cache_port', parse_url($object_cache_config, PHP_URL_PORT) ?: null);
		}
		else
		{
			Context::set('object_cache_host', null);
			Context::set('object_cache_port', null);
		}
		
		// Other settings
		Context::set('use_mobile_view', Rhymix\Framework\Config::get('use_mobile_view'));
		Context::set('use_rewrite', Rhymix\Framework\Config::get('use_rewrite'));
		Context::set('use_sso', Rhymix\Framework\Config::get('use_sso'));
		Context::set('delay_session', Rhymix\Framework\Config::get('session.delay'));
		Context::set('use_db_session', Rhymix\Framework\Config::get('session.use_db'));
		Context::set('minify_scripts', Rhymix\Framework\Config::get('view.minify_scripts'));
		Context::set('use_gzip', Rhymix\Framework\Config::get('view.gzip'));
		
		$this->setTemplateFile('config_advanced');
	}
	
	/**
	 * Display Debug Settings page
	 * @return void
	 */
	function dispAdminConfigDebug()
	{
		// Load debug settings.
		Context::set('debug_enabled', Rhymix\Framework\Config::get('debug.enabled'));
		Context::set('debug_log_errors', Rhymix\Framework\Config::get('debug.log_errors'));
		Context::set('debug_log_queries', Rhymix\Framework\Config::get('debug.log_queries'));
		Context::set('debug_log_slow_queries', Rhymix\Framework\Config::get('debug.log_slow_queries'));
		Context::set('debug_log_slow_triggers', Rhymix\Framework\Config::get('debug.log_slow_triggers'));
		Context::set('debug_log_slow_widgets', Rhymix\Framework\Config::get('debug.log_slow_widgets'));
		Context::set('debug_display_type', Rhymix\Framework\Config::get('debug.display_type'));
		Context::set('debug_display_to', Rhymix\Framework\Config::get('debug.display_to'));
		
		// IP access control
		$allowed_ip = Rhymix\Framework\Config::get('debug.allow');
		Context::set('debug_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		$this->setTemplateFile('config_debug');
	}
	
	/**
	 * Display Sitelock Settings page
	 * @return void
	 */
	function dispAdminConfigSitelock()
	{
		Context::set('sitelock_locked', Rhymix\Framework\Config::get('lock.locked'));
		Context::set('sitelock_title', escape(Rhymix\Framework\Config::get('lock.title')));
		Context::set('sitelock_message', escape(Rhymix\Framework\Config::get('lock.message')));
		
		$allowed_ip = Rhymix\Framework\Config::get('lock.allow') ?: array();
		$allowed_localhost = false;
		$allowed_current = false;
		foreach ($allowed_ip as $range)
		{
			if (Rhymix\Framework\IpFilter::inRange('127.0.0.1', $range))
			{
				$allowed_localhost = true;
			}
			if (Rhymix\Framework\IpFilter::inRange(RX_CLIENT_IP, $range))
			{
				$allowed_current = true;
			}
		}
		if (!$allowed_localhost)
		{
			array_unshift($allowed_ip, '127.0.0.1');
		}
		if (!$allowed_current)
		{
			array_unshift($allowed_ip, RX_CLIENT_IP);
		}
		Context::set('sitelock_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		$this->setTemplateFile('config_sitelock');
	}
	
	/**
	 * Display FTP Configuration(settings) page
	 * @return void
	 */
	function dispAdminConfigFtp()
	{
		Context::set('ftp_info', Rhymix\Framework\Config::get('ftp'));
		Context::set('sftp_support', function_exists('ssh2_sftp'));

		$this->setTemplateFile('config_ftp');
	}
	
	/**
	 * Display Admin Menu Configuration(settings) page
	 * @return void
	 */
	function dispAdminSetup()
	{
		$oModuleModel = getModel('module');
		$configObject = $oModuleModel->getModuleConfig('admin');

		$oAdmin = getClass('admin');
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuByTitle($oAdmin->getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);
		Context::set('config_object', $configObject);
		$this->setTemplateFile('admin_setup');
	}

	/**
	 * Retrun server environment to XML string
	 * @return object
	*/
	function dispAdminViewServerEnv()
	{
		$info = array();

		$oAdminModel = getAdminModel('admin');
		$envInfo = $oAdminModel->getEnv();
		$tmp = explode("&", $envInfo);
		$arrInfo = array();
		$xe_check_env = array();
		foreach($tmp as $value) {
			$arr = explode("=", $value);
			if($arr[0]=="type") {
				continue;
			}elseif($arr[0]=="phpext" ) {
				$str = urldecode($arr[1]);
				$xe_check_env[$arr[0]]= str_replace("|", ", ", $str);
			} elseif($arr[0]=="module" ) {
				$str = urldecode($arr[1]);
				$arrModuleName = explode("|", $str);
				$oModuleModel = getModel("module");
				$mInfo = array();
				foreach($arrModuleName as $moduleName) {
					$moduleInfo = $oModuleModel->getModuleInfoXml($moduleName);
					$mInfo[] = "{$moduleName}({$moduleInfo->version})";
				}
				$xe_check_env[$arr[0]]= join(", ", $mInfo);
			} elseif($arr[0]=="addon") {
				$str = urldecode($arr[1]);
				$arrAddonName = explode("|", $str);
				$oAddonModel = getAdminModel("addon");
				$mInfo = array();
				foreach($arrAddonName as $addonName) {
					$addonInfo = $oAddonModel->getAddonInfoXml($addonName);
					$mInfo[] = "{$addonName}({$addonInfo->version})";
				}
				$xe_check_env[$arr[0]]= join(", ", $mInfo);
			} elseif($arr[0]=="widget") {
				$str = urldecode($arr[1]);
				$arrWidgetName = explode("|", $str);
				$oWidgetModel = getModel("widget");
				$mInfo = array();
				foreach($arrWidgetName as $widgetName) {
					$widgetInfo = $oWidgetModel->getWidgetInfo($widgetName);
					$mInfo[] = "{$widgetName}({$widgetInfo->version})";
				}
				$xe_check_env[$arr[0]]= join(", ", $mInfo);
			} elseif($arr[0]=="widgetstyle") {
				$str = urldecode($arr[1]);
				$arrWidgetstyleName = explode("|", $str);
				$oWidgetModel = getModel("widget");
				$mInfo = array();
				foreach($arrWidgetstyleName as $widgetstyleName) {
					$widgetstyleInfo = $oWidgetModel->getWidgetStyleInfo($widgetstyleName);
					$mInfo[] = "{$widgetstyleName}({$widgetstyleInfo->version})";
				}
				$xe_check_env[$arr[0]]= join(", ", $mInfo);

			} elseif($arr[0]=="layout") {
				$str = urldecode($arr[1]);
				$arrLayoutName = explode("|", $str);
				$oLayoutModel = getModel("layout");
				$mInfo = array();
				foreach($arrLayoutName as $layoutName) {
					$layoutInfo = $oLayoutModel->getLayoutInfo($layoutName);
					$mInfo[] = "{$layoutName}({$layoutInfo->version})";
				}
				$xe_check_env[$arr[0]]= join(", ", $mInfo);
			} else {
				$xe_check_env[$arr[0]] = urldecode($arr[1]);
			}
		}
		$info['XE_Check_Evn'] = $xe_check_env;

		$ini_info = ini_get_all();
		$php_core = array();
		$php_core['max_file_uploads'] = "{$ini_info['max_file_uploads']['local_value']}";
		$php_core['post_max_size'] = "{$ini_info['post_max_size']['local_value']}";
		$php_core['memory_limit'] = "{$ini_info['memory_limit']['local_value']}";
		$info['PHP_Core'] = $php_core;

		$str_info = "[Rhymix Server Environment " . date("Y-m-d") . "]\n\n";
		$str_info .= "realpath : ".realpath('./')."\n";
		foreach( $info as $key=>$value )
		{
			if( is_array( $value ) == false ) {
				$str_info .= "{$key} : {$value}\n";
			} else {
				//$str_info .= "\n{$key} \n";
				foreach( $value as $key2=>$value2 )
					$str_info .= "{$key2} : {$value2}\n";
			}
		}

		Context::set('str_info', $str_info);
		$this->setTemplateFile('server_env.html');
	}

}
/* End of file admin.admin.view.php */
/* Location: ./modules/admin/admin.admin.view.php */
