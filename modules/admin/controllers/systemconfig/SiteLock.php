<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Filters\IpFilter;
use Rhymix\Modules\Admin\Controllers\Base;

class SiteLock extends Base
{
	/**
	 * Display Sitelock Settings page
	 */
	public function dispAdminConfigSitelock()
	{
		Context::set('sitelock_locked', Config::get('lock.locked'));
		Context::set('sitelock_title', escape(Config::get('lock.title')));
		Context::set('sitelock_message', escape(Config::get('lock.message')));

		$allowed_ip = Config::get('lock.allow') ?: array();
		Context::set('sitelock_allowed_ip', implode(\PHP_EOL, $allowed_ip));
		Context::set('remote_addr', \RX_CLIENT_IP);

		$this->setTemplateFile('config_sitelock');
	}

	/**
	 * Update sitelock configuration.
	 */
	public function procAdminUpdateSitelock()
	{
		$vars = Context::gets('sitelock_locked', 'sitelock_allowed_ip', 'sitelock_title', 'sitelock_message');

		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->sitelock_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));

		if (!IpFilter::validateRanges($allowed_ip))
		{
			throw new Exception('msg_invalid_ip');
		}

		Config::set('lock.locked', $vars->sitelock_locked === 'Y');
		Config::set('lock.title', trim($vars->sitelock_title));
		Config::set('lock.message', trim($vars->sitelock_message));
		Config::set('lock.allow', array_values($allowed_ip));
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSitelock'));
	}
}
