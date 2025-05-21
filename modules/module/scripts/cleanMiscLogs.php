<?php

/**
 * This script deletes old logs from the database.
 *
 * Rhymix produces various logs that can increase the size of the database
 * unnecessarily if not cleaned. This script removes old logs.
 */
if (!defined('RX_VERSION'))
{
	exit;
}

// Initialize the exit status.
$exit_status = 0;

// Delete logs older than this number of days.
$days = intval($args[0] ?? 0) ?: 30;

// Delete advanced mailer email logs.
$output = executeQuery('advanced_mailer.deleteMailLogs', [
	'regdate' => date('YmdHis', time() - ($days * 86400)),
]);
if ($output->toBool())
{
	echo "Successfully deleted all email logs older than $days days.\n";
}
else
{
	echo "Error while deleting email logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 11;
}

// Delete advanced mailer SMS logs.
$output = executeQuery('advanced_mailer.deleteSMSLogs', [
	'regdate' => date('YmdHis', time() - ($days * 86400)),
]);
if ($output->toBool())
{
	echo "Successfully deleted all SMS logs older than $days days.\n";
}
else
{
	echo "Error while deleting SMS logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 12;
}

// Delete advanced mailer Push logs.
$output = executeQuery('advanced_mailer.deletePushLogs', [
	'regdate' => date('YmdHis', time() - ($days * 86400)),
]);
if ($output->toBool())
{
	echo "Successfully deleted all Push logs older than $days days.\n";
}
else
{
	echo "Error while deleting Push logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 13;
}

// Delete spamfilter logs.
$output = executeQuery('spamfilter.deleteLog', [
	'regdate' => date('YmdHis', time() - ($days * 86400)),
]);
if ($output->toBool())
{
	echo "Successfully deleted all spamfilter logs older than $days days.\n";
}
else
{
	echo "Error while deleting spamfilter logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 21;
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
