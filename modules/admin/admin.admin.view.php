<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminView class
 * Admin view class of admin module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class AdminAdminView extends Admin
{
	/**
	 * Make the admin menu.
	 * 
	 * @deprecated
	 */
	public function makeGnbUrl($module = 'admin')
	{
		Rhymix\Modules\Admin\Controllers\Base::getInstance()->loadAdminMenu($module);
	}
	
	/**
	 * Display FTP Configuration(settings) page
	 * 
	 * @deprecated
	 */
	public function dispAdminConfigFtp()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}
	
	/**
	 * Display Admin Menu Configuration(settings) page
	 */
	public function dispAdminSetup()
	{
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuByTitle($this->getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);
		$this->setTemplateFile('admin_setup');
	}
}
