<?php

/**
 * This script updates all modules.
 * 
 * Running this script on the CLI is better than clicking 'update' in the
 * admin dashboard because some module updates may take a long time.
 */
require_once __DIR__ . '/common.php';

Context::init();
$oModuleModel = getModel('module');

// Get the list of modules that need to be updated.
$module_list = $oModuleModel->getModuleList();
$need_install = array();
$need_update = array();
foreach($module_list as $key => $value)
{
	if($value->need_install)
	{
		$need_install[] = $value->module;
	}
	if($value->need_update)
	{
		$need_update[] = $value->module;
	}
}

// Install all modules.
$oInstallController = getController('install');
foreach ($need_install as $module)
{
	try
	{
		echo 'Installing ' . $module . '...' . PHP_EOL;
		$oInstallController->installModule($module, './modules/' . $module);
	}
	catch (\Exception $e)
	{
		// pass
	}
}

// Update all modules.
$oInstallAdminController = getAdminController('install');
foreach ($need_update as $module)
{
	echo 'Updating ' . $module . '...' . PHP_EOL;
	$oModule = getModule($module, 'class');
	if ($oModule)
	{
		$oModule->moduleUpdate();
	}
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
