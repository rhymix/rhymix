<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widgetView
 * @author NAVER (developers@xpressengine.com)
 * @brief View class of the widget modules
 */
class WidgetView extends Widget
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * @brief Details of the widget (conf/info.xml) a pop-out
	 */
	function dispWidgetInfo()
	{
		// If people have skin widget widget output as a function of the skin More Details
		if (Context::get('skin'))
		{
			return $this->dispWidgetSkinInfo();
		}

		// Wanted widget is selected information
		$widget_info = WidgetModel::getWidgetInfo(Context::get('selected_widget'));
		Context::set('widget_info', $widget_info);

		// Specifies the widget to pop up
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplateFile('widget_detail_info');
	}

	/**
	 * @brief Widget details of the skin (skin.xml) a pop-out
	 */
	function dispWidgetSkinInfo()
	{
		$widget = Context::get('selected_widget');
		$skin = preg_replace('/[^a-zA-Z0-9-_]/', '', Context::get('skin'));

		// Wanted widget is selected information
		$path = sprintf('./widgets/%s/', $widget);
		$skin_info = ModuleModel::loadSkinInfo($path, $skin);
		Context::set('skin_info',$skin_info);

		// Specifies the widget to pop up
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplateFile('skin_info');
	}

	/**
	 * @brief Widget's code generator
	 */
	function dispWidgetGenerateCode()
	{
		// Wanted widget is selected information
		$widget_list = WidgetModel::getDownloadedWidgetList();
		$selected_widget = Context::get('selected_widget');
		if (!$selected_widget)
		{
			$selected_widget = $widget_list[0]->widget;
		}

		$widget_info = WidgetModel::getWidgetInfo($selected_widget);
		Context::set('widget_info', $widget_info);
		Context::set('widget_list', $widget_list);
		Context::set('selected_widget', $selected_widget);

		// Get a list of module categories
		$module_categories = ModuleModel::getModuleCategories();

		// Get a mid list
		$site_module_info = Context::get('site_module_info');
		$args = new stdClass();
		$args->site_srl = $site_module_info->site_srl;
		$columnList = array('module_srl', 'module_category_srl', 'browser_title', 'mid');
		$mid_list = ModuleModel::getMidList($args, $columnList);

		// Get a list of groups
		$group_list = MemberModel::getGroups($site_module_info->site_srl);
		Context::set('group_list', $group_list);

		// module_category and module combination
		if($module_categories)
		{
			foreach($mid_list as $module_srl => $module)
			{
				if(!isset($module_categories[$module->module_category_srl]))
				{
					$module_categories[$module->module_category_srl] = new stdClass();
				}
				$module_categories[$module->module_category_srl]->list[$module_srl] = $module;
			}
		}
		else
		{
			$module_categories[0] = new stdClass();
			$module_categories[0]->list = $mid_list;
		}

		Context::set('mid_list',$module_categories);

		// Menu Get a list
		$output = executeQueryArray('menu.getMenus');
		Context::set('menu_list',$output->data);

		// Wanted information on skin
		$skin_list = ModuleModel::getSkins($widget_info->path);
		Context::set('skin_list', $skin_list);

		// Specifies the widget to pop up
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplateFile('widget_generate_code');
	}

	/**
	 * @brief Managing pop-up pages used in the generated code
	 */
	function dispWidgetGenerateCodeInPage()
	{
		$widget_list = WidgetModel::getDownloadedWidgetList();
		Context::set('widget_list', $widget_list);

		// When there is no widget is selected in the first widget
		if (!Context::get('selected_widget'))
		{
			Context::set('selected_widget', $widget_list[0]->widget);
		}

		$this->dispWidgetGenerateCode();
		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('widget_generate_code_in_page');
	}

	/**
	 * @brief Create widget style code page used in the pop-up management
	 */
	function dispWidgetStyleGenerateCodeInPage()
	{
		// Widget-style list
		$widgetStyle_list = WidgetModel::getDownloadedWidgetStyleList();
		Context::set('widgetStyle_list',$widgetStyle_list);

		// Selected list of widget styles
		$widgetstyle = Context::get('widgetstyle');
		$widgetstyle_info = WidgetModel::getWidgetStyleInfo($widgetstyle);
		if ($widgetstyle && $widgetstyle_info)
		{
			Context::set('widgetstyle_info', $widgetstyle_info);
		}

		$this->dispWidgetGenerateCode();
		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('widget_style_generate_code_in_page');
	}
}
/* End of file widget.view.php */
/* Location: ./modules/widget/widget.view.php */
