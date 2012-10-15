<?php
/**
 * @class  layoutAdminView
 * @author NHN (developers@xpressengine.com)
 * admin view class of the layout module
 **/

class layoutAdminModel extends layout {
	
	/**
	 * init
	 */
	public function init()
	{
	}

	/**
	 * get layout setting view.
	 * @return void
	 */
	public function getLayoutAdminSetInfoView()
	{
		$layout_srl = Context::get('layout_srl');

		// Get layout information
		$oLayoutModel = &getModel('layout');
		$layout_info = $oLayoutModel->getLayout($layout_srl);

		// Error appears if there is no layout information is registered
		if(!$layout_info)
		{
			return $this->stop('msg_invalid_request');
		}

		// Get a menu list
		$oMenuAdminModel = &getAdminModel('menu');
		$menu_list = $oMenuAdminModel->getMenus();
		Context::set('menu_list', $menu_list);

		$security = new Security();
		$security->encodeHTML('menu_list..');

		$security = new Security($layout_info);
		$layout_info = $security->encodeHTML('.', 'author..', 'extra_var..');

		$layout_info->description = nl2br(trim($layout_info->description));
		if (!is_object($layout_info->extra_var))
		{
			$layout_info->extra_var = new StdClass();
		}

		foreach($layout_info->extra_var as $var_name => $val)
		{
			if (isset($layout_info->{$var_name}->description))
			{
				$layout_info->{$var_name}->description = nl2br(trim($val->description));
			}
		}
		Context::set('selected_layout', $layout_info);

		$script = '<script src="/xe1.7/modules/layout/tpl/js/layout_modify.js"></script>';
		$oTemplate = &TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->module_path.'tpl/', 'layout_info_view');

		$this->add('html', $script.$html);
	}
}
