<?php

/**
 * This file must be included at the top of all shell scripts (cron jobs).
 * 
 * HERE BE DRAGONS.
 * 
 * Failure to perform the checks listed in this file at the top of a cron job,
 * or any attempt to work around the limitations deliberately placed in this
 * file, may result in errors or degradation of service.
 * 
 * Please be warned that errors may not show up immediately, especially if you
 * screw up the permissions inside deeply nested directory trees. You may find
 * it difficult and/or costly to undo the damages when errors begin to show up
 * several months later.
 */

// Abort if not CLI.
if (PHP_SAPI !== 'cli')
{
	echo "This script must be executed on the command line interface.\n";
	exit(1);
}

// Load Rhymix.
chdir(dirname(dirname(__DIR__)));
require_once dirname(__DIR__) . '/autoload.php';

// Abort if the UID does not match.
$uid = Rhymix\Framework\Storage::getServerUID();
if ($uid === 0)
{
	echo "This script must not be executed by the root user.\n";
	exit(2);
}
$web_server_uid = fileowner(RX_BASEDIR . 'files/config/config.php');
if ($uid !== $web_server_uid)
{
	$web_server_uid = posix_getpwuid($web_server_uid);
	echo "This script must be executed by the same user as the usual web server process ({$web_server_uid['name']}).\n";
	exit(3);
}
