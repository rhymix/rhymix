<?php
/* Copyright (C) misol and Rhymix contributors */

if(!defined('RX_VERSION'))
{
	exit();
}

/**
 * @file rx_photoswipe.addon.php
 * @author MinSoo Kim <misol.kr@gmail.com>
 * @brief Add-on to highlight an activated image.
 */
if($called_position == 'after_module_proc' && Context::getResponseMethod() == "HTML" && Context::get('module') != 'admin' && !isCrawler())
{
	Context::loadFile(array('./addons/photoswipe/PhotoSwipe/photoswipe.css', '', '', null), true);
	Context::loadFile(array('./addons/photoswipe/PhotoSwipe/default-skin/default-skin.css', '', '', null), true);

	Context::loadFile(array('./addons/photoswipe/PhotoSwipe/photoswipe.js', 'body', '', null), true);
	Context::loadFile(array('./addons/photoswipe/PhotoSwipe/photoswipe-ui-default.js', 'body', '', null), true);
	Context::loadFile(array('./addons/photoswipe/rx_photoswipe.js', 'body', '', null), true);

	$footer = FileHandler::readFile('./addons/photoswipe/PhotoSwipe/pswp.html');

	$style_display = isset($addon_info->display_name) ? "<style>.pswp__caption__center {  display:{$addon_info->display_name} }</style>" : '<style>.pswp__caption__center {  display:block }</style>';

	Context::addHtmlFooter($style_display . $footer);
}

/* End of file photoswipe.addon.php */
/* Location: ./addons/photoswipe/photoswipe.addon.php */
