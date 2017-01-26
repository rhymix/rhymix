<?php

/**
 * This script deletes files that were not properly uploaded.
 * 
 * Files can remain in an invalid status for two reasons: 1) a user abandons
 * a document or comment after uploading files; or 2) a chunked upload is
 * aborted without the server having any opportunity to clean it up.
 * These files can obviously take up a lot of disk space. In order to prevent
 * them from accumulating too much, you should run this script at least once
 * every few days.
 */
require_once __DIR__ . '/common.php';

// Delete garbage files older than this number of days.
$days = 10;

// Initialize the exit status.
$exit_status = 0;

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
