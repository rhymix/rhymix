<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  layoutAdminView
 * @author NAVER (developers@xpressengine.com)
 * admin view class of the layout module
 */
class LayoutAdminModel extends Layout
{
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
		$this->setLayoutAdminSetInfoView();

		Context::set('is_sitemap', '1');
		$script = '<script src="./modules/layout/tpl/js/layout_modify.js"></script>';
		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->module_path.'tpl/', 'layout_info_view');
		$csss = '';
		preg_match_all('/<!--#JSPLUGIN:(.*)-->/', $html, $m);
		$pluginList = $m[1];

		foreach($pluginList as $plugin)
		{
			$info = Context::getJavascriptPluginInfo($plugin);
			if(!$info)
			{
				continue;
			}

			foreach($info->jsList as $js)
			{
				$script .= sprintf('<script src="%s"></script>', $js);
			}
			foreach($info->cssList as $css)
			{
				$csss .= sprintf('<link rel="stylesheet" href="%s" />', $css);
			}
		}

		$this->add('html', $csss . $script . $html);
	}

	public function setLayoutAdminSetInfoView()
	{
		$layout_srl = Context::get('layout_srl');

		// Get layout information
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($layout_srl, false);

		// Error appears if there is no layout information is registered
		if(!$layout_info)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Get a menu list
		$oMenuAdminModel = getAdminModel('menu');
		$menu_list = $oMenuAdminModel->getMenus();
		Context::set('menu_list', $menu_list);

		$security = new Security();
		$security->encodeHTML('menu_list..');

		$security = new Security($layout_info);
		$layout_info = $security->encodeHTML('.', 'author..', 'extra_var..');

		$layout_info->description = nl2br(trim($layout_info->description));
		if(!is_object($layout_info->extra_var))
		{
			$layout_info->extra_var = new \stdClass();
		}

		foreach($layout_info->extra_var as $var_name => $val)
		{
			if(isset($layout_info->{$var_name}->description))
			{
				$layout_info->{$var_name}->description = nl2br(trim($val->description));
			}
		}
		Context::set('selected_layout', $layout_info);
	}

	public function getLayoutAdminSiteDefaultLayout()
	{
		$type = Context::get('type');
		$layoutSrl = self::getSiteDefaultLayout($type);

		$oLayoutModel = getModel('layout');
		$layoutInfo = $oLayoutModel->getLayoutRawData($layoutSrl, array('title'));

		$this->add('layout_srl', $layoutSrl);
		$this->add('title', $layoutInfo->title);
	}

	public static function getSiteDefaultLayout($viewType = 'P')
	{
		$target = ($viewType === 'M') ? 'mlayout_srl' : 'layout_srl';
		$designInfo = Rhymix\Modules\Layout\Models\Theme::getDefaultDesignConfig();
		if (!$designInfo || empty($designInfo->{$target}))
		{
			return 0;
		}

		$layout_srl = $designInfo->{$target};
		$layout_info = LayoutModel::getLayout($layout_srl);
		if (!$layout_info)
		{
			return 0;
		}

		return $layout_srl;
	}
}
/* End of file layout.admin.model.php */
/* Location: ./modules/layout/layout.admin.model.php */
