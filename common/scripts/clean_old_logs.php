<?php

/**
 * This script deletes old logs from the database.
 * 
 * Rhymix produces various logs that can increase the size of the database
 * unnecessarily if not cleaned. This script removes old logs.
 */
require_once __DIR__ . '/common.php';

// Delete logs older than this number of days.
$days = 30;

// Initialize the exit status.
$exit_status = 0;

// Delete advanced mailer mail logs.
$args = new stdClass;
$args->regdate = date('YmdHis', time() - ($days * 86400));
$output = executeQuery('advanced_mailer.deleteMailLogs', $args);
if ($output->toBool())
{
	echo "Successfully deleted all mail logs older than $days days.\n";
}
else
{
	echo "Error while deleting mail logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 11;
}

// Delete advanced mailer SMS logs.
$args = new stdClass;
$args->regdate = date('YmdHis', time() - ($days * 86400));
$output = executeQuery('advanced_mailer.deleteSMSLogs', $args);
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

// Delete spamfilter logs.
$args = new stdClass;
$args->regdate = date('YmdHis', time() - ($days * 86400));
$output = executeQuery('spamfilter.deleteLog', $args);
if ($output->toBool())
{
	echo "Successfully deleted all spamfilter logs older than $days days.\n";
}
else
{
	echo "Error while deleting spamfilter logs older than $days days.\n";
	echo $output->getMessage() . "\n";
	$exit_status = 12;
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
