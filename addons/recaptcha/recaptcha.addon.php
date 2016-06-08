<?php

if (!defined('RX_BASEDIR') || !$addon_info->site_key || !$addon_info->secret_key || $called_position !== 'before_module_init')
{
	return;
}

$current_action = Context::get('act');
$current_member = Context::get('logged_info');

if ($current_member->is_admin === 'Y')
{
	$enable_captcha = false;
}
elseif ($addon_info->target_users !== 'everyone' && $current_member->member_srl)
{
	$enable_captcha = false;
}
elseif ($addon_info->target_frequency !== 'every_time' && isset($_SESSION['recaptcha_authenticated']) && $_SESSION['recaptcha_authenticated'])
{
	$enable_captcha = false;
}
elseif ($addon_info->use_signup === 'Y' && preg_match('/^(?:disp|proc)Member(?:SignUp|Insert)/i', $current_action))
{
	$enable_captcha = true;
}
elseif ($addon_info->use_recovery === 'Y' && preg_match('/^(?:disp|proc)Member(?:FindAccount|ResendAuthMail)/i', $current_action))
{
	$enable_captcha = true;
}
elseif ($addon_info->use_document === 'Y' && preg_match('/^(?:disp|proc)Board(Write|InsertDocument)/i', $current_action))
{
	$enable_captcha = true;
}
elseif ($addon_info->use_comment === 'Y' && (preg_match('/^(?:disp|proc)Board(Content|InsertComment)/i', $current_action) || (!$current_action && Context::get('document_srl'))))
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
	
	if (strncasecmp('proc', $current_action, 4) === 0)
	{
		getController('module')->addTriggerFunction('moduleObject.proc', 'before', 'reCAPTCHA::check');
	}
	else
	{
		Context::set('captcha', new reCAPTCHA());
	}
}
