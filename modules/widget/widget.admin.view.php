<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widgetAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief admin view class for widget modules
 */
class widgetAdminView extends widget
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * @brief Showing a list of widgets
	 */
	function dispWidgetAdminDownloadedList()
	{
		// Set widget list
		$oWidgetModel = getModel('widget');
		$widget_list = $oWidgetModel->getDownloadedWidgetList();

		$security = new Security($widget_list);
		$widget_list = $security->encodeHTML('..', '..author..');

		foreach($widget_list as $no => $widget)
		{
			if($widget->widget)
			{
				$widget_list[$no]->description = nl2br(trim($widget->description));
			}
			else
			{
				unset($widget_list[$no]);
			}
		}

		Context::set('widget_list', $widget_list);
		Context::set('tCount', count($widget_list));

		$this->setTemplateFile('downloaded_widget_list');
	}

	function dispWidgetAdminGenerateCode()
	{
		$oView = getView('widget');
		Context::set('in_admin', true);
		$this->setTemplateFile('widget_generate_code');
		return $oView->dispWidgetGenerateCode();
	}

	/**
	 * @brief For information on direct entry widget popup kkuhim
	 */
	function dispWidgetAdminAddContent()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl) return $this->stop("msg_invalid_request");

		$document_srl = Context::get('document_srl');
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		Context::set('oDocument', $oDocument);

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'mid');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		Context::set('module_info', $module_info);
		// Editors settings of the module by calling getEditor
		$oEditorModel = getModel('editor');
		$editor = $oEditorModel->getModuleEditor('document',$module_srl, $module_srl,'module_srl','content');
		Context::set('editor', $editor);

		$security = new Security();
		$security->encodeHTML('member_config..');

		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile("default_layout");
		$this->setTemplateFile('add_content_widget');
	}
}
/* End of file widget.admin.view.php */
/* Location: ./modules/widget/widget.admin.view.php */
