<?php

/**
 * This script deletes empty directories under the 'files' directory.
 * 
 * It may be useful when your web host imposes a hard limit on the number of
 * inodes, or when your backups take too long due to the large number of
 * unused directories.
 */
require_once __DIR__ . '/common.php';

// Initialize the exit status.
$exit_status = 0;

// Delete empty directories in the attachment directory.
if (\RX_WINDOWS)
{
	passthru(sprintf('powershell.exe "Get-ChildItem %s -Recurse -Force -Directory | Sort-Object -Property FullName -Descending | Where-Object { $($_ | Get-ChildItem -Force | Select-Object -First 1).Count -eq 0 } | Remove-Item -Verbose"', escapeshellarg(RX_BASEDIR . 'files/attach')), $result);
}
else
{
	passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/attach')), $result);
}
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
if (\RX_WINDOWS)
{
	passthru(sprintf('powershell.exe "Get-ChildItem %s -Recurse -Force -Directory | Sort-Object -Property FullName -Descending | Where-Object { $($_ | Get-ChildItem -Force | Select-Object -First 1).Count -eq 0 } | Remove-Item -Verbose"', escapeshellarg(RX_BASEDIR . 'files/member_extra_info')), $result);
}
else
{
	passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/member_extra_info')), $result);
}
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
if (\RX_WINDOWS)
{
	passthru(sprintf('powershell.exe "Get-ChildItem %s -Recurse -Force -Directory | Sort-Object -Property FullName -Descending | Where-Object { $($_ | Get-ChildItem -Force | Select-Object -First 1).Count -eq 0 } | Remove-Item -Verbose"', escapeshellarg(RX_BASEDIR . 'files/thumbnails')), $result);
}
else
{
	passthru(sprintf('find %s -type d -empty -delete', escapeshellarg(RX_BASEDIR . 'files/thumbnails')), $result);
}
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
