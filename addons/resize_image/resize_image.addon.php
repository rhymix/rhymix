<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
{
	exit();
}

/**
 * @file resize_image.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Add-on to resize images in the body
 */
if($called_position == 'after_module_proc' && Context::getResponseMethod() == "HTML" && !isCrawler())
{
	if(Mobile::isFromMobilePhone())
	{
		Context::loadFile('./addons/resize_image/css/resize_image.mobile.css', true);
	}
	else
	{
		Context::loadJavascriptPlugin('ui');
		Context::loadFile(array('./addons/resize_image/js/resize_image.min.js', 'body', '', null), true);
	}
}

/* End of file resize_image.addon.php */
/* Location: ./addons/resize_image/resize_image.addon.php */
