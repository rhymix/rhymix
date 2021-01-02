<?php
require_once(_XE_PATH_ . 'modules/sociallogin/sociallogin.view.php');

class SocialloginMobile extends SocialloginView
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		Context::set('config', $this->config);

		$this->setTemplatePath(sprintf('%sm.skins/%s/', $this->module_path, $this->config->mskin));

		Context::addJsFile($this->module_path . 'tpl/js/sociallogin.js');

		// 사용자 모바일 레이아웃
		if ($this->config->mlayout_srl && $layout_path = getModel('layout')->getLayout($this->config->mlayout_srl)->path)
		{
			$this->module_info->mlayout_srl = $this->config->mlayout_srl;

			$this->setLayoutPath($layout_path);
		}
	}
}
