<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use FileHandler;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Filters\IpFilter;
use Rhymix\Modules\Admin\Controllers\Base;

class Debug extends Base
{
	/**
	 * Display Debug Settings page
	 */
	public function dispAdminConfigDebug()
	{
		// Load debug settings.
		Context::set('debug_enabled', Config::get('debug.enabled'));
		Context::set('debug_log_slow_queries', Config::get('debug.log_slow_queries'));
		Context::set('debug_log_slow_triggers', Config::get('debug.log_slow_triggers'));
		Context::set('debug_log_slow_widgets', Config::get('debug.log_slow_widgets'));
		Context::set('debug_log_slow_remote_requests', Config::get('debug.log_slow_remote_requests'));
		Context::set('debug_log_filename', Config::get('debug.log_filename') ?: 'files/debug/YYYYMMDD.php');
		Context::set('debug_display_type', (array)Config::get('debug.display_type'));
		Context::set('debug_display_content', Config::get('debug.display_content'));
		Context::set('debug_display_to', Config::get('debug.display_to') ?? 'admin');
		Context::set('debug_query_comment', Config::get('debug.query_comment') ?? false);
		Context::set('debug_query_full_stack', Config::get('debug.query_full_stack') ?? false);
		Context::set('debug_consolidate', Config::get('debug.consolidate') ?? true);
		Context::set('debug_write_error_log', Config::get('debug.write_error_log') ?? 'fatal');

		// IP access control
		$allowed_ip = Config::get('debug.allow');
		Context::set('debug_allowed_ip', implode(PHP_EOL, $allowed_ip));
		Context::set('remote_addr', RX_CLIENT_IP);

		$this->setTemplateFile('config_debug');
	}

	/**
	 * Update debug configuration.
	 */
	public function procAdminUpdateDebug()
	{
		$vars = Context::getRequestVars();

		// Save display type settings
		$display_type = array_values(array_filter($vars->debug_display_type ?: [], function($str) {
			return in_array($str, ['panel', 'comment', 'file']);
		}));

		// Debug settings
		Config::set('debug.enabled', $vars->debug_enabled === 'Y');
		Config::set('debug.log_slow_queries', max(0, floatval($vars->debug_log_slow_queries)));
		Config::set('debug.log_slow_triggers', max(0, floatval($vars->debug_log_slow_triggers)));
		Config::set('debug.log_slow_widgets', max(0, floatval($vars->debug_log_slow_widgets)));
		Config::set('debug.log_slow_remote_requests', max(0, floatval($vars->debug_log_slow_remote_requests)));
		Config::set('debug.display_type', $display_type);
		Config::set('debug.display_to', strval($vars->debug_display_to) ?: 'admin');
		Config::set('debug.query_comment', $vars->debug_query_comment === 'Y');
		Config::set('debug.query_full_stack', $vars->debug_query_full_stack === 'Y');
		Config::set('debug.consolidate', $vars->debug_consolidate === 'Y');
		Config::set('debug.write_error_log', strval($vars->debug_write_error_log) ?: 'fatal');

		// Debug content
		$debug_content = array_values($vars->debug_display_content ?: array());
		Config::set('debug.display_content', $debug_content);

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
			throw new Exception('msg_debug_log_filename_not_writable');
		}
		if (!file_exists(dirname(RX_BASEDIR . $log_filename)) && !FileHandler::makeDir(dirname(RX_BASEDIR . $log_filename)))
		{
			throw new Exception('msg_debug_log_filename_not_writable');
		}
		if (!is_writable(dirname(RX_BASEDIR . $log_filename)))
		{
			throw new Exception('msg_debug_log_filename_not_writable');
		}
		Config::set('debug.log_filename', $log_filename);

		// IP access control
		$allowed_ip = array_map('trim', preg_split('/[\r\n]/', $vars->debug_allowed_ip));
		$allowed_ip = array_unique(array_filter($allowed_ip, function($item) {
			return $item !== '';
		}));
		if (!IpFilter::validateRanges($allowed_ip)) {
			throw new Exception('msg_invalid_ip');
		}
		Config::set('debug.allow', array_values($allowed_ip));

		// Save
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigDebug'));
	}
}
