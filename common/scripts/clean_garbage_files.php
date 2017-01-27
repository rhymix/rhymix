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

// Initialize objects.
$oDB = DB::getInstance();
$oFileController = getController('file');

// Find and delete files where isvalid = N.
$args = new stdClass;
$args->isvalid = 'N';
$args->list_count = 50;
$args->regdate_before = date('YmdHis', time() - ($days * 86400));
while (true)
{
	$output = executeQueryArray('file.getFileList', $args);
	if ($output->toBool())
	{
		if ($output->data)
		{
			$oDB->begin();
			foreach ($output->data as $file_info)
			{
				$oFileController->deleteFile($file_info->file_srl);
			}
			$oDB->commit();
			
			if ($output->page_navigation && $output->page_navigation->total_count == count($output->data))
			{
				break;
			}
		}
		else
		{
			break;
		}
	}
	else
	{
		echo "Error while deleting garbage files older than $days days.\n";
		echo $output->getMessage() . "\n";
		$exit_status = 11;
		break;
	}
}
if ($exit_status == 0)
{
	echo "Successfully deleted all garbage files older than $days days.\n";
}

// Find and delete temporary chunks.
$dirname = RX_BASEDIR . 'files/attach/chunks';
$threshold = time() - ($days * 86400);
$chunks = Rhymix\Framework\Storage::readDirectory($dirname);
if ($chunks)
{
	foreach ($chunks as $chunk)
	{
		if (@filemtime($chunk) < $threshold)
		{
			$result = Rhymix\Framework\Storage::delete($chunk);
			if (!$result)
			{
				$exit_status = 12;
			}
		}
	}
}
if ($exit_status == 0)
{
	echo "Successfully deleted aborted file chunks older than $days days.\n";
}
else
{
	echo "Error while deleting aborted file chunks older than $days days.\n";
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
