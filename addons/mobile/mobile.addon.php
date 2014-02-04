<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file mobile.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Mobile XE add-on
 *
 * If a mobile connection is made (see the header information), display contents with WAP tags
 *
 * Time to call
 *
 * before_module_proc > call when changing general settings for mobile
 *
 * after_module_proc > display mobile content
 * Condition
 * */
// Ignore admin page
if(Context::get('module') == 'admin')
{
	return;
}
// Manage when to call it
if($called_position != 'before_module_proc' && $called_position != 'after_module_proc')
{
	return;
}
// Ignore if not mobile browser
require_once(_XE_PATH_ . 'addons/mobile/classes/mobile.class.php');
if(!mobileXE::getBrowserType())
{
	return;
}
// Generate mobile instance
$oMobile = &mobileXE::getInstance();
if(!$oMobile)
{
	return;
}
// Specify charset on the add-on settings 
$oMobile->setCharSet($addon_info->charset);
// Set module information
$oMobile->setModuleInfo($this->module_info);
// Register the current module object
$oMobile->setModuleInstance($this);

// Extract content and display/exit if navigate mode is or if WAP class exists
if($called_position == 'before_module_proc')
{
	if($oMobile->isLangChange())
	{
		$oMobile->setLangType();
		$oMobile->displayLangSelect();
	}
	// On navigation mode, display navigation content
	if($oMobile->isNavigationMode())
	{
		$oMobile->displayNavigationContent();
	}
	// If you have a WAP class content output via WAP class
	else
	{
		$oMobile->displayModuleContent();
	}
	// If neither navigation mode nor WAP class is, display the module's result
}
else if($called_position == 'after_module_proc')
{
	// Display
	$oMobile->displayContent();
}

/* End of file mobile.addon.php */
/* Location: ./addons/mobile/mobile.addon.php */
