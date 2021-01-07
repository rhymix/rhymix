<?php
require_once(RX_BASEDIR . 'modules/sociallogin/sociallogin.view.php');

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

		// 사용자 모바일 레이아웃
		if (self::getConfig()->mlayout_srl && $layout_path = getModel('layout')->getLayout(self::getConfig()->mlayout_srl)->path)
		{
			$this->module_info->mlayout_srl = self::getConfig()->mlayout_srl;

			$this->setLayoutPath($layout_path);
		}
	}
}
