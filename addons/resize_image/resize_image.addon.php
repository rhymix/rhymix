<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file resize_image.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief Add-on to resize images in the body
     **/

    if($called_position == 'after_module_proc' && Context::getResponseMethod()=="HTML") {
		if(Mobile::isFromMobilePhone()) {
			Context::addCssFile('./addons/resize_image/css/resize_image.mobile.css');
		} else { 
			Context::loadJavascriptPlugin('ui');
			Context::addJsFile('./addons/resize_image/js/resize_image.min.js',false, '',null, 'body');
		}
    }
?>
