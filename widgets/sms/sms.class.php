<?php
class sms extends WidgetHandler	{

	function proc($args) {
		// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		Context::set('colorset', $args->colorset);

		// 템플릿 파일을 지정
		$tpl_file = 'sms_widget';

		Context::set('sms_info', $args);

		// 템플릿 컴파일
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
