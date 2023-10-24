<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Filters\IpFilter;
use Rhymix\Framework\Filters\MediaFilter;
use Rhymix\Modules\Admin\Controllers\Base;

class Security extends Base
{
	/**
	 * Display Security Settings page
	 */
	public function dispAdminConfigSecurity()
	{
		// Load embed filter.
		context::set('mediafilter_whitelist', implode(PHP_EOL, MediaFilter::getWhitelist()));
		context::set('mediafilter_classes', implode(PHP_EOL, Config::get('mediafilter.classes') ?: array()));

		// Load robot user agents.
		$robot_user_agents = Config::get('security.robot_user_agents') ?: array();
		Context::set('robot_user_agents', implode(PHP_EOL, $robot_user_agents));

		// Admin IP access control
		$allowed_ip = Config::get('admin.allow');
		Context::set('admin_allowed_ip', implode(PHP_EOL, $allowed_ip));
		$denied_ip = Config::get('admin.deny');
		Context::set('admin_denied_ip', implode(PHP_EOL, $denied_ip));
		Context::set('remote_addr', RX_CLIENT_IP);

		// Session and cookie security settings
		Context::set('autologin_lifetime', Config::get('session.autologin_lifetime') ?: 365);
		Context::set('autologin_refresh', Config::get('session.autologin_refresh') ?? true);
		Context::set('use_httponly', Config::get('session.httponly'));
		Context::set('use_samesite', Config::get('session.samesite'));
		Context::set('use_session_ssl', Config::get('session.use_ssl'));
		Context::set('use_cookies_ssl', Config::get('session.use_ssl_cookies'));
		Context::set('check_csrf_token', Config::get('security.check_csrf_token'));
		Context::set('use_nofollow', Config::get('security.nofollow'));
		Context::set('x_frame_options', Config::get('security.x_frame_options'));
		Context::set('x_content_type_options', Config::get('security.x_content_type_options'));

		$this->setTemplateFile('config_security');
	}

	/**
	 * Update security configuration.
	 */
	public function procAdminUpdateSecurity()
	{
		$vars = Context::getRequestVars();

		// Media Filter iframe/embed whitelist
		$whitelist = $vars->mediafilter_whitelist;
		$whitelist = array_filter(array_map('trim', preg_split('/[\r\n]/', $whitelist)), function($item) {
			return $item !== '';
		});
		$whitelist = array_unique(array_map(function($item) {
			return MediaFilter::formatPrefix($item);
		}, $whitelist));
		natcasesort($whitelist);
		Config::set('mediafilter.whitelist', array_values($whitelist));
		Config::set('mediafilter.iframe', []);
		Config::set('mediafilter.object', []);

		// HTML classes
		$classes = $vars->mediafilter_classes;
		$classes = array_filter(array_map('trim', preg_split('/[\r\n]/', $classes)), function($item) {
			return preg_match('/^[a-zA-Z0-9_-]+$/u', $item);
		});
		natcasesort($classes);
		Config::set('mediafilter.classes', array_values($classes));

		// Robot user agents
		$robot_user_agents = $vars->robot_user_agents;
		$robot_user_agents = array_filter(array_map('trim', preg_split('/[\r\n]/', $robot_user_agents)), function($item) {
			return $item !== '';
		});
		Config::set('security.robot_user_agents', array_values($robot_user_agents));

		// Remove old embed filter
		$config = Config::getAll();
		unset($config['embedfilter']);
		Config::setAll($config);

		// Admin IP access control
		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->admin_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));
		if (!IpFilter::validateRanges($allowed_ip)) {
			throw new Exception('msg_invalid_ip');
		}

		$denied_ip = array_map('trim', preg_split('/[\r\n]/', $vars->admin_denied_ip));
		$denied_ip = array_unique(array_filter($denied_ip, function($item) {
			return $item !== '';
		}));
		if (!IpFilter::validateRanges($denied_ip)) {
			throw new Exception('msg_invalid_ip');
		}

		$oMemberAdminModel = getAdminModel('member');
		if (!$oMemberAdminModel->getMemberAdminIPCheck($allowed_ip, $denied_ip))
		{
			throw new Exception('msg_current_ip_will_be_denied');
		}

		$site_module_info = Context::get('site_module_info');
		if (!in_array($vars->use_samesite ?? '', ['Strict', 'Lax', 'None', '']))
		{
			$vars->use_samesite = '';
		}
		if ($vars->use_samesite === 'None' && ($vars->use_session_ssl !== 'Y' || $site_module_info->security !== 'always'))
		{
			$vars->use_samesite = '';
		}
		if (!in_array($vars->x_frame_options ?? '', ['DENY', 'SAMEORIGIN', '']))
		{
			$vars->x_frame_options = '';
		}
		if (!in_array($vars->x_content_type_options ?? '', ['nosniff', '']))
		{
			$vars->x_content_type_options = '';
		}

		Config::set('admin.allow', array_values($allowed_ip));
		Config::set('admin.deny', array_values($denied_ip));
		Config::set('session.autologin_lifetime', max(1, min(400, intval($vars->autologin_lifetime))));
		Config::set('session.autologin_refresh', ($vars->autologin_refresh ?? 'N') === 'Y');
		Config::set('session.httponly', $vars->use_httponly === 'Y');
		Config::set('session.samesite', $vars->use_samesite);
		Config::set('session.use_ssl', $vars->use_session_ssl === 'Y');
		Config::set('session.use_ssl_cookies', $vars->use_cookies_ssl === 'Y');
		Config::set('security.check_csrf_token', $vars->check_csrf_token === 'Y');
		Config::set('security.nofollow', $vars->use_nofollow === 'Y');
		Config::set('security.x_frame_options', strtoupper($vars->x_frame_options));
		Config::set('security.x_content_type_options', strtolower($vars->x_content_type_options));

		// Prepare the alternate config key for cookies.
		if (Config::get('cookie'))
		{
			Config::set('cookie.secure', $vars->use_cookies_ssl === 'Y');
		}
		else
		{
			Config::set('cookie', [
				'domain' => null,
				'path' => null,
				'secure' => $vars->use_cookies_ssl === 'Y',
				'httponly' => null,
				'samesite' => 'Lax',
			]);
		}

		// Save
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSecurity'));
	}
}
