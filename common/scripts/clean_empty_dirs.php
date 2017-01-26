<?php

/**
 * This script deletes empty directories under the 'files' directory.
 * 
 * It may be useful when your web host imposes a hard limit on the number of
 * inodes, or when your backups take too long due to the large number of
 * unused directories.
 * 
 * This script only works on Unix-like operating systems where the 'find'
 * command is available.
 */
require_once __DIR__ . '/common.php';

// Initialize the exit status.
$exit_status = 0;

// Delete empty directories in the attachment directory.
passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/attach')), $result);
if ($result == 0)
{
	echo "Successfully deleted all empty directories under files/attach.\n";
}
else
{
	echo "Error while deleting empty directories under files/attach.\n";
	$exit_status = $result;
}

// Delete empty directories in the member extra info directory.
passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/member_extra_info')), $result);
if ($result == 0)
{
	echo "Successfully deleted all empty directories under files/member_extra_info.\n";
}
else
{
	echo "Error while deleting empty directories under files/member_extra_info.\n";
	$exit_status = $result;
}

// Delete empty directories in the thumbnails directory.
passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/thumbnails')), $result);
if ($result == 0)
{
	echo "Successfully deleted all empty directories under files/thumbnails.\n";
}
else
{
	echo "Error while deleting empty directories under files/thumbnails.\n";
	$exit_status = $result;
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
