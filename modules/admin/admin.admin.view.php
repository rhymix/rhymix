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
		$db_info = Context::getDBInfo();

		if(strpos($db_info->default_url, 'xn--') !== FALSE)
		{
			$xe_default_url = Context::decodeIdna($db_info->default_url);
		}
		else
		{
			$xe_default_url = $db_info->default_url;
		}
		Context::set('xe_default_url', $xe_default_url);
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

		$db_info = Context::getDBInfo();

		Context::set('time_zone_list', $GLOBALS['time_zone']);
		Context::set('time_zone', $GLOBALS['_time_zone']);
		Context::set('use_rewrite', $db_info->use_rewrite == 'Y' ? 'Y' : 'N');
		Context::set('use_sso', $db_info->use_sso == 'Y' ? 'Y' : 'N');
		Context::set('use_html5', $db_info->use_html5 == 'Y' ? 'Y' : 'N');
		Context::set('use_spaceremover', $db_info->use_spaceremover ? $db_info->use_spaceremover : 'Y'); //not use
		Context::set('qmail_compatibility', $db_info->qmail_compatibility == 'Y' ? 'Y' : 'N');
		Context::set('use_db_session', $db_info->use_db_session == 'N' ? 'N' : 'Y');
		Context::set('use_mobile_view', $db_info->use_mobile_view == 'Y' ? 'Y' : 'N');
		Context::set('use_ssl', $db_info->use_ssl ? $db_info->use_ssl : "none");
		if($db_info->http_port)
		{
			Context::set('http_port', $db_info->http_port);
		}
		if($db_info->https_port)
		{
			Context::set('https_port', $db_info->https_port);
		}

		$this->showSendEnv();
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

		foreach((array) $moduleActionInfo->menu as $key => $value)
		{
			if(isset($value->acts) && is_array($value->acts) && in_array($currentAct, $value->acts))
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
		$gnbTitleInfo->adminTitle = $objConfig->adminTitle ? $objConfig->adminTitle : 'XE Admin';
		$gnbTitleInfo->adminLogo = $objConfig->adminLogo ? $objConfig->adminLogo : 'modules/admin/tpl/img/xe.h1.png';

		$browserTitle = ($subMenuTitle ? $subMenuTitle : 'Dashboard') . ' - ' . $gnbTitleInfo->adminTitle;

		// Get list of favorite
		$oAdminAdminModel = getAdminModel('admin');
		$output = $oAdminAdminModel->getFavoriteList(0, true);
		Context::set('favorite_list', $output->get('favoriteList'));

		// Retrieve recent news and set them into context,
		// move from index method, because use in admin footer
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
				if(isset($news) && is_array($news))
				{
					Context::set('latestVersion', array_shift($news));
				}
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}

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
		$db_info = Context::getDBInfo();
		Context::set('db_info',$db_info);

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
				$helpUrl = './admin/help/index.html#';
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

		// gathering enviroment check
		$mainVersion = join('.', array_slice(explode('.', __XE_VERSION__), 0, 2));
		$path = FileHandler::getRealPath('./files/env/' . $mainVersion);
		$isEnviromentGatheringAgreement = FALSE;
		if(file_exists($path))
		{
			$isEnviromentGatheringAgreement = TRUE;
		}
		Context::set('isEnviromentGatheringAgreement', $isEnviromentGatheringAgreement);

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
	 * Display Configuration(settings) page
	 * @return void
	 */
	function dispAdminConfigGeneral()
	{
		Context::loadLang('modules/install/lang');

		$db_info = Context::getDBInfo();

		Context::set('selected_lang', $db_info->lang_type);

		if(strpos($db_info->default_url, 'xn--') !== FALSE)
		{
			$db_info->default_url = Context::decodeIdna($db_info->default_url);
		}
		Context::set('default_url', $db_info->default_url);
		Context::set('langs', Context::loadLangSupported());

		// site lock
		Context::set('IP', $_SERVER['REMOTE_ADDR']);
		if(!$db_info->sitelock_title) $db_info->sitelock_title = 'Maintenance in progress...';
		if(!in_array('127.0.0.1', $db_info->sitelock_whitelist)) $db_info->sitelock_whitelist[] = '127.0.0.1';
		if(!in_array($_SERVER['REMOTE_ADDR'], $db_info->sitelock_whitelist)) $db_info->sitelock_whitelist[] = $_SERVER['REMOTE_ADDR'];
		$db_info->sitelock_whitelist = array_unique($db_info->sitelock_whitelist);
		Context::set('remote_addr', $_SERVER['REMOTE_ADDR']);
		Context::set('use_sitelock', $db_info->use_sitelock);
		Context::set('sitelock_title', $db_info->sitelock_title);
		Context::set('sitelock_message', htmlspecialchars($db_info->sitelock_message, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));

		$whitelist = implode("\r\n", $db_info->sitelock_whitelist);
		Context::set('sitelock_whitelist', $whitelist);


		if($db_info->admin_ip_list) $admin_ip_list = implode("\r\n", $db_info->admin_ip_list);
		else $admin_ip_list = '';
		Context::set('admin_ip_list', $admin_ip_list);

		Context::set('lang_selected', Context::loadLangSelected());

		$oAdminModel = getAdminModel('admin');
		$favicon_url = $oAdminModel->getFaviconUrl();
		$mobicon_url = $oAdminModel->getMobileIconUrl();
		Context::set('favicon_url', $favicon_url.'?'.$_SERVER['REQUEST_TIME']);
		Context::set('mobicon_url', $mobicon_url.'?'.$_SERVER['REQUEST_TIME']);

		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('thumbnail_type', $config->thumbnail_type);


		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		Context::set('siteTitle', $config->siteTitle);
		Context::set('htmlFooter', htmlspecialchars($config->htmlFooter));

		// embed filter
		require_once(_XE_PATH_ . 'classes/security/EmbedFilter.class.php');
		$oEmbedFilter = EmbedFilter::getInstance();
		context::set('embed_white_object', implode(PHP_EOL, $oEmbedFilter->whiteUrlList));
		context::set('embed_white_iframe', implode(PHP_EOL, $oEmbedFilter->whiteIframeUrlList));

		$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
		$start_module = $oModuleModel->getSiteInfo(0, $columnList);
		Context::set('start_module', $start_module);

		Context::set('pwd', $pwd);
		$this->setTemplateFile('config_general');

		$security = new Security();
		$security->encodeHTML('news..', 'released_version', 'download_link', 'selected_lang', 'module_list..', 'module_list..author..', 'addon_list..', 'addon_list..author..', 'start_module.');
	}

	/**
	 * Display FTP Configuration(settings) page
	 * @return void
	 */
	function dispAdminConfigFtp()
	{
		Context::loadLang('modules/install/lang');

		$ftp_info = Context::getFTPInfo();
		Context::set('ftp_info', $ftp_info);
		Context::set('sftp_support', function_exists(ssh2_sftp));

		$this->setTemplateFile('config_ftp');

		//$security = new Security();
		//$security->encodeHTML('ftp_info..');
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
	 * Enviroment information send to XE collect server
	 * @return void
	 */
	function showSendEnv()
	{
		if(Context::getResponseMethod() != 'HTML')
		{
			return;
		}

		$server = 'http://collect.xpressengine.com/env/img.php?';
		$path = './files/env/';
		$install_env = $path . 'install';
		$mainVersion = join('.', array_slice(explode('.', __XE_VERSION__), 0, 2));

		if(file_exists(FileHandler::getRealPath($install_env)))
		{
			$oAdminAdminModel = getAdminModel('admin');
			$params = $oAdminAdminModel->getEnv('INSTALL');
			$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server . $params);
			Context::addHtmlFooter($img);

			FileHandler::writeFile($path . $mainVersion, '1');
		}
		else if(isset($_SESSION['enviroment_gather']) && !file_exists(FileHandler::getRealPath($path . $mainVersion)))
		{
			if($_SESSION['enviroment_gather'] == 'Y')
			{
				$oAdminAdminModel = getAdminModel('admin');
				$params = $oAdminAdminModel->getEnv();
				$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server . $params);
				Context::addHtmlFooter($img);
			}

			FileHandler::writeFile($path . $mainVersion, '1');
			unset($_SESSION['enviroment_gather']);
		}
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

		$str_info = "[XE Server Environment " . date("Y-m-d") . "]\n\n";
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
