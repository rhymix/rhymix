<?php

/**
 * This script updates all modules.
 *
 * When upgrading from a very old version, it is safer to run this script
 * on the CLI than clicking 'update' in the admin dashboard.
 * This is because some module updates may take a long time.
 *
 * Note that if you use APC cache, you may need to reset the cache
 * in the admin dashboard after running this script.
 */
if (!defined('RX_VERSION'))
{
	exit;
}

// Get the list of modules that need to be updated.
$module_list = ModuleModel::getModuleList();
$need_install = array();
$need_update = array();
foreach ($module_list as $key => $value)
{
	if ($value->need_install)
	{
		$need_install[] = $value->module;
	}
	if ($value->need_update)
	{
		$need_update[] = $value->module;
	}
}

// Install all modules.
$oInstallController = InstallController::getInstance();
foreach ($need_install as $module)
{
	try
	{
		echo 'Installing ' . $module . '...' . PHP_EOL;
		$oInstallController->installModule($module, './modules/' . $module);
	}
	catch (\Exception $e)
	{
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
	}
}

// Update all modules.
foreach ($need_update as $module)
{
	try
	{
		echo 'Updating ' . $module . '...' . PHP_EOL;
		$oInstallController->updateModule($module);
	}
	catch (\Exception $e)
	{
		echo 'Error: ' . $e->getMessage() . PHP_EOL;
	}
}

// Set the exit status if there were any errors.
if ($exit_status != 0)
{
	exit($exit_status);
}
