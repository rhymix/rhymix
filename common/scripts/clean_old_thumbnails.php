<?php

/**
 * This script deletes old thumbnails.
 * 
 * Thumbnails can take up a large amount of disk space and inodes if they are
 * allowed to accumulate. Since most websites only need thumbnails for recent
 * posts, it is okay to delete old thumbnails.
 * 
 * Do not run this script if you have a gallery-style module where visitors
 * regularly view old posts. This will force thumbnails to be regenerated,
 * increasing the server load and making your pages load slower.
 * 
 * This script only works on Unix-like operating systems where the 'find'
 * command is available.
 */
require_once __DIR__ . '/common.php';

// Delete thumbnails older than this number of days.
$days = 90;

// Initialize the exit status.
$exit_status = 0;

// Delete old thumbnails.
passthru(sprintf('find %s -type f -mtime +%d -delete', escapeshellarg(RX_BASEDIR . 'files/thumbnails'), abs($days)), $result);
if ($result == 0)
{
	echo "Successfully deleted all thumbnails older than $days days.\n";
}
else
{
	echo "Error while deleting thumbnails older than $days days.\n";
	$exit_status = $result;
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
