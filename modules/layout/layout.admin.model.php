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

	public function getLayoutAdminSetHTMLCSS()
	{
		// Set the layout with its information
		$layout_srl = Context::get('layout_srl');
		// Get layout information
		$oLayoutModel = &getModel('layout');
		$layout_info = $oLayoutModel->getLayout($layout_srl);
		// Error appears if there is no layout information is registered
		if(!$layout_info) 
		{
			return $this->dispLayoutAdminInstalledList();
		}

		// Get Layout Code
		$oLayoutModel = &getModel('layout');
		$layout_file = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
		
		if(!file_exists($layout_file))
		{
			// If faceoff
			if($oLayoutModel->useDefaultLayout($layout_info->layout_srl))
			{
				$layout_file  = $oLayoutModel->getDefaultLayoutHtml($layout_info->layout);
			}
			else
			{
				$layout_file = sprintf('%s%s', $layout_info->path, 'layout.html');
			}
		}

		$layout_css_file = $oLayoutModel->getUserLayoutCss($layout_info->layout_srl);
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
		$oWidgetModel = &getModel('widget');
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

		$script = '<script src="/xe1.7/modules/layout/tpl/js/layout_admin_set_html.js"></script>';
		$oTemplate = &TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->module_path.'tpl/', 'layout_html_css_view');

		$this->add('html', $script.$html);
	}
}
