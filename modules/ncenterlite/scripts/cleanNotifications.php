<?php

/**
 * This script deletes old notifications.
 *
 * Notifications must be dismissed as quickly as possible in order to prevent
 * the ncenterlite_notify table from becoming too large. For best performance,
 * you should run this script at least once every few days.
 */
if (!defined('RX_VERSION'))
{
	exit;
}

// Initialize the exit status.
$exit_status = 0;

// Delete notifications older than this number of days.
$days = intval($args[0] ?? 0) ?: 30;

// Execute the query.
$output = executeQuery('ncenterlite.deleteNotifyAll', [
	'old_date' => date('YmdHis', time() - ($days * 86400)),
]);

if ($output->toBool())
{
	echo "Successfully deleted all notifications older than $days days.\n";
	$delete_obj = (object)array('regdate' => time());
	Rhymix\Framework\Cache::clearGroup('ncenterlite');
	Rhymix\Framework\Storage::writePHPData(\RX_BASEDIR . 'files/cache/ncenterlite/new_notify/delete_date.php', $delete_obj);
}
else
{
	echo "Error while deleting notifications older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 11;
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
