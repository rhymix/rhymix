<?php

/**
 * This script runs the task queue.
 *
 * Unlike other scripts provided with Rhymix, it can be called
 * both on the command line and over the network.
 */

if (PHP_SAPI === 'cli')
{
	require_once __DIR__ . '/common.php';
}
else
{
	// If called over the network, bypass CLI checks.
	chdir(dirname(dirname(__DIR__)));
	require_once dirname(__DIR__) . '/autoload.php';
	Context::init();

	// On the other hand, we should check the key.
	$key = (string)Context::get('key');
	if (!Rhymix\Framework\Queue::checkKey($key))
	{
		header('HTTP/1.1 403 Forbidden');
		echo "Invalid key\n";
		Context::close();
		exit;
	}
}

// The rest of the work will be done by the Queue class.
$timeout = config('queue.interval') ?? 60;
Rhymix\Framework\Queue::process($timeout);
