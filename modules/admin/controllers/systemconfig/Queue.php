<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Queue as RFQueue;
use Rhymix\Framework\Security;
use Rhymix\Modules\Admin\Controllers\Base;

class Queue extends Base
{
	/**
	 * Display Notification Settings page
	 */
	public function dispAdminConfigQueue()
	{
		// Load queue drivers.
		$queue_drivers = RFQueue::getSupportedDrivers();
		uasort($queue_drivers, function($a, $b) {
			if ($a['name'] === 'Dummy') return -1;
			if ($b['name'] === 'Dummy') return 1;
			return strnatcasecmp($a['name'], $b['name']);
		});
		Context::set('queue_drivers', $queue_drivers);
		Context::set('queue_driver', config('queue.driver') ?: 'dummy');

		// Set the default auth key.
		if (!config('queue.key'))
		{
			config('queue.key', Security::getRandom(32));
		}

		// Set defaults for Redis.
		if (!config('queue.redis'))
		{
			config('queue.redis', [
				'host' => '127.0.0.1',
				'port' => '6379',
				'dbnum' => 0,
			]);
		}

		$this->setTemplateFile('config_queue');
	}

	/**
	 * Update notification configuration.
	 */
	public function procAdminUpdateQueue()
	{
		$vars = Context::getRequestVars();

		// Enabled?
		$enabled = $vars->queue_enabled === 'Y';

		// Validate the driver.
		$drivers = RFQueue::getSupportedDrivers();
		$driver = trim($vars->queue_driver);
		if (!array_key_exists($driver, $drivers))
		{
			throw new Exception('msg_queue_driver_not_found');
		}
		if ($enabled && (!$driver || $driver === 'dummy'))
		{
			throw new Exception('msg_queue_driver_cannot_be_dummy');
		}

		// Validate required and optional driver settings.
		$driver_config = [];
		foreach ($drivers[$driver]['required'] as $conf_name)
		{
			$conf_value = trim($vars->{'queue_' . $driver . '_' . $conf_name} ?? '');
			if ($conf_value === '')
			{
				throw new Exception('msg_queue_invalid_config');
			}
			$driver_config[$conf_name] = $conf_value === '' ? null : $conf_value;
		}
		foreach ($drivers[$driver]['optional'] as $conf_name)
		{
			$conf_value = trim($vars->{'queue_' . $driver . '_' . $conf_name} ?? '');
			$driver_config[$conf_name] = $conf_value === '' ? null : $conf_value;
		}

		// Validate error display setting.
		$display_errors = Context::get('webcron_display_errors') === 'Y' ? true : false;

		// Validate the interval.
		$interval = intval($vars->queue_interval ?? 1);
		if ($interval < 1 || $interval > 10)
		{
			throw new Exception('msg_queue_invalid_interval');
		}

		// Validate the process count.
		$process_count = intval($vars->queue_process_count ?? 1);
		if ($process_count < 1 || $process_count > 10)
		{
			throw new Exception('msg_queue_invalid_process_count');
		}

		// Validate the key.
		$key = trim($vars->queue_key ?? '');
		if (strlen($key) < 16 || !ctype_alnum($key))
		{
			throw new Exception('msg_queue_invalid_key');
		}

		// Validate actual operation of the driver.
		$driver_class = '\\Rhymix\\Framework\\Drivers\\Queue\\' . $driver;
		if (!class_exists($driver_class) || !$driver_class::isSupported())
		{
			throw new Exception('msg_queue_driver_not_found');
		}
		if (!$driver_class::validateConfig($driver_config))
		{
			throw new Exception('msg_queue_driver_not_usable');
		}

		// Save system config.
		Config::set("queue.enabled", $enabled);
		Config::set("queue.driver", $driver);
		Config::set("queue.display_errors", $display_errors);
		Config::set("queue.interval", $interval);
		Config::set("queue.process_count", $process_count);
		Config::set("queue.key", $key);
		Config::set("queue.$driver", $driver_config);
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigQueue'));
	}
}
