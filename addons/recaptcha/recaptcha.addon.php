<?php

if (!defined('RX_BASEDIR') || !$addon_info->site_key || !$addon_info->secret_key || $called_position !== 'before_module_init')
{
	return;
}

if ($addon_info->use_signup === 'Y' && preg_match('/^(?:disp|proc)Member(?:SignUp|Insert)/i', Context::get('act')))
{
	$enable_captcha = true;
}
elseif ($addon_info->use_recovery === 'Y' && preg_match('/^(?:disp|proc)Member(?:FindAccount|ResendAuthMail)/i', Context::get('act')))
{
	$enable_captcha = true;
}
else
{
	$enable_captcha = false;
}

if ($enable_captcha)
{
	include_once __DIR__ . '/recaptcha.class.php';
	reCAPTCHA::init($addon_info);
	
	if (strncasecmp('proc', Context::get('act'), 4) === 0)
	{
		getController('module')->addTriggerFunction('moduleObject.proc', 'before', 'reCAPTCHA::check');
	}
	else
	{
		Context::set('captcha', new reCAPTCHA());
	}
}
else
{
	return;
}
