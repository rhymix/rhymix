<?php
/* Copyright (C) misol and Rhymix contributors */

if(!defined('RX_VERSION'))
{
	exit();
}

/**
 * @file rx_photoswipe.addon.php
 * @author misol <misol@snu.ac.kr>
 * @brief Add-on to highlight an activated image.
 */
if($called_position == 'after_module_proc' && Context::getResponseMethod() == "HTML" && !isCrawler())
{
	Context::loadFile(array('./addons/rx_photoswipe/PhotoSwipe/photoswipe.css', '', '', null), true);
	Context::loadFile(array('./addons/rx_photoswipe/PhotoSwipe/default-skin/default-skin.css', '', '', null), true);

	Context::loadFile(array('./addons/rx_photoswipe/PhotoSwipe/photoswipe.js', 'body', '', null), true);
	Context::loadFile(array('./addons/rx_photoswipe/PhotoSwipe/photoswipe-ui-default.js', 'body', '', null), true);
	Context::loadFile(array('./addons/rx_photoswipe/rx_photoswipe.js', 'body', '', null), true);

	$footer = FileHandler::readFile('./addons/rx_photoswipe/PhotoSwipe/pswp.html');
	Context::addHtmlFooter($footer);
}

/* End of file rx_photoswipe.addon.php */
/* Location: ./addons/rx_photoswipe/rx_photoswipe.addon.php */
