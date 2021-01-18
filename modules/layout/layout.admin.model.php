<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  layoutAdminView
 * @author NAVER (developers@xpressengine.com)
 * admin view class of the layout module
 */
class layoutAdminModel extends layout
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

		if($isReturn)
		{
			return $this->get('html');
		}
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
			$layout_info->extra_var = new StdClass();
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

	public function getLayoutAdminSetHTMLCSS()
	{
		// Set the layout with its information
		$layout_srl = Context::get('layout_srl');
		// Get layout information
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($layout_srl);
		// Error appears if there is no layout information is registered
		if(!$layout_info)
		{
			return $this->dispLayoutAdminInstalledList();
		}

		// Get Layout Code
		if($oLayoutModel->useDefaultLayout($layout_info->layout_srl))
		{
			$layout_file  = $oLayoutModel->getDefaultLayoutHtml($layout_info->layout);
			$layout_css_file  = $oLayoutModel->getDefaultLayoutCss($layout_info->layout);
		}
		else
		{
			$layout_file = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
			$layout_css_file = $oLayoutModel->getUserLayoutCss($layout_info->layout_srl);

			if(!file_exists($layout_file)) $layout_file = $layout_info->path . 'layout.html';
			if(!file_exists($layout_css_file)) $layout_css_file = $layout_info->path . 'layout.css';
		}

		if(file_exists($layout_css_file))
		{
			$layout_code_css = FileHandler::readFile($layout_css_file);
			Context::set('layout_code_css', $layout_code_css);
		}

		$layout_code = FileHandler::readFile($layout_file);
		Context::set('layout_code', $layout_code);

		// set User Images
		$layout_image_list = $oLayoutModel->getUserLayoutImageList($layout_info->layout_srl);
		Context::set('layout_image_list', $layout_image_list);

		$layout_image_path = $oLayoutModel->getUserLayoutImagePath($layout_info->layout_srl);
		Context::set('layout_image_path', $layout_image_path);
		// Set widget list
		$oWidgetModel = getModel('widget');
		$widget_list = $oWidgetModel->getDownloadedWidgetList();
		Context::set('widget_list', $widget_list);

		$security = new Security($layout_info);
		$layout_info = $security->encodeHTML('.', '.author..');
		Context::set('selected_layout', $layout_info);

		//Security
		$security = new Security();
		$security->encodeHTML('layout_list..');
		$security->encodeHTML('layout_list..author..');

		$security = new Security();
		$security->encodeHTML('layout_code_css', 'layout_code', 'widget_list..title');

		$script = '<script src="./modules/layout/tpl/js/layout_admin_set_html.js"></script>';
		$oTemplate = &TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->module_path.'tpl/', 'layout_html_css_view');

		$this->add('html', $script.$html);
	}

	public function getLayoutAdminSiteDefaultLayout()
	{
		$type = Context::get('type');
		$layoutSrl = $this->getSiteDefaultLayout($type);

		$oLayoutModel = getModel('layout');
		$layoutInfo = $oLayoutModel->getLayoutRawData($layoutSrl, array('title'));

		$this->add('layout_srl', $layoutSrl);
		$this->add('title', $layoutInfo->title);
	}

	public function getSiteDefaultLayout($viewType = 'P')
	{
		$target = ($viewType == 'M') ? 'mlayout_srl' : 'layout_srl';
		$designInfoFile = RX_BASEDIR . 'files/site_design/design_0.php';
		if(FileHandler::exists($designInfoFile)) include($designInfoFile);

		if(!$designInfo || !$designInfo->{$target})
		{
			return 0;
		}

		$oModel = getModel('layout');
		$layout_info = $oModel->getLayout($designInfo->{$target});

		if(!$layout_info)
		{
			return 0;
		}

		return $designInfo->{$target};
	}
}
/* End of file layout.admin.model.php */
/* Location: ./modules/layout/layout.admin.model.php */
