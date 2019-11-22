<?php

/**
 * This script deletes old message attachments.
 * 
 * Files attached to member messages are not viewable by other users, but they
 * take up space on the server. You may want to delete them after a certain
 * number of days in order to prevent users from using messages as a sort of
 * private storage space.
 */
require_once __DIR__ . '/common.php';

// Delete attachments older than this number of days.
$days = 30;

// Initialize the exit status.
$exit_status = 0;

// Initialize objects.
$oDB = DB::getInstance();
$oFileController = getController('file');

// Find and delete files where upload_target_type = msg.
$args = new stdClass;
$args->upload_target_type = 'msg';
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
		echo "Error while deleting message attachments older than $days days.\n";
		echo $output->getMessage() . "\n";
		$exit_status = 11;
		break;
	}
}
if ($exit_status == 0)
{
	echo "Successfully deleted all message attachments older than $days days.\n";
}
