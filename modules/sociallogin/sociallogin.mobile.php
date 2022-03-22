<?php

class SocialloginMobile extends SocialloginView
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		Context::set('config', self::getConfig());

		$this->setTemplatePath(sprintf('%sm.skins/%s/', $this->module_path, self::getConfig()->mskin));

		Context::addJsFile($this->module_path . 'tpl/js/sociallogin.js');
	}
}
