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
		parent::__construct();
	}

	/**
	 * Initilization
	 * @return void
	 */
	function init()
	{
		// forbit access if the user is not an administrator
		if (!$this->user->isAdmin())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('admin.msg_is_not_administrator');
		}

		// change into administration layout
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setLayoutPath($this->getTemplatePath());
		$this->setLayoutFile('layout.html');

		$this->makeGnbUrl();

		// Check system configuration
		$this->checkSystemConfiguration();
		
		// Retrieve the list of installed modules
		$this->checkEasyinstall();
	}

	/**
	 * check system configuration
	 * @return void
	 */
	function checkSystemConfiguration()
	{
		$changed = false;
		
		// Check encryption keys.
		if (config('crypto.encryption_key') === null)
		{
			config('crypto.encryption_key', Rhymix\Framework\Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('crypto.authentication_key') === null)
		{
			config('crypto.authentication_key', Rhymix\Framework\Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('crypto.session_key') === null)
		{
			config('crypto.session_key', Rhymix\Framework\Security::getRandom(64, 'alnum'));
			$changed = true;
		}
		if (config('file.folder_structure') === null)
		{
			config('file.folder_structure', 1);
			$changed = true;
		}
		
		// Save new configuration.
		if ($changed)
		{
			Rhymix\Framework\Config::save();
		}
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

		$oAutoinstallAdminModel = getAdminModel('autoinstall');
		$config = $oAutoinstallAdminModel->getAutoInstallAdminModuleConfig();

		$oAutoinstallModel = getModel('autoinstall');
		$params = array();
		$params["act"] = "getResourceapiLastupdate";
		$body = XmlGenerater::generate($params);
		$buff = FileHandler::getRemoteResource($config->download_server, $body, 3, "POST", "application/xml");
		$xml_lUpdate = new XeXmlParser();
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
			$oXml = new XeXmlParser();
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
		Context::addBrowserTitle($subMenuTitle ? $subMenuTitle : 'Dashboard');
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
			$priority = array(
				'module' => 1000000,
				'member' => 100000,
				'document' => 10000,
				'comment' => 1000,
				'file' => 100,
			);
			usort($module_list, function($a, $b) use($priority) {
				$a_priority = isset($priority[$a->module]) ? $priority[$a->module] : 0;
				$b_priority = isset($priority[$b->module]) ? $priority[$b->module] : 0;
				if ($a_priority == 0 && $b_priority == 0)
				{
					return strcmp($a->module, $b->module);
				}
				else
				{
					return $b_priority - $a_priority;
				}
			});
			foreach($module_list as $value)
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
		//$oAutoinstallAdminModel = getAdminModel('autoinstall');
		//$needUpdateList = $oAutoinstallAdminModel->getNeedUpdateList();
		$needUpdateList = array();
		
		// Check counter addon
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

		Context::set('layout', 'none');
		$this->setTemplateFile('index');
	}

	/**
	 * Display General Settings page
	 * @return void
	 */
	function dispAdminConfigGeneral()
	{
		// Get domain list.
		$oModuleModel = getModel('module');
		$page = intval(Context::get('page')) ?: 1;
		$domain_list = $oModuleModel->getAllDomains(20, $page);
		Context::set('domain_list', $domain_list);
		Context::set('page_navigation', $domain_list->page_navigation);
		Context::set('page', $page);
		
		// Get index module info.
		$module_list = array();
		$oModuleModel = getModel('module');
		foreach ($domain_list->data as $domain)
		{
			if ($domain->index_module_srl && !isset($module_list[$domain->index_module_srl]))
			{
				$module_list[$domain->index_module_srl] = $oModuleModel->getModuleInfoByModuleSrl($domain->index_module_srl);
			}
		}
		Context::set('module_list', $module_list);
		
		// Get language list.
		Context::set('supported_lang', Rhymix\Framework\Lang::getSupportedList());
		
		$this->setTemplateFile('config_domains');
	}
	
	/**
	 * Display Notification Settings page
	 * @return void
	 */
	function dispAdminConfigNotification()
	{
		// Load advanced mailer module (for lang).
		$oAdvancedMailerAdminView = getAdminView('advanced_mailer');
		
		// Load advanced mailer config.
		$advanced_mailer_config = $oAdvancedMailerAdminView->getConfig();
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		
		// Load member config.
		$member_config = getModel('module')->getModuleConfig('member');
		Context::set('member_config', $member_config);
		Context::set('webmaster_name', $member_config->webmaster_name ? $member_config->webmaster_name : 'webmaster');
		Context::set('webmaster_email', $member_config->webmaster_email);
		
		// Load module config.
		$module_config = getModel('module')->getModuleConfig('module');
		Context::set('module_config', $module_config);

		// Load mail drivers.
		$mail_drivers = Rhymix\Framework\Mail::getSupportedDrivers();
		Context::set('mail_drivers', $mail_drivers);
		Context::set('mail_driver', config('mail.type') ?: 'mailfunction');
		
		// Load SMS drivers.
		$sms_drivers = Rhymix\Framework\SMS::getSupportedDrivers();
		Context::set('sms_drivers', $sms_drivers);
		Context::set('sms_driver', config('sms.type') ?: 'dummy');
		
		// Load Push drivers.
		$push_drivers = Rhymix\Framework\Push::getSupportedDrivers();
		uasort($push_drivers, function($a, $b) { return strcmp($a['name'], $b['name']); });
		Context::set('push_drivers', $push_drivers);
		Context::set('push_config', config('push') ?: []);
		$apns_certificate = false;
		if ($apns_certificate_filename = config('push.apns.certificate'))
		{
			$apns_certificate = Rhymix\Framework\Storage::read($apns_certificate_filename);
		}
		Context::set('apns_certificate', $apns_certificate);
		
		// Workaround for compatibility with older version of Amazon SES driver.
		config('mail.ses.api_key', config('mail.ses.api_user'));
		config('mail.ses.api_secret', config('mail.ses.api_pass'));
		
		$this->setTemplateFile('config_notification');
	}
	
	/**
	 * Display Security Settings page
	 * @return void
	 */
	function dispAdminConfigSecurity()
	{
		// Load embed filter.
		context::set('mediafilter_iframe', implode(PHP_EOL, Rhymix\Framework\Filters\MediaFilter::getIframeWhitelist()));
		context::set('mediafilter_object', implode(PHP_EOL, Rhymix\Framework\Filters\MediaFilter::getObjectWhitelist()));
		context::set('mediafilter_classes', implode(PHP_EOL, Rhymix\Framework\Config::get('mediafilter.classes') ?: array()));
		
		// Load robot user agents.
		$robot_user_agents = Rhymix\Framework\Config::get('security.robot_user_agents') ?: array();
		Context::set('robot_user_agents', implode(PHP_EOL, $robot_user_agents));
		
		// Admin IP access control
		$allowed_ip = Rhymix\Framework\Config::get('admin.allow');
		Context::set('admin_allowed_ip', implode(PHP_EOL, $allowed_ip));
		$denied_ip = Rhymix\Framework\Config::get('admin.deny');
		Context::set('admin_denied_ip', implode(PHP_EOL, $denied_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		// Session and cookie security settings
		Context::set('use_samesite', Rhymix\Framework\Config::get('session.samesite'));
		Context::set('use_session_keys', Rhymix\Framework\Config::get('session.use_keys'));
		Context::set('use_session_ssl', Rhymix\Framework\Config::get('session.use_ssl'));
		Context::set('use_cookies_ssl', Rhymix\Framework\Config::get('session.use_ssl_cookies'));
		Context::set('check_csrf_token', Rhymix\Framework\Config::get('security.check_csrf_token'));
		Context::set('use_nofollow', Rhymix\Framework\Config::get('security.nofollow'));
		
		$this->setTemplateFile('config_security');
	}
	
	/**
	 * Display Advanced Settings page
	 * @return void
	 */
	function dispAdminConfigAdvanced()
	{
		// Object cache
		$object_cache_types = Rhymix\Framework\Cache::getSupportedDrivers();
		$object_cache_type = Rhymix\Framework\Config::get('cache.type');
		if ($object_cache_type)
		{
			$cache_default_ttl = Rhymix\Framework\Config::get('cache.ttl');
			$cache_servers = Rhymix\Framework\Config::get('cache.servers');
		}
		else
		{
			$cache_config = array_first(Rhymix\Framework\Config::get('cache'));
			if ($cache_config)
			{
				$object_cache_type = preg_replace('/^memcache$/', 'memcached', preg_replace('/:.+$/', '', $cache_config));
			}
			else
			{
				$object_cache_type = 'dummy';
			}
			$cache_default_ttl = 86400;
			$cache_servers = Rhymix\Framework\Config::get('cache');
		}
		
		Context::set('object_cache_types', $object_cache_types);
		Context::set('object_cache_type', $object_cache_type);
		Context::set('cache_default_ttl', $cache_default_ttl);
		
		if ($cache_servers)
		{
			if (preg_match('!^(/.+)(#[0-9]+)?$!', array_first($cache_servers), $matches))
			{
				Context::set('object_cache_host', $matches[1]);
				Context::set('object_cache_port', 0);
				Context::set('object_cache_dbnum', $matches[2] ? substr($matches[2], 1) : 0);
			}
			else
			{
				Context::set('object_cache_host', parse_url(array_first($cache_servers), PHP_URL_HOST) ?: null);
				Context::set('object_cache_port', parse_url(array_first($cache_servers), PHP_URL_PORT) ?: null);
				$cache_dbnum = preg_replace('/[^\d]/', '', parse_url(array_first($cache_servers), PHP_URL_FRAGMENT) ?: parse_url(array_first($cache_servers), PHP_URL_PATH));
				Context::set('object_cache_dbnum', $cache_dbnum === '' ? 1 : intval($cache_dbnum));
			}
		}
		else
		{
			Context::set('object_cache_host', null);
			Context::set('object_cache_port', null);
			Context::set('object_cache_dbnum', 1);
		}
		Context::set('cache_truncate_method', Rhymix\Framework\Config::get('cache.truncate_method'));
		
		// Thumbnail settings
		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('thumbnail_target', $config->thumbnail_target ?: 'all');
		Context::set('thumbnail_type', $config->thumbnail_type ?: 'crop');
		Context::set('thumbnail_quality', $config->thumbnail_quality ?: 75);
		if ($config->thumbnail_type === 'none')
		{
			Context::set('thumbnail_target', 'none');
			Context::set('thumbnail_type', 'crop');
		}
		
		// Default and enabled languages
		Context::set('supported_lang', Rhymix\Framework\Lang::getSupportedList());
		Context::set('default_lang', Rhymix\Framework\Config::get('locale.default_lang'));
		Context::set('enabled_lang', Rhymix\Framework\Config::get('locale.enabled_lang'));
		Context::set('auto_select_lang', Rhymix\Framework\Config::get('locale.auto_select_lang'));
		
		// Default time zone
		Context::set('timezones', Rhymix\Framework\DateTime::getTimezoneList());
		Context::set('selected_timezone', Rhymix\Framework\Config::get('locale.default_timezone'));
		
		// Other settings
		Context::set('use_rewrite', Rhymix\Framework\Router::getRewriteLevel());
		Context::set('use_mobile_view', (config('mobile.enabled') !== null ? config('mobile.enabled') : config('use_mobile_view')) ? true : false);
		Context::set('tablets_as_mobile', config('mobile.tablets') ? true : false);
		Context::set('mobile_viewport', config('mobile.viewport') ?? HTMLDisplayHandler::DEFAULT_VIEWPORT);
		Context::set('use_ssl', Rhymix\Framework\Config::get('url.ssl'));
		Context::set('delay_session', Rhymix\Framework\Config::get('session.delay'));
		Context::set('use_db_session', Rhymix\Framework\Config::get('session.use_db'));
		Context::set('manager_layout', Rhymix\Framework\Config::get('view.manager_layout'));
		Context::set('minify_scripts', Rhymix\Framework\Config::get('view.minify_scripts'));
		Context::set('concat_scripts', Rhymix\Framework\Config::get('view.concat_scripts'));
		Context::set('use_server_push', Rhymix\Framework\Config::get('view.server_push'));
		Context::set('use_gzip', Rhymix\Framework\Config::get('view.use_gzip'));
		
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
		Context::set('debug_log_slow_queries', Rhymix\Framework\Config::get('debug.log_slow_queries'));
		Context::set('debug_log_slow_triggers', Rhymix\Framework\Config::get('debug.log_slow_triggers'));
		Context::set('debug_log_slow_widgets', Rhymix\Framework\Config::get('debug.log_slow_widgets'));
		Context::set('debug_log_slow_remote_requests', Rhymix\Framework\Config::get('debug.log_slow_remote_requests'));
		Context::set('debug_log_filename', Rhymix\Framework\Config::get('debug.log_filename') ?: 'files/debug/YYYYMMDD.php');
		Context::set('debug_display_type', (array)Rhymix\Framework\Config::get('debug.display_type'));
		Context::set('debug_display_content', Rhymix\Framework\Config::get('debug.display_content'));
		Context::set('debug_display_to', Rhymix\Framework\Config::get('debug.display_to'));
		Context::set('debug_write_error_log', Rhymix\Framework\Config::get('debug.write_error_log'));
		
		// IP access control
		$allowed_ip = Rhymix\Framework\Config::get('debug.allow');
		Context::set('debug_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		$this->setTemplateFile('config_debug');
	}
	
	/**
	 * Display Debug Settings page
	 * @return void
	 */
	function dispAdminConfigSEO()
	{
		// Meta keywords and description
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		Context::set('site_meta_keywords', escape($config->meta_keywords));
		Context::set('site_meta_description', escape($config->meta_description));
		
		// Titles
		Context::set('seo_main_title', escape(Rhymix\Framework\Config::get('seo.main_title') ?: '$SITE_TITLE - $SITE_SUBTITLE'));
		Context::set('seo_subpage_title', escape(Rhymix\Framework\Config::get('seo.subpage_title') ?: '$SITE_TITLE - $SUBPAGE_TITLE'));
		Context::set('seo_document_title', escape(Rhymix\Framework\Config::get('seo.document_title') ?: '$SITE_TITLE - $DOCUMENT_TITLE'));
		
		// OpenGraph metadata
		Context::set('og_enabled', Rhymix\Framework\Config::get('seo.og_enabled'));
		Context::set('og_extract_description', Rhymix\Framework\Config::get('seo.og_extract_description'));
		Context::set('og_extract_images', Rhymix\Framework\Config::get('seo.og_extract_images'));
		Context::set('og_extract_hashtags', Rhymix\Framework\Config::get('seo.og_extract_hashtags'));
		Context::set('og_use_timestamps', Rhymix\Framework\Config::get('seo.og_use_timestamps'));
		Context::set('twitter_enabled', Rhymix\Framework\Config::get('seo.twitter_enabled'));
		
		$this->setTemplateFile('config_seo');
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
		Context::set('sitelock_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', RX_CLIENT_IP);
		
		$this->setTemplateFile('config_sitelock');
	}
	
	/**
	 * Display domain edit screen
	 * @return void
	 */
	function dispAdminInsertDomain()
	{
		// Get selected domain.
		$domain_srl = strval(Context::get('domain_srl'));
		$domain_info = null;
		if ($domain_srl !== '')
		{
			$domain_info = getModel('module')->getSiteInfo($domain_srl);
			if ($domain_info->domain_srl != $domain_srl)
			{
				throw new Rhymix\Framework\Exception('msg_domain_not_found');
			}
		}
		Context::set('domain_info', $domain_info);
		
		// Get modules.
		if ($domain_info && $domain_info->index_module_srl)
		{
			$index_module_srl = $domain_info->index_module_srl;
		}
		else
		{
			$index_module_srl = '';
		}
		Context::set('index_module_srl', $index_module_srl);
		
		// Get language list.
		Context::set('supported_lang', Rhymix\Framework\Lang::getSupportedList());
		Context::set('enabled_lang', Rhymix\Framework\Config::get('locale.enabled_lang'));
		if ($domain_info && $domain_info->settings->language)
		{
			$domain_lang = $domain_info->settings->language;
		}
		else
		{
			$domain_lang = Rhymix\Framework\Config::get('locale.default_lang');
		}
		Context::set('domain_lang', $domain_lang);
		
		// Get timezone list.
		Context::set('timezones', Rhymix\Framework\DateTime::getTimezoneList());
		if ($domain_info && $domain_info->settings->timezone)
		{
			$domain_timezone = $domain_info->settings->timezone;
		}
		else
		{
			$domain_timezone = Rhymix\Framework\Config::get('locale.default_timezone');
		}
		Context::set('domain_timezone', $domain_timezone);
		
		// Get favicon and images.
		if ($domain_info)
		{
			$oAdminAdminModel = getAdminModel('admin');
			Context::set('favicon_url', $oAdminAdminModel->getFaviconUrl($domain_info->domain_srl));
			Context::set('mobicon_url', $oAdminAdminModel->getMobileIconUrl($domain_info->domain_srl));
			Context::set('default_image_url', $oAdminAdminModel->getSiteDefaultImageUrl($domain_info->domain_srl));
		}
		
		$this->setTemplateFile('config_domains_edit');
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

		$oAdmin = getClass('admin');
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuByTitle($oAdmin->getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);
		$this->setTemplateFile('admin_setup');
	}

	/**
	 * Retrun server environment to XML string
	 * @return object
	*/
	function dispAdminViewServerEnv()
	{
		$info = array();
		$skip = array(
			'phpext' => array('core', 'session', 'spl', 'standard', 'date', 'ctype', 'tokenizer', 'apache2handler', 'filter', 'reflection'),
			'module' => array('addon', 'admin', 'autoinstall', 'comment', 'communication', 'counter', 'document', 'editor', 'file', 'importer', 'install', 'integration_search', 'layout', 'member', 'menu', 'message', 'module', 'opage', 'page', 'point', 'poll', 'rss', 'session', 'spamfilter', 'tag', 'trackback', 'trash', 'widget'),
			'addon' => array('autolink', 'blogapi', 'captcha', 'counter', 'member_communication', 'member_extra_info', 'mobile', 'openid_delegation_id', 'point_level_icon', 'resize_image'),
			'layout' => array('default'),
			'widget' => array('content', 'language_select', 'login_info', 'mcontent'),
			'widgetstyle' => array(),
		);
		
		// Basic environment
		$info[] = '[Basic Information]';
		$info['rhymix_version'] = RX_VERSION;
		$info['date'] = Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Y-m-d H:i:s O') . ' (' . gmdate('Y-m-d H:i:s') . ' UTC)';
		$info['php'] = sprintf('%s (%d-bit)', phpversion(), PHP_INT_SIZE * 8);
		$info['server'] = $_SERVER['SERVER_SOFTWARE'];
		$info['os'] = sprintf('%s %s', php_uname('s'), php_uname('r'));
		$info['sapi'] = php_sapi_name();
		$info['baseurl'] = Context::getRequestUri();
		$info['basedir'] = RX_BASEDIR;
		$info['owner'] = sprintf('%s (%d:%d)', get_current_user(), getmyuid(), getmygid());
		if (function_exists('posix_getpwuid') && function_exists('posix_geteuid') && $user = @posix_getpwuid(posix_geteuid()))
		{
			$info['user'] = sprintf('%s (%d:%d)', $user['name'], $user['uid'], $user['gid']);
		}
		else
		{
			$info['user'] = 'unknown';
		}
		$info['ssl'] = Context::get('site_module_info')->security ?: Context::getDbInfo()->use_ssl;
		$info[] = '';
		
		// System settings
		$info[] = '[System Settings]';
		$info['db.type'] = preg_replace('/^mysql.+/', 'mysql', config('db.master.type'));
		$db_extra_info = array();
		if (config('db.master.engine')) $db_extra_info[] = config('db.master.engine');
		if (config('db.master.charset')) $db_extra_info[] = config('db.master.charset');
		if (count($db_extra_info))
		{
			$info['db.type'] .= ' (' . implode(', ', $db_extra_info) . ')';
		}
		$info['db.version'] = Rhymix\Framework\DB::getInstance()->db_version;
		if (preg_match('/\d+\.\d+\.\d+-MariaDB.*$/', $info['db.version'], $matches))
		{
			$info['db.version'] = $matches[0];
		}
		$info['cache.type'] = config('cache.type') ?: 'none';
		$info['file.folder_structure'] = config('file.folder_structure');
		$info['file.umask'] = config('file.umask');
		$info['url.rewrite'] = Rhymix\Framework\Router::getRewriteLevel();
		$info['locale.default_lang'] = config('locale.default_lang');
		$info['locale.default_timezone'] = config('locale.default_timezone');
		$info['locale.internal_timezone'] = config('locale.internal_timezone');
		$info['mobile.enabled'] = config('mobile.enabled') ? 'true' : 'false';
		$info['mobile.tablets'] = config('mobile.tablets') ? 'true' : 'false';
		$info['session.delay'] = config('session.delay') ? 'true' : 'false';
		$info['session.use_db'] = config('session.use_db') ? 'true' : 'false';
		$info['session.use_keys'] = config('session.use_keys') ? 'true' : 'false';
		$info['session.use_ssl'] = config('session.use_ssl') ? 'true' : 'false';
		$info['session.use_ssl_cookies'] = config('session.use_ssl_cookies') ? 'true' : 'false';
		$info['view.concat_scripts'] = config('view.concat_scripts');
		$info['view.minify_scripts'] = config('view.minify_scripts');
		$info['use_sso'] = config('use_sso') ? 'true' : 'false';
		$info[] = '';
		
		// PHP settings
		$ini_info = ini_get_all();
		$info[] = '[PHP Settings]';
		$info['session.auto_start'] = $ini_info['session.auto_start']['local_value'];
		$info['max_file_uploads'] = $ini_info['max_file_uploads']['local_value'];
		$info['memory_limit'] = $ini_info['memory_limit']['local_value'];
		$info['post_max_size'] = $ini_info['post_max_size']['local_value'];
		$info['upload_max_filesize'] = $ini_info['upload_max_filesize']['local_value'];
		$info['extensions'] = array();
		foreach(get_loaded_extensions() as $ext)
		{
			$ext = strtolower($ext);
			if (!in_array($ext, $skip['phpext']))
			{
				$info['extensions'][] = $ext;
			}
		}
		natcasesort($info['extensions']);
		$info[] = '';
		
		// Modules
		$info[] = '[Modules]';
		$info['module'] = array();
		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList() ?: array();
		foreach ($module_list as $module)
		{
			if (!in_array($module->module, $skip['module']))
			{
				$moduleInfo = $oModuleModel->getModuleInfoXml($module->module);
				$info['module'][] = sprintf('%s (%s)', $module->module, $moduleInfo->version);
			}
		}
		natcasesort($info['module']);
		$info[] = '';
		
		// Addons
		$info[] = '[Addons]';
		$info['addon'] = array();
		$oAddonAdminModel = getAdminModel('addon');
		$addon_list = $oAddonAdminModel->getAddonList() ?: array();
		foreach ($addon_list as $addon)
		{
			if (!in_array($addon->addon, $skip['addon']))
			{
				$addonInfo = $oAddonAdminModel->getAddonInfoXml($addon->addon);
				$info['addon'][] = sprintf('%s (%s)', $addon->addon, $addonInfo->version);
			}
		}
		natcasesort($info['addon']);
		$info[] = '';
		
		// Layouts
		$info[] = '[Layouts]';
		$info['layout'] = array();
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getDownloadedLayoutList() ?: array();
		foreach($layout_list as $layout)
		{
			if (!in_array($layout->layout, $skip['layout']))
			{
				$layoutInfo = $oLayoutModel->getLayoutInfo($layout->layout);
				$info['layout'][] = sprintf('%s (%s)', $layout->layout, $layoutInfo->version);
			}
		}
		natcasesort($info['layout']);
		$info[] = '';
		
		// Widgets
		$info[] = '[Widgets]';
		$info['widget'] = array();
		$oWidgetModel = getModel('widget');
		$widget_list = $oWidgetModel->getDownloadedWidgetList() ?: array();
		foreach ($widget_list as $widget)
		{
			if (!in_array($widget->widget, $skip['widget']))
			{
				$widgetInfo = $oWidgetModel->getWidgetInfo($widget->widget);
				$info['widget'][] = sprintf('%s (%s)', $widget->widget, $widgetInfo->version);
			}
		}
		natcasesort($info['widget']);
		$info[] = '';
		
		// Widgetstyles
		$info[] = '[Widgetstyles]';
		$info['widgetstyle'] = array();
		$oWidgetModel = getModel('widget');
		$widgetstyle_list = $oWidgetModel->getDownloadedWidgetStyleList() ?: array();
		foreach ($widgetstyle_list as $widgetstyle)
		{
			if (!in_array($widgetstyle->widgetStyle, $skip['widgetstyle']))
			{
				$widgetstyleInfo = $oWidgetModel->getWidgetStyleInfo($widgetstyle->widgetStyle);
				$info['widgetstyle'][] = sprintf('%s (%s)', $widgetstyle->widgetStyle, $widgetstyleInfo->version);
			}
		}
		natcasesort($info['widgetstyle']);
		$info[] = '';
		
		// Convert to string.
		foreach ($info as $key => $value)
		{
			if (is_array($value))
			{
				$value = implode(', ', $value);
			}
			
			if (is_int($key) || ctype_digit($key))
			{
				$str_info .= "$value\n";
			}
			else
			{
				$str_info .= "$key : $value\n";
			}
		}

		Context::set('str_info', $str_info);
		$this->setTemplateFile('server_env.html');
	}

}
/* End of file admin.admin.view.php */
/* Location: ./modules/admin/admin.admin.view.php */
