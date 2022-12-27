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
class AdminAdminView extends Admin
{
	/**
	 * Make the admin menu.
	 */
	public function makeGnbUrl($module = 'admin')
	{
		Rhymix\Modules\Admin\Controllers\Base::getInstance()->loadAdminMenu($module);
	}

	/**
	 * Display Notification Settings page
	 * @return void
	 */
	public function dispAdminConfigNotification()
	{
		// Load advanced mailer module (for lang).
		$oAdvancedMailerAdminView = getAdminView('advanced_mailer');
		
		// Load advanced mailer config.
		$advanced_mailer_config = $oAdvancedMailerAdminView->getConfig();
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		
		// Load member config.
		$member_config = getModel('module')->getModuleConfig('member');
		Context::set('member_config', $member_config);
		Context::set('webmaster_name', !empty($member_config->webmaster_name) ? $member_config->webmaster_name : 'webmaster');
		Context::set('webmaster_email', $member_config->webmaster_email ?? '');
		
		// Load module config.
		$module_config = getModel('module')->getModuleConfig('module');
		Context::set('module_config', $module_config);
		
		// Load mail drivers.
		$mail_drivers = Rhymix\Framework\Mail::getSupportedDrivers();
		uasort($mail_drivers, function($a, $b) {
			if ($a['name'] === 'Dummy') return -1;
			if ($b['name'] === 'Dummy') return 1;
			return strnatcasecmp($a['name'], $b['name']);
		});
		Context::set('mail_drivers', $mail_drivers);
		Context::set('mail_driver', config('mail.type') ?: 'mailfunction');
		
		// Load SMS drivers.
		$sms_drivers = Rhymix\Framework\SMS::getSupportedDrivers();
		uasort($sms_drivers, function($a, $b) {
			if ($a['name'] === 'Dummy') return -1;
			if ($b['name'] === 'Dummy') return 1;
			return strnatcasecmp($a['name'], $b['name']);
		});
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
	public function dispAdminConfigSecurity()
	{
		// Load embed filter.
		context::set('mediafilter_whitelist', implode(PHP_EOL, Rhymix\Framework\Filters\MediaFilter::getWhitelist()));
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
	public function dispAdminConfigAdvanced()
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
				Context::set('object_cache_user', parse_url(array_first($cache_servers), PHP_URL_USER) ?? '');
				Context::set('object_cache_pass', parse_url(array_first($cache_servers), PHP_URL_PASS) ?? '');
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
		Context::set('thumbnail_type', $config->thumbnail_type ?: 'fill');
		Context::set('thumbnail_quality', $config->thumbnail_quality ?: 75);
		if ($config->thumbnail_type === 'none')
		{
			Context::set('thumbnail_target', 'none');
			Context::set('thumbnail_type', 'fill');
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
	public function dispAdminConfigDebug()
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
		Context::set('debug_query_comment', Rhymix\Framework\Config::get('debug.query_comment'));
		Context::set('debug_query_full_stack', Rhymix\Framework\Config::get('debug.query_full_stack'));
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
	public function dispAdminConfigSEO()
	{
		// Meta keywords and description
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		Context::set('site_meta_keywords', escape($config->meta_keywords ?? ''));
		Context::set('site_meta_description', escape($config->meta_description ?? ''));
		
		// Titles
		Context::set('seo_main_title', escape(Rhymix\Framework\Config::get('seo.main_title') ?: '$SITE_TITLE - $SITE_SUBTITLE'));
		Context::set('seo_subpage_title', escape(Rhymix\Framework\Config::get('seo.subpage_title') ?: '$SITE_TITLE - $SUBPAGE_TITLE'));
		Context::set('seo_document_title', escape(Rhymix\Framework\Config::get('seo.document_title') ?: '$SITE_TITLE - $DOCUMENT_TITLE'));
		
		// OpenGraph metadata
		Context::set('og_enabled', Rhymix\Framework\Config::get('seo.og_enabled'));
		Context::set('og_extract_description', Rhymix\Framework\Config::get('seo.og_extract_description'));
		Context::set('og_extract_images', Rhymix\Framework\Config::get('seo.og_extract_images'));
		Context::set('og_extract_hashtags', Rhymix\Framework\Config::get('seo.og_extract_hashtags'));
		Context::set('og_use_nick_name', Rhymix\Framework\Config::get('seo.og_use_nick_name'));
		Context::set('og_use_timestamps', Rhymix\Framework\Config::get('seo.og_use_timestamps'));
		Context::set('twitter_enabled', Rhymix\Framework\Config::get('seo.twitter_enabled'));
		
		$this->setTemplateFile('config_seo');
	}
	
	/**
	 * Display Sitelock Settings page
	 * @return void
	 */
	public function dispAdminConfigSitelock()
	{
		Context::set('sitelock_locked', Rhymix\Framework\Config::get('lock.locked'));
		Context::set('sitelock_title', escape(Rhymix\Framework\Config::get('lock.title')));
		Context::set('sitelock_message', escape(Rhymix\Framework\Config::get('lock.message')));
		
		$allowed_ip = Rhymix\Framework\Config::get('lock.allow') ?: array();
		Context::set('sitelock_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', \RX_CLIENT_IP);
		
		$this->setTemplateFile('config_sitelock');
	}
	
	/**
	 * Display FTP Configuration(settings) page
	 * @return void
	 */
	public function dispAdminConfigFtp()
	{
		Context::set('ftp_info', Rhymix\Framework\Config::get('ftp'));
		Context::set('sftp_support', function_exists('ssh2_sftp'));

		$this->setTemplateFile('config_ftp');
	}
	
	/**
	 * Display Admin Menu Configuration(settings) page
	 * @return void
	 */
	public function dispAdminSetup()
	{
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuByTitle($this->getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);
		$this->setTemplateFile('admin_setup');
	}
}
/* End of file admin.admin.view.php */
/* Location: ./modules/admin/admin.admin.view.php */
