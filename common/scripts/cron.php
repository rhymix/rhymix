<?php

/**
 * This script runs the task queue.
 *
 * Unlike other scripts provided with Rhymix, it can be called
 * both on the command line and over the network.
 */
define('RXQUEUE_CRON', true);

// If called on the CLI, run additional checks.
if (PHP_SAPI === 'cli')
{
	require_once __DIR__ . '/common.php';
}
else
{
	// If called over the network, load Rhymix directly.
	chdir(dirname(dirname(__DIR__)));
	require_once dirname(__DIR__) . '/autoload.php';
	Context::init();

	// On the other hand, we should check the key.
	$key = (string)Context::get('key');
	if (!Rhymix\Framework\Queue::checkKey($key))
	{
		Context::setCacheControl(0);
		header('HTTP/1.1 403 Forbidden');
		echo "Invalid key\n";
		Context::close();
		exit;
	}
}

// Get queue configuration set by the administrator.
$display_errors = config('queue.display_errors') === false ? false : true;
$timeout = (config('queue.interval') ?? 1) * 60;
$process_count = config('queue.process_count') ?? 1;

// If called over the network, try to increase the timeout.
if (PHP_SAPI !== 'cli')
{
	ignore_user_abort(true);
	set_time_limit(max(60, $timeout));
	if ($display_errors)
	{
		ini_set('display_errors', true);
	}
	if (Rhymix\Framework\Session::checkStart())
	{
		Rhymix\Framework\Session::close();
	}
}

// Create multiple processes if configured.
if (PHP_SAPI === 'cli' && $process_count > 1 && function_exists('pcntl_fork') && function_exists('pcntl_waitpid'))
{
	// This array will keep a dictionary of subprocesses.
	$pids = [];

	// The database connection must be closed before forking.
	Rhymix\Framework\DB::getInstance()->disconnect();
	Rhymix\Framework\Debug::disable();

	// Create the required number of subprocesses.
	for ($i = 0; $i < $process_count; $i++)
	{
		$pid = pcntl_fork();
		if ($pid > 0)
		{
			$pids[$pid] = true;
			usleep(200000);
		}
		elseif ($pid == 0)
		{
			Rhymix\Framework\Queue::process($i, $process_count, $timeout);
			exit;
		}
		else
		{
			error_log('RxQueue: could not fork!');
			exit;
		}
	}

	// The parent process waits for its children to finish.
	while (count($pids))
	{
		$pid = pcntl_waitpid(-1, $status, \WNOHANG);
		if ($pid)
		{
			unset($pids[$pid]);
		}
		usleep(200000);
	}
}
else
{
	Rhymix\Framework\Queue::process(0, 1, $timeout);
}

// If called over the network, display a simple OK message to indicate success.
if (PHP_SAPI !== 'cli')
{
	echo "OK\n";
}
