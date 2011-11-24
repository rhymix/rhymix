<?php
if(!defined('__XE__')) exit();

/**
 * @file autolink.addon.php
 * @author NHN (developers@xpressengine.com)
 * @brief Automatic link add-on
 **/
if($called_position == 'after_module_proc' && Context::getResponseMethod()!="XMLRPC") {
	Context::loadFile(array('./addons/autolink/autolink.js', 'body', '', null), true);
}
?>
